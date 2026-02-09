<?php

namespace App\Http\Controllers\Ecer;

use App\Http\Controllers\Controller;
use App\Models\Akun;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EcerAkunController extends Controller
{
    public function index(Request $request)
    {
        // Biasanya akun diurutkan berdasarkan kode agar rapi di laporan
        $akun = Akun::orderBy('kode_akun', 'asc')->get();

        return inertia('Ecer/Setting/Akun', [
            'akun' => $akun
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kode_akun'     => 'required|string|unique:akuns,kode_akun',
            'nama_akun'     => 'required|string',
            'kategori'      => 'required|string',
            'posisi_normal' => 'required|in:debit,kredit', // Gunakan 'in' agar data konsisten
        ]);

        Akun::create($data);
        return back();
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            // Cara yang benar untuk mengabaikan ID saat update unik:
            'kode_akun'     => ['required', 'string', Rule::unique('akuns')->ignore($id)],
            'nama_akun'     => 'required|string',
            'kategori'      => 'required|string',
            'posisi_normal' => 'required|in:debit,kredit',
        ]);

        $akun = Akun::findOrFail($id);
        $akun->update($data);

        return back();
    }

    public function destroy($id)
    {
        $akun = Akun::findOrFail($id);


        if ($akun->jurnalItems()->exists()) {
            return back()->withErrors(['message' => 'Akun tidak bisa dihapus karena sudah memiliki transaksi.']);
        }

        $akun->delete();
        return back();
    }
}
