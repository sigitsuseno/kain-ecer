<?php

namespace App\Http\Controllers\Grosir;

use App\Http\Controllers\Controller;
use App\Models\Pelanggan;
use App\Models\Penjualan;
use App\Models\PenjualanItem;
use App\Models\Produk;
use App\Models\ProdukDetail;
use App\Models\ProdukVarian;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PenjualanGrosirController extends Controller
{
    public function index(Request $request)
    {
        $penjualan = Penjualan::with(['items.details.satuan', 'items.varian.produk', 'pelanggan', 'pembayaran'])
            ->where('bagian', 'grosir')
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    // Cari berdasarkan nomor nota penjualan
                    $query->where('nomer', 'like', "%{$request->search}%")
                        // ATAU cari berdasarkan nama di tabel pelanggan
                        ->orWhereHas('pelanggan', function ($subQuery) use ($request) {
                            $subQuery->where('nama', 'like', "%{$request->search}%");
                        });
                });
            })
            ->latest()
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return inertia('Grosir/Penjualan/Transaksi', [
            'penjualan' => $penjualan,
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    public function create()
    {
        $no_nota = '';
        $createNo = Penjualan::orderBy('id', 'desc')->latest()->first();

        if ($createNo) {
            $nomernya = $createNo->id + 1;
            $no_nota = 'INV' . date('Ymd') . str_pad($nomernya, 4, '0', STR_PAD_LEFT);
        } else {
            $nomernya = 1;
            $no_nota = 'INV' . date('Ymd') . str_pad($nomernya, 4, '0', STR_PAD_LEFT);
        }


        $varian = ProdukVarian::with('produk', 'details.satuan')->where('gudang_id', 2)->get();

        return inertia('Grosir/Penjualan/Create', [
            'no_nota' => $no_nota,
            'produk_varian' => $varian,
            'penjualan' => Penjualan::orderBy('id')->get(),
            'customers_data' => Pelanggan::all()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_no' => ['required', 'string', 'max:50'],
            'transaction_date' => ['required', 'date'],
            'customer_name' => ['nullable', 'string', 'max:100'],
            'marketing_name' => ['nullable', 'string', 'max:100'],

            'items' => ['required', 'array', 'min:1'],

            // ===== ITEM LEVEL =====
            'items.*.product_id' => ['required', 'integer', 'exists:produk_varian,id'],
            'items.*.product_name' => ['required', 'string'],
            'items.*.color' => ['required', 'string'],
            'items.*.total_qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.subtotal' => ['required', 'numeric', 'min:0'],

            // ===== ROLL LEVEL =====
            'items.*.rolls' => ['required', 'array', 'min:1'],
            'items.*.rolls.*.roll_id' => ['required', 'integer', 'exists:produk_detail,id'],
            'items.*.rolls.*.varian_id' => ['required', 'integer'],
            'items.*.rolls.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.rolls.*.satuan_id' => ['required', 'integer', 'exists:satuan,id'],
        ]);

        /*
    |--------------------------------------------------------------------------
    | VALIDASI LOGIS (WAJIB, TIDAK BISA DENGAN RULE SAJA)
    |--------------------------------------------------------------------------
    */

        foreach ($validated['items'] as $item) {

            // subtotal = total_qty * unit_price (anti manipulasi frontend)
            $expectedSubtotal = $item['total_qty'] * $item['unit_price'];

            if ((float) $item['subtotal'] !== (float) $expectedSubtotal) {
                abort(422, 'Subtotal tidak valid pada produk ' . $item['product_name']);
            }

            // total_qty = sum roll.qty
            $rollQty = collect($item['rolls'])->sum('qty');

            if ((float) $rollQty !== (float) $item['total_qty']) {
                abort(422, 'Total roll tidak sesuai pada produk ' . $item['product_name']);
            }
        }

        /*
    |--------------------------------------------------------------------------
    | SIMPAN DATA (nanti)
    |--------------------------------------------------------------------------
    */

        // DB::transaction(function () use ($validated) {
        //   simpan penjualan
        //   simpan item
        //   simpan roll
        //   update stok
        // });

        return redirect()
            ->route('grosir.penjualan.index')
            ->with('success', 'Transaksi berhasil disimpan');
    }

    public function updateData()
    {
        DB::transaction(function () {
            $oldSale = DB::connection('sqlite_lama')->table('sales')->get();

            foreach ($oldSale as $sale) {
                $oldCustomer = DB::connection('sqlite_lama')->table('customers')
                    ->where('id', $sale->customer_id)->first();

                if (!$oldCustomer) continue;

                // 1. Pelanggan (Cari berdasarkan nama, update sisanya)
                $pelanggan = Pelanggan::updateOrCreate(
                    ['nama' => $oldCustomer->name],
                    [
                        'phone' => $oldCustomer->phone,
                        'email' => $oldCustomer->email,
                        'alamat' => $oldCustomer->address,
                        'slug' => Str::slug($oldCustomer->name),
                        'status' => $oldCustomer->status ?? 'aktif',
                    ]
                );

                // 2. Marketing (Simpan di tabel Supplier/Marketing sesuai strukturmu)
                $marketing = Supplier::updateOrCreate(
                    ['nama' => $oldCustomer->pnj],
                    [
                        'slug' => Str::slug($oldCustomer->pnj),
                        'status' => 'marketing',
                    ]
                );

                // 3. Header Penjualan (Gunakan updateOrCreate agar tidak duplikat saat dijalankan ulang)
                $newSale = Penjualan::updateOrCreate(
                    ['nomer' => $sale->invoice_no],
                    [
                        'tanggal' => $sale->transaction_date,
                        'bagian' => 'grosir',
                        'pelanggan_id' => $pelanggan->id,
                        'marketing_id' => $marketing->id,
                        'total_penjualan' => $sale->total_amount,
                        'diskon' => $sale->discount_amount,
                        'ongkir' => $sale->ongkir ?? 0,
                        'status' => strtoupper($sale->status),
                    ]
                );

                $oldSaleItems = DB::connection('sqlite_lama')->table('sale_items')
                    ->where('sale_id', $sale->id)->get();

                foreach ($oldSaleItems as $item) {
                    // Cari varian di DB baru berdasarkan ID produk lama
                    $varian = ProdukVarian::find($item->product_id);
                    if (!$varian) continue;

                    $newSaleItem = $newSale->items()->updateOrCreate(
                        ['varian_id' => $varian->id, 'penjualan_id' => $newSale->id],
                        [
                            'satuan_id' => $varian->produk->satuan_id ?? 1,
                            'total_qty' => $item->length_out,
                            'total_jumlah' => $item->roll_out,
                            'harga_satuan' => $item->unit_price,
                            'subtotal' => $item->subtotal,
                        ]
                    );

                    // Ambil detail roll dari data lama
                    $oldItemDetail = DB::connection('sqlite_lama')->table('jumlah_rolls')
                        ->where('sale_item_id', $item->id)->get();

                    foreach ($oldItemDetail as $det) {

                        $produkDetail = ProdukDetail::where('old_id', $det->sale_item_id)->first();

                        if ($produkDetail) {
                            // Buat detail penjualan
                            $newSaleItem->details()->create([
                                'produk_detail_id' => $produkDetail->id,
                                'satuan_id' => $newSaleItem->satuan_id,
                                'qty' => $det->panjang,
                                'harga' => $newSaleItem->harga_satuan,
                                'jumlah_harga' => $det->panjang * $newSaleItem->harga_satuan,
                            ]);

                            // UPDATE STOK: Ubah status menjadi terjual
                            $produkDetail->update(['status' => 'terjual']);

                            Log::info('update :' . $produkDetail->id . 'terjual');
                        }
                    }

                    // KURANGI STOK AKUMULASI DI VARIAN
                    $varian->decrement('jumlah', $item->roll_out);
                    $varian->decrement('panjang', $item->length_out);
                }
            }
        });

        return back()->with('message', 'Refactoring Penjualan Selesai!');
    }
}
