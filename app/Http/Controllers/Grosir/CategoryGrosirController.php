<?php

namespace App\Http\Controllers\Grosir;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryGrosirController extends Controller
{
    public function index()
    {
        return inertia('Grosir/Master/Category');
    }

    public function store(Request $request)
    {
        // dd($request);
        $request->validate([
            'nama_kategori' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    // Cek ke database: apakah ada nama yang sama setelah keduanya diubah ke huruf kecil
                    $exists = Kategori::whereRaw('LOWER(nama_kategori) = ?', [strtolower($value)])
                        ->exists();

                    if ($exists) {
                        $fail('Kategori atau Warna ini sudah ada dalam sistem.');
                    }
                },
            ],
            'status' => 'required|in:nama,warna',
        ]);

        $kategori = Kategori::create([
            'nama_kategori' => $request->nama_kategori,
            'status' => $request->status,
            'slug' => Str::slug($request->nama_kategori, '-'),
        ]);

        return back()->with('success', 'Kategori berhasil diperbarui');
    }

    public function update(Request $request, $id)
    {
        // dd($request);
        $request->validate([
            'nama_kategori' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    // Cek ke database: apakah ada nama yang sama setelah keduanya diubah ke huruf kecil
                    $exists = Kategori::whereRaw('LOWER(nama_kategori) = ?', [strtolower($value)])
                        ->exists();

                    if ($exists) {
                        $fail('Kategori atau Warna ini sudah ada dalam sistem.');
                    }
                },
            ],
            'status' => 'required|in:nama,warna',
        ]);

        $kategori = Kategori::findOrFail($id);
        $kategori->update([
            'nama_kategori' => $request->nama_kategori,
            'status' => $request->status,
            'slug' => Str::slug($request->nama_kategori, '-'),
        ]);

        return back()->with('success', 'Kategori berhasil diperbarui');
    }

    public function destroy($id)
    {
        $kategori = Kategori::findOrFail($id);
        $kategori->delete();

        return back()->with('success', 'Kategori berhasil dihapus');
    }
}
