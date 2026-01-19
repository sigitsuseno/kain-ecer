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
        Schema::create('stok_fisiks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('varian_id')->constrained('produk_varians');
            $table->unsignedBigInteger('gudang_id')->nullable();
            $table->string('nomor_batch')->nullable();
            $table->date('tanggal_kadaluwarsa')->nullable();
            $table->decimal('total_panjang', 19, 2)->default(0);
            $table->decimal('total_qty', 19, 2)->default(0);
            $table->decimal('harga_beli', 19, 2)->default(0);
            $table->decimal('harga_jual', 19, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stok_fisiks');
    }
};
