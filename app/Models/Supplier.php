<?php

namespace App\Models;

use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'kode',
        'nama',
        'alamat',
        'kontak',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    /**
     * Raw materials for which this supplier is the routine (default) supplier.
     *
     * @return HasMany<BahanBaku, $this>
     */
    public function bahanBaku(): HasMany
    {
        return $this->hasMany(BahanBaku::class);
    }

    /**
     * @return HasMany<PesananPembelian, $this>
     */
    public function pesananPembelian(): HasMany
    {
        return $this->hasMany(PesananPembelian::class);
    }
}
