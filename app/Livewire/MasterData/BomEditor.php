<?php

namespace App\Livewire\MasterData;

use App\Models\BahanBaku as BahanBakuModel;
use App\Models\Bom;
use App\Models\FinishedGood;
use App\Services\BomService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

/**
 * BomEditor component to manage recipe lines for a FinishedGood.
 */
class BomEditor extends Component
{
    use AuthorizesRequests;

    public FinishedGood $finishedGood;

    /**
     * @var array<int, array{bahan_baku_id: int|string, qty_per_unit: float, satuan: string}>
     */
    public array $lines = [];

    /**
     * Mount the component.
     */
    public function mount(FinishedGood $finishedGood): void
    {
        $this->authorize('viewAny', Bom::class);
        $this->finishedGood = $finishedGood;

        // Load existing BOM lines
        foreach ($this->finishedGood->bomLines()->with('bahanBaku')->get() as $line) {
            $this->lines[] = [
                'bahan_baku_id' => $line->bahan_baku_id,
                'qty_per_unit' => (float) $line->qty_per_unit,
                'satuan' => $line->bahanBaku->satuan ?? '—',
            ];
        }
    }

    /**
     * Update unit of measure when bahan_baku_id is selected.
     */
    public function updatedLines(mixed $value, string $key): void
    {
        if (str_contains($key, 'bahan_baku_id')) {
            // Key format is like "lines.0.bahan_baku_id"
            $parts = explode('.', $key);
            $index = (int) $parts[1];

            $bbId = (int) $value;
            $bb = BahanBakuModel::find($bbId);

            if ($bb) {
                $this->lines[$index]['satuan'] = $bb->satuan;
            } else {
                $this->lines[$index]['satuan'] = '—';
            }
        }
    }

    /**
     * Add an empty row.
     */
    public function addLine(): void
    {
        $this->authorize('create', Bom::class);

        $this->lines[] = [
            'bahan_baku_id' => '',
            'qty_per_unit' => 1.0,
            'satuan' => '—',
        ];
    }

    /**
     * Remove a row.
     */
    public function removeLine(int $index): void
    {
        $this->authorize('delete', Bom::class);

        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    /**
     * Save the recipe composition.
     */
    public function save(BomService $bomService)
    {
        $this->authorize('update', Bom::class);

        // Validation rules
        $rules = [
            'lines' => 'required|array|min:1',
            'lines.*.bahan_baku_id' => 'required|integer|exists:bahan_baku,id',
            'lines.*.qty_per_unit' => 'required|numeric|gt:0',
        ];

        $customMessages = [
            'lines.required' => 'Resep BOM tidak boleh kosong.',
            'lines.min' => 'Harus ada minimal 1 bahan baku.',
            'lines.*.bahan_baku_id.required' => 'Bahan baku harus dipilih.',
            'lines.*.qty_per_unit.required' => 'Jumlah pemakaian harus diisi.',
            'lines.*.qty_per_unit.gt' => 'Jumlah pemakaian harus lebih besar dari 0.',
        ];

        $this->validate($rules, $customMessages);

        // Validate duplicates
        $ids = array_column($this->lines, 'bahan_baku_id');
        if (count($ids) !== count(array_unique($ids))) {
            $this->addError('lines', 'Bahan baku duplikat tidak diperbolehkan dalam satu resep.');

            return;
        }

        // Clean ingredients format
        $ingredients = [];
        foreach ($this->lines as $line) {
            $ingredients[] = [
                'bahan_baku_id' => (int) $line['bahan_baku_id'],
                'qty_per_unit' => (float) $line['qty_per_unit'],
            ];
        }

        try {
            $bomService->saveBom($this->finishedGood, $ingredients, auth()->user());

            $this->dispatch('notify', message: 'Resep BOM berhasil disimpan.', type: 'success');

            return redirect()->route('barang_jadi.index');
        } catch (\Exception $e) {
            $this->addError('lines', $e->getMessage());
        }
    }

    /**
     * Render view.
     */
    public function render()
    {
        $materials = BahanBakuModel::orderBy('nama')->get();

        return view('livewire.master-data.bom-editor', [
            'materials' => $materials,
        ])->layout('components.layout.app', [
            'pageTitle' => 'BOM Editor',
            'pageSubtitle' => 'Kelola resep komposisi bahan baku untuk '.$this->finishedGood->nama,
        ]);
    }
}
