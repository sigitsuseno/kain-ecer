<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("pembelians", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("supplier_id")->nullable();
            $table->string("nomer_pembelian")->unique();
            $table->date("tanggal_transaksi")->nullable();
            $table->string("status")->default("UNPAID");
            $table->decimal("total_jumlah_qty")->nullable();
            $table->decimal("total_jumlah_panjang")->nullable();
            $table->text("catatan")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("pembelians");
    }
};
