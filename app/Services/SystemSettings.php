<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Redis-backed system settings store with database fallback.
 *
 * All reads are served from the cache (TTL: 24 hours by default).
 * On cache miss the value is fetched from `system_settings` and
 * written back to cache. Writes update both the DB and the cache
 * atomically (DB first, then cache invalidate — no stale reads).
 *
 * Usage (from any service or controller):
 *   SystemSettings::get('z_factor')         // returns '1.65' (string)
 *   SystemSettings::getFloat('z_factor')     // returns 1.65 (float)
 *   SystemSettings::getInt('historical_window') // returns 12 (int)
 *   SystemSettings::set('z_factor', '1.96', $request) // updates DB + cache
 *
 * The service is a static façade over the DB model — it has no constructor
 * injection requirement, making it usable in seeders, queue jobs, and
 * Livewire components without the service container.
 */
final class SystemSettings
{
    private const CACHE_PREFIX = 'system_settings:';

    private const CACHE_TTL = 86400; // 24 hours in seconds

    // ─── Readers ──────────────────────────────────────────────────────────────

    /**
     * Get a setting value as a raw string (the native DB storage type).
     *
     * Returns $default when the key does not exist in DB or cache.
     *
     * @param  string  $key  Setting key (e.g. 'z_factor')
     * @param  string  $default  Fallback value when key not found
     */
    public static function get(string $key, string $default = ''): string
    {
        $cacheKey = self::CACHE_PREFIX.$key;

        /** @var string|null $cached */
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        /** @var SystemSetting|null $setting */
        $setting = SystemSetting::where('key', $key)->first();

        if ($setting === null) {
            return $default;
        }

        $value = (string) $setting->value;
        Cache::put($cacheKey, $value, self::CACHE_TTL);

        return $value;
    }

    /**
     * Get a setting value cast to float.
     *
     * @param  string  $key  Setting key
     * @param  float  $default  Fallback float value
     */
    public static function getFloat(string $key, float $default = 0.0): float
    {
        $raw = self::get($key);

        return $raw !== '' ? (float) $raw : $default;
    }

    /**
     * Get a setting value cast to int.
     *
     * @param  string  $key  Setting key
     * @param  int  $default  Fallback int value
     */
    public static function getInt(string $key, int $default = 0): int
    {
        $raw = self::get($key);

        return $raw !== '' ? (int) $raw : $default;
    }

    // ─── Writers ──────────────────────────────────────────────────────────────

    /**
     * Persist a setting to the database and invalidate its cache entry.
     *
     * DB write happens first; cache is then invalidated (not updated)
     * so the next read re-fetches from DB, preventing stale cache from
     * winning over a failed DB write.
     *
     * @param  string  $key  Setting key
     * @param  string  $value  Setting value (stored as string)
     */
    public static function set(string $key, string $value): SystemSetting
    {
        /** @var SystemSetting $setting */
        $setting = SystemSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        // Invalidate so next get() re-fetches from DB
        Cache::forget(self::CACHE_PREFIX.$key);

        return $setting;
    }

    /**
     * Flush all system_settings cache entries.
     *
     * Called by SystemSettingsSeeder after initial seeding.
     * Also used in tests to clear state between test cases.
     */
    public static function flush(): void
    {
        /** @var array<string> $keys */
        $keys = SystemSetting::pluck('key')->toArray();

        foreach ($keys as $key) {
            Cache::forget(self::CACHE_PREFIX.$key);
        }
    }
}
