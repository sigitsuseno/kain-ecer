<?php

namespace App\Http\Controllers\Grosir;

use App\Http\Controllers\Controller;
use App\Models\Gudang;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GudangGrosirController extends Controller
{
    public function index(Request $request)
    {
        $query = Gudang::query();

        if ($request->has('search')) {
            $query->where('nama_gudang', 'like', '%' . $request->search . '%')
                ->orWhere('lokasi', 'like', '%' . $request->search . '%');
        }

        return Inertia::render('Grosir/Master/Gudang', [
            'gudangs' => $query->latest()->get(),
            'filters' => $request->only(['search'])
        ]);
    }

    public function store(Request $request)
    {
        $valid = $request->validate([
            'nama_gudang' => 'required|string|max:255',
            'lokasi'      => 'nullable|string|max:255',
            'deskripsi'   => 'nullable|string',
        ]);

        Gudang::create($valid);
        return back();
    }

    public function update(Request $request, $id)
    {
        $valid = $request->validate([
            'nama_gudang' => 'required|string|max:255',
            'lokasi'      => 'nullable|string|max:255',
            'deskripsi'   => 'nullable|string',
        ]);

        Gudang::findOrFail($id)->update($valid);
        return back();
    }

    public function destroy($id)
    {
        Gudang::findOrFail($id)->delete();
        return back();
    }
}
