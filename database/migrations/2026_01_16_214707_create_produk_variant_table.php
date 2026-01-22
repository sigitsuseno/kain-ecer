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
        Schema::create('produk_varians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_id')->constrained('produks')->onDelete('cascade');
            $table->unsignedBigInteger('gudang_id')->nullable();
            $table->string('nama')->nullable();
            $table->string('tipe')->nullable();
            $table->decimal('panjang', 15, 2)->default(0);
            $table->decimal('lebar', 15, 2)->default(0);
            $table->decimal('tinggi', 15, 2)->default(0);
            $table->decimal('tebal', 15, 2)->default(0);
            $table->string('sku')->nullable()->unique();
            $table->boolean('is_paket')->default(false);
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
        Schema::dropIfExists('produk_variants');
    }
};
