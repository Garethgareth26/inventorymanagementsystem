<?php

namespace App\Models;

use Database\Factories\BahanBakuFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Raw material master data.
 *
 * `stok_saat_ini` is a running balance maintained exclusively by
 * StockMutationService (M-2.9) — never written to directly outside
 * an atomic mutasi_stok transaction.
 */
class BahanBaku extends Model
{
    /** @use HasFactory<BahanBakuFactory> */
    use HasFactory;

    protected $table = 'bahan_baku';

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
        'supplier_id',
        'harga_satuan',
        'lead_time_hari',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stok_saat_ini' => 'decimal:2',
            'harga_satuan' => 'decimal:2',
            'lead_time_hari' => 'integer',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    /**
     * Routine (default) supplier for this material. Nullable.
     *
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Official EOQ/Safety Stock/ROP parameter set (one active row per material, ADR-004).
     *
     * @return HasOne<InventoryParameter, $this>
     */
    public function inventoryParameter(): HasOne
    {
        return $this->hasOne(InventoryParameter::class);
    }

    /**
     * BOM lines that consume this material (across all finished goods).
     *
     * @return HasMany<Bom, $this>
     */
    public function bomLines(): HasMany
    {
        return $this->hasMany(Bom::class);
    }

    /**
     * @return HasMany<PesananPembelian, $this>
     */
    public function pesananPembelian(): HasMany
    {
        return $this->hasMany(PesananPembelian::class);
    }

    /**
     * All stock mutation legs (masuk/keluar) recorded against this material.
     *
     * @return HasMany<MutasiStok, $this>
     */
    public function mutasiStok(): HasMany
    {
        return $this->hasMany(MutasiStok::class);
    }
}
