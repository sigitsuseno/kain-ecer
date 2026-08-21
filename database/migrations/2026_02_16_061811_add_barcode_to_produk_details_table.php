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
        Schema::table('produk_details', function (Blueprint $table) {
            // Menambahkan kolom barcode setelah varian_id agar rapi
            // Menggunakan unique() karena satu barcode hanya boleh untuk satu item spesifik
            $table->string('barcode')->nullable()->unique()->after('varian_id');

            // Tambahan opsional: Index untuk mempercepat pencarian saat scan
            $table->index('barcode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produk_details', function (Blueprint $table) {
            //
        });
    }
};
