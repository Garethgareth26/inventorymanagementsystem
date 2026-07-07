<?php

namespace App\Livewire\MasterData;

use App\Models\BahanBaku as BahanBakuModel;
use App\Models\Bom;
use App\Models\InventoryParameter;
use App\Models\MutasiStok;
use App\Models\Supplier;
use App\Services\AuditLogger;
use App\Services\CalculationEngine;
use App\Services\DashboardQueryService;
use App\Services\StockMutationService;
use App\Services\SystemSettings;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * BahanBaku CRUD component.
 */
class BahanBaku extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    public string $filterAbc = '';

    public string $filterSupplier = '';

    // Form fields
    public ?int $materialId = null;

    public string $kode = '';

    public string $nama = '';

    public string $satuan = '';

    public float $stok_saat_ini = 0.0;

    public ?int $supplier_id = null;

    public float $harga_satuan = 0.0;

    public int $lead_time_hari = 1;

    // UI flags
    public bool $isModalOpen = false;

    public ?int $confirmingDeletionId = null;

    /**
     * Reset pagination when search or filters update.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterAbc(): void
    {
        $this->resetPage();
    }

    public function updatedFilterSupplier(): void
    {
        $this->resetPage();
    }

    /**
     * Render the component view.
     */
    public function render()
    {
        $this->authorize('viewAny', BahanBakuModel::class);

        $query = BahanBakuModel::query()->with(['supplier', 'inventoryParameter']);

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('kode', 'like', '%'.$this->search.'%')
                    ->orWhere('nama', 'like', '%'.$this->search.'%');
            });
        }

        if (! empty($this->filterSupplier)) {
            $query->where('supplier_id', $this->filterSupplier);
        }

        if (! empty($this->filterAbc)) {
            $query->whereHas('inventoryParameter', function ($q) {
                // Class ABC is determined by calculations; we will need to calculate ABC dynamically
                // or check the parameter class. Wait! Let's classify ABC dynamically for filtering
                // since ABC class is computed. Let's see: we can filter materials in PHP,
                // or since we have a small dataset (10 items), we can just fetch and filter.
                // Wait! Let's do dynamic ABC calculation of all materials and filter the IDs.
            });
        }

        $allMaterials = $query->orderBy('kode')->get();

        // Perform ABC classification for list badges and filtering
        $abcInput = [];
        foreach ($allMaterials as $bb) {
            $kebutuhanTahunan = (float) ($bb->inventoryParameter->kebutuhan_tahunan ?? 0.0);
            $abcInput[] = [
                'id' => $bb->id,
                'annual_usage_value' => $kebutuhanTahunan * (float) $bb->harga_satuan,
            ];
        }

        $engine = app(CalculationEngine::class);
        $abcMap = $engine->classifyAbc($abcInput);

        // Map class badge to items
        foreach ($allMaterials as $bb) {
            $bb->abc_class = $abcMap[$bb->id] ?? '—';
        }

        // Apply ABC filter in PHP if specified
        if (! empty($this->filterAbc)) {
            $allMaterials = $allMaterials->filter(fn ($bb) => $bb->abc_class === $this->filterAbc);
        }

        // Paginate manually since we filtered in PHP
        $total = $allMaterials->count();
        $perPage = 25;
        $page = $this->getPage();
        $items = $allMaterials->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );

        $suppliers = Supplier::where('is_active', true)->orderBy('nama')->get();

        return view('livewire.master-data.bahan-baku', [
            'materials' => $paginator,
            'suppliers' => $suppliers,
        ])->layout('components.layout.app', [
            'pageTitle' => 'Daftar Bahan Baku',
            'pageSubtitle' => 'Kelola persediaan bahan baku dan parameter logistik',
        ]);
    }

    /**
     * Open modal to create a raw material.
     */
    public function openCreateModal(): void
    {
        $this->authorize('create', BahanBakuModel::class);

        $this->resetErrorBag();
        $this->resetForm();

        $this->isModalOpen = true;
    }

    /**
     * Open modal to edit an existing raw material.
     */
    public function openEditModal(int $id): void
    {
        $this->resetErrorBag();
        $this->resetForm();

        $material = BahanBakuModel::findOrFail($id);
        $this->authorize('update', $material);

        $this->materialId = $material->id;
        $this->kode = $material->kode;
        $this->nama = $material->nama;
        $this->satuan = $material->satuan;
        $this->stok_saat_ini = (float) $material->stok_saat_ini;
        $this->supplier_id = $material->supplier_id;
        $this->harga_satuan = (float) $material->harga_satuan;
        $this->lead_time_hari = (int) $material->lead_time_hari;

        $this->isModalOpen = true;
    }

    /**
     * Close modal.
     */
    public function closeModal(): void
    {
        $this->isModalOpen = false;
        $this->resetForm();
    }

    /**
     * Save record (Create or Update).
     */
    public function save(): void
    {
        if ($this->materialId) {
            $material = BahanBakuModel::findOrFail($this->materialId);
            $this->authorize('update', $material);
        } else {
            $this->authorize('create', BahanBakuModel::class);
        }

        $rules = [
            'kode' => 'required|string|max:50|unique:bahan_baku,kode,'.($this->materialId ?? 'NULL'),
            'nama' => 'required|string|max:255',
            'satuan' => 'required|string|max:20',
            'supplier_id' => 'required|exists:suppliers,id',
            'harga_satuan' => 'required|numeric|min:0',
            'lead_time_hari' => 'required|integer|min:1',
        ];

        // Only validate initial stock for creation
        if (! $this->materialId) {
            $rules['stok_saat_ini'] = 'required|numeric|min:0';
        }
        $validated = $this->validate($rules);

        // Custom validation for lead_time_hari based on supplier location
        $supplier = Supplier::find($this->supplier_id);
        if ($supplier) {
            $alamat = strtolower($supplier->alamat ?? '');
            
            $isJakarta = str_contains($alamat, 'jakarta');
            $isJogja = str_contains($alamat, 'yogyakarta') || 
                       str_contains($alamat, 'jogja') ||
                       str_contains($alamat, 'bantul') ||
                       str_contains($alamat, 'sleman') ||
                       str_contains($alamat, 'gunungkidul') ||
                       str_contains($alamat, 'kulon progo');

            if ($isJakarta) {
                if ($this->lead_time_hari > 2) {
                    $this->addError('lead_time_hari', 'Lead time untuk supplier area Jakarta maksimal 2 hari.');
                    return;
                }
            } elseif ($isJogja) {
                if ($this->lead_time_hari > 1) {
                    $this->addError('lead_time_hari', 'Lead time untuk supplier area Yogyakarta & sekitarnya maksimal 1 hari.');
                    return;
                }
            } else {
                if ($this->lead_time_hari < 3 || $this->lead_time_hari > 5) {
                    $this->addError('lead_time_hari', 'Lead time untuk supplier di luar Jakarta/Yogyakarta harus 3-5 hari.');
                    return;
                }
            }
        }

        if ($this->materialId) {
            $material = BahanBakuModel::findOrFail($this->materialId);
            $oldValues = $material->toArray();

            // Exclude stok_saat_ini from update to prevent direct mutations
            unset($validated['stok_saat_ini']);

            $material->update($validated);

            AuditLogger::log(
                auth()->user(),
                'material.update',
                $material,
                $oldValues,
                $material->toArray()
            );

            $this->dispatch('notify', message: 'Bahan baku berhasil diperbarui.', type: 'success');
        } else {
            // Force stock to 0 initially during DB insert, we write mutation to increment it
            $initialStock = (float) $this->stok_saat_ini;
            $validated['stok_saat_ini'] = 0.0;

            $material = BahanBakuModel::create($validated);

            // Seeding default parameters
            $settings = app(SystemSettings::class);
            InventoryParameter::create([
                'bahan_baku_id' => $material->id,
                'kebutuhan_tahunan' => 0.0,
                'standar_deviasi_harian' => 0.0,
                'biaya_pesan' => $settings->getFloat('biaya_pesan', 75000.0),
                'biaya_simpan_persen' => $settings->getFloat('biaya_simpan', 20.0) / 100.0,
                'eoq' => 0.0,
                'safety_stock' => 0.0,
                'reorder_point' => 0.0,
                'z_factor' => $settings->getFloat('z_factor', 1.65),
                'historical_window_months' => $settings->getInt('historical_window', 12),
            ]);

            // Seeding initial stock mutation if requested
            if ($initialStock > 0.0) {
                app(StockMutationService::class)->recordMutation(
                    itemType: 'bahan_baku',
                    itemId: $material->id,
                    jenisMutasi: MutasiStok::JENIS_MASUK,
                    jumlah: $initialStock,
                    tanggal: now()->toDateString(),
                    actor: auth()->user(),
                    sumber: MutasiStok::SUMBER_MANUAL,
                    keterangan: 'Pencatatan stok awal bahan baku'
                );
            }

            AuditLogger::log(
                auth()->user(),
                'material.create',
                $material,
                null,
                $material->toArray()
            );

            $this->dispatch('notify', message: 'Bahan baku baru berhasil ditambahkan.', type: 'success');
        }

        app(DashboardQueryService::class)->invalidateCache();
        $this->closeModal();
    }

    /**
     * Set the ID for the deletion confirmation modal.
     */
    public function confirmDelete(int $id): void
    {
        $material = BahanBakuModel::findOrFail($id);
        $this->authorize('delete', $material);

        $this->confirmingDeletionId = $id;
        $this->dispatch('toggle-modal', name: 'delete-confirm', show: true);
    }

    /**
     * Delete raw material record if not referenced in any BOM.
     */
    public function delete(): void
    {
        if (! $this->confirmingDeletionId) {
            return;
        }

        $material = BahanBakuModel::findOrFail($this->confirmingDeletionId);
        $this->authorize('delete', $material);

        // Check reference constraints
        $linkedBoms = Bom::where('bahan_baku_id', $material->id)->count();

        if ($linkedBoms > 0) {
            $this->dispatch('notify', message: "Gagal menghapus: Bahan baku ini terhubung dengan {$linkedBoms} item BOM.", type: 'danger');
        } else {
            $oldValues = $material->toArray();

            // Delete associated parameters first
            $material->inventoryParameter()->delete();
            $material->delete();

            AuditLogger::log(
                auth()->user(),
                'material.delete',
                $material,
                $oldValues,
                null
            );

            app(DashboardQueryService::class)->invalidateCache();
            $this->dispatch('notify', message: 'Bahan baku berhasil dihapus.', type: 'success');
        }

        $this->confirmingDeletionId = null;
        $this->dispatch('toggle-modal', name: 'delete-confirm', show: false);
    }

    /**
     * Reset form fields.
     */
    private function resetForm(): void
    {
        $this->materialId = null;
        $this->kode = '';
        $this->nama = '';
        $this->satuan = '';
        $this->stok_saat_ini = 0.0;
        $this->supplier_id = null;
        $this->harga_satuan = 0.0;
        $this->lead_time_hari = 1;
    }
}
