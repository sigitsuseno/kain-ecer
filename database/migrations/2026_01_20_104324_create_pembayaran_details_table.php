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
        Schema::create('pembayaran_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pembayaran_id');
            $table->string('metode_bayar')->nullable();
            $table->string('bank_asal')->nullable();
            $table->string('akun_asal')->nullable();
            $table->string('bank_tujuan')->nullable();
            $table->string('no_ref')->nullable();
            $table->decimal('nominal', 19, 2)->default(0);
            $table->decimal('jumlah_bayar', 19, 2)->default(0);
            $table->decimal('ongkir', 19, 2)->default(0);
            $table->decimal('diskon', 19, 2)->default(0);
            $table->decimal('pajak', 19, 2)->default(0);
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
        Schema::dropIfExists('pembayaran_details');
    }
};
