<?php

namespace App\Http\Controllers\Grosir;

use App\Http\Controllers\Controller;
use App\Models\Pembelian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PenerimaanController extends Controller
{
    public function index()
    {

        return inertia('Grosir/Penerimaan/index');
    }
    public function create()
    {
        return inertia('Grosir/Penerimaan/Create');
    }

    public function updateData()
    {
        $invoice = DB::connection('sqlite_lama')->table('purchase_invoices')->get();


        foreach ($invoice as $inv) {

            $pembelianId = DB::table('pembelians')->insertGetId([
                'supplier_id'       => $inv->supplier_id,
                'nomer_pembelian'   => $inv->supplier_invoice_no,
                'tanggal_transaksi' => $inv->transaction_date,
                'status'            => $inv->payment_status,
                'total_roll'        => $inv->total_purchase_roll ?? 0,
                'total_panjang'     => $inv->total_purchase_length ?? 0,
                'created_at'        => $inv->created_at ?? now(),
                'updated_at'        => $inv->updated_at ?? now(),
            ]);

            // 2. Ambil Item Invoice dari SQLite
            $oldItems = DB::connection('sqlite_lama')->table('purchase_invoice_items')
                ->where('purchase_invoice_id', $inv->id)->get();

            foreach ($oldItems as $item) {
                // 3. Ambil Rincian Roll untuk Item ini
                $oldRolls = DB::connection('sqlite_lama')->table('jumlah_rolls')
                    ->where('purchase_invoice_item_id', $item->id)->get();

                foreach ($oldRolls as $roll) {
                    // --- DISINI KUNCI PERUBAHAN LEVELNYA ---

                    $oldProduct = DB::connection('sqlite_lama')->table('products')
                        ->find($roll->product_id);

                    if (!$oldProduct) continue;

                    // --- LOGIKA KATEGORI & WARNA ---
                    $namaClean = trim(ucwords(strtolower($oldProduct->name)));
                    $warnaClean = trim(strtoupper($oldProduct->kode_warna));

                    // 1. Cari/Buat Kategori (Nama Kain)
                    $kategori = \App\Models\Kategori::firstOrCreate(
                        ['nama_kategori' => $namaClean, 'status' => 'nama'],
                        ['slug' => \Illuminate\Support\Str::slug($namaClean)]
                    );

                    // 2. Cari/Buat Warna (Kode Warna)
                    $warna = \App\Models\Kategori::firstOrCreate(
                        ['nama_kategori' => $warnaClean, 'status' => 'warna'],
                        ['slug' => \Illuminate\Support\Str::slug($warnaClean)]
                    );

                    // A. Buat Produk baru (sebagai Identitas Roll fisik)
                    $produkId = DB::table('produks')->insertGetId([
                        'kode_produk'     => 'GR-' . date('ym') . '-' . strtoupper(Str::random(6)),
                        'nama_produk'     => $oldProduct->name,
                        'kode_warna'     => $oldProduct->kode_warna,
                        'slug'     => $oldProduct->slug,
                        'lebar_kain'     => $oldProduct->lebar_kain,
                        'tipe'     => $oldProduct->tipe,
                        'satuan'     => $oldProduct->unit_satuan,
                        'panjang_perroll'     => $roll->panjang,
                        'gudang_id'       => 1,
                        'warna_id'       => $warna->id,
                        'kategori_id'       => $kategori->id,
                        'status'          => $roll->status,
                        'created_at'      => $oldProduct->created_at,
                        'updated_at'      => $oldProduct->created_at,
                    ]);

                    // B. Buat Pembelian Item (Satu baris per satu produk/roll)
                    // Jadi jika di invoice lama ada 10 roll, di tabel ini akan muncul 10 baris
                    DB::table('pembelian_items')->insert([
                        'pembelian_id'    => $pembelianId,
                        'produk_id'       => $produkId,
                        'panjang_perroll' => $roll->panjang,
                        'jumlah_roll'     => 1,
                        'created_at'      => $oldProduct->created_at,
                        'updated_at'      => $oldProduct->created_at,
                    ]);
                }
            }
        };

        return inertia('Grosir/Penerimaan/index');
    }
}
