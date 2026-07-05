<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesanan_pembelian', function (Blueprint $table) {
            $table->id();
            $table->string('kode_po', 30)->unique();
            $table->foreignId('bahan_baku_id')
                ->constrained('bahan_baku')
                ->restrictOnDelete();
            $table->foreignId('supplier_id')
                ->constrained('suppliers')
                ->restrictOnDelete();
            $table->decimal('jumlah', 15, 2)->comment('Must be > 0');
            $table->decimal('harga_satuan', 15, 2)->nullable()
                ->comment('Price per unit at time of order');
            // Status enum: Menunggu → Dalam Proses → Diterima (or Dibatalkan)
            // Naming follows PRD §6.5; status values confirmed per UI Spec §3.2
            $table->string('status', 20)->default('Menunggu')
                ->comment('Menunggu | Dalam Proses | Diterima | Dibatalkan');
            $table->string('jenis', 10)->default('Rutin')
                ->comment('Rutin | Darurat');
            $table->date('tanggal_pesan');
            $table->date('tanggal_terima')->nullable()
                ->comment('Set when status transitions to Diterima');
            $table->date('estimasi_tiba')->nullable()
                ->comment('Computed: tanggal_pesan + lead_time_hari');
            $table->foreignId('dicatat_oleh')
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamps();

            $table->index(['status', 'jenis']);
            $table->index(['bahan_baku_id', 'status']);
            $table->index('tanggal_pesan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesanan_pembelian');
    }
};
