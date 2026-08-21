<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel Header Jurnal
        Schema::create('mini_jurnals', function (Blueprint $table) {
            $table->id();
            $table->string('no_jurnal')->unique(); // Contoh: JUR-20240001
            $table->date('tanggal');
            $table->string('keterangan')->nullable();

            // Kolom Polymorphic untuk referensi (id_pembayaran, id_retur, dll)
            $table->string('jurnalable_type')->nullable();
            $table->unsignedBigInteger('jurnalable_id')->nullable();
            $table->unsignedBigInteger('pembayaran_id')->nullable();

            // Relasi ke Customer agar mudah tarik laporan piutang/saldo per orang
            $table->foreignId('pelanggan_id')->nullable()->constrained('pelanggans');

            $table->decimal('total_amount', 15, 2)->default(0); // Untuk validasi balance
            $table->timestamps();

            // Indexing untuk kecepatan pencarian
            $table->index(['jurnalable_type', 'jurnalable_id']);
        });

        // Tabel Detail Baris Jurnal (Debit & Kredit)
        Schema::create('mini_jurnal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mini_jurnal_id')->constrained('mini_jurnals')->onDelete('cascade');
            $table->foreignId('akun_id')->constrained('akuns'); // Relasi ke COA
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('kredit', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mini_jurnal_items');
        Schema::dropIfExists('mini_jurnals');
    }
};
