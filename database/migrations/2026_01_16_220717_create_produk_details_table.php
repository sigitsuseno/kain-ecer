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
        Schema::create('produk_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('gudang_id')->nullable();
            $table->unsignedBigInteger('varian_id')->nullable();
            $table->unsignedBigInteger('stok_id')->nullable();
            $table->unsignedBigInteger('old_id')->nullable();
            $table->unsignedBigInteger('satuan_id')->nullable();
            $table->decimal('qty', 15, 2)->default(0);
            $table->decimal('isi', 15, 2)->default(0);
            $table->string('status')->default('tersedia');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produk_details');
    }
};
