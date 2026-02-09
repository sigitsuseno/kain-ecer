<?php

namespace Database\Seeders;

use App\Models\Gudang;
use App\Models\Kategori;
use App\Models\Pelanggan;
use App\Models\Satuan;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::create([
            'name' => 'Owner',
            'email' => 'owner@toko.com',
            'password' => bcrypt('password123'),
            'role' => 'owner',
        ]);
        User::create([
            'name' => 'Admin',
            'email' => 'grosir@toko.com',
            'password' => bcrypt('password123'),
            'role' => 'admin-grosir',
        ]);

        User::create([
            'name' => 'Admin',
            'email' => 'ecer@toko.com',
            'password' => bcrypt('password123'),
            'role' => 'admin-ecer',
        ]);

        Gudang::create([
            'nama_gudang' => 'Grosir',
            'lokasi' => 'Ungaran',
            'deskripsi' => 'Lokasi Penjualan',
        ]);
        Gudang::create([
            'nama_gudang' => 'Ecer',
            'lokasi' => 'Ungaran',
            'deskripsi' => 'Lokasi Penjualan',
        ]);
        Supplier::create([
            'nama_pt' => 'PT. SM',
            'nama' => 'Budi Santoso',
            'phone' => '081234567890',
            'email' => 'budi@sumbermkmur.com',
            'email' => 'budi@sumbermkmur.com',
            'alamat' => 'Jl. Raya Ungaran No. 123',
            'status' => 'aktif',
        ]);
        Pelanggan::create([
            'nama' => 'Siti Aminah',
            'phone' => '089876543210',
            'email' => 'siti@aminah.com',
            'alamat' => 'Jl. Melati No. 45',
            'status' => 'aktif',
        ]);

        $dataSatuan = [
            ['nama' => 'Yard', 'inisial' => 'Yd'],
            ['nama' => 'Meter', 'inisial' => 'M'],
            ['nama' => 'Kilogram', 'inisial' => 'Kg'],
        ];

        foreach ($dataSatuan as $item) {
            Satuan::firstOrCreate(
                ['nama_satuan' => $item['nama']],
                ['inisial' => $item['inisial']]
            );
        }

        $this->call([
            AkunJurnalSeeder::class,
        ]);
    }
}
