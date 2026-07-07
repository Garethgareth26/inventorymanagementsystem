<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Key-value system settings row.
 *
 * Values are stored as strings and cast at the application layer
 * by the SystemSettings service (App\Services\SystemSettings).
 * Do not add cast definitions here — casting is context-dependent
 * (a float for z_factor, an int for historical_window, etc.).
 */
class SystemSetting extends Model
{
    protected $table = 'system_settings';

    /**
     * @var list<string>
     */
    protected $fillable = ['key', 'value'];
}
