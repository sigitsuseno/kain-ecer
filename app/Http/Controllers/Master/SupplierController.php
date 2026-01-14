<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SupplierController extends Controller
{

    public function store(Request $request)
    {
        $request->validate([
            'nama_pt' => 'nullable|string|max:255',
            'nama'    => 'required|string|max:255',
            'phone'   => 'nullable|string|max:20',
            'email'   => 'nullable|email|max:255',
            'alamat'  => 'nullable|string',
        ]);

        \App\Models\Supplier::create($request->all());
        return back();
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_pt' => 'nullable|string|max:255',
            'nama'    => 'required|string|max:255',
        ]);

        \App\Models\Supplier::findOrFail($id)->update($request->all());
        return back();
    }
    public function destroy($id)
    {
        \App\Models\Supplier::findOrFail($id)->delete();
        return back();
    }
}
