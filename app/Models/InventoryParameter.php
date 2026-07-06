<?php

namespace App\Models;

use Database\Factories\InventoryParameterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Official EOQ / Safety Stock / Reorder Point parameter set for one raw material.
 *
 * One active row per bahan_baku (unique constraint, ADR-004). Historical
 * old/new values on Apply are captured in audit_logs, not versioned here.
 * All computation (EOQ/SS/ROP formulas) lives in CalculationEngine (M-2.4) —
 * this model only stores inputs, outputs, and the audit trail pointer.
 */
class InventoryParameter extends Model
{
    /** @use HasFactory<InventoryParameterFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'bahan_baku_id',
        'kebutuhan_tahunan',
        'standar_deviasi_harian',
        'biaya_pesan',
        'biaya_simpan_persen',
        'eoq',
        'safety_stock',
        'reorder_point',
        'z_factor',
        'historical_window_months',
        'last_applied_by',
        'last_applied_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kebutuhan_tahunan' => 'decimal:4',
            'standar_deviasi_harian' => 'decimal:4',
            'biaya_pesan' => 'decimal:2',
            'biaya_simpan_persen' => 'decimal:4',
            'eoq' => 'decimal:4',
            'safety_stock' => 'decimal:4',
            'reorder_point' => 'decimal:4',
            'z_factor' => 'decimal:4',
            'historical_window_months' => 'integer',
            'last_applied_at' => 'datetime',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    /**
     * @return BelongsTo<BahanBaku, $this>
     */
    public function bahanBaku(): BelongsTo
    {
        return $this->belongsTo(BahanBaku::class);
    }

    /**
     * Karyawan who last applied (persisted) these simulation results.
     *
     * @return BelongsTo<User, $this>
     */
    public function lastAppliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_applied_by');
    }
}
