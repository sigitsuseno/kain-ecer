<?php

use App\Models\Penjualan;
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
        Schema::create('retur_penjualans', function (Blueprint $table) {
            $table->id();
            $table->string('nomer');
            $table->foreignIdFor(Penjualan::class)->nullable()->constrained()->nullOnDelete();
            $table->date('tanggal');
            $table->decimal('total_nilai_retur', '15', 0)->default(0);
            $table->decimal('retur_qty', '15', 0)->default(0);
            $table->decimal('retur_jumlah', '15', 0)->default(0);
            $table->text('alasan')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retur_penjualans');
    }
};
