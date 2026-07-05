<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bom', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finished_goods_id')
                ->constrained('finished_goods')
                ->restrictOnDelete()
                ->comment('ON DELETE RESTRICT — cannot delete a finished good with active BOM lines');
            $table->foreignId('bahan_baku_id')
                ->constrained('bahan_baku')
                ->restrictOnDelete()
                ->comment('ON DELETE RESTRICT — cannot delete a raw material used in any BOM');
            $table->decimal('qty_per_unit', 15, 4)->comment('Raw material qty required per 1 unit of finished good; must be > 0');
            $table->string('satuan', 20)->comment('Unit for qty_per_unit (may differ from bahan_baku.satuan if conversion applies)');
            $table->timestamps();

            // A raw material can only appear once per finished good's active BOM (Decision 2)
            $table->unique(['finished_goods_id', 'bahan_baku_id'], 'uq_bom_finished_good_material');
            $table->index('finished_goods_id', 'idx_bom_finished_goods');
            $table->index('bahan_baku_id', 'idx_bom_bahan_baku');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bom');
    }
};
