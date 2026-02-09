<?php

namespace App\Http\Controllers\Ecer;

use App\Http\Controllers\Controller;
use App\Models\BankPembayaran;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BankController extends Controller
{
    public function index()
    {
        return Inertia::render('Ecer/Setting/Index', [
            'banks' => BankPembayaran::all()
        ]);
    }

    public function store(Request $request)
    {
        // dd($request);
        $request->validate(['nama_bank' => 'required|unique:bank_pembayarans,nama_bank']);
        BankPembayaran::create($request->only('nama_bank'));
        return redirect()->back();
    }

    public function destroy($id)
    {
        BankPembayaran::findOrFail($id)->delete();
        return redirect()->back();
    }
}
