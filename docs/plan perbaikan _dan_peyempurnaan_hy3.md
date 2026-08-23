# Plan Perbaikan & Penyempurnaan - Aplikasi Kasir Kain (Grosir & Ecer)

> Hasil analisis kode aplikasi `kain-ecer` (Laravel 11 + Inertia.js + Vue 3).
> Dokumen ini berisi daftar **error / konflik / isu / kemungkinan masalah** yang ditemukan,
> beserta **saran perbaikan** dan **rencana implementasi** bertahap.
>
> Catatan: sebagian besar temuan bersifat *kritis* karena menyangkut **integritas stok & keuangan**.
> Disarankan memperbaiki lapisan data (Model/Migrasi) terlebih dahulu, baru logika controller, lalu UI.

---

## 1. Ringkasan Arsitektur & Temuan Utama

Aplikasi memiliki model produk 3 lapis:
- **Produk (Header)** -> `produks`
- **ProdukVarian (Actual)** -> `produk_varians` (grosir/ecer dibedakan via `gudang_id`)
- **ProdukDetail (Fisik / Roll)** -> `produk_details`

Tiga representasi stok yang **saling tumpang tindih**:
- `produk_details.qty/isi` = roll fisik (seharusnya sumber data)
- `stoks.total_qty/total_unit` = agregat cache per varian+gudang
- `kartu_stoks` = kartu stok / trail audit

**Penyebab akar masalah:** tidak ada *single source of truth* untuk stok, tidak ada
foreign key, tidak ada transaksi DB konsisten, dan banyak *magic number* `gudang_id`
(1 = ecer, 2 = grosir, 55 = sentinel "terjual", 0 = sentinel kartu stok).

---

## 2. Error & Bug Kritis (Harus Diperbaiki Segera)

### E-01 - Typo relasi `stock()` vs `stok()` (Edit penjualan grosir selalu gagal)
- **Lokasi:** `app/Http/Controllers/Grosir/PenjualanGrosirController.php:372-373`
- **Dampak:** Edit nota penjualan grosir melempar `BadMethodCallException` -> seluruh `DB::transaction` rollback -> **edit penjualan tidak bisa dilakukan**.
- **Perbaikan:** Ganti `$varian->stock()` -> `$varian->stok()` (relasi model `stok()`, `ProdukVarian.php:31`).

### E-02 - `Pembelian::supplier()` sintaks salah (fatal saat diakses)
- **Lokasi:** `app/Models/Pembelian.php:21-24`
- **Salah:** `belongsTo(Supplier::class . 'supplier_id')` -> string `"App\Models\Supplier.supplier_id"` (kelas invalid).
- **Benar:** `return $this->belongsTo(Supplier::class, 'supplier_id');`

### E-03 - `PenerimaanController::destroy` menghapus roll yang SALAH
- **Lokasi:** `app/Http/Controllers/Grosir/PenerimaanController.php:412-419`
- **Salah:** mencocokkan `where("qty", $detail->qty)->where("status","tersedia")` bukan by `produk_detail_id`. Dua roll panjang sama -> salah satu dihapus; roll sudah dipindah tidak dihapus namun stok tetap dikurangi -> **stok under-count + orphan**.
- **Perbaikan:** hapus by `produk_detail_id` (sudah benar di `rollbackStokItem()` line 383).

### E-04 - `gudang_id` sentinel acak & tidak konsisten
- **Lokasi:** `PenjualanGrosirController.php:211, 412` (`=> 55`), `:218, 418` (`ke_gudang_id = 0`); konvensi ecer = 1, grosir = 2.
- **Dampak:** `55` bisa bertabrakan dengan ID gudang asli; `0` menyesatkan trail.
- **Perbaikan:** pakai konstanta `Gudang::ECER_ID / GROSIR_ID` (cari by `tipe`), jangan hardcode.

### E-05 - Restore penjualan grosir salah menulis `gudang_id`
- **Lokasi:** `app/Http/Controllers/Grosir/PenjualanGrosirController.php:464`
- **Salah:** `$produkDetail->update(['status' => 'tersedia', 'gudang_id', 2]);` -> `'gudang_id', 2` elemen array terbuang, bukan `'gudang_id' => 2`.
- **Dampak:** roll dikembalikan logis tapi `gudang_id` tetap 55 -> roll "hilang"/tidak tampil.
- **Perbaikan:** `['status' => 'tersedia', 'gudang_id' => 2]`.

### E-06 - `ProdukDetail` punya dua FK ambigu (`varian_id` & `stok_id`), tanpa constraint
- **Lokasi:** `produk_details` migration (`2026_01_16_220717`), `ProdukDetail.php:21-29`, `ProdukVarian.details()` (varian_id) vs `Stok.details()` (stok_id).
- **Dampak:** tidak ada jaminan `ProdukDetail.varian_id == Stok.varian_id` untuk `stok_id` yang di-link -> roll bisa menempel ke stok varian lain -> **inkonsistensi stok diam-diam**.
- **Perbaikan:** tetapkan satu jalur (mis. `varian_id` FK utama, `stok_id` derivatif), tambah constraint/uniqueness `(varian_id, gudang_id)` pada `stoks`.

### E-07 - `ProdukVarian` memasukkan `'id'` ke `$fillable`
- **Lokasi:** `app/Models/ProdukVarian.php:10`
- **Dampak:** mass-assignment primary key (PK spoofing / collision).
- **Perbaikan:** hapus `'id'` dari `$fillable`.

### E-08 - Relasi model menunjuk ke kolom yang tidak ada (runtime error / data kosong)
| Model | Relasi | Masalah | Bukti |
|---|---|---|---|
| `Produk` | `supplier()`, `gudang()` | kolom `supplier_id`/`gudang_id` tidak ada di `produks` | `Produk.php:34-41` |
| `PembelianItems` | `produk()` | `hasOne(Produk::class,'produk_id')` padahal tabel pakai `varian_id` | `PembelianItems.php:24-27` |
| `Kategori` | `warnaProduk()` | kolom `warna_id` tidak ada | `Kategori.php:26-29` |
| `Penjualan` | `user()` | `user_id` tidak ada (ada `marketing_id`) | `Penjualan.php:39-42` |
| `Pelanggan` | `saldo()` | typo `pelanggal_id` (harus `pelanggan_id`) | `Pelanggan.php:22-25` |

- **Perbaikan:** sesuaikan kolom/relasi dengan skema migration; untuk `Penjualan.user` tambah kolom `user_id` (audit) atau arahkan ke relasi benar.

### E-09 - `Master\ProductController` & `ProdukGrosirController` benturan skema
- **Lokasi:** `app/Http/Controllers/Master/ProductController.php` (20-98) vs `app/Http/Controllers/Grosir/ProdukGrosirController.php`.
- **Masalah:** `ProductController` menulis kolom **yang tidak ada** (`nama_produk`, `kode_produk`, `slug`, `kode_seri`, `kode_warna`, `satuan`) dan tidak membuat `ProdukVarian`/`Stok`. `ProdukGrosirController` pakai skema benar (`nama`, varian+stok).
- **Dampak:** tergantung route mana dipanggil, data produk jadi sampah/terpisah.
- **Perbaikan:** jadikan **satu** product manager (`ProdukGrosirController`/`ProdukEcerController`), harmonisasi/hapus `Master\ProductController`.

### E-10 - `colorsByName` query kolom salah -> selalu kosong
- **Lokasi:** `ProdukGrosirController.php:73`, `Master\ProductController.php:23`, `ProdukEcerController.php:85`.
- **Salah:** `Produk::where('nama_produk', ...)` padahal kolom adalah `nama`.
- **Perbaikan:** `where('nama', ...)`.

---

## 3. Isu Integritas Stok & Keuangan (Tinggi)

### I-01 - Transfer gudang (Grosir -> Ecer) tidak verifikasi kepemilikan roll
- **Lokasi:** `app/Http/Controllers/Ecer/StokEcerController.php:52-124`
- Roll di-reparent (`stok_id`, `gudang_id->1`) dari input klien tanpa cek roll benar-benar milik `Stok` grosir. Roll milik `stok_id` lain tetap mengurangi `$stok['stok_id']` yang diminta -> warehouse salah.
- `createTransfer` (`StokEcerController.php:39-45`) memuat semua `Stok` **tanpa filter `where('gudang_id', 2)`** -> UI mengizinkan pilih roll ecer juga (Ecer->Ecer corrupt).
- **Perbaikan:** filter sumber `gudang_id=2`, validasi `selected_rolls.*.stok_id` == `details.*.stok_id`, tambah `lockForUpdate`, tulis `KartuStok` masuk di ecer + keluar di grosir dalam satu transaksi.

### I-02 - `simpanItems` (Ecer) bisa **double-sell / dobel stok**
- **Lokasi:** `app/Http/Controllers/Ecer/TransaksiPenjualanEceranController.php:167-285`
- Tidak cek `status`; submit ulang (masih `UNPAID`) membuat ulang `PenjualanItem`/`PenjualanDetail` dan **mengurangi stok lagi** + duplikat baris.
- `KartuStok.dari_gudang_id = 2` (line 238) salah untuk penjualan ecer yang mengurangi dari gudang `1`.
- **Perbaikan:** cek `if ($penjualan->status !== 'DRAFT') return`; pakai `dari_gudang_id=1`; idempoten via unique constraint / cek existing items.

### I-03 - `hapusPenjualan` (Ecer) tidak merekalkulasi `Stok` header
- **Lokasi:** `TransaksiPenjualanEceranController.php:289-321`
- Mengembalikan `ProdukDetail.qty` + tulis KartuStok masuk, tapi **tidak** update `Stok.total_qty/total_unit` ecer -> drift permanen antara header dan detail.
- Juga **tidak cek status**: nota `LUNAS` bisa dihapus -> stok membali & `Pembayaran` orphan.
- **Perbaikan:** setelah restore detail, `Stok` di-recompute (`total_qty = sum(details.qty)`); tolak hapus jika lunas (atau buat retur resmi).

### I-04 - Penerimaan (edit) menciptakan roll tanpa `stok_id`
- **Lokasi:** `PenerimaanController.php:274-283, 340-345` (item baru saat edit) vs `store()` (130-138) yang selalu buat batch `Stok`.
- **Dampak:** roll baru tidak terikat batch -> inkonsistensi model batch.
- **Perbaikan:** samakan logika pembuatan `Stok`/`stok_id` antara `store` dan `update`.

### I-05 - Edit roll penerimaan tidak update `Stok.total_qty`
- **Lokasi:** `PenerimaanController.php:316-337` (hanya `varian->increment('panjang')`), berbeda dengan branch roll baru (353-359) yang increment `Stok.total_qty`.
- **Dampak:** `Stok.total_qty` melenceng dari `sum(ProdukDetail.qty)`.
- **Perbaikan:** update `Stok.total_qty` di kedua branch, atau - lebih baik - **jangan simpan qty di `Stok`; hitung dari `ProdukDetail`** (lihat saran S-01).

### I-06 - KartuStok hanya mencatat *keluar*, bukan *masuk*
- **Lokasi:** `PenerimaanController` (store/update/destroy) tidak pernah tulis `KartuStok` IN; hanya `updateData()` sekali pakai & tiap penjualan yang tulis OUT. `stok_awal`/`stok_akhir` diisi `qty` roll sendiri, bukan saldo berjalan.
- **Dampak:** kartu stok bukan ledger sungguhan -> laporan stok tidak bisa ditelusuri.
- **Perbaikan:** tulis `KartuStok` IN saat penerimaan & transfer; `stok_akhir` = saldo warehouse berjalan.

### I-07 - Tidak ada perlindungan stok negatif / race condition
- **Lokasi:** `PenjualanGrosirController` store/update (205-212, 408-412), `simpanItems` ecer.
- Tidak ada `>=0` check dan tidak ada `lockForUpdate` -> oversell & korupsi stok concurrency.
- **Perbaikan:** `DB::transaction` + `lockForUpdate` pada baris `Stok`/`ProdukDetail`, validasi `qty >= 0` dan status `tersedia` sebelum tandai `terjual`.

### I-08 - Penjualan tidak verifikasi kepemilikan/status roll
- **Lokasi:** `PenjualanGrosirController.php:205-212, 408-412`
- Request kirim `roll_id`+`product_id` tapi tidak cek roll milik `varian_id` tersebut & `status==='tersedia'` & `gudang_id===2`.
- **Dampak:** API call langsung bisa jual roll varian lain / double-sell.
- **Perbaikan:** validasi kepemilikan & status, lalu kunci.

---

## 4. Isu Skema / Migrasi (Tinggi-Sedang)

### M-01 - Migrasi uang pakai presisi salah
- `retur_penjualans.total_nilai_retur/retur_qty/retur_jumlah` = `decimal(15,0)` -> **0 desimal**, Rupiah terpotong bulat (`2026_01_28_150520`).
- `pembelians.total_pembelian/beban_*` = `decimal()` -> default `(8,2)` (maks 999.999,99), mudah **overflow** (`2026_01_17_000008`).
- `pembelian_items.subtotal/harga_beli` = `decimal(8,2)` vs `penjualan_items` = `decimal(19,2)`.
- **Perbaikan:** seragamkan ke `decimal(19,2)` via migration (`Schema::table(...)->decimal('x',19,2)->change()`).

### M-02 - Hampir semua FK hanya `unsignedBigInteger` tanpa constraint
- **Dampak:** hapus Kategori/Brand/Satuan/Varian/Stok/Detail -> **anak orphan** di seluruh POS.
- **Perbaikan:** ubah ke `foreignId('x')->constrained()->cascadeOnDelete()/restrictOnDelete()`. `kartu_stoks.dari_gudang_id/ke_gudang_id` juga jadikan FK ke `gudangs`.

### M-03 - Tidak ada SoftDeletes
- Entitas finansial/stok dihapus hard -> memperparah risiko orphan (M-02).
- **Perbaikan:** tambah `SoftDeletes` + `deleted_at` pada entitas inti (Penjualan, Pembelian, Produk, dll) agar penghapusan bisa diaudit/dibalas.

### M-04 - `groups` table adalah skema mati / benturan model role
- `0001_01_02_create_groups_table.php` tidak direferensikan mana pun; sementara `users.role` (default `'kasir'`) adalah penyimpan role sesungguhnya. `AuthController` if/else tidak menangani `'kasir'` -> fallback tanpa redirect.
- **Perbaikan:** sambungkan `groups` ke `users` (FK) atau hapus migration; tangani semua role di `AuthController`/middleware.

### M-05 - Tipe kolom `kartu_stoks` lebih kecil dari sumber
- `kartu_stoks.qty/stok_awal/stok_akhir` = `decimal(10,2)` vs sumber `decimal(15,2)`/`decimal(19,2)` -> roll besar overflow di ledger.
- **Perbaikan:** samakan presisi ke `decimal(19,2)`.

### M-06 - `ReturPenjualan` `$fillable` tidak lengkap
- `app/Models/ReturPenjualan.php:9-13` hanya `nomer` & `penjualan_id`; kolom `tanggal, total_nilai_retur, retur_qty, retur_jumlah, alasan, status` tidak mass-assignable -> lupa set manual.
- **Perbaikan:** masukkan ke `$fillable`.

### M-07 - `penjualan_details.is_convert` bertipe `decimal(15,2)` tapi semantik flag/faktor
- **Lokasi:** `2026_01_20_010155`. Menyesatkan; ubah ke `boolean`/`decimal(8,4)` sesuai kebutuhan konversi.

### M-08 - Model kosong tanpa `$fillable`/`$guarded`
- `ReturPenjualanItem`, `ReturPenjualanDetail`, `Perusahaan` -> semua kolom mass-assignable.
- **Perbaikan:** definisikan `$fillable` eksplisit.

---

## 5. Isu Frontend (Vue / Inertia) - Sedang

### F-01 - Variabel non-reactive di template (`let ... = []`)
- **Lokasi:** `Grosir/Penjualan/Show.vue:154` (`let listPembayaran = []`), `Grosir/Penjualan/test.vue:154`, `Ecer/Penjualan/Print.vue:178` (`let flattendPayments = []`).
- **Dampak:** bila di-reassign di kemudian waktu, template tidak update (kehilangan reaktivitas).
- **Perbaikan:** gunakan `ref([])` / `reactive`.

### F-02 - Efek samping di dalam `computed` (grandTotal)
- **Lokasi:** `Ecer/Penjualan/Kasir.vue:373-375` - `grandTotal` memanggil `calculateTotal(item)` yang memutasi `item.total`.
- **Dampak:** computed tidak murni -> bisa trigger re-render tak terduga / sulit di-debug.
- **Perbaikan:** pisahkan perhitungan (`watch`/`method`), jangan mutasi di `computed`.

### F-03 - Konversi satuan (Yard<->Meter) hanya satu arah presisi benar
- **Lokasi:** `Ecer/Penjualan/Kasir.vue:350-365` (toggle), `getMaxQty` (462-468). Toggle Yard->Meter pakai 0.9144 (benar), tapi alur balik mengembalikan ke `qty_stok_asli` (aman). Risiko: bila user ubah qty manual lalu toggle, nilai tidak konsisten.
- **Perbaikan:** simpan `konversi` eksplisit & hitung ulang dari qty asli selalu; hindari mutasi `qty` asli.

### F-04 - Penjualan ecer tidak memvalidasi batas qty per roll di UI
- Input `d.qty` (`Kasir.vue:181`) bebas; tidak ada `max="qty_stok_asli"` atau peringatan bila melebihi sisa roll -> potensi jual melebihi stok (backend I-07 juga tidak cek).
- **Perbaikan:** tambah `max` + peringatan; cek juga di backend.

### F-05 - File sampah / placeholder di dalam `Pages`
- `Grosir/Penjualan/test.vue`, `Grosir/Penjualan/edit.md`, `Grosir/Penerimaan/pembelian.txt`, `Ecer/Penjualan/pembayaran.txt`, `Ecer/Penjualan/prompt.md`, `Grosir/refaktoring-kedatabase-baru.txt`.
- **Dampak:** kebingungan, bisa ikut ter-build/ter-commit. **Perbaikan:** pindahkan ke `docs/` atau hapus.

### F-06 - Magic number `gudang_id` di frontend
- `StokEcerController`/`ProdukEcerController`/`Transaksi...` menyebar angka 1/2 tanpa konstanta (sudah dijelaskan I-01/E-04). Frontend juga mengasumsikan struktur `varian.details`, `roll.satuan.nama` yang bisa null bila `satuan_id` kosong -> runtime error.
- **Perbaikan:** ekspos konstanta gudang dari backend (prop/inertia shared), dan guard `roll.satuan?.nama`.

### F-07 - Tidak ada penanganan error request yang konsisten
- Beberapa `router.post` hanya `alert()` generik (mis. `Kasir.vue:451`). **Perbaikan:** tampilkan `errors` dari validasi Laravel (Inertia `onError`) secara field-by-field.

---

## 6. Saran Perbaikan & Penyempurnaan (Prioritas)

### S-01 (STRATEGIS) - Tetapkan *single source of truth* untuk stok
- Jadikan **`produk_details` sebagai satu-satunya penyimpan qty fisik**. `stoks.total_qty/total_unit` dihilangkan atau dijadikan *view/aksesor* yang dihitung dari `sum(details.qty where gudang_id = X)`.
- `kartu_stoks` dipakai murni sebagai ledger (IN/OUT) dengan `stok_akhir` = saldo warehouse berjalan.
- Manfaat: eliminasi drift I-03/I-05/I-06 sekaligus.

### S-02 (STRATEGIS) - Service layer untuk mutasi stok
- Buat `app/Services/StokService.php` dengan method `terima(Penerimaan)`, `jual(Penjualan)`, `transfer($dari, $ke)`, `retur(...)`, semuanya dalam `DB::transaction` + `lockForUpdate` + penulisan `KartuStok` otomatis.
- Semua controller panggil service ini -> logika stok terpusat, konsisten, teruji.

### S-03 - Konstanta & seeder Gudang
- Buat `Gudang` dengan `tipe` ('grosir'/'ecer') + seeder, dan helper `gudangEcer()` / `gudangGrosir()`. Ganti semua angka 1/2/55/0.

### S-04 - Validasi & otorisasi
- Tambah Form Request untuk tiap controller (cek `qty >= 0`, roll milik varian & `tersedia`, status nota sebelum hapus/jual). Gunakan Policy untuk akses per role.

### S-05 - Retur Penjualan & Retur Pembelian
- Dokumen menyebutkan Retur tapi implementasi Ecer retur tidak ada; Grosir retur ada model tapi alur belum lengkap. Bangun alur retur via `StokService` (tambah stok + KartuStok IN + jurnal balik).

### S-06 - Penyempurnaan UX / Laporan
- Grosir tampil detail dikelompokkan **Varian -> Nama Produk**; Ecer tampil per **detail item** (sesuai dok). Pastikan grouping di backend sudah benar (saat ini `Master\ProductController` salah skema).
- Laporan (stok, penjualan, saldo, omset) pakai sumber `StokService`/aksesor agar angka konsisten dengan kartu stok.

### S-07 - Testing & CI
- Tambah feature test untuk alur kritis: penerimaan, transfer gudang, penjualan, hapus nota, retur - assert stok & kartu stok konsisten. Jalankan `php artisan pint` (lint) & `phpstan` opsional.

### S-08 - Pembersihan
- Hapus `Master\ProductController` (gabung ke `ProdukGrosir/ProdukEcer`), hapus file sampah (F-05), hapus `groups` migration (M-04), perbaiki typo `flattend` -> `flattened`.

---

## 7. Rencana Implementasi Bertahap

**Fase 0 - Persiapan (1 hari)**
1. Backup DB & buat branch `fix/integrasi-stok`.
2. Jalankan `php artisan pint` untuk rapikan kode existing.

**Fase 1 - Lapisan Data (Kritis) - E-01..E-10, M-01..M-08**
- Perbaiki model/typo relasi, `$fillable`, precision migrasi, FK constraint, soft deletes.
- Ganti magic number gudang dengan konstanta/seeder.
- Verifikasi: `php artisan migrate:fresh --seed` berhasil; tidak ada query kolom hilang.

**Fase 2 - StokService & Transaksi (Tinggi) - I-01..I-08, S-01..S-03**
- Bangun `StokService` (terima/jual/transfer/retur) dgn transaction + lock + KartuStok.
- Refactor `PenerimaanController`, `PenjualanGrosirController`, `StokEcerController`, `TransaksiPenjualanEceranController` memanggil service.
- Validasi kepemilikan/status/qty & cek double-sell.

**Fase 3 - Frontend (Sedang) - F-01..F-07**
- Perbaiki reaktivitas, computed murni, validasi qty UI, error handling, hapus file sampah.
- Pastikan grouping produk sesuai dokumen.

**Fase 4 - Retur & Laporan - S-05..S-06**
- Alur retur pembelian & penjualan lengkap.
- Laporan konsisten dengan kartu stok.

**Fase 5 - Testing & Rilis - S-07..S-08**
- Tulis feature test alur kritis; jalankan; dokumentasikan runbook.

---

## 8. Ringkasan Jumlah Temuan
- **Kritis (Error/Bug):** 10 (E-01 s.d E-10)
- **Tinggi (Integritas Stok/Keuangan):** 8 (I-01 s.d I-08)
- **Skema/Migrasi:** 8 (M-01 s.d M-08)
- **Frontend:** 7 (F-01 s.d F-07)
- **Saran strategis:** 8 (S-01 s.d S-08)

> Rekomendasi: selesaikan Fase 1 & 2 terlebih dahulu karena menyangkut uang & stok nyata
> (pembukuan bisa selisih tanpa terasa). Fase 3-5 bisa paralel setelah inti aman.
