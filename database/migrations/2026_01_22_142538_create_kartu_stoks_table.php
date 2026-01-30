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
        Schema::create('kartu_stoks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_detail_id')->constrained('produk_details')->onDelete('cascade');
            $table->foreignId('varian_id')->constrained('produk_varians');
            $table->integer('dari_gudang_id')->nullable();
            $table->integer('ke_gudang_id')->nullable();
            $table->enum('tipe', ['masuk', 'keluar', 'transfer', 'penyesuaian']);
            $table->decimal('qty', 10, 2);
            $table->decimal('stok_awal', 10, 2);
            $table->decimal('stok_akhir', 10, 2);
            $table->string('keterangan')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kartu_stoks');
    }
};
