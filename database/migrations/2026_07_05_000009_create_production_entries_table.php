<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finished_goods_id')
                ->constrained('finished_goods')
                ->restrictOnDelete();
            $table->decimal('jumlah_diproduksi', 15, 2)->comment('Must be > 0');
            $table->date('tanggal_produksi');
            $table->foreignId('dicatat_oleh')
                ->constrained('users')
                ->restrictOnDelete()
                ->comment('Karyawan only — enforced at application layer (production.record capability)');
            $table->timestamps();

            // No status column: entries that fail stock check are rejected before writing (Decision 1)
            $table->index(['finished_goods_id', 'tanggal_produksi'], 'idx_production_finished_goods_tanggal');
            $table->index('tanggal_produksi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_entries');
    }
};
