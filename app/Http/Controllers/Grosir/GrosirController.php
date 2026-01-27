<?php

namespace App\Http\Controllers\Grosir;

use App\Http\Controllers\Controller;
use App\Models\Gudang;
use App\Models\Kategori;
use App\Models\Pelanggan;
use App\Models\Produk;
use App\Models\ProdukVarian;
use App\Models\Satuan;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GrosirController extends Controller
{
    public function index()
    {
        return inertia('Grosir');
    }

    // public function master(Request $request)
    // {
    //     $perPage = $request->per_page ?? 10;
    //     $gudangId = 1;

    //     $products = ProdukVarian::with([
    //         'produk.kategori', // Level 1 & Kategori
    //         'produk.satuan',   // Level 1 & Satuan Dasar
    //         // 'stokFisiks.detailIsiPaket',
    //         'stokFisiks' => function ($q) use ($gudangId) {
    //             // Level 3: Hanya ambil stok di gudang yang dipilih
    //             $q->where('gudang_id', $gudangId)
    //                 ->with('detailPakets'); // Level 4: Ambil daftar roll-nya
    //         }
    //     ])
    //         ->withSum(['stokFisiks as total_qty' => function ($q) use ($gudangId) {
    //             $q->where('gudang_id', $gudangId);
    //         }], 'total_qty') // parameter kedua adalah nama kolom yang di-sum
    //         ->withSum(['stokFisiks as total_panjang' => function ($q) use ($gudangId) {
    //             $q->where('gudang_id', $gudangId);
    //         }], 'total_panjang')
    //         ->when($request->search, function ($q) use ($request) {
    //             $q->where('kode_warna', 'like', "%{$request->search}%") // Cari di Level 2
    //                 ->orWhereHas('produk', function ($pq) use ($request) {
    //                     $pq->where('nama_produk', 'like', "%{$request->search}%"); // Cari di Level 1
    //                 });
    //         })
    //         ->paginate($perPage)
    //         ->withQueryString();

    //     // dd($products->first()->stokFisiks);
    //     $allProduk = Produk::with([
    //         'kategori',
    //         'varian' => function ($q) use ($gudangId) {
    //             $q->with(['stokFisiks' => function ($sq) use ($gudangId) {
    //                 $sq->where('gudang_id', $gudangId)
    //                     ->with('detailPakets');
    //             }]);
    //         }
    //     ])->get();

    //     return Inertia::render('Grosir/Master', [
    //         'products' => $products,
    //         'categories' => Kategori::orderBy('id', 'asc')->get(),
    //         'nama_produks' => Kategori::where('status', 'nama')->get(),
    //         'gudangs' => Gudang::orderBy('nama_gudang')->get(),
    //         'semua_produk' => $allProduk,
    //         'suppliers' => Supplier::orderBy('id')->get(),
    //         'pelanggans' => Pelanggan::orderBy('id')->get(),
    //         'satuan' => Satuan::orderBy('id')->get(),
    //     ]);
    // }
}
