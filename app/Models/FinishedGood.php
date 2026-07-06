<?php

namespace App\Models;

use Database\Factories\FinishedGoodFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Finished good master data.
 *
 * `stok_saat_ini` is a running balance credited exclusively by
 * ProductionService (M-2.13) via StockMutationService — never written
 * to directly outside an atomic mutasi_stok transaction.
 */
class FinishedGood extends Model
{
    /** @use HasFactory<FinishedGoodFactory> */
    use HasFactory;

    protected $table = 'finished_goods';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'kode',
        'nama',
        'satuan',
        'stok_saat_ini',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stok_saat_ini' => 'decimal:2',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    /**
     * BOM lines that define this finished good's recipe.
     *
     * @return HasMany<Bom, $this>
     */
    public function bomLines(): HasMany
    {
        return $this->hasMany(Bom::class, 'finished_goods_id');
    }

    /**
     * @return HasMany<ProductionEntry, $this>
     */
    public function productionEntries(): HasMany
    {
        return $this->hasMany(ProductionEntry::class, 'finished_goods_id');
    }

    /**
     * All stock mutation legs (masuk/keluar) recorded against this finished good.
     *
     * @return HasMany<MutasiStok, $this>
     */
    public function mutasiStok(): HasMany
    {
        return $this->hasMany(MutasiStok::class, 'finished_goods_id');
    }
}
