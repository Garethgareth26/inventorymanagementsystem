<?php

namespace App\Livewire\Purchasing;

use App\Models\PesananPembelian;
use App\Services\AuditLogger;
use App\Services\DashboardQueryService;
use App\Services\StockMutationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * PurchaseOrderDetail Component.
 *
 * @property-read PesananPembelian $po
 */
class PurchaseOrderDetail extends Component
{
    use AuthorizesRequests;

    public int $poId;

    // Transition variables
    public string $tanggal_terima = '';

    public bool $isReceiveModalOpen = false;

    /**
     * Mount component.
     */
    public function mount(PesananPembelian $po): void
    {
        $this->authorize('view', $po);
        $this->poId = $po->id;
        $this->tanggal_terima = now()->toDateString();
    }

    /**
     * Computed property to resolve the PO model.
     */
    #[Computed]
    public function po(): PesananPembelian
    {
        return PesananPembelian::with(['bahanBaku', 'supplier', 'dicatatOleh'])->findOrFail($this->poId);
    }

    /**
     * Transition PO to "Dalam Proses".
     */
    public function processOrder(): void
    {
        $po = $this->po;
        $this->authorize('update', $po);

        if ($po->status !== PesananPembelian::STATUS_MENUNGGU) {
            $this->dispatch('notify', message: 'Hanya pesanan berstatus Menunggu yang dapat diproses.', type: 'danger');

            return;
        }

        DB::transaction(function () use ($po) {
            $oldValues = $po->toArray();
            $po->update([
                'status' => PesananPembelian::STATUS_DALAM_PROSES,
            ]);

            AuditLogger::log(
                auth()->user(),
                'po.process',
                $po,
                $oldValues,
                $po->toArray()
            );

            app(DashboardQueryService::class)->invalidateCache();
        });

        $this->dispatch('notify', message: 'Status PO berhasil diubah menjadi Dalam Proses.', type: 'success');
    }

    /**
     * Transition PO to "Dibatalkan".
     */
    public function cancelOrder(): void
    {
        $po = $this->po;
        $this->authorize('delete', $po);

        if ($po->status !== PesananPembelian::STATUS_MENUNGGU) {
            $this->dispatch('notify', message: 'Hanya pesanan berstatus Menunggu yang dapat dibatalkan.', type: 'danger');

            return;
        }

        DB::transaction(function () use ($po) {
            $oldValues = $po->toArray();
            $po->update([
                'status' => PesananPembelian::STATUS_DIBATALKAN,
            ]);

            AuditLogger::log(
                auth()->user(),
                'po.cancel',
                $po,
                $oldValues,
                $po->toArray()
            );

            app(DashboardQueryService::class)->invalidateCache();
        });

        $this->dispatch('notify', message: 'Pesanan Pembelian berhasil dibatalkan.', type: 'success');
    }

    /**
     * Show modal to finalize receipt.
     */
    public function openReceiveModal(): void
    {
        $po = $this->po;
        $this->authorize('update', $po);

        if ($po->status !== PesananPembelian::STATUS_DALAM_PROSES) {
            $this->dispatch('notify', message: 'Hanya pesanan berstatus Dalam Proses yang dapat diterima.', type: 'danger');

            return;
        }

        $this->resetErrorBag();
        $this->tanggal_terima = now()->toDateString();
        $this->isReceiveModalOpen = true;
        $this->dispatch('open-modal', 'po-receive-modal');
    }

    /**
     * finalize PO receipt, incrementing stock and changing status to "Diterima".
     */
    public function receiveOrder(StockMutationService $mutationService)
    {
        $po = $this->po;
        $this->authorize('update', $po);

        if ($po->status !== PesananPembelian::STATUS_DALAM_PROSES) {
            $this->dispatch('notify', message: 'Hanya pesanan berstatus Dalam Proses yang dapat diterima.', type: 'danger');

            return;
        }

        $this->validate([
            'tanggal_terima' => 'required|date|after_or_equal:po.tanggal_pesan',
        ], [
            'tanggal_terima.after_or_equal' => 'Tanggal terima tidak boleh sebelum tanggal pesan.',
        ]);

        DB::transaction(function () use ($mutationService, $po) {
            $oldValues = $po->toArray();

            // 1. Record stock receipt mutation
            $mutationService->recordPoReceipt(
                bahanBaku: $po->bahanBaku,
                jumlah: (float) $po->jumlah,
                tanggal: $this->tanggal_terima,
                actor: auth()->user(),
                poId: $po->id
            );

            // 2. Update PO status & date
            $po->update([
                'status' => PesananPembelian::STATUS_DITERIMA,
                'tanggal_terima' => $this->tanggal_terima,
            ]);

            // 3. Log high-level audit
            AuditLogger::log(
                auth()->user(),
                'po.receive',
                $po,
                $oldValues,
                $po->toArray()
            );

            app(DashboardQueryService::class)->invalidateCache();
        });

        $this->isReceiveModalOpen = false;
        $this->dispatch('close-modal', 'po-receive-modal');
        $this->dispatch('notify', message: 'Purchase Order berhasil diterima dan stok terupdate.', type: 'success');
    }

    /**
     * Render view.
     */
    public function render()
    {
        $po = $this->po;

        return view('livewire.purchasing.purchase-order-detail', [
            'po' => $po,
            'mutation' => $po->status === 'Diterima' ? $po->mutasiStok()->first() : null,
        ])->layout('components.layout.app', [
            'pageTitle' => 'Detail Purchase Order',
            'pageSubtitle' => 'Informasi lengkap dan status pesanan pembelian',
        ]);
    }
}
