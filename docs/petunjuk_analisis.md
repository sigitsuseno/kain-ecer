# Analisis aplikasi Kasir Toko Kain Jual Grosir dan Eceran

## Overview

Ini adalah aplikasir untuk toko kain. di aplikasi ini punya 2 mode penjualan, yaitu Penjualan Grosir (Per Roll / Per Batch/kilo ) dan penjualan eceran (Per Meter / Yard / Kilo) di tangani oleh 2 Kasir yang berbeda, Multi Dashboard dan Multi User.

## Fasilitas

### Produk

- 3 layer product Header -> actual -> detail
- Header adalah Nama Produk (contoh : Katur Brokat)
- Actual adalah Varian Produk (contoh : Merah Maroon)
- Detail adalah bentuk Fisik (kalau di toko kain ini berarti Roll dengan jumlah yard/meter yang unik)
- di UI daftar produk tampil dikelompokkan berdasarkan Nama -> Varian -> detail
- Produk grosir dan ecer di bedakan berdasarkan Gudang_id

**TAMPILAN PRODUK DI UI :**

- Di Grosir ditampilkan detail tiap item yang dikelompokkan berdasarkan Varian kemudian berdasarkan Nama Produk.
- Di Ecer ditampilkan berdasarkan detail tiap item.

### Pembelian

- 3 Layer Pembelian Header -> actual -> detail
- Header = Pembelian/Purchase
- Actual = pembelian_items
- Detail = pembelian_item_detail

#### Flow Pembelian Grosir

- Barang datang -> Pilih atau buat produk -> pilih atau buat varian -> imput detail
- bisa edit dan update

#### Flow Pembelian Ecer

- Pilih Produk dari Grosir -> transfer gudang (automatis penyesuaian stok grosir dan gudang)

### Retur Pembelian

### Penjualan

### Retur Penjualan

### Stock

### Laporan
