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
        Schema::create("pembelian_items", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("pembelian_id")->nullable();
            $table->unsignedBigInteger("varian_id")->nullable();
            $table->bigInteger("total_panjang")->nullable();
            $table->bigInteger("total_qty")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("pembelian_items");
    }
};
