<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AkunJurnalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            // ASET (1xxx)
            ['kode_akun' => '1101', 'nama_akun' => 'Kas Tunai', 'kategori' => 'Aset', 'posisi_normal' => 'debit'],
            ['kode_akun' => '1102', 'nama_akun' => 'Bank BCA', 'kategori' => 'Aset', 'posisi_normal' => 'debit'],
            ['kode_akun' => '1201', 'nama_akun' => 'Piutang Pelanggan', 'kategori' => 'Aset', 'posisi_normal' => 'debit'],
            ['kode_akun' => '1301', 'nama_akun' => 'Persediaan Kain', 'kategori' => 'Aset', 'posisi_normal' => 'debit'],

            // KEWAJIBAN / UTANG (2xxx)
            ['kode_akun' => '2101', 'nama_akun' => 'Utang PPN', 'kategori' => 'Kewajiban', 'posisi_normal' => 'kredit'],
            ['kode_akun' => '2102', 'nama_akun' => 'Titipan Retur Pelanggan', 'kategori' => 'Kewajiban', 'posisi_normal' => 'kredit'],

            // PENDAPATAN (4xxx)
            ['kode_akun' => '4101', 'nama_akun' => 'Penjualan Kain', 'kategori' => 'Pendapatan', 'posisi_normal' => 'kredit'],
            ['kode_akun' => '4102', 'nama_akun' => 'Pendapatan Ongkir', 'kategori' => 'Pendapatan', 'posisi_normal' => 'kredit'],
            ['kode_akun' => '4201', 'nama_akun' => 'Potongan Penjualan (Diskon)', 'kategori' => 'Pendapatan', 'posisi_normal' => 'debit'],
            ['kode_akun' => '4202', 'nama_akun' => 'Retur Penjualan', 'kategori' => 'Pendapatan', 'posisi_normal' => 'debit'],

            // BEBAN (5xxx)
            ['kode_akun' => '5101', 'nama_akun' => 'HPP (Harga Pokok Penjualan)', 'kategori' => 'Beban', 'posisi_normal' => 'debit'],
        ];

        DB::table('akuns')->insert($data);
    }
}
