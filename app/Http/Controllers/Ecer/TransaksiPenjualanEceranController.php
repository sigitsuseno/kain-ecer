<?php

namespace App\Http\Controllers\Ecer;

use App\Http\Controllers\Controller;
use App\Models\BankPembayaran;
use App\Models\DetailPaket;
use App\Models\DetailPenjualan;
use App\Models\ItemPenjualan;
use App\Models\Pelanggan;
use App\Models\Penjualan;
use App\Models\PenjualanDetail;
use App\Models\PenjualanItem;
use App\Models\Perusahaan;
use App\Models\ProdukDetail;
use App\Models\ProdukVarian;
use App\Models\Satuan;
use App\Models\Stok;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransaksiPenjualanEceranController extends Controller
{
    public function list(Request $request)
    {
        $query = Penjualan::with([
            'pelanggan',
            'marketing',
            'items.varian.produk',
            'items.details'
        ])
            ->where('status', 'UNPAID')
            ->where('bagian', 'ecer');


        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('nomer', 'like', "%{$request->search}%")
                    ->orWhereHas('pelanggan', function ($sub) use ($request) {
                        $sub->where('nama', 'like', "%{$request->search}%");
                    });
            });
        }
        // dd($query);
        return inertia('Ecer/Penjualan/List', [
            'penjualan' => $query->latest()->get(),
        ]);
    }

    public function index(Request $request)
    {
        $search = $request->input('cari_produk');
        // dd($request);
        $penjualan = Penjualan::with([
            'items.varian.produk',
            'items.varian.details',
            'pelanggan',
            'marketing'
        ])
            ->where('bagian', 'ecer')
            ->where('status', 'DRAFT')
            ->first();

        $produk = ProdukVarian::with([
            'produk',
            'stok.details',
            'details' => fn($q) => $q->where('qty', '>', 0)->where('gudang_id', 1)->with('satuan'),
        ])
            ->whereHas('details', fn($q) => $q->where('qty', '>', 0)->where('gudang_id', 1)->where('status', 'tersedia'))
            ->where(function ($query) use ($search) {
                $query->where('nama', 'like', "%$search%")
                    ->orWhereHas('produk', function ($q) use ($search) {
                        $q->where('nama', 'like', "%$search%");
                    });
            })
            ->orderBy('nama', 'asc')
            ->simplePaginate(20)
            ->withQueryString();

        return inertia('Ecer/Penjualan/Kasir', [
            'draft' => $penjualan,
            'pelanggan' => Pelanggan::orderBy('nama')->get(),
            'marketing' => Supplier::where('status', 'marketing')->get(),
            'produk' => $produk,
            'company' => Perusahaan::find(1) ?? null,
            'banks' => BankPembayaran::all(),
        ]);
    }

    public function storeDraft(Request $request)
    {
        $data = $request->validate([
            'tanggal_transaksi' => 'required|date',
            'pelanggan' => 'required|string',
            'marketing' => 'nullable|string',
        ]);
        // dd($request);

        return DB::transaction(function () use ($data) {

            $slugPelanggan = Str::slug($data['pelanggan']);

            $pelanggan = Pelanggan::where('slug', $slugPelanggan)->first();

            if (!$pelanggan) {
                $pelanggan = Pelanggan::firstOrCreate(
                    ['slug' => $slugPelanggan],
                    ['nama' => $data['pelanggan']]
                );
            }

            $marketing = null;

            if (!empty($data['marketing'])) {
                $slugMarketing = Str::slug($data['marketing']);

                $marketing = Supplier::firstOrCreate(
                    ['slug' => $slugMarketing],
                    [
                        'nama' => $data['marketing'],
                        'status' => 'marketing'
                    ]
                );
            }

            $penjualan = Penjualan::updateOrCreate(
                [
                    'pelanggan_id' => $pelanggan->id,
                    'status' => 'DRAFT',
                    'bagian' => 'ecer',
                ],
                [
                    'nomer' => $this->generateNota(),
                    'tanggal' => $data['tanggal_transaksi'],
                    'marketing_id' => $marketing?->id,
                    'total_penjualan' => 0,
                    'diskon' => 0,
                    'pajak' => 0,
                    'ongkir' => 0,
                    'kembalian' => 0,
                ]
            );

            return redirect()->back()->with('draft_id', $penjualan->id);
        });
    }

    protected function generateNota(): string
    {
        $prefix = 'INV-' . now()->format('Ym');

        $last = Penjualan::where('nomer', 'like', "$prefix%")
            ->where('bagian', 'ecer')
            ->orderByDesc('id')
            ->value('nomer');

        $number = $last
            ? (int) substr($last, -4) + 1
            : 1;

        return $prefix . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function simpanItems(Request $request)
    {

        // dd($request);
        $request->validate([
            'penjualan_id' => 'required|exists:penjualans,id',

            // Validasi Array Utama (Items/Varian)
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|exists:produk_varians,id',
            'items.*.harga' => 'required|numeric|min:0',
            'items.*.total' => 'required|numeric',

            // Validasi Array di dalam Array (Details/Rolls)
            'items.*.details' => 'required|array|min:1',
            'items.*.details.*.roll_id' => 'required|exists:produk_details,id',
            'items.*.details.*.qty' => 'required|numeric|min:0.01',
            'items.*.details.*.konversi' => 'required|numeric',
            'items.*.details.*.satuan_jual' => 'required|string',
        ]);
        // dd($request);
        return DB::transaction(function () use ($request) {
            $penjualan = Penjualan::findOrFail($request->penjualan_id);
            $grandTotal = 0;

            foreach ($request->items as $item) {
                $totalQtyJual = 0;
                $subtotalUangItem = 0;
                $totalYardKeluar = 0;

                $item_penjualan = $penjualan->items()->create([
                    'varian_id' => $item['variant_id'],
                    'harga_satuan' => $item['harga'], // Harga dasar per Yard
                ]);
                $listSatuan = Satuan::pluck('id', 'nama_satuan');

                foreach ($item['details'] as $detail) {
                    // LOCK FOR UPDATE: Mencegah data diubah transaksi lain saat proses ini berjalan
                    $p_detail = ProdukDetail::where('id', $detail['roll_id'])->lockForUpdate()->firstOrFail();

                    $qtyAwal = $p_detail->qty; // Simpan untuk kartu stok
                    $realQtyOut = round($detail['qty'] / $detail['konversi'], 2);
                    $sisaStok = round($qtyAwal - $realQtyOut, 2);

                    // Validasi stok cukup
                    if ($realQtyOut > $qtyAwal) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'items' => "Stok roll {$p_detail->nomer_roll} tidak cukup!"
                        ]);
                    }

                    // Simpan Detail Penjualan
                    $detailPenjualan = PenjualanDetail::create([
                        'penjualan_item_id' => $item_penjualan->id,
                        'produk_detail_id' => $p_detail->id,
                        'satuan_id' => $listSatuan[$detail['satuan_jual']] ?? null,
                        'qty' => $detail['qty'],
                        'harga' => $item['harga'],
                        'jumlah_harga' => $item['harga'] * $detail['qty'],
                        'status' => 'transisi',
                        'is_convert' => $detail['konversi'],
                    ]);


                    $p_detail->update([
                        'qty' => max(0, $sisaStok),
                        'status' => ($sisaStok <= 0.01) ? 'habis' : 'tersedia'
                    ]);

                    $p_detail->kartuStoks()->create([
                        'varian_id'      => $p_detail->varian_id,
                        'dari_gudang_id' => 1,
                        'ke_gudang_id'   => 0,
                        'tipe'           => 'keluar',
                        'qty'            => $realQtyOut,
                        'stok_awal'      => $qtyAwal,
                        'stok_akhir'     => max(0, $sisaStok), // Sisa setelah dikurangi
                        'keterangan'     => 'Penjualan No. ' . $penjualan->nomer,
                        'user_id'        => Auth::id(),
                    ]);

                    $subtotalUangItem += $detailPenjualan->jumlah_harga;
                    $totalQtyJual += $detail['qty']; // Satuan Jual (Meter/Pcs)
                    $totalYardKeluar += $realQtyOut; // Satuan Stok (Yard)
                }

                // UPDATE STOK ECER (Header)
                $stokEcer = Stok::where('varian_id', $item['variant_id'])
                    ->where('gudang_id', 1)
                    ->first();

                if ($stokEcer) {
                    // Cara paling aman: Hitung ulang dari database agar tidak pernah selisih
                    $stokEcer->update([
                        'total_unit' => $stokEcer->details()->where('qty', '>', 0.01)->count(),
                        'total_qty'  => $stokEcer->details()->sum('qty'),
                    ]);
                }
                // dd($totalYardKeluar);
                // Update Header Item per Varian
                $item_penjualan->update([
                    'total_qty' => $totalYardKeluar,
                    'total_jumlah' => $totalQtyJual,
                    'subtotal' => $subtotalUangItem,
                    'satuan_id' => $detailPenjualan->satuan_id
                ]);

                $grandTotal += $subtotalUangItem;
            }

            // Update Grand Total Penjualan
            $penjualan->update([
                'total_penjualan' => $grandTotal,
                'status' => 'UNPAID',
            ]);

            return redirect()->route('ecer.penjualan.pembayaran.prepare', $request->penjualan_id);
        });
    }



    public function hapusPenjualan(Request $request, $id)
    {
        return DB::transaction(function () use ($id) {
            $penjualan = Penjualan::with('items.details')->findOrFail($id);

            foreach ($penjualan->items as $item) {
                foreach ($item->details as $detail) {
                    $p_detail = ProdukDetail::find($detail->produk_detail_id);
                    if ($p_detail) {
                        // Balikin stok (Konversi balik)
                        $qtyKembali = round($detail->qty / $detail->is_convert, 2);
                        $p_detail->increment('qty', $qtyKembali);
                        $p_detail->update(['status' => 'tersedia']);

                        // Kartu Stok (Audit trail)
                        $p_detail->kartuStoks()->create([
                            'varian_id' => $p_detail->varian_id,
                            'tipe' => 'masuk',
                            'qty' => $qtyKembali,
                            'stok_awal' => $p_detail->qty - $qtyKembali,
                            'stok_akhir' => $p_detail->qty,
                            'keterangan' => 'Pembatalan: ' . $penjualan->nomer,
                            'user_id' => Auth::id(),
                        ]);
                    }
                }
            }
            $penjualan->items()->delete();
            $penjualan->delete();

            return redirect()->back();
        });
    }
}