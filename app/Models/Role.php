<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    /**
     * The capabilities this role possesses, sourced from config/capabilities.php.
     *
     * @return array<string>
     */
    public function capabilities(): array
    {
        return config("capabilities.{$this->slug}", []);
    }

    /**
     * Determine if this role has a given capability.
     */
    public function hasCapability(string $capability): bool
    {
        return in_array($capability, $this->capabilities(), strict: true);
    }

    // Relations

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
