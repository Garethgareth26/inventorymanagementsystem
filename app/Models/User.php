<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'is_active',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Purchase orders recorded by this user.
     *
     * @return HasMany<PesananPembelian, $this>
     */
    public function pesananPembelian(): HasMany
    {
        return $this->hasMany(PesananPembelian::class, 'dicatat_oleh');
    }

    /**
     * Production entries recorded by this user (Karyawan only).
     *
     * @return HasMany<ProductionEntry, $this>
     */
    public function productionEntries(): HasMany
    {
        return $this->hasMany(ProductionEntry::class, 'dicatat_oleh');
    }

    /**
     * Stock mutation legs recorded by this user.
     *
     * @return HasMany<MutasiStok, $this>
     */
    public function mutasiStok(): HasMany
    {
        return $this->hasMany(MutasiStok::class, 'dicatat_oleh');
    }

    /**
     * Inventory parameter sets this user last applied (EOQ/SS/ROP simulations).
     *
     * @return HasMany<InventoryParameter, $this>
     */
    public function appliedInventoryParameters(): HasMany
    {
        return $this->hasMany(InventoryParameter::class, 'last_applied_by');
    }

    /**
     * Audit log rows where this user is the actor.
     *
     * @return HasMany<AuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    // ─── RBAC helpers ─────────────────────────────────────────────────────────

    /**
     * Determine if the user possesses the given capability.
     *
     * Capabilities are resolved from config/capabilities.php via the Role model.
     * This is the single call site for all capability checks in Policies and Middleware.
     */
    public function hasCapability(string $capability): bool
    {
        return $this->role?->hasCapability($capability) ?? false;
    }

    /**
     * Convenience check: is the user a Karyawan (employee)?
     */
    public function isKaryawan(): bool
    {
        return $this->role?->slug === 'karyawan';
    }

    /**
     * Convenience check: is the user an Owner?
     */
    public function isOwner(): bool
    {
        return $this->role?->slug === 'owner';
    }
}
