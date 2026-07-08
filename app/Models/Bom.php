<?php

namespace App\Models;

use Database\Factories\BomFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One ingredient line in a finished good's Bill of Materials.
 *
 * A raw material may appear at most once per finished good (unique
 * constraint `uq_bom_finished_good_material`), enforced at the DB and
 * validated inline by the BOM Editor (M-2.11).
 */
class Bom extends Model
{
    /** @use HasFactory<BomFactory> */
    use HasFactory;

    protected $table = 'bom';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'finished_goods_id',
        'bahan_baku_id',
        'qty_per_unit',
        'satuan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty_per_unit' => 'decimal:4',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    /**
     * @return BelongsTo<FinishedGood, $this>
     */
    public function finishedGood(): BelongsTo
    {
        return $this->belongsTo(FinishedGood::class, 'finished_goods_id')->withTrashed();
    }

    /**
     * @return BelongsTo<BahanBaku, $this>
     */
    public function bahanBaku(): BelongsTo
    {
        return $this->belongsTo(BahanBaku::class)->withTrashed();
    }
}
