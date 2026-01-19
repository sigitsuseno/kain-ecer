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
        Schema::create('konversi_satuans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('varian_id')->constrained('produk_varians');
            $table->foreignId('satuan_id')->constrained('satuans');
            $table->decimal('koefisien', 15, 4);
            $table->boolean('is_satuan_terkecil')->default(false);
            $table->decimal('harga_default', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('konversi_satuans');
    }
};
