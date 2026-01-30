<?php

use App\Models\ReturPenjualan;
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
        Schema::create('retur_penjualan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ReturPenjualan::class)->constrained()->onDelete('cascade');
            $table->foreignId('varian_id')->constrained('produk_varians');
            $table->foreignId('satuan_id')->constrained('satuans');
            $table->decimal('qty', 15, 2)->default(0);
            $table->decimal('jumlah', 15, 2)->default(0);
            $table->decimal('harga_satuan', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retur_penjualan_items');
    }
};
