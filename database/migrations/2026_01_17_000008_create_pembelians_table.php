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
            $table->string("nomer")->unique();
            $table->date("tanggal")->nullable();
            $table->decimal("total_pembelian")->nullable();
            $table->decimal("beban_ongkir")->nullable();
            $table->decimal("beban_biaya")->nullable();
            $table->text("catatan")->nullable();
            $table->string("status")->default("STOKED");
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
