<?php

namespace App\Livewire\Administration;

use App\Models\SystemSetting;
use App\Services\AuditLogger;
use App\Services\DashboardQueryService;
use App\Services\SystemSettings;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

/**
 * Livewire component to manage system settings (Owner only).
 */
final class SystemSettingsManager extends Component
{
    use AuthorizesRequests;

    // Company Profile Form state
    public string $company_name = '';

    public string $company_address = '';

    // Calculation Parameters Form state
    public float $z_factor = 1.65;

    public int $abc_threshold_a = 80;

    public int $abc_threshold_b = 95;

    public int $historical_window = 12;

    public float $biaya_pesan = 75000.0;

    public float $biaya_simpan_pct = 20.0;

    /**
     * Initialize component and load settings.
     */
    public function mount(): void
    {
        $this->authorize('manage', SystemSetting::class);

        $this->company_name = SystemSettings::get('company_name', 'CV Akuna');
        $this->company_address = SystemSettings::get('company_address', '');
        $this->z_factor = SystemSettings::getFloat('z_factor', 1.65);
        $this->abc_threshold_a = SystemSettings::getInt('abc_threshold_a', 80);
        $this->abc_threshold_b = SystemSettings::getInt('abc_threshold_b', 95);
        $this->historical_window = SystemSettings::getInt('historical_window', 12);
        $this->biaya_pesan = SystemSettings::getFloat('biaya_pesan', 75000.0);
        $this->biaya_simpan_pct = SystemSettings::getFloat('biaya_simpan', 20.0);
    }

    /**
     * Save Company Profile settings.
     */
    public function saveCompanyProfile(): void
    {
        $this->authorize('manage', SystemSetting::class);

        $this->validate([
            'company_name' => 'required|string|max:255',
            'company_address' => 'required|string|max:1000',
        ]);

        $keys = [
            'company_name' => $this->company_name,
            'company_address' => $this->company_address,
        ];

        $updatedCount = 0;
        foreach ($keys as $key => $newValue) {
            $oldValue = SystemSettings::get($key);
            if ($oldValue !== $newValue) {
                $setting = SystemSettings::set($key, $newValue);
                AuditLogger::log(
                    auth()->user(),
                    'settings.update',
                    $setting,
                    $oldValue !== '' ? ['value' => $oldValue] : null,
                    ['value' => $newValue]
                );
                $updatedCount++;
            }
        }

        if ($updatedCount > 0) {
            $this->dispatch('notify', message: 'Profil perusahaan berhasil diperbarui.', type: 'success');
        } else {
            $this->dispatch('notify', message: 'Tidak ada perubahan profil.', type: 'info');
        }
    }

    /**
     * Save Calculation Parameters settings.
     */
    public function saveCalculationParameters(): void
    {
        $this->authorize('manage', SystemSetting::class);

        $this->validate([
            'z_factor' => 'required|numeric|min:0.01|max:3',
            'abc_threshold_a' => 'required|integer|min:0|max:100',
            'abc_threshold_b' => 'required|integer|min:0|max:100',
            'historical_window' => 'required|integer|min:1|max:24',
            'biaya_pesan' => 'required|numeric|min:1',
            'biaya_simpan_pct' => 'required|numeric|min:0|max:100',
        ]);

        if ($this->abc_threshold_b <= $this->abc_threshold_a) {
            $this->addError('abc_threshold_b', 'Ambang Batas Kelas B harus lebih besar dari Kelas A.');

            return;
        }

        $keys = [
            'z_factor' => (string) $this->z_factor,
            'abc_threshold_a' => (string) $this->abc_threshold_a,
            'abc_threshold_b' => (string) $this->abc_threshold_b,
            'historical_window' => (string) $this->historical_window,
            'biaya_pesan' => (string) $this->biaya_pesan,
            'biaya_simpan' => (string) $this->biaya_simpan_pct,
        ];

        $updatedCount = 0;
        foreach ($keys as $key => $newValue) {
            $oldValue = SystemSettings::get($key);
            if ($oldValue !== $newValue) {
                $setting = SystemSettings::set($key, $newValue);
                AuditLogger::log(
                    auth()->user(),
                    'settings.update',
                    $setting,
                    $oldValue !== '' ? ['value' => $oldValue] : null,
                    ['value' => $newValue]
                );
                $updatedCount++;
            }
        }

        if ($updatedCount > 0) {
            app(DashboardQueryService::class)->invalidateCache();
            $this->dispatch('notify', message: 'Parameter kalkulasi berhasil diperbarui.', type: 'success');
        } else {
            $this->dispatch('notify', message: 'Tidak ada perubahan parameter.', type: 'info');
        }
    }

    /**
     * Reset Calculation Parameters to default values.
     */
    public function resetCalculationParameters(): void
    {
        $this->authorize('manage', SystemSetting::class);

        $defaults = [
            'z_factor' => '1.65',
            'abc_threshold_a' => '80',
            'abc_threshold_b' => '95',
            'historical_window' => '12',
            'biaya_pesan' => '75000',
            'biaya_simpan' => '20.0',
        ];

        $updatedCount = 0;
        foreach ($defaults as $key => $newValue) {
            $oldValue = SystemSettings::get($key);
            if ($oldValue !== $newValue) {
                $setting = SystemSettings::set($key, $newValue);
                AuditLogger::log(
                    auth()->user(),
                    'settings.update',
                    $setting,
                    $oldValue !== '' ? ['value' => $oldValue] : null,
                    ['value' => $newValue]
                );
                $updatedCount++;
            }
        }

        $this->z_factor = 1.65;
        $this->abc_threshold_a = 80;
        $this->abc_threshold_b = 95;
        $this->historical_window = 12;
        $this->biaya_pesan = 75000.0;
        $this->biaya_simpan_pct = 20.0;

        if ($updatedCount > 0) {
            app(DashboardQueryService::class)->invalidateCache();
            $this->dispatch('notify', message: 'Parameter kalkulasi berhasil direset ke default.', type: 'success');
        } else {
            $this->dispatch('notify', message: 'Parameter kalkulasi sudah sesuai dengan nilai default.', type: 'info');
        }
    }

    /**
     * Render the component view.
     */
    public function render()
    {
        $this->authorize('manage', SystemSetting::class);

        return view('livewire.administration.system-settings-manager')
            ->layout('components.layout.app', [
                'pageTitle' => 'System Settings',
                'pageSubtitle' => 'Pengaturan Sistem — Kelola profil perusahaan, parameter default optimasi persediaan, dan preferensi notifikasi',
            ]);
    }
}
