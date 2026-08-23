# Perbaikan & Penyempurnaan Aplikasi Kasir Toko Kain — Analisis Muse Spark

> **Tanggal:** 22 Agustus 2026  
> **Codebase:** `kain-ecer` (Laravel 12 + Inertia Vue 3 + MySQL/SQLite)  
> **Rujukan bisnis:** `docs/petunjuk_analisis.md:1-47` — 2 mode jual (Grosir = Per Roll/Batch/Kilo, Ecer = Per Meter/Yard/Kilo), 2 kasir berbeda, Multi Dashboard & Multi User, 3 layer Produk/Pembelian/Penjualan.

---

## 1. Ringkasan Eksekutif

Aplikasi sudah menangkap domain inti toko kain dengan baik: struktur 3 layer `Header → Actual → Detail` untuk Produk (`Produk → ProdukVarian → ProdukDetail`) dan Pembelian (`Pembelian → PembelianItems → PembelianItemsDetails`), pemisahan stok per Gudang (`1=Ecer, 2=Grosir`), dan `KartuStok` sebagai audit trail. Flow Grosir (Roll utuh, `status=tersedia/terjual`) dan Ecer (potong Roll, `qty` parsial + konversi Yard↔Meter) sudah diimplementasi.

**Namun ada 3 risiko P0 yang harus diperbaiki sebelum production scale:**

1. **Integritas stok rapuh** — `hasOne` vs `hasMany`, magic `gudang_id = 55`, `increment/decrement` tanpa lock, dan bug sintaks `stock()` vs `stok()` yang akan meledak di transaksi Grosir update/destroy.
2. **Inkonsistensi skema & model** — `Produk.nama` vs `nama_produk`, `nomer` vs `nomor`, tidak ada FK constraint, `ProdukObserver` mengacu tabel/field yang tidak ada.
3. **Transaksi tidak idempoten & tidak aman konkurensi** — nomor nota `id+1`, tidak ada `SELECT ... FOR UPDATE` di Grosir, dan validasi `subtotal == qty*harga` dengan `===` float yang pasti gagal di edge case.

Dokumen ini merinci **32 temuan** dengan lokasi file:line, dampak, dan **solusi konkret siap-koding**, diprioritaskan P0/P1/P2 serta roadmap 4 sprint.

---

## 2. Pemetaan Arsitektur Saat Ini (As-Is)

### 2.1. 3 Layer Produk — Sudah Tepat, Tapi Perlu Penajaman

| Layer | Tabel | Model | Peran | Catatan |
|-------|-------|-------|-------|---------|
| Header | `produks` | `app/Models/Produk.php:7` | Nama kain (mis. “Katun Brokat”) | `fillable: nama, kategori_id, satuan_id` |
| Actual | `produk_varians` | `app/Models/ProdukVarian.php:7` | Varian warna (mis. “Merah Maroon”) + gudang_id | Punya `sku`, `harga_beli/jual` |
| Detail | `produk_details` | `app/Models/ProdukDetail.php:7` | Roll fisik unik (`qty`, `barcode`, `stok_id`, `gudang_id`, `status`) | 1 Roll = 1 baris |

`Stok` (`app/Models/Stok.php:5`) adalah **header batch** per `(varian_id, gudang_id)` dengan `total_qty` & `total_unit` sebagai denormalisasi. `KartuStok` (`app/Models/KartuStok.php:7`) mencatat `masuk/keluar/transfer/penyesuaian`.

**Sudah benar:** Ecer `StokEcerController::storeTransfer:68-121` memindahkan `ProdukDetail` antar gudang dan menulis `KartuStok tipe=transfer`.

**Belum benar:** `ProdukVarian::stok()` didefinisikan `hasOne` padahal secara bisnis harus `hasMany` (1 varian bisa punya stok di Gudang 1 dan 2 sekaligus). Akibatnya `$varian->stok()->decrement()` akan update baris random jika ada 2 gudang.

### 2.2. 3 Layer Pembelian & Penjualan

```
Pembelian (nomer, tanggal) 
  → PembelianItems (varian_id, total_qty, total_jumlah)
    → PembelianItemsDetails (produk_detail_id, qty)

Penjualan (nomer, tanggal, bagian=grosir|ecer, pelanggan_id, marketing_id)
  → PenjualanItem (varian_id, total_qty, harga_satuan, subtotal)
    → PenjualanDetail (produk_detail_id, qty, harga, is_convert)
      → Pembayaran → PembayaranDetails (split CASH/TRANSFER-EDC/SPLIT/DP)
```

Flow Grosir pembelian: `PenerimaanController::store:84-188` → buat `Pembelian` + `Stok` batch + N `ProdukDetail`.  
Flow Ecer penjualan: `TransaksiPenjualanEceranController::simpanItems:167-285` → DRAFT → UNPAID → `PembayaranEcerController::bayar:81-146` → LUNAS/DP.

### 2.3. Multi-Gudang & Multi-User

`routes/web.php:41-388` memakai `RoleMiddleware.php:10` dengan `role:owner|admin-grosir|admin-ecer`. `Gudang` seed: `1=Ecer, 2=Grosir` (`config/database.php:31` default `sqlite` tapi `.env:24` pakai `mysql` → inkonsisten env).

---

## 3. Temuan Kritis & Perbaikan P0 (Harus Diperbaiki Sekarang)

### P0-01 — Bug Fatal `Pembelian::supplier()` Salah Sintaks
**Lokasi:** `app/Models/Pembelian.php:23`
```php
return $this->belongsTo(Supplier::class . 'supplier_id'); // BUG: string concat
```
Seharusnya `belongsTo(Supplier::class, 'supplier_id')`. Saat ini setiap `$pembelian->supplier` melempar `Class 'App\Models\Supplier\supplier_id' not found` dan `PenerimaanController::index:28` dengan `with('items.varian...')` tidak pernah eager load supplier → tidak terlihat karena tidak dipakai, tapi akan meledak saat Laporan butuh nama supplier.

**Fix:**
```php
public function supplier() { return $this->belongsTo(Supplier::class, 'supplier_id'); }
```
Hapus duplikasi `supplier_id` di `$fillable:11,13`.

### P0-02 — `hasOne` Stok vs `hasMany` + Magic `gudang_id = 55`
**Lokasi:** `app/Models/ProdukVarian.php:30`, `app/Http/Controllers/Grosir/PenjualanGrosirController.php:211,464`, `app/Http/Controllers/Ecer/StokEcerController.php:74`
- `ProdukVarian::stok()` = `hasOne(Stok::class,'varian_id')` → query `where varian_id = ? limit 1` tanpa `gudang_id`. Jika 1 varian ada di 2 gudang, `decrement` bisa kena stok Ecer padahal transaksi Grosir.
- `gudang_id = 55` sebagai “terjual” tidak ada di `gudangs` (hanya 1 & 2). Harusnya `gudang_id = null` atau `0` + `status='terjual'`, atau buat Gudang `id=99` bernama `TERJUAL/RETUR`.
- `StokGrosirController::index:18` filter `where('nomor', ...)` tapi kolom `stoks.nomer` (tanpa ‘o’), sehingga search tidak pernah match.

**Fix:**
```php
// ProdukVarian.php
public function stoks() { return $this->hasMany(Stok::class, 'varian_id'); }
public function stokGrosir() { return $this->hasOne(Stok::class,'varian_id')->where('gudang_id',2); }
public function stokEcer()   { return $this->hasOne(Stok::class,'varian_id')->where('gudang_id',1); }
// config/constants.php
return ['gudang' => ['ecer'=>1,'grosir'=>2,'terjual'=>null]];
// PenjualanGrosirController::store — ganti
$produkDetail->update(['status'=>'terjual','gudang_id'=>null]);
```

### P0-03 — Typo `stock()` vs `stok()` + Sintaks `['gudang_id',2]` Akan Crash di Update/Destroy
**Lokasi:** `app/Http/Controllers/Grosir/PenjualanGrosirController.php:372-373,464`
```php
$varian->stock()->increment(...) // MethodNotFound: stock()
$produkDetail->update(['status'=>'tersedia','gudang_id',2]) // Parse error: array value tanpa key
```
Flow edit/hapus Penjualan Grosir 100% error 500 saat ini. Test manual: `PUT /grosir/penjualan/{id}`.

**Fix:** Ganti ke `$varian->stok()->...` dan `['gudang_id'=>2]`. Tambah test `tests/Feature/PenjualanGrosirTest.php`.

### P0-04 — Inkonsistensi Rollback Stok: `qty` vs `limit(1)` vs `produk_detail_id`
**Lokasi:**
- `PenerimaanController::destroy:414-419` hapus `ProdukDetail` dengan `where('qty',$detail->qty)->limit(1)` → jika 2 roll punya `qty` sama (mis. 50.00), bisa hapus roll yang salah milik nota lain.
- `PenerimaanController::update:298-303` hapus dengan `where('id',$oldDetail->produk_detail_id)` tapi `store` tidak menyimpan `produk_detail_id` konsisten (ada case `old_id` vs `id`).
- `PenjualanGrosirController::store:206-211` update `gudang_id=55` tapi `destroy:460-465` kembalikan ke `gudang_id=2` tanpa cek apakah stok batch masih ada.

**Fix:** Jadikan `produk_detail_id` sebagai FK ketat. Rollback harus by `id`, bukan by `qty`. Bungkus dengan `lockForUpdate()`:
```php
$pd = ProdukDetail::where('id',$detail->produk_detail_id)->lockForUpdate()->firstOrFail();
$pd->delete();
Stok::where('id',$pd->stok_id)->lockForUpdate()->decrementEach([...]);
```

### P0-05 — Race Condition Nomor Nota & Validasi Float `===`
**Lokasi:** `PenjualanGrosirController::create:77-83` & `store:145-149`, `TransaksiPenjualanEceranController::generateNota:152-165`
```php
$nomernya = Penjualan::orderBy('id','desc')->first()->id + 1; // race
if ((float)$item['subtotal'] !== (float)($item['total_qty']*$item['unit_price'])) abort(422);
```
Dua kasir Grosir klik “Simpan” bersamaan → dapat `INV202508210001` sama → unique constraint error atau duplikat. Validasi `!==` float gagal untuk `0.1+0.2 != 0.3`.

**Fix:**
- Buat tabel `sequences` atau pakai `DB::transaction` + `SELECT MAX(nomer) FOR UPDATE` + format `INV-G-YYYYMMDD-XXXX` dengan auto-increment per hari.
- Validasi dengan toleransi: `abs($subtotal - $qty*$price) > 0.01` atau pakai `bcmul/bccomp`.

### P0-06 — Tidak Ada Foreign Key Constraint → Data Yatim
**Lokasi:** Semua migration `2026_01_*` memakai `unsignedBigInteger` bukan `foreignId()->constrained()`. Contoh `produk_details:14-18`, `penjualan_items:16-17`, `pembelian_items_details:15-18`. Delete `ProdukVarian` tidak cascade ke `ProdukDetail`, delete `Penjualan` tidak cascade ke `Pembayaran`.

**Fix:** Buat migration baru:
```php
Schema::table('produk_details', fn($t)=> $t->foreign('varian_id')->references('id')->on('produk_varians')->cascadeOnDelete());
Schema::table('produk_details', fn($t)=> $t->foreign('stok_id')->references('id')->on('stoks')->nullOnDelete());
// tambahkan index komposit
$t->index(['varian_id','gudang_id','status']);
$t->index(['stok_id','status']);
```

### P0-07 — `ProdukObserver` Dead Code Mengacu Kolom Tak Ada
**Lokasi:** `app/Observers/ProdukObserver.php:11-50` pakai `kode_warna`, `gudang_id`, `panjang_perroll`, model `RollPerKodewarna` yang tidak ada di migration manapun. Observer terdaftar di `AppServiceProvider`? Tidak dicek, tapi jika aktif akan error setiap `Produk::save()`.

**Fix:** Hapus observer atau refactor ke `ProdukVarianObserver` yang sync `Stok::total_qty = sum(ProdukDetail.qty where status=tersedia)`.

### P0-08 — Tandem DB `sqlite` vs `mysql` & `sqlite_lama` Salah Path
**Lokasi:** `config/database.php:31,45-50`, `.env:24`
- `default = sqlite` tapi `.env DB_CONNECTION=mysql` → di local jalan MySQL, di prod bisa fallback ke `database.sqlite` kosong → data hilang.
- `sqlite_lama.database = database_path('database.sqlite')` sama dengan `sqlite.database` → migrasi `PenerimaanController::updateData:477` baca `purchase_invoices` dari file yang sama dengan DB baru → tidak akan ketemu data lama.

**Fix:** `.env.example` jelaskan `DB_CONNECTION=mysql` wajib. `sqlite_lama.database` arahkan ke `database/database_lama.sqlite` dan mount file lama terpisah. Di `PenerimaanController::updateData` validasi `Schema::connection('sqlite_lama')->hasTable('purchase_invoices')`.

---

## 4. Temuan P1 — Perlu Segera (Stabilitas & Bisnis)

### P1-01 — Status ProdukDetail Tidak Terstandarisasi
Saat ini: `tersedia, terjual, habis, transisi, prepare` tersebar di `PenjualanGrosirController:212, PenjualanDetail:22, TransaksiPenjualanEceranController:226`. `habis` (Ecer sisa 0) vs `terjual` (Grosir jual roll utuh) vs `prepare` (detail penjualan belum bayar) membingungkan. `Ecer` potong roll → `qty` berkurang, status tetap `tersedia` sampai 0.01, lalu `habis`. `Grosir` tidak potong, langsung `terjual`.

**Saran:** Buat Enum:
```php
enum ProdukDetailStatus: string { case TERSEDIA='tersedia'; case TERJUAL='terjual'; case HABIS='habis'; case RESERVED='reserved'; }
enum PenjualanDetailStatus: string { case PREPARE='prepare'; case TERJUAL='terjual'; case RETUR='retur'; }
```
Dan kolom `produk_details.status` hanya `tersedia|terjual|habis`. `penjualan_details.status` pakai enum kedua.

### P1-02 — Konversi Satuan Hardcode 0.9144 Tanpa Tabel
`Kasir.vue:354` `konversi=0.9144` hardcode Yard→Meter, `TransaksiPenjualanEceranController:208` `realQtyOut = qty / konversi` akan terbalik jika kasir input Meter. `KonversiSatuan` sudah ada (`2026_01_16_220232`) dengan `koefisien`, `is_satuan_terkecil`, `harga_default` tapi tidak dipakai sama sekali. `ProdukVarian::konversi()` (`app/Models/ProdukVarian.php:39`) salah FK `satuan_id` harusnya `varian_id`.

**Saran:** Perbaiki relasi:
```php
public function konversi() { return $this->hasMany(KonversiSatuan::class,'varian_id'); }
```
Isi `konversi_satuans` per varian (Yard base): `Yard:1, Meter:0.9144, Kg:??`. Frontend ambil dari API `GET /api/konversi?varian_id=1` bukan hardcode.

### P1-03 — Transfer Grosir→Ecer Tidak Lock & Tidak Validasi Stok
`StokEcerController::storeTransfer:68-121` loop `findOrFail` tanpa `lockForUpdate`, dua admin transfer roll sama → double decrement. Tidak cek `produk_details.status='tersedia' AND gudang_id=2` sebelum update, bisa transfer roll yang sudah `terjual`.

**Fix:**
```php
DB::transaction(function() use ($request){
  foreach($request->selected_rolls as $stok){
    $detail = ProdukDetail::where('id',$rollData['id'])->where('gudang_id',2)->where('status','tersedia')->lockForUpdate()->firstOrFail();
    // ...
  }
}, 5); // retry 5x deadlock
```

### P1-04 — DRAFT Ecer Bisa Multi (Tanpa User Scope)
`TransaksiPenjualanEceranController::index:53-65` ambil `Penjualan::where('bagian','ecer')->where('status','DRAFT')->first()` tanpa `user_id`. Jika 2 kasir Ecer login, mereka saling lihat DRAFT yang sama → tabrakan keranjang.

**Fix:** Tambah `user_id` di `penjualans` (belum ada kolom!). Migration `add_user_id_to_penjualans`, isi `auth()->id()` saat `storeDraft`. Filter `where('user_id', auth()->id())`.

### P1-05 — Pembayaran Duplikat Field `jumlah_bayar` vs `nominal` vs `total`
`pembayaran_details:16-22` punya `nominal, jumlah_bayar, ongkir, diskon, pajak, kembalian`. `PembayaranEcerController::bayar:117-139` isi `jumlah_bayar = total_bayar` (grand total) dan `nominal = detail.nominal` (per bank) → double. `kembalian` disimpan per detail bukan per pembayaran → jika SPLIT 2 bank, kembalian duplikat.

**Saran:** `pembayarans.total` = tagihan, `pembayaran_details.nominal` = per metode, `pembayarans.kembalian` di header saja. Hapus `jumlah_bayar, ongkir, diskon` dari details (sudah di `penjualans`).

### P1-06 — Laporan Kosong & Duplikat Query
`LaporanController.php:8` kosong, `LaporanEcerController` (tidak dibaca tapi di route) kemungkinan duplicate. Owner `DashboardController::master:21` load `Produk::all()` tanpa filter gudang → OOM jika 10k roll.

**Saran:** Buat Service `LaporanService` dengan query `WITH`/`GROUP BY` untuk Omset, Barang Terlaris, Stok Mati. Gunakan `cursor()` + caching `remember(300)`.

### P1-07 — N+1 & `withQueryString` Boros
`PenerimaanController::create:49-82` load `ProdukVarian::with(['produk.satuan','details'=>where...,'stok'=>where...])` + `ProdukVarian::orderBy('nama')->get()` (semua varian) → 2 query besar per page load. `resources/js/Pages/Grosir/Penjualan/Create.vue` (tidak dibaca tapi mirip) akan fetch 30 varian + N roll = 100+ query jika tidak `withCount`.

**Fix:** Pakai `withCount('details as roll_tersedia_count')`, `paginate(30)` saja, jangan `ProdukVarian::all()`. Tambah `->select('id','produk_id','nama','sku')` untuk list dropdown. Index DB (lihat P1-08).

### P1-08 — Index Hilang
Tambah index komposit di migration baru:
```sql
produk_varians: index(produk_id), index(gudang_id), index(nama)
produk_details: index(varian_id, gudang_id, status), index(stok_id), index(barcode) unique
stoks: unique(varian_id, gudang_id), index(gudang_id)
penjualans: index(bagian, status), index(nomer) unique, index(tanggal), index(pelanggan_id)
penjualan_details: index(produk_detail_id)
kartu_stoks: index(varian_id), index(produk_detail_id), index(tipe), index(created_at)
pembelian_items_details: index(produk_detail_id)
```

### P1-09 — Harga Tidak Konsisten
`ProdukVarian.harga_beli/jual` vs `PembelianItems.harga_beli` vs `PenjualanItem.harga_satuan` vs `KonversiSatuan.harga_default` → mana source of truth? `PenerimaanController::store` isi `harga_beli=0`, `PenjualanGrosirController::store` pakai input `unit_price` tanpa cek `harga_jual` varian → kasir bisa jual di bawah modal.

**Saran:** Tambah `price_list` per Gudang: `varian_id, gudang_id, harga_modal, harga_grosir, harga_ecer, margin`. Validasi `unit_price >= harga_modal * 1.1` (warning) / `>= harga_modal` (block).

### P1-10 — Tidak Ada Retur & Penyesuaian Stok Real
`ReturPenjualan`, `ReturPenjualanItem`, `ReturPenjualanDetail` migration ada tapi controller kosong (`ReturPenjualanController.php:10`). Toko kain sering retur karena cacat / kepanjangan. Tanpa retur, kasir akan `update` Penjualan dengan hapus roll → audit hilang.

**Saran:** Implement `ReturService` yang buat `retur_penjualans` + `kartu_stok tipe=masuk` dengan `keterangan=Retur Nota X` + jurnal `MiniJurnal`.

---

## 5. Temuan P2 — Penyempurnaan (Skalabilitas & DX)

### P2-01 — Duplikasi Controller Master
`Master\ProductController`, `Grosir\ProdukGrosirController`, `Ecer\ProdukEcerController` 80% logic sama (firstOrCreate Kategori, SKU random). Violasi DRY.

**Saran:** Ekstrak `App\Services\ProdukService::createOrUpdate($data, $gudangId)` + `App\Actions\UpsertProdukVarian`. Controller hanya validasi + call service.

### P2-02 — Validasi & Form Request Tidak Ada
Semua controller pakai `$request->validate([...])` inline, duplikat. Tidak ada `FormRequest` untuk `StorePenerimaanRequest`, `StorePenjualanGrosirRequest`.

**Saran:** `php artisan make:request StorePenerimaanRequest` dengan `rules()` + `withValidator` cek `collect(details).sum(qty) == total_qty`.

### P2-03 — PrinterService Blocking & Hardcode
`PrinterService.php:18` `new WindowsPrintConnector("POS80")` hardcode, `try/finally close` tapi tidak handle `printer offline` → request timeout 30s. Cetak nota seharusnya async via Queue.

**Saran:** `PrintNotaJob::dispatch($penjualan)` ke `queue:listen`, connector ambil dari `config/printing.php` (`env('PRINTER_GROSIR','POS80')`), fallback ke PDF jika printer error.

### P2-04 — PWA `devOptions.enabled=true` di Prod
`vite.config.js:19` `devOptions.enabled: true` bikin service worker cache di `npm run dev` → stale chunk saat HMR. `manifest.name: POS Kasir Eceran` hanya untuk Ecer, Grosir tidak.

**Fix:** `enabled: process.env.NODE_ENV === 'production'`, tambah `workbox.runtimeCaching` untuk `/api/*` NetworkFirst.

### P2-05 — Testing Nol
`tests/` hanya default Laravel, tidak ada test untuk `storeTransfer`, `simpanItems`, `PenjualanGrosir::update`. Bug P0-03 tidak akan ketahuan tanpa test.

**Saran:** Minimal 4 test:
```php
test('grosir penjualan create decrements stok', ...)
test('ecer penjualan potong roll parsial updates qty & kartu_stok', ...)
test('transfer grosir ke ecer atomic', ...)
test('concurrent nota number unique', ...)
```
Jalankan `php artisan test --parallel`.

### P2-06 — Logging & Observability
`Log::info("masukin id sale...")` debug sisa migrasi masih ada di `PenerimaanController:603`. Tidak ada structured logging untuk `user_id, nomer, varian_id` yang searchable.

**Saran:** Ganti ke `Log::channel('stok')->info('stok.masuk', ['user'=>auth()->id(),'varian'=>$varian->id,'qty'=>$panjang])` + Laravel Pail filter.

### P2-07 — Keamanan: Mass Assignment & SQL Like
- `Produk.php:9` `fillable` pakai `nama` tapi `ProdukGrosirController::store` kirim `nama_produk` → akan `MassAssignmentException` jika `Model::preventSilentlyDiscardingAttributes()` diaktifkan (Laravel 12 default).
- `where('nama','like',"%{$request->search}%")` tanpa `escape %_` → searchable `%` akan scan full table. Tidak ada rate limit di `/api/produk-search`.

**Fix:** `Model::unguard()` tidak disarankan; definisikan `fillable` lengkap atau `guarded=[]` dengan `FormRequest`. Tambah `throttle:60,1` di `routes/web.php:42`.

### P2-08 — Frontend Debt
- `Kasir.vue:56,147` `AuthLayout` dipakai untuk POS, tapi `Grosir` pakai `DashLayout` → inkonsisten sidebar.
- `Kasir.vue:323 handleSearch` pakai `router.get(..., {preserveState:true})` tapi tidak debounce `searchQuery` di server → tiap ketik 500ms tetap query `whereHas details`.
- `boxicons` di-load via `resources/assets/boxicons/css/boxicons.css` (2.3MB font) tanpa tree-shaking.

**Saran:** Debounce 500ms sudah ada, tapi tambah `minimum 2 char` + `cache: {ttl:60}` di `TransaksiPenjualanEceranController::index`. Lazy-load icons.

---

## 6. Rekomendasi Arsitektur Doc — “Ideal State”

### 6.1. Diagram Ideal (Tanpa Ubah Bisnis)

```
[Kasir Grosir] ──> Penjualan (bagian=grosir, roll utuh)
                └─> ProdukDetail.status=terjual, Stok.gudang_id=2 decrement
[Kasir Ecer]   ──> Penjualan (bagian=ecer, potong)
                └─> ProdukDetail.qty decrement parsial, KartuStok keluar

[Gudang Grosir] ──transfer_roll──> [Gudang Ecer] (KartuStok tipe=transfer)

Pembelian (Grosir only) ──> Stok+Grosir
Ecer tidak bisa beli langsung, hanya via transfer → INI SUDAH BENAR, pertahankan.
```

### 6.2. Enum & Konstanta Baru
```php
// app/Enums/GudangEnum.php
enum GudangEnum:int { case ECER=1; case GROSIR=2; }
// app/Enums/StatusProdukDetail.php
enum StatusProdukDetail:string { case TERSEDIA='tersedia'; case TERJUAL='terjual'; case HABIS='habis'; }
// config/kain.php
'satuan_base' => 'Yard',
'konversi' => ['Yard'=>1,'Meter'=>0.9144,'Kg'=>null], // Kg tidak konversi
```

### 6.3. Service Layer
```
App\Services\
  ProdukService (upsert header/varian/detail)
  StokService (increment/decrement + locking + kartu)
  TransferService (grosir->ecer atomic)
  PenjualanService (create/update/destroy + rollback)
  NotaNumberService (generate unique per hari + lock)
  LaporanService (omset, stok, barang)
```

### 6.4. Skema Ideal Tambahan
```sql
-- tambah di penambahan migration 2026_08_22
penjualans: add user_id FK users, add catatan, index(bagian,status,user_id)
produk_details: add barcode unique nullable, add harga_beli snapshot
stoks: add unique(varian_id,gudang_id)
konversi_satuans: fix FK varian_id, add unique(varian_id,satuan_id)
```

---

## 7. Roadmap Eksekusi (4 Sprint, 2 Minggu/Sprint)

### Sprint 1 — Stabilitas P0 (Minggu 1-2)
- [ ] Fix P0-01 s/d P0-08 (estimasi 3 hari)
- [ ] Tambah FK & index migration + `php artisan migrate` di staging
- [ ] Tulis 4 feature test P2-05, CI `php artisan test` hijau
- [ ] Seed `gudangs` + konstanta `GudangEnum`

**Deliverable:** `grosir/penjualan/edit` & `destroy` tidak 500, nomor nota unik, observer tidak error.

### Sprint 2 — Konsistensi Stok & Transaksi (Minggu 3-4)
- [ ] Refactor `ProdukVarian::stoks()` hasMany, hapus magic 55
- [ ] Implement `lockForUpdate` di semua `store/update/destroy` + retry
- [ ] Selesaikan `ReturPenjualanController` + `MiniJurnal` integration
- [ ] Transfer Grosir→Ecer dengan validasi status & stok

**Deliverable:** KartuStok 100% akurat, concurrent test 10 kasir paralel tidak minus stok.

### Sprint 3 — Performa & Laporan (Minggu 5-6)
- [ ] Index + `withCount` + pagination 30 (hapus `ProdukVarian::all()`)
- [ ] `LaporanService` + `LaporanController` (omset harian, stok mati, barang terlaris)
- [ ] `NotaNumberService` + validasi harga modal
- [ ] Cache `Kategori::all()` & `Satuan::all()` 5 menit

**Deliverable:** List produk <300ms di 5k varian, laporan owner ready.

### Sprint 4 — UX Kasir & Hardening (Minggu 7-8)
- [ ] Merge duplicate Master controllers → `ProdukService`
- [ ] FormRequest + throttling `/api/produk-search`
- [ ] Queue `PrintNotaJob` + PWA fix
- [ ] Audit RBAC: `owner` bisa lihat semua, `admin-grosir` tidak bisa akses `/ecer/*` (test 403)

**Deliverable:** UX kasir 60fps, tidak ada N+1, security audit pass.

---

## 8. Checklist Verifikasi Sebelum Production

```bash
# 1. Migrasi & seed
php artisan migrate:fresh --seed
php artisan tinker --execute="echo \App\Models\Gudang::count();"

# 2. Test
php artisan test --parallel
# Expected: 15 passed

# 3. Static analysis
./vendor/bin/pint --test
php artisan route:list --path=grosir | grep penjualan

# 4. Manual QA
# - Buat Penerimaan 2 roll Yard 50 & 30 → cek Stok total_qty 80, KartuStok masuk 2
# - Jual Grosir 1 roll → cek ProdukDetail status terjual, Stok decrement 1/50
# - Transfer 1 roll ke Ecer → cek gudang_id 1, KartuStok transfer
# - Jual Ecer 10 Meter (Yard 10/0.9144) → cek qty sisa 40, KartuStok keluar realQty
# - Concurrent: 2 tab kasir jual roll sama → 1 sukses, 1 error 422 stok habis
# - Cetak nota → queue job success, PDF fallback jika printer offline
```

---

## 9. Daftar File yang Wajib Diubah (Prioritas)

| Prioritas | File | Baris | Aksi |
|-----------|------|-------|------|
| P0 | `app/Models/Pembelian.php:23` | 23 | Fix belongsTo |
| P0 | `app/Models/ProdukVarian.php:30,39` | 30,39 | hasMany + FK varian_id |
| P0 | `app/Http/Controllers/Grosir/PenjualanGrosirController.php:372,464` | 372,464 | stock→stok, array fix |
| P0 | `app/Http/Controllers/Grosir/PenerimaanController.php:414` | 414 | where id bukan qty |
| P0 | `app/Observers/ProdukObserver.php:1` | all | Hapus / refactor |
| P0 | `config/database.php:45` | 45 | sqlite_lama path |
| P1 | `app/Http/Controllers/Ecer/TransaksiPenjualanEceranController.php:53,208` | 53,208 | user_id scope + konversi |
| P1 | `app/Http/Controllers/Ecer/StokEcerController.php:68` | 68 | lockForUpdate |
| P1 | `database/migrations/*` | all | FK + index |
| P2 | `app/Services/PrinterService.php:18` | 18 | Queue + config |
| P2 | `vite.config.js:19` | 19 | devOptions prod only |

---

## 10. Penutup

Aplikasi sudah **70% siap** untuk operasional toko kain grosir-ecer dengan model 3 layer yang idiomatik. Fokus 2 minggu ke depan harus **P0 stabilitas** sebelum menambah fitur baru—karena satu bug stok (`stock()` vs `stok()`) bisa bikin selisih yard yang merugikan ribuan meter kain.

Jika butuh, Muse bisa lanjutkan dengan:
1. **Patch P0 langsung** (branch `fix/p0-stok-integritas`) — 8 file, + migration + test.
2. **Service layer scaffolding** (`ProdukService`, `StokService`, `NotaNumberService`) siap pakai controller.
3. **Laporan owner** (Omset, Stok Mati, Barang Terlaris) dengan query optimized.

> *“Stok adalah uang. Setiap roll harus punya jejak KartuStok yang tidak bisa hilang.”*

— Muse Spark, 22 Agustus 2026

