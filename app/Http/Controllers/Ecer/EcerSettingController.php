<?php

namespace App\Http\Controllers\Ecer;

use App\Http\Controllers\Controller;
use App\Models\BankPembayaran;
use Illuminate\Http\Request;

class EcerSettingController extends Controller
{
    public function index()
    {
        return inertia('Ecer/Setting/Settings');
    }

    public function bank()
    {
        return inertia('Ecer/Setting/index');
    }


    public function storebank(Request $request)
    {
        dd($request);
        $request->validate(['nama_bank' => 'required|unique:bank_pembayarans,nama_bank']);
        BankPembayaran::create($request->only('nama_bank'));
        return redirect()->back();
    }

    public function destrobank($id)
    {
        BankPembayaran::findOrFail($id)->delete();
        return redirect()->back();
    }
}
