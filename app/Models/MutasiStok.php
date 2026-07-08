<?php

namespace App\Models;

use Database\Factories\MutasiStokFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable stock movement ledger row (append-only audit trail).
 *
 * Exactly one of `bahan_baku_id` / `finished_goods_id` is set — enforced
 * by a DB CHECK constraint on PostgreSQL (`chk_mutasi_item_exclusive`)
 * and must also be validated at the application layer, since SQLite
 * (used in tests) does not enforce CHECK constraints.
 *
 * `sumber` / `po_id` / `production_entry_id` consistency is likewise
 * DB-enforced only on PostgreSQL (`chk_mutasi_sumber_consistency`) and
 * must be re-validated in StockMutationService / ProductionService.
 * This model intentionally contains none of that validation logic —
 * it is a pure, business-logic-free row representation per M-2.2 scope.
 */
class MutasiStok extends Model
{
    /** @use HasFactory<MutasiStokFactory> */
    use HasFactory;

    protected $table = 'mutasi_stok';

    /**
     * Valid values for the `jenis_mutasi` column.
     */
    public const JENIS_MASUK = 'masuk';

    public const JENIS_KELUAR = 'keluar';

    /**
     * Valid values for the `sumber` column.
     */
    public const SUMBER_MANUAL = 'manual';

    public const SUMBER_PO_PENERIMAAN = 'po_penerimaan';

    public const SUMBER_PRODUKSI = 'produksi';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'bahan_baku_id',
        'finished_goods_id',
        'jenis_mutasi',
        'jumlah',
        'tanggal',
        'sumber',
        'po_id',
        'production_entry_id',
        'dicatat_oleh',
        'keterangan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:2',
            'tanggal' => 'date',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    /**
     * Set only when this leg affects a raw material (mutually exclusive
     * with finishedGood).
     *
     * @return BelongsTo<BahanBaku, $this>
     */
    public function bahanBaku(): BelongsTo
    {
        return $this->belongsTo(BahanBaku::class)->withTrashed();
    }

    /**
     * Set only when this leg affects a finished good (mutually exclusive
     * with bahanBaku).
     *
     * @return BelongsTo<FinishedGood, $this>
     */
    public function finishedGood(): BelongsTo
    {
        return $this->belongsTo(FinishedGood::class, 'finished_goods_id')->withTrashed();
    }

    /**
     * Source purchase order — set only when sumber = po_penerimaan.
     *
     * @return BelongsTo<PesananPembelian, $this>
     */
    public function pesananPembelian(): BelongsTo
    {
        return $this->belongsTo(PesananPembelian::class, 'po_id');
    }

    /**
     * Source production entry — set only when sumber = produksi.
     *
     * @return BelongsTo<ProductionEntry, $this>
     */
    public function productionEntry(): BelongsTo
    {
        return $this->belongsTo(ProductionEntry::class);
    }

    /**
     * User who recorded this mutation.
     *
     * @return BelongsTo<User, $this>
     */
    public function dicatatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }
}
