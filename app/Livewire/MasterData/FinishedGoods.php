<?php

namespace App\Livewire\MasterData;

use App\Models\Bom;
use App\Models\FinishedGood as FinishedGoodModel;
use App\Models\MutasiStok;
use App\Models\ProductionEntry;
use App\Services\AuditLogger;
use App\Services\DashboardQueryService;
use App\Services\StockMutationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * FinishedGoods CRUD component.
 */
class FinishedGoods extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    // Form fields
    public ?int $fgId = null;

    public string $kode = '';

    public string $nama = '';

    public string $satuan = '';

    public float $stok_saat_ini = 0.0;

    // UI flags
    public bool $isModalOpen = false;

    public ?int $confirmingDeletionId = null;

    /**
     * Reset pagination when search query updates.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Render the component view.
     */
    public function render()
    {
        $this->authorize('viewAny', FinishedGoodModel::class);

        $query = FinishedGoodModel::query()->withCount('bomLines');

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('kode', 'like', '%'.$this->search.'%')
                    ->orWhere('nama', 'like', '%'.$this->search.'%');
            });
        }

        $goods = $query->orderBy('kode')->paginate(25);

        return view('livewire.master-data.finished-goods', [
            'goods' => $goods,
        ])->layout('components.layout.app', [
            'pageTitle' => 'Daftar Barang Jadi',
            'pageSubtitle' => 'Kelola barang jadi hasil produksi CV Akuna',
        ]);
    }

    /**
     * Open modal to create a finished good.
     */
    public function openCreateModal(): void
    {
        $this->authorize('create', FinishedGoodModel::class);

        $this->resetErrorBag();
        $this->resetForm();

        $this->isModalOpen = true;
        $this->dispatch('open-modal', 'fg-form-modal');
    }

    /**
     * Open modal to edit an existing finished good.
     */
    public function openEditModal(int $id): void
    {
        $this->resetErrorBag();
        $this->resetForm();

        $fg = FinishedGoodModel::findOrFail($id);
        $this->authorize('update', $fg);

        $this->fgId = $fg->id;
        $this->kode = $fg->kode;
        $this->nama = $fg->nama;
        $this->satuan = $fg->satuan;
        $this->stok_saat_ini = (float) $fg->stok_saat_ini;

        $this->isModalOpen = true;
        $this->dispatch('open-modal', 'fg-form-modal');
    }

    /**
     * Close modal.
     */
    public function closeModal(): void
    {
        $this->isModalOpen = false;
        $this->dispatch('close-modal', 'fg-form-modal');
        $this->resetForm();
    }

    /**
     * Save record (Create or Update).
     */
    public function save(): void
    {
        if ($this->fgId) {
            $fg = FinishedGoodModel::findOrFail($this->fgId);
            $this->authorize('update', $fg);
        } else {
            $this->authorize('create', FinishedGoodModel::class);
        }

        $rules = [
            'kode' => 'required|string|max:50|unique:finished_goods,kode,'.($this->fgId ?? 'NULL'),
            'nama' => 'required|string|max:255',
            'satuan' => 'required|string|max:20',
        ];

        if (! $this->fgId) {
            $rules['stok_saat_ini'] = 'required|numeric|min:0';
        }

        $validated = $this->validate($rules);

        if ($this->fgId) {
            $fg = FinishedGoodModel::findOrFail($this->fgId);
            $oldValues = $fg->toArray();

            // Exclude stok_saat_ini from update to prevent direct mutations
            unset($validated['stok_saat_ini']);

            $fg->update($validated);

            AuditLogger::log(
                auth()->user(),
                'finished-good.update',
                $fg,
                $oldValues,
                $fg->toArray()
            );

            $this->dispatch('notify', message: 'Barang jadi berhasil diperbarui.', type: 'success');
        } else {
            $initialStock = (float) $this->stok_saat_ini;
            $validated['stok_saat_ini'] = 0.0;

            $fg = FinishedGoodModel::create($validated);

            // Record initial stock mutation if requested
            if ($initialStock > 0.0) {
                app(StockMutationService::class)->recordMutation(
                    itemType: 'finished_good',
                    itemId: $fg->id,
                    jenisMutasi: MutasiStok::JENIS_MASUK,
                    jumlah: $initialStock,
                    tanggal: now()->toDateString(),
                    actor: auth()->user(),
                    sumber: MutasiStok::SUMBER_MANUAL,
                    keterangan: 'Pencatatan stok awal barang jadi'
                );
            }

            AuditLogger::log(
                auth()->user(),
                'finished-good.create',
                $fg,
                null,
                $fg->toArray()
            );

            $this->dispatch('notify', message: 'Barang jadi baru berhasil ditambahkan.', type: 'success');
        }

        app(DashboardQueryService::class)->invalidateCache();
        $this->closeModal();
    }

    /**
     * Set the ID for the deletion confirmation modal.
     */
    public function confirmDelete(int $id): void
    {
        $fg = FinishedGoodModel::findOrFail($id);
        $this->authorize('delete', $fg);

        $this->confirmingDeletionId = $id;
        $this->dispatch('toggle-modal', name: 'delete-confirm', show: true);
    }

    /**
     * Delete finished good record if not referenced in BOM or Production.
     */
    public function delete(): void
    {
        if (! $this->confirmingDeletionId) {
            return;
        }

        $fg = FinishedGoodModel::findOrFail($this->confirmingDeletionId);
        $this->authorize('delete', $fg);

        // Check reference constraints
        $linkedBoms = Bom::where('finished_goods_id', $fg->id)->count();
        $linkedProduction = ProductionEntry::where('finished_goods_id', $fg->id)->count();

        if ($linkedBoms > 0) {
            $this->dispatch('notify', message: "Gagal menghapus: Barang jadi ini memiliki {$linkedBoms} resep BOM yang aktif.", type: 'danger');
        } elseif ($linkedProduction > 0) {
            $this->dispatch('notify', message: "Gagal menghapus: Barang jadi ini terhubung dengan {$linkedProduction} entri produksi.", type: 'danger');
        } else {
            $oldValues = $fg->toArray();
            $fg->delete();

            AuditLogger::log(
                auth()->user(),
                'finished-good.delete',
                $fg,
                $oldValues,
                null
            );

            app(DashboardQueryService::class)->invalidateCache();
            $this->dispatch('notify', message: 'Barang jadi berhasil dihapus.', type: 'success');
        }

        $this->confirmingDeletionId = null;
        $this->dispatch('toggle-modal', name: 'delete-confirm', show: false);
    }

    /**
     * Reset form fields.
     */
    private function resetForm(): void
    {
        $this->fgId = null;
        $this->kode = '';
        $this->nama = '';
        $this->satuan = '';
        $this->stok_saat_ini = 0.0;
    }
}
