<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('produks', function (Blueprint $table) {
            $table->id();
            $table->string('kode_produk')->unique();
            $table->string('nama_produk');
            $table->string('kode_warna');
            $table->string('slug')->nullable();
            $table->string('lebar_kain')->nullable();
            $table->string('tipe')->nullable();
            $table->string('satuan')->nullable();
            $table->decimal('panjang_perroll', 19, 2)->default(0);
            $table->decimal('harga_jual_ecer', 19, 2)->default(0);
            $table->decimal('harga_jual_grosir', 19, 2)->default(0);
            $table->decimal('harga_beli', 19, 2)->default(0);
            $table->unsignedBigInteger('kategori_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('gudang_id')->nullable();
            $table->string('mode')->default('grosir');
            $table->string('status')->default('tersedia');
            $table->string('deskripsi')->nullable();
            $table->string('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produks');
    }
};
