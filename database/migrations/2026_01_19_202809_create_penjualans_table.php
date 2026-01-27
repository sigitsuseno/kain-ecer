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
        Schema::create('penjualans', function (Blueprint $table) {
            $table->id();
            $table->string('nomer')->unique();
            $table->date('tanggal');
            $table->string('bagian', 10)->default('grosir');
            $table->unsignedBigInteger('pelanggan_id');
            $table->unsignedBigInteger('marketing_id')->nullable();
            $table->decimal('total_penjualan', 19, 2)->default(0);
            $table->decimal('diskon', 19, 2)->default(0);
            $table->decimal('pajak', 19, 2)->default(0);
            $table->decimal('ongkir', 19, 2)->default(0);
            $table->decimal('kembalian', 19, 2)->default(0);
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penjualans');
    }
};
