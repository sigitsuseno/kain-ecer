# Perbaikan & Penyempurnaan — Aplikasi Kasir Toko Kain (Grosir & Eceran)

> Hasil analisis menyeluruh `docs/petunjuk_analisis.md` terhadap codebase `H:\laragon\www\kain-ecer`.
> Dibuat: 23 Agustus 2026. Pendekatan: audit statis (routes/controllers/models/views) + automated gates (`php -l`, `artisan test`, `composer audit`, `npm run build`) + runtime smoke test (boot & authz).

## Ringkasan Eksekutif

Aplikasi ini adalah **Laravel 12 + Inertia 2 + Vue 3 + Tailwind 4**, 3-layer produk (Header `produks` → Varian `produk_varians` → Detail roll `produk_details`), DB **MySQL `utekstilecer`**, auth role manual (`owner`, `admin-grosir`, `admin-ecer`) via `RoleMiddleware`.

**Kondisi keseluruhan: kode tampak "rapi" (semua file lolos `php -l`, route:list sukses, `npm run build` sukses), TETAPI banyak fitur inti rusak karena satu akar masalah: proyek ini berada di tengah refactor schema.** Schema sudah dipindah ke model 3-layer berbasis kolom `nama`, sedangkan sebagian besar controller **dan seluruh komponen Vue produk** masih memakai nama kolom lama dari schema sebelumnya (`nama_produk`, `kode_warna`, `nama_varian`, `nomor`). Akibatnya query terhadap kolom yang sudah tidak ada melempar SQL error, dan UI produk menampilkan data kosong.

Tabel inti saat ini masih kosong (produk/varian/detail/stok/pembelian/penjualan = 0 baris), `users` = 3 (Owner/Grosir/Ecer), `gudangs` = 2, `satuans` = 3. Artinya app baru disetup data master, belum ada transaksi.

Semua temuan di bawah sudah diaudit dan diverifikasi terhadap schema aktual. Severity: **KRITIS** → harus diperbaiki sebelum dipakai produksi; **TINGGI** → merusak fitur inti; **SEDANG** → risiko data/keamanan/kualitas; **RENDAH** → kebersihan/hardcode.

---

## Ringkasan Temuan

| # | Severity | Lokasi | Isu |
|---|----------|--------|-----|
| K1 | KRITIS | banyak controller + Vue | Schema kolom produk lama vs baru (nama_produk/kode_warna/nomor) |
| K2 | KRITIS | Grosir\PenjualanGrosirController@update | `$varian->stock()` — method tidak ada (harusnya `stok()`) |
| K3 | KRITIS | Master\CategoryController@store & @update | `dd($request)` ketinggalan → kategori tidak pernah tersimpan |
| K4 | KRITIS | Grosir\SettingController & Ecer\EcerSettingController | Route memanggil method yang tidak ada (copy-paste dari stok) |
| K5 | KRITIS | Grosir\PenjualanGrosirController@destroy | Array `['status'=>'tersedia','gudang_id',2]` salah syntax → SQL error |
| T1 | TINGGI | App\Models\Pembelian | Relasi `supplier()` salah (`belongsTo(Supplier::class . 'supplier_id')`) |
| T2 | TINGGI | Grosir\PenerimaanController@store | `supplier_id => 1` hardcoded, mengabaikan supplier terpilih |
| T3 | TINGGI | Banyak file | `'nomor'` pada `stoks` (kolom sebenarnya `nomer`) → SQL error search/insert |
| T4 | TINGGI | TransaksiPenjualanEceranController@simpanItems | Kartu stok ecer tulis `dari_gudang_id=2` padahal jualan dari gudang 1 |
| S1 | SEDANG | routes/web.php | `/grosir/comparing` TANPA auth → bocor data produk+stok |
| S2 | SEDANG | routes/web.php + PrintController | `/print-thermal` POST tanpa auth & CSRF-exempt; tulis file + cetak fisik |
| S3 | SEDANG | bootstrap/app.php | CSRF-exempt `/ecer/penjualan/print-thermal` tidak cocok dengan route mana pun |
| S4 | SEDANG | .env | `APP_DEBUG=true` |
| S5 | SEDANG | Fitur Retur | Retur Penjualan & Retur Barang belum diimplementasi (controller kosong, tanpa route) |
| S6 | SEDANG | Settings & Stock transfer | Magic number gudang (1=ecer,2=grosir,55=terjual) hardcode di banyak tempat |
| R1 | RENDAH | PenjualanEcerController, PrintController | `WindowsPrintConnector("POS80")` hardcoded + file temp tetap |
| R2 | RENDAH | Import tidak terpakai | `use item;`, `use App\Models\Penerimaan;`, `use Faker\Guesser\Name;`, `DetailPaket/DetailPenjualan/ItemPenjualan` |
| R3 | RENDAH | resources/js/Pages | File sisa pengembangan (.md, .txt, test.vue) ikut tercampur |
| R4 | RENDAH | Master\CategoryController@index + Inertia | Case mismatch `index` vs `Index.vue`, rawan gagal di Linux |
| R5 | RENDAH | Penomoran nota | `generateNota()` berbasis id terakhir+1 → rawan tabrakan (multi kasir) |

---

## K1 — KRITIS: Schema kolom produk tidak sinkron (akar masalah terbesar)

**Kolom aktual (dari migration):**
- `produks`: `id, nama, kategori_id, brand_id, satuan_id, deskripsi, status` — TIDAK ada `nama_produk`, `kode_warna`, `kode_produk`, `slug`.
- `produk_varians`: `id, produk_id, gudang_id, nama, tipe, panjang, lebar, tinggi, tebal, jumlah, sku, is_paket, harga_beli, harga_jual`.
- `produk_details`: `id, gudang_id, varian_id, barcode, stok_id, old_id, satuan_id, qty, isi, status`.
- `stoks`: `id, gudang_id, varian_id, nomer, total_qty, total_unit, tanggal_kadaluarsa` — kolom **`nomer`**, bukan `nomor`.

**Controller yang memakai kolom yang sudah tidak ada (query akan SQL error):**
- `Master\ProductController@colorsByName` (baris 23): `Produk::where('nama_produk', ...)` → `Unknown column 'nama_produk'`.
- `Master\ProductController@store` (36-62): Rule::unique memakai `where('nama_produk', ...)` → SQL error; lalu `Produk::create($data)` dengan `nama_produk/kode_warna/kode_produk/slug/satuan/kode_seri/lebar_kain` — semuanya TIDAK fillable → baris produk tercipta hanya berisi `kategori_id`, `nama` = null → `NOT NULL` violation.
- `ProdukEcerController@colorsByName` (85-86): `where('nama_produk')` + `pluck('kode_warna')` → SQL error.
- `ProdukEcerController@index` (52,54): filter `nama_varian` & `nama_produk` → SQL error saat cari produk ecer.
- `ProdukEcerController@store` (146) & `@importCsv` (323): insert `'nomor'` ke tabel `stoks` → `Unknown column 'nomor'`.
- `ProdukGrosirController@colorsByName` (73): `where('nama_produk')` → SQL error.
- `StokEcerController@index` (21) & `StokGrosirController@index` (18): `where('nomor', ...)` → `Unknown column 'nomor'` saat cari stok.
- `DashboardController@master` (29) & `EcerController@index` (29): `where('kode_warna', ...)` → SQL error saat search di dashboard owner & index ecer.

**Frontend (Vue) yang juga memakai nama lama — menampilkan kolom kosong:**
- `resources/js/Components/Dashboard/Master/ProductModal.vue` & `ProductTable.vue`: membaca `p.nama_produk`, `p.kode_warna`.
- `resources/js/Components/Eceran/ProductModal.vue`: `p.nama_produk`, `p.kode_warna`.

**Dampak:** seluruh pencarian & pembuatan produk (owner dashboard, grosir, ecer) rusak; daftar produk tampil kosong/blangko; dropdown "pilih warna" (autocomplete) mati. Ini fitur yang paling sering dipakai kasir.

**Rekomendasi:** satukan nama field ke schema baru (`produk->nama`, `varian->nama`), rename field di Vue (`form.nama`, `form.nama_varian`), ganti `'nomor'`→`'nomer'`. Alternatif (tidak disarankan): tambahkan kolom kompatibilitas `nama_produk`/`kode_warna` di DB + accessor di model. Pilih SATU konvensi agar tidak terpecah lagi.

## K2 — KRITIS: `$varian->stock()` method tidak ada
- **File:** `app/Http/Controllers/Grosir/PenjualanGrosirController.php` baris **372-373** (dalam `update()`).
- **Isu:** `$varian->stock()->increment('total_qty', ...)` — model `App\Models\ProdukVarian` hanya punya relasi `stok()` (baris 30), TIDAK ada `stock()`.
- **Dampak:** saat kasir **mengedit transaksi penjualan grosir**, aplikasi melempar `BadMethodCallException: Call to undefined method App\Models\ProdukVarian::stock()`. Flow edit transaksi grosir MATI total.
- **Fix:** ganti `$varian->stock()` → `$varian->stok()` di kedua baris.

## K3 — KRITIS: `dd($request)` ketinggalan di CRUD kategori
- **File:** `app/Http/Controllers/Master/CategoryController.php` baris **19 (`store`)** dan **49 (`update`)**.
- **Isu:** `dd($request)` (dump & die) masih tertinggal. Karena rute kategori `ecer/*` dan `dashboard/master/*` memakai controller yang sama, maka **submit kategori/warna dari dashboard owner & dari menu Ecer tidak akan pernah tersimpan** (skrip berhenti di dd).
- **Dampak:** fitur tambah/edit kategori (nama & warna) rusak di kedua tempat.
- **Fix:** hapus baris `dd($request);`. (Pola `dd` serial juga perlu dicek di semua controller lain — grep `dd(` di `app/`.)

## K4 — KRITIS: Route Settings memanggil method yang tidak ada
- **File:** `app/Http/Controllers/Grosir/SettingController.php` (hanya punya `index()`), `app/Http/Controllers/Ecer/EcerSettingController.php` (hanya `index()`, `bank()`, `storebank()`, `destrobank()`).
- **Isu:** `routes/web.php` mengarahkan rute settings (POST `store`, GET `create`, PUT `{id}` update, DELETE `{id}` destroy) ke method `storeTransfer` / `createTransfer` / `update` / `destroy`. Method tersebut **TIDAK ADA** di kedua controller (pola copy-paste dari `StokEcerController`).
- **Dampak:** apa pun aksi di halaman Settings (kecuali menampilkan index) melempar 500 `Method ... does not exist`. Module Settings grosir & ecer praktis tidak bisa dipakai.
- **Fix:** tulis ulang method Settings yang sesuai kebutuhan (mis. simpan pengaturan perusahaan/akun), atau sembunyikan/lepaskan rute yang belum diimplementasi. Jangan meninggalkan rute yang menunjuk ke method hantu.

## K5 — KRITIS: Syntax array salah pada destroy transaksi grosir
- **File:** `app/Http/Controllers/Grosir/PenjualanGrosirController.php` baris **464**.
- **Isu:** `$produkDetail->update(['status' => 'tersedia', 'gudang_id', 2])` — `'gudang_id', 2` tidak memakai `=>`, sehingga jadi entry dengan key numerik `0 => 'gudang_id'`, `1 => 2`. Saat di-save, Eloquent mencoba mengisi kolom `0` & `1` → SQL error, dan `gudang_id` tidak benar-benar di-set ke 2.
- **Dampak:** **menghapus transaksi penjualan grosir** crash dan stok tidak dikembalikan dengan benar → data stok bisa selisih.
- **Fix:** `['status' => 'tersedia', 'gudang_id' => 2]`.

---

## T1 — TINGGI: Relasi `Pembelian::supplier()` rusak
- **File:** `app/Models/Pembelian.php` baris **23**.
- **Isu:** `return $this->belongsTo(Supplier::class . 'supplier_id');` — hasilnya `App\Models\Suppliersupplier_id` (class tidak ada) dan argument relasi hilang. Benar: `belongsTo(Supplier::class, 'supplier_id')`.
- **Dampak:** setiap pemanggilan `$pembelian->supplier` melempar fatal. Saat ini tidak ada alur yang memanggilnya, tapi jadi bom waktu (misal untuk laporan/label supplier).
- **Fix:** `return $this->belongsTo(Supplier::class, 'supplier_id');`. Tambahan kecil: `supplier_id` duplikat di `$fillable` (baris 11 & 13) — hapus salah satu.

## T2 — TINGGI: `supplier_id` hardcoded di penerimaan barang
- **File:** `app/Http/Controllers/Grosir/PenerimaanController.php` baris **100**.
- **Isu:** `"supplier_id" => 1` dipakai saat membuat header `Pembelian`, dan validasi tidak menyertakan `supplier_id`. Jadi **semua penerimaan barang selalu dikaitkan ke supplier ID 1**, mengabaikan supplier yang dipilih pengguna (form seharusnya punya pemilih supplier).
- **Dampak:** data pembelian salah supplier → laporan pembelian per supplier tidak akurat.
- **Fix:** tambahkan `supplier_id` ke validasi (`items`/header) dan tulis `$request->supplier_id`; pastikan form Create mengirimkan supplier terpilih.

## T3 — TINGGI: Kolom `nomor` vs `nomer` pada tabel `stoks`
- **File:** `ProdukEcerController@store` (146), `@importCsv` (323), `StokEcerController@index` (21), `StokGrosirController@index` (18).
- **Isu:** tabel `stoks` memakai kolom **`nomer`**, tapi beberapa query/insert memakai **`nomor`**.
- **Dampak:** (a) import & pembuatan produk ecer gagal (`Unknown column 'nomor'`), (b) pencarian stok (ecer & grosir) dengan nomor → SQL error.
- **Fix:** normalisasi seluruh `'nomor'` → `'nomer'` (termasuk `where('nomor')`, `'nomor' =>` di create). Lakukan grep menyeluruh.

## T4 — TINGGI: Kartu stok ecer memakai gudang yang salah
- **File:** `app/Http/Controllers/Ecer/TransaksiPenjualanEceranController.php` baris **238** (`simpanItems`).
- **Isu:** penjualan ecer terjadi dari gudang **1** (lihat baris 70/72), tetapi pencatatan kartu stok menulis `'dari_gudang_id' => 2` (gudang grosir).
- **Dampak:** audit trail kartu stok salah (mengaku barang keluar dari gudang grosir padahal dari gudang ecer) → laporan per gudang tidak konsisten.
- **Fix:** ubah menjadi `'dari_gudang_id' => 1`. Sebaiknya jadikan nilai gudang sebagai konstanta/konfigurasi (lihat S6) agar tidak hardcode.

---

## S1 — SEDANG: `/grosir/comparing` terbuka tanpa autentikasi
- **File:** `routes/web.php` (baris terakhir): `Route::get('/grosir/comparing', [PenerimaanController::class, 'comparing'])->name('comparing');` — berada **di luar** grup `auth`/`role`.
- **Isu:** endpoint tanpa login yang mengembalikan daftar seluruh produk varian + perbandingan stok (hasil `comparing()` di `PenerimaanController`). Di runtime smoke test, route ini mengembalikan **HTTP 200 tanpa login** (terkonfirmasi), sementara `/grosir` membutuhkan login (302).
- **Dampak:** bocor data produk & stok ke pihak tak berwenang. Sekarang DB kosong (tidak fatal), tapi akan bocor sekali data terisi.
- **Fix:** masukkan ke grup `['auth','role:admin-grosir']`, atau pindahkan ke dalam prefix `grosir` yang sudah ber-auth.

## S2 — SEDANG: `/print-thermal` POST tanpa auth & tanpa CSRF
- **File:** `routes/web.php` (`Route::post('/print-thermal', [PrintController::class, 'print'])`) + `bootstrap/app.php` (`validateCsrfTokens(except: ['/print-thermal', ...])`).
- **Isu:** endpoint penerima base64 gambar, menulis ke `storage_path('app/temp_nota.png')`, lalu mencetak ke printer fisik `POS80` — semuanya **tanpa login & tanpa token CSRF**.
- **Dampak:** siapa pun bisa memicu pencetakan fisik (spam nota) dan menulis file ke storage. Plus basis `temp_nota.png` tetap → bentrok jika 2 request bersamaan.
- **Fix:** pindahkan ke grup ber-auth; tetap butuh proteksi CSRF (hapus dari `except`); gunakan nama file unik (mis. `temp_{time()}.png`). Konfirmasi dulu ke user apakah endpoint ini memang perlu publik untuk akses langsung ke printer.

## S3 — SEDANG: Exclusion CSRF yang tidak cocok dengan route
- **File:** `bootstrap/app.php` baris `'/ecer/penjualan/print-thermal'`.
- **Isu:** tidak ada route bernama path itu. Route yang ada adalah `GET /ecer/penjualan/print-tagihan/{id}` (GET → tidak butuh CSRF). Jadi exclusion ini mati/nyasar.
- **Fix:** hapus entri `/ecer/penjualan/print-thermal` dari `validateCsrfTokens(except:)` karena tidak relevan.

## S4 — SEDANG: `APP_DEBUG=true`
- **File:** `.env`.
- **Isu:** mode debug aktif, menampilkan error stack & detail env ke pengguna saat error.
- **Dampak:** kebocoran informasi teknis; bahaya di lingkungan produksi/lebih dari satu pengguna.
- **Fix:** set `APP_DEBUG=false` pada environment produksi (nilai `.env` diserahkan ke user; jangan kami ubah tanpa permintaan).

## S5 — SEDANG: Modul Retur belum diimplementasikan
- **File:** `app/Http/Controllers/Dashboard/ReturPenjualanController.php` (kosong) & `ReturBarangController.php` (kosong). Tidak ada route retur di `web.php`.
- **Isu:** meskipun ada migrasi & model `ReturPenjualan*` dan `petunjuk_analisis.md` menyebut fitur "Retur Penjualan" & "Retur Pembelian", backend-nya belum konek (controller stub, tanpa route).
- **Dampak:** fitur retur yang tertera di dokumen belum bisa dipakai.
- **Fix:** perlu spesifikasi & implementasi penuh (retur jual: balik stok + kartu stok + pembayaran; retur beli: balik stok grosir). Masukkan ke backlog sebelum rilis, atau tandai jelas sebagai "belum tersedia" di UI.

## S6 — SEDANG: Magic number gudang & konfigurasi
- **Isu:** ID gudang di-hardcode di banyak controller: `gudang_id = 2` (grosir, penerimaan, penjualan grosir), `gudang_id = 1` (ecer, stock), `gudang_id = 55` (barang terjual, di `PenjualanGrosirController@store`/`@update`), `supplier_id = 1`. Nilai `55` bahkan tidak ada di tabel `gudangs` (baru ada 2 baris).
- **Dampak:** bila gudang/supplier berubah atau ada cabang baru, seluruh logika harus diedit manual; angka `55` merusak makna data (barang "terjual" di-set ke gudang fiktif).
- **Fix:** pindahkan ID gudang inti ke `config/` (mis. `config/toko.php` → `gudang_grosir`, `gudang_ecer`, `gudang_terjual`) atau tabel `settings`, lalu ganti semua hardcode dengan `config(...)`. Ini menyederhanakan maintenance dan menghindari angka ajaib.

---
## RENDAH
- **R1 — Printer hardcoded & file temp tetap:** `WindowsPrintConnector("POS80")` di `PrintController` & `PenjualanEcerController@printTagihan`. Jika nama printer berbeda → gagal. Gunakan nama printer dari `settings`/`config`. File `app/temp_nota.png` tetap → bentrok antarkasir; gunakan nama unik.
- **R2 — Import tidak terpakai (dead code):** `use item;` (`PenerimaanController` baris 22), `use App\Models\Penerimaan;` & `use Faker\Guesser\Name;` (`web.php`), `use App\Models\DetailPaket / DetailPenjualan / ItemPenjualan;` (`TransaksiPenjualanEceranController` baris 6-9), `use App\Models\Pembelian;` di `Pembelian` model (prob). Bersihkan agar tidak menyesatkan.
- **R3 — File sisa pengembangan di Pages:** `resources/js/Pages/Grosir/Penjualan/edit.md`, `test.vue`, `pembelian.txt`, `Ecer/Produk...` (`.txt`, `.md`, `prompt.md`). Harus dihapus dari source tree (ikut ke produksi & menyerap build). Pindahkan catatan/dev ke luar `resources/js/Pages`.
- **R4 — Case mismatch page Inertia:** `inertia('Ecer/Setting/index')` vs file `Index.vue`; juga `Grosir/Penerimaan/index` vs `index.vue`. Aman di Windows (case-insensitive) tapi gagal saat deploy ke Linux. Samakan case (pakai `Index.vue`/`Kategori` konsisten). Catatan: `Master\CategoryController@index` merender `Dashboard/Master/Category` yang **tidak ada file-nya** (direktori `Pages/Dashboard/Master` kosong) — hapus blok `index()` atau buat halamannya.
- **R5 — Penomoran nota rawan tabrakan:** `TransaksiPenjualanEceranController@generateNota()` & `PenjualanGrosirController@create/edit` memakai `id terakhir + 1` / `nomer terakhir + 1`. Dengan 2 kasir (multi-user) sangat mungkin dua nota bernomor sama, apalagi setelah ada nota dihapus. Gunakan sequence/DB `auto_increment nomor` atau simpan counter terpisah + `lock`/`unique index`.

---

## Rencana Perbaikan (Prioritas & Bertahap)

Disusun agar bisa dikerjakan bertahap, dari yang paling berdampak & berdampak luas. Setiap langkah menyertakan target file & cara verifikasi.

### Fase 1 — Perbaiki yang langsung merusak fitur inti (KRITIS)
1. **K3:** Hapus `dd($request);` di `Master/CategoryController@store` & `@update`. *(Grep seluruh `app/` untuk `dd(` tersisa.)*
   - Verifikasi: buka menu kategori ecer/master → tambah kategori → harus redirect & data tersimpan.
2. **K1/T3:** Sinkronkan kolom produk ke schema baru:
   - Backend: ganti semua `'nomor'` → `'nomer'` (grep `nomor` di `app/`), ganti `nama_produk`/`nama_varian`/`kode_warna` pada query dengan `nama`.
   - Frontend: ganti `form.nama_produk`, `p.nama_produk`, `p.kode_warna` → `form.nama`, `p.nama`, dll. di `ProductModal.vue`, `ProductTable.vue`, `Eceran/ProductModal.vue`.
   - Verifikasi: `npm run build` sukses; buka daftar produk (owner/grosir/ecer) → baris & dropdown tidak blank; cari produk tidak error.
3. **K2:** Ganti `$varian->stock()` → `$varian->stok()` di `PenjualanGrosirController@update`.
4. **K4:** Tulis ulang / sembunyikan rute Settings yang menunjuk ke method hantu di `Grosir\SettingController` & `Ecer\EcerSettingController`. Minimal: buat method `storeTransfer/createTransfer/update/destroy` yang benar, atau nonaktifkan rutenya.
5. **K5:** Perbaiki `['status' => 'tersedia', 'gudang_id', 2]` → `['status' => 'tersedia', 'gudang_id' => 2]`.

### Fase 2 — Perbaiki konsistensi data & relasi (TINGGI)
6. **T1:** `Pembelian` model → `belongsTo(Supplier::class, 'supplier_id')`; hapus `supplier_id` duplikat di `$fillable`.
7. **T2:** `PenerimaanController@store` — terima `supplier_id` dari form & simpan ke `Pembelian` (ganti hardcode `1`).
8. **T4:** `TransaksiPenjualanEceranController@simpanItems` — kartu stok `dari_gudang_id => 1` (ecer), jangan `2`.
9. **S6:** Pindahkan ID gudang (1/2/55) & `supplier_id` ke `config/` atau tabel settings, lalu ganti semua hardcode dengan helper/config. *(Refactor luas — kerjakan hati-hati & uji ulang Fase 1.)*

### Fase 3 — Keamanan & konfigurasi
10. **S1:** Masukkan `/grosir/comparing` ke grup ber-auth (`role:admin-grosir`).
11. **S2:** Proteksi `/print-thermal` — pindahkan ke grup auth, hapus dari CSRF-except, nama file temp unik.
12. **S3:** Hapus exclusion CSRF `/ecer/penjualan/print-thermal` yang mati.
13. **S4:** `APP_DEBUG=false` di produksi (koordinasi dengan user).

### Fase 4 — Kebersihan & kualitas
14. **R2:** Hapus import tidak terpakai (`use item;`, `Penerimaan`, `Name`, `DetailPaket`, dll).
15. **R3:** Pindahkan/hapus file `.md`/`.txt`/`test.vue` dari `resources/js/Pages`.
16. **R4:** Samakan case halaman Inertia (`Index.vue`, `index.vue`); hapus `Master\CategoryController@index` yang merender page tak ada.
17. **R5:** Perbaiki penomoran nota (sequence/unique index) untuk mendukung multi-kasir.
18. **R1:** Nama printer dari settings/config; file temp unik.

### Fase 5 — Fitur yang belum lengkap (backlog)
19. **S5:** Implementasi retur penjualan & retur pembelian (controller + route + Vue), atau sembunyikan menu hingga siap.
20. **Coverage test:** tambah feature test (RefreshDatabase) untuk alur pembelian/sale/stok agar regresi tertangkap. Saat ini hanya ada 2 test contoh bawaan Laravel.

> Catatan: BARU kerjakan Fase 2-5 setelah Fase 1 disetujui & diverifikasi oleh user, karena refactor field produk berdampak luas dan ada bagian yang perlu keputusan bisnis (nilai `55`, nama printer, prioritas retur).

---

## Verifikasi Keseluruhan (setelah perbaikan)
- `php -l` semua file PHP → tidak ada error.
- `php artisan route:list` → tanpa error.
- `php artisan test` → hijau (tambahkan test nyata).
- `npm run build` → sukses, tanpa blok produk blank.
- Smoke test manual: login 3 role (owner/grosir/ecer) → buat produk → terima barang → jual → cek stok & kartu stok → retur (jika sudah dibuat).

## Catatan Lingkungan
- DB: MySQL `utekstilecer`, koneksi `sqlite_lama` menunjuk ke `database/database.sqlite` (file kosong) — dipakai hanya oleh `comparing()`/`updateData()` (alat migrasi sekali pakai). **Seharusnya tidak menjadi route live**; kunci/arsipkan atau lindungi.
- `.env` berisi `APP_DEBUG=true` & `APP_KEY` valid (jangan dicetak isinya).
- Aplikasi di-desain untuk Windows (XAMPP): direferensikan printer `POS80` via `mike42/escpos-php`, tidak ada dependency mail/queue eksternal yang krusial.

---
*Dokumen ini adalah hasil audit awal. Sebelum eksekusi, diskusikan tiap fase dengan pemilik untuk menetapkan pendekatan yang disetujui.*
