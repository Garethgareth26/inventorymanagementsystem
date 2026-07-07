<?php

namespace App\Livewire\Purchasing;

use App\Models\PesananPembelian;
use App\Models\Supplier;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * PurchaseOrders List Component.
 */
class PurchaseOrders extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    public string $filterStatus = '';

    public string $filterSupplier = '';

    public string $filterStartDate = '';

    public string $filterEndDate = '';

    /**
     * Reset page on search or filter update.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFilterSupplier(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStartDate(): void
    {
        $this->resetPage();
    }

    public function updatedFilterEndDate(): void
    {
        $this->resetPage();
    }

    /**
     * Render view.
     */
    public function render()
    {
        $this->authorize('viewAny', PesananPembelian::class);

        $query = PesananPembelian::query()->with(['bahanBaku', 'supplier', 'dicatatOleh']);

        if (! empty($this->search)) {
            $query->where('kode_po', 'like', '%'.$this->search.'%');
        }

        if (! empty($this->filterStatus)) {
            $query->where('status', $this->filterStatus);
        }

        if (! empty($this->filterSupplier)) {
            $query->where('supplier_id', $this->filterSupplier);
        }

        if (! empty($this->filterStartDate)) {
            $query->whereDate('tanggal_pesan', '>=', $this->filterStartDate);
        }

        if (! empty($this->filterEndDate)) {
            $query->whereDate('tanggal_pesan', '<=', $this->filterEndDate);
        }

        $purchaseOrders = $query->orderBy('tanggal_pesan', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(25);

        $suppliers = Supplier::orderBy('nama')->get();

        return view('livewire.purchasing.purchase-orders', [
            'purchaseOrders' => $purchaseOrders,
            'suppliers' => $suppliers,
        ])->layout('components.layout.app', [
            'pageTitle' => 'Daftar Pesanan Pembelian',
            'pageSubtitle' => 'Kelola pesanan pembelian (Purchase Order) bahan baku',
        ]);
    }
}
