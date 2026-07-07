<?php

namespace App\Livewire\Reports;

use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Livewire component to configure and dynamically download PDF reports.
 */
class ReportGenerator extends Component
{
    public string $reportType = 'valuasi_aset';

    public string $startDate = '';

    public string $endDate = '';

    public function mount(): void
    {
        $this->startDate = Carbon::today()->startOfMonth()->toDateString();
        $this->endDate = Carbon::today()->toDateString();
    }

    public function render(): View
    {
        return view('livewire.reports.index')
            ->layout('components.layout.app', [
                'pageTitle' => 'Reports',
                'pageSubtitle' => 'Generator Laporan — Konfigurasi dan unduh laporan PDF inventori, pembelian, dan produksi',
            ]);
    }

    /**
     * Compile report data and download streamed PDF directly in memory.
     */
    public function generate(ReportService $service)
    {
        $this->validate([
            'reportType' => 'required|in:valuasi_aset,performa_supplier,mutasi_bulanan',
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
        ]);

        $typeLabel = '';
        $data = [];
        $viewName = '';

        if ($this->reportType === 'valuasi_aset') {
            $typeLabel = 'Valuasi_Aset';
            $data = $service->generateValuasiAset($this->endDate);
            $viewName = 'pdf.valuasi_aset';
        } elseif ($this->reportType === 'performa_supplier') {
            $typeLabel = 'Performa_Supplier';
            $data = $service->generatePerformaSupplier($this->startDate, $this->endDate);
            $viewName = 'pdf.performa_supplier';
        } elseif ($this->reportType === 'mutasi_bulanan') {
            $typeLabel = 'Mutasi_Bulanan';
            $data = $service->generateMutasiBulanan($this->startDate, $this->endDate);
            $viewName = 'pdf.mutasi_bulanan';
        }

        $pdf = Pdf::loadView($viewName, $data);

        $filename = 'laporan_'.$typeLabel.'_'.$this->startDate.'_to_'.$this->endDate.'.pdf';

        $this->dispatch('notify', message: 'Laporan berhasil dibuat.', type: 'success');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }
}
