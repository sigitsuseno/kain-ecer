<?php

namespace App\Http\Controllers\Grosir;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenerimaanController extends Controller
{
    public function index()
    {
        $data = DB::connection('sqlite_lama')
            ->table('jumlah_rolls')
            ->join('purchase_invoice_items', 'jumlah_rolls.purchase_invoice_item_id', '=', 'purchase_invoice_items.id')
            ->join('purchase_invoices', 'purchase_invoice_items.purchase_invoice_id', '=', 'purchase_invoices.id')
            ->join('products', 'jumlah_rolls.product_id', '=', 'products.id')
            ->get();
        $data = DB::connection('sqlite_lama')
            ->table('jumlah_rolls')
            // ->join('products', 'jumlah_rolls.product_id', '=', 'products.id')
            // ->join('purchase_invoice_items', 'jumlah_rolls.purchase_invoice_item_id', '=', 'purchase_invoice_items.id')
            // ->join('purchase_invoices', 'purchase_invoice_items.purchase_invoice_id', '=', 'purchase_invoices.id')
            ->get();

        dd($data);
        return inertia('Grosir/Penerimaan/index');
    }
    public function create()
    {
        return inertia('Grosir/Penerimaan/Create');
    }

    public function updateData()
    {
        $invoices = DB::connection('sqlite_lama')->table('purchase_invoices')->get();
        $items = [];
        foreach ($invoices as $inv) {
            // Ambil semua item yang milik invoice ini
            $items = DB::connection('sqlite_lama')
                ->table('purchase_invoice_items')
                ->where('purchase_invoice_id', $inv->id)
                ->get();

            // Sekarang kamu punya data lengkap:
            // $inv -> Data Header Invoice
            // $items -> Array isi produk-produk di dalam invoice tersebut

            // Contoh simpan ke MySQL:
            // $newInvoice = PurchaseInvoice::create([...]);
            // foreach($items as $it) { $newInvoice->items()->create([...]); }
        }
        dd($items);
        return inertia('Grosir/Penerimaan/index');
    }
}
