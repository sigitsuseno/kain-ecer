<?php

namespace App\Http\Controllers\Grosir;

use App\Http\Controllers\Controller;
use App\Models\Gudang;
use App\Models\Kategori;
use App\Models\Pelanggan;
use App\Models\Produk;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MasterGrosirController extends Controller
{
    public function index()
    {
        return Inertia::render('Grosir/Master');
    }
}
