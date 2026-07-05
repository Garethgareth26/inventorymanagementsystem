<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bahan_baku', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama', 150);
            $table->string('satuan', 20);
            $table->decimal('stok_saat_ini', 15, 2)->default(0)
                ->comment('Running balance; updated atomically on every mutasi_stok write');
            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained('suppliers')
                ->nullOnDelete()
                ->comment('Routine supplier');
            $table->decimal('harga_satuan', 15, 2)->nullable()
                ->comment('Routine supplier price per unit');
            $table->integer('lead_time_hari')->nullable()
                ->comment('Routine supplier lead time in days');
            $table->timestamps();

            $table->index('nama');
            $table->index('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bahan_baku');
    }
};
