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
        Schema::create('penjualan_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('penjualan_id');
            $table->unsignedBigInteger('varian_id');
            $table->unsignedBigInteger('satuan_id')->nullable();
            $table->decimal('total_qty', 19, 2)->default(0);
            $table->decimal('total_jumlah', 19, 2)->default(0);
            $table->decimal('harga_satuan', 19, 2)->default(0);
            $table->decimal('subtotal', 19, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penjualan_items');
    }
};
