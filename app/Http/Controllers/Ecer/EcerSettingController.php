<?php

namespace App\Http\Controllers\Ecer;

use App\Http\Controllers\Controller;
use App\Models\BankPembayaran;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

        $request->validate(['nama_bank' => 'required|unique:bank_pembayarans,nama_bank']);
        BankPembayaran::create([
            'nama_bank' => $request->nama_bank,
            'slug' => Str::slug($request->nama_bank)
        ]);
        return redirect()->back();
    }

    public function destrobank($id)
    {
        BankPembayaran::findOrFail($id)->delete();
        return redirect()->back();
    }
}
