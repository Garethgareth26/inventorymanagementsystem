<?php

use App\Models\SystemSetting;
use App\Services\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

// Reset cache between tests to prevent bleed
beforeEach(function () {
    Cache::flush();
});

// ─── get() / set() round-trip ─────────────────────────────────────────────────

describe('SystemSettings::get and set', function () {
    it('returns the default when key does not exist', function () {
        $value = SystemSettings::get('nonexistent_key', 'my_default');

        expect($value)->toBe('my_default');
    });

    it('returns empty string as default when no default supplied', function () {
        $value = SystemSettings::get('nonexistent_key');

        expect($value)->toBe('');
    });

    it('set() persists to the database', function () {
        SystemSettings::set('z_factor', '1.65');

        $this->assertDatabaseHas('system_settings', [
            'key' => 'z_factor',
            'value' => '1.65',
        ]);
    });

    it('get() returns the value after set()', function () {
        SystemSettings::set('z_factor', '1.65');

        $value = SystemSettings::get('z_factor');

        expect($value)->toBe('1.65');
    });

    it('set() is idempotent (updateOrCreate)', function () {
        SystemSettings::set('z_factor', '1.65');
        SystemSettings::set('z_factor', '1.96');

        $count = SystemSetting::where('key', 'z_factor')->count();

        expect($count)->toBe(1)
            ->and(SystemSettings::get('z_factor'))->toBe('1.96');
    });
});

// ─── DB fallback when cache is empty ─────────────────────────────────────────

describe('SystemSettings DB fallback', function () {
    it('reads from DB when cache is empty and populates cache', function () {
        // Seed DB directly
        SystemSetting::create(['key' => 'biaya_pesan', 'value' => '75000']);
        Cache::flush(); // Ensure cache miss

        $value = SystemSettings::get('biaya_pesan');

        expect($value)->toBe('75000');

        // Cache should now be populated
        $cached = Cache::get('system_settings:biaya_pesan');
        expect($cached)->toBe('75000');
    });

    it('cache serves subsequent reads without DB hit', function () {
        SystemSettings::set('biaya_pesan', '75000');

        // Subsequent read should come from cache
        $value = SystemSettings::get('biaya_pesan');

        expect($value)->toBe('75000');
    });

    it('set() invalidates the cache so next get() re-fetches from DB', function () {
        SystemSettings::set('biaya_pesan', '75000');
        SystemSettings::get('biaya_pesan'); // populate cache

        SystemSettings::set('biaya_pesan', '80000'); // update + invalidate

        // Cache should be gone
        expect(Cache::get('system_settings:biaya_pesan'))->toBeNull();

        // Next get() fetches from DB
        $value = SystemSettings::get('biaya_pesan');
        expect($value)->toBe('80000');
    });
});

// ─── Typed getters ────────────────────────────────────────────────────────────

describe('SystemSettings typed getters', function () {
    it('getFloat returns correct float', function () {
        SystemSettings::set('z_factor', '1.65');

        $value = SystemSettings::getFloat('z_factor');

        expect($value)->toBe(1.65)
            ->and($value)->toBeFloat();
    });

    it('getFloat returns default when key missing', function () {
        $value = SystemSettings::getFloat('missing_key', 1.65);

        expect($value)->toBe(1.65);
    });

    it('getInt returns correct int', function () {
        SystemSettings::set('historical_window', '12');

        $value = SystemSettings::getInt('historical_window');

        expect($value)->toBe(12)
            ->and($value)->toBeInt();
    });

    it('getInt returns default when key missing', function () {
        $value = SystemSettings::getInt('missing_key', 12);

        expect($value)->toBe(12);
    });
});

// ─── flush() ─────────────────────────────────────────────────────────────────

describe('SystemSettings::flush', function () {
    it('flush() clears all cache entries for seeded keys', function () {
        SystemSettings::set('z_factor', '1.65');
        SystemSettings::set('biaya_pesan', '75000');

        // Warm the cache
        SystemSettings::get('z_factor');
        SystemSettings::get('biaya_pesan');

        SystemSettings::flush();

        // Both should be gone from cache
        expect(Cache::get('system_settings:z_factor'))->toBeNull()
            ->and(Cache::get('system_settings:biaya_pesan'))->toBeNull();
    });
});

// ─── Pre-seeded defaults (as defined in SystemSettingsSeeder) ─────────────────

describe('SystemSettings seeded defaults', function () {
    it('z_factor is 1.65 after seeding', function () {
        SystemSettings::set('z_factor', '1.65');

        expect(SystemSettings::getFloat('z_factor'))->toBe(1.65);
    });

    it('reflects updates on next get() call', function () {
        SystemSettings::set('z_factor', '1.65');
        SystemSettings::get('z_factor'); // warm cache

        SystemSettings::set('z_factor', '2.33');

        expect(SystemSettings::getFloat('z_factor'))->toBe(2.33);
    });
});
