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
        Schema::create('detail_pakets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('induk_varian_id')->nullable();
            $table->unsignedBigInteger('anak_varian_id')->nullable();
            $table->unsignedBigInteger('satuan_id')->nullable();
            $table->decimal('qty', 15, 2)->default(0);
            $table->decimal('isi_2', 15, 2)->default(0);
            $table->string('status')->default('tersedia');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_pakets');
    }
};
