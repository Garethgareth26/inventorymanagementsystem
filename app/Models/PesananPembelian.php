<?php

namespace App\Models;

use Database\Factories\PesananPembelianFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Purchase order header (flat, header-less shape — one row per PO).
 *
 * Status machine (application-enforced, no DB enum column):
 * Menunggu → Dalam Proses → Diterima, or Menunggu → Dibatalkan.
 * Advancing to Diterima triggers a `masuk` mutasi_stok via
 * StockMutationService (M-2.12) — never performed by this model.
 */
class PesananPembelian extends Model
{
    /** @use HasFactory<PesananPembelianFactory> */
    use HasFactory;

    protected $table = 'pesanan_pembelian';

    /**
     * Valid values for the `status` column.
     */
    public const STATUS_MENUNGGU = 'Menunggu';

    public const STATUS_DALAM_PROSES = 'Dalam Proses';

    public const STATUS_DITERIMA = 'Diterima';

    public const STATUS_DIBATALKAN = 'Dibatalkan';

    /**
     * Valid values for the `jenis` column.
     */
    public const JENIS_RUTIN = 'Rutin';

    public const JENIS_DARURAT = 'Darurat';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'kode_po',
        'bahan_baku_id',
        'supplier_id',
        'jumlah',
        'harga_satuan',
        'status',
        'jenis',
        'tanggal_pesan',
        'tanggal_terima',
        'estimasi_tiba',
        'dicatat_oleh',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:2',
            'harga_satuan' => 'decimal:2',
            'tanggal_pesan' => 'date',
            'tanggal_terima' => 'date',
            'estimasi_tiba' => 'date',
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
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * User who recorded this purchase order.
     *
     * @return BelongsTo<User, $this>
     */
    public function dicatatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }

    /**
     * The stock receipt mutation created once this PO transitions to Diterima.
     *
     * @return HasMany<MutasiStok, $this>
     */
    public function mutasiStok(): HasMany
    {
        return $this->hasMany(MutasiStok::class, 'po_id');
    }
}
