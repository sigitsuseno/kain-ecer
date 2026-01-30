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
        Schema::create('retur_penjualan_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retur_item_id')->constrained('retur_penjualan_items')->onDelete('cascade');
            $table->foreignId('produk_detail_id')->constrained('produk_details');
            $table->decimal('qty', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retur_penjualan_details');
    }
};
