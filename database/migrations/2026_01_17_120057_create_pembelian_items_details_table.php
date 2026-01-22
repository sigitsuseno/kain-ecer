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
        Schema::create('pembelian_items_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pembelian_item_id');
            $table->unsignedBigInteger('satuan_id');
            $table->decimal('qty', 15, 2)->default(0);
            $table->string('lot_number')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembelian_items_details');
    }
};
