<?php

namespace App\Livewire\MasterData;

use App\Models\BahanBaku;
use App\Models\Supplier;
use App\Services\AuditLogger;
use App\Services\DashboardQueryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Suppliers CRUD component.
 */
class Suppliers extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    // Form fields
    public ?int $supplierId = null;

    public string $kode = '';

    public string $nama = '';

    public string $alamat = '';

    public string $kontak = '';

    public bool $is_active = true;

    // UI flags
    public bool $isModalOpen = false;

    public ?int $confirmingDeletionId = null;

    /**
     * Reset pagination when search query updates.
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Render the component view.
     */
    public function render()
    {
        $this->authorize('viewAny', Supplier::class);

        $query = Supplier::query();

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('kode', 'like', '%'.$this->search.'%')
                    ->orWhere('nama', 'like', '%'.$this->search.'%');
            });
        }

        $suppliers = $query->orderBy('kode')->paginate(25);

        return view('livewire.master-data.suppliers', [
            'suppliers' => $suppliers,
        ])->layout('components.layout.app', [
            'pageTitle' => 'Daftar Supplier',
            'pageSubtitle' => 'Kelola informasi supplier CV Akuna',
        ]);
    }

    /**
     * Open modal to create a new supplier.
     */
    public function openCreateModal(): void
    {
        $this->authorize('create', Supplier::class);

        $this->resetErrorBag();
        $this->resetForm();

        $this->isModalOpen = true;
    }

    /**
     * Open modal to edit an existing supplier.
     */
    public function openEditModal(int $id): void
    {
        $this->resetErrorBag();
        $this->resetForm();

        $supplier = Supplier::findOrFail($id);
        $this->authorize('update', $supplier);

        $this->supplierId = $supplier->id;
        $this->kode = $supplier->kode;
        $this->nama = $supplier->nama;
        $this->alamat = $supplier->alamat;
        $this->kontak = $supplier->kontak;
        $this->is_active = $supplier->is_active;

        $this->isModalOpen = true;
    }

    /**
     * Close input modal.
     */
    public function closeModal(): void
    {
        $this->isModalOpen = false;
        $this->resetForm();
    }

    /**
     * Save supplier record (Create or Update).
     */
    public function save(): void
    {
        if ($this->supplierId) {
            $supplier = Supplier::findOrFail($this->supplierId);
            $this->authorize('update', $supplier);
        } else {
            $this->authorize('create', Supplier::class);
        }

        $rules = [
            'kode' => 'required|string|max:50|unique:suppliers,kode,'.($this->supplierId ?? 'NULL'),
            'nama' => 'required|string|max:255',
            'alamat' => 'required|string|max:500',
            'kontak' => 'required|string|max:100',
        ];

        $validated = $this->validate($rules);
        $validated['is_active'] = $this->is_active;

        if ($this->supplierId) {
            $supplier = Supplier::findOrFail($this->supplierId);
            $oldValues = $supplier->toArray();
            $supplier->update($validated);

            AuditLogger::log(
                auth()->user(),
                'supplier.update',
                $supplier,
                $oldValues,
                $supplier->toArray()
            );

            $this->dispatch('notify', message: 'Supplier berhasil diperbarui.', type: 'success');
        } else {
            $supplier = Supplier::create($validated);

            AuditLogger::log(
                auth()->user(),
                'supplier.create',
                $supplier,
                null,
                $supplier->toArray()
            );

            $this->dispatch('notify', message: 'Supplier baru berhasil ditambahkan.', type: 'success');
        }

        app(DashboardQueryService::class)->invalidateCache();
        $this->closeModal();
    }

    /**
     * Set the ID for the deletion confirmation modal.
     */
    public function confirmDelete(int $id): void
    {
        $supplier = Supplier::findOrFail($id);
        $this->authorize('delete', $supplier);

        $this->confirmingDeletionId = $id;
        $this->dispatch('toggle-modal', name: 'delete-confirm', show: true);
    }

    /**
     * Delete supplier record if not referenced in bahan_baku.
     */
    public function delete(): void
    {
        if (! $this->confirmingDeletionId) {
            return;
        }

        $supplier = Supplier::findOrFail($this->confirmingDeletionId);
        $this->authorize('delete', $supplier);

        // Check reference constraints
        $linkedCount = BahanBaku::where('supplier_id', $supplier->id)->count();

        if ($linkedCount > 0) {
            $this->dispatch('notify', message: "Gagal menghapus: Supplier ini terhubung dengan {$linkedCount} bahan baku.", type: 'danger');
        } else {
            $oldValues = $supplier->toArray();
            $supplier->delete();

            AuditLogger::log(
                auth()->user(),
                'supplier.delete',
                $supplier,
                $oldValues,
                null
            );

            app(DashboardQueryService::class)->invalidateCache();
            $this->dispatch('notify', message: 'Supplier berhasil dihapus.', type: 'success');
        }

        $this->confirmingDeletionId = null;
        $this->dispatch('toggle-modal', name: 'delete-confirm', show: false);
    }

    /**
     * Reset form fields.
     */
    private function resetForm(): void
    {
        $this->supplierId = null;
        $this->kode = '';
        $this->nama = '';
        $this->alamat = '';
        $this->kontak = '';
        $this->is_active = true;
    }
}
