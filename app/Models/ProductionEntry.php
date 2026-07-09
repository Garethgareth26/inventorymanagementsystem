<?php

namespace App\Models;

use Database\Factories\ProductionEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Source document for a production run.
 *
 * No status column by design (Decision 1): entries that fail the
 * insufficient-stock check are rejected before any row is written.
 * Each entry is the anchor for N+1 mutasi_stok rows (N bahan_baku
 * `keluar` legs exploded from the BOM, plus 1 finished_good `masuk`
 * leg), created atomically by ProductionService (M-2.13).
 */
class ProductionEntry extends Model
{
    /** @use HasFactory<ProductionEntryFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'finished_goods_id',
        'jumlah_diproduksi',
        'tanggal_produksi',
        'dicatat_oleh',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jumlah_diproduksi' => 'decimal:2',
            'tanggal_produksi' => 'date',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    /**
     * @return BelongsTo<FinishedGood, $this>
     */
    public function finishedGood(): BelongsTo
    {
        return $this->belongsTo(FinishedGood::class, 'finished_goods_id');
    }

    /**
     * Karyawan who recorded this production entry.
     *
     * @return BelongsTo<User, $this>
     */
    public function dicatatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }

    /**
     * The N+1 stock mutation legs generated from this production run.
     *
     * @return HasMany<MutasiStok, $this>
     */
    public function mutasiStok(): HasMany
    {
        return $this->hasMany(MutasiStok::class);
    }
}
