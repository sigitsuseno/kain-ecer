<?php

namespace App\Http\Controllers\Grosir;

use App\Http\Controllers\Controller;
use App\Models\Pelanggan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PelangganGrosirController extends Controller
{
    public function index(Request $request)
    {
        $query = Pelanggan::query();

        // Logika Search
        if ($request->has('search')) {
            $query->where('nama', 'like', '%' . $request->search . '%')
                ->orWhere('phone', 'like', '%' . $request->search . '%');
        }

        return Inertia::render('Grosir/Master/Pelanggan', [
            'pelanggans' => $query->latest()->get(),
            'filters' => $request->only(['search'])
        ]);
    }
    public function store(Request $request)
    {
        $request->validate([
            'nama'        => 'required|string|max:255',
            'phone'       => 'nullable|string|max:20',
            'email'       => 'nullable|email|max:255',
            'alamat'      => 'nullable|string',
            'bank'        => 'nullable|string|max:100',
            'no_rekening' => 'nullable|string|max:50',
            'nama_kartu'  => 'nullable|string|max:100',
            'status'      => 'nullable',
        ]);

        Pelanggan::create($request->all());
        return back();
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama'        => 'required|string|max:255',
            'phone'       => 'nullable|string|max:20',
            'email'       => 'nullable|email|max:255',
            'alamat'      => 'nullable|string',
            'bank'        => 'nullable|string|max:100',
            'no_rekening' => 'nullable|string|max:50',
            'nama_kartu'  => 'nullable|string|max:100',
            'status'      => 'nullable',
        ]);

        Pelanggan::findOrFail($id)->update($request->all());
        return back();
    }
    public function destroy($id)
    {
        Pelanggan::findOrFail($id)->delete();
        return back();
    }
}
