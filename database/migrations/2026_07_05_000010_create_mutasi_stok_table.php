<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mutasi_stok', function (Blueprint $table) {
            $table->id();

            // Exactly ONE of bahan_baku_id / finished_goods_id must be set (CHECK constraint below)
            $table->foreignId('bahan_baku_id')
                ->nullable()
                ->constrained('bahan_baku')
                ->restrictOnDelete()
                ->comment('Set only for raw-material mutation legs');
            $table->foreignId('finished_goods_id')
                ->nullable()
                ->constrained('finished_goods')
                ->restrictOnDelete()
                ->comment('Set only for finished-goods mutation legs');

            $table->string('jenis_mutasi', 10)->comment('masuk | keluar');
            $table->decimal('jumlah', 15, 2)->comment('Must be > 0');
            $table->date('tanggal');

            // sumber tracks the origin of this mutation (Design Decision 2)
            $table->string('sumber', 20)->default('manual')
                ->comment('manual | po_penerimaan | produksi');

            // Nullable FKs for linking mutations back to their source documents
            $table->foreignId('po_id')
                ->nullable()
                ->constrained('pesanan_pembelian')
                ->restrictOnDelete()
                ->comment('Set only when sumber = po_penerimaan');
            $table->foreignId('production_entry_id')
                ->nullable()
                ->constrained('production_entries')
                ->restrictOnDelete()
                ->comment('Set only when sumber = produksi');

            $table->foreignId('dicatat_oleh')
                ->constrained('users')
                ->restrictOnDelete();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            // Performance indexes matching the hot query paths identified in Domain Analysis §5
            $table->index(['bahan_baku_id', 'tanggal'], 'idx_mutasi_bahan_baku_tanggal');
            $table->index(['finished_goods_id', 'tanggal'], 'idx_mutasi_finished_goods_tanggal');
            $table->index(['jenis_mutasi', 'sumber'], 'idx_mutasi_jenis_sumber');
        });

        // DB-level CHECK constraints — enforce referential integrity that Eloquent alone cannot guarantee.
        // These are PostgreSQL-specific; SQLite (used in tests) does not enforce CHECK constraints,
        // so the application layer must also validate these rules.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE mutasi_stok ADD CONSTRAINT chk_mutasi_item_exclusive
                CHECK (num_nonnulls(bahan_baku_id, finished_goods_id) = 1)');

            DB::statement("ALTER TABLE mutasi_stok ADD CONSTRAINT chk_mutasi_sumber_consistency
                CHECK (
                    (sumber = 'manual' AND po_id IS NULL AND production_entry_id IS NULL)
                    OR (sumber = 'po_penerimaan' AND po_id IS NOT NULL AND production_entry_id IS NULL)
                    OR (sumber = 'produksi' AND production_entry_id IS NOT NULL AND po_id IS NULL)
                )");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mutasi_stok');
    }
};
