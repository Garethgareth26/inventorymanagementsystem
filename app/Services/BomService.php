<?php

namespace App\Services;

use App\Models\BahanBaku;
use App\Models\FinishedGood;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Service to handle transactional recipe modifications (BOM editing).
 */
final class BomService
{
    /**
     * Atomically save the Bill of Materials recipe for a finished good.
     *
     * @param  FinishedGood  $fg  Finished good model.
     * @param  array<int, array{bahan_baku_id: int, qty_per_unit: float}>  $ingredients  List of recipe ingredients.
     * @param  User  $actor  Performing user.
     *
     * @throws InvalidArgumentException If duplicate bahan_baku_id is found.
     */
    public function saveBom(FinishedGood $fg, array $ingredients, User $actor): void
    {
        // Validate against empty ingredients list
        if (empty($ingredients)) {
            throw new InvalidArgumentException('Resep BOM tidak boleh kosong.');
        }

        // Validate duplicates
        $ids = array_column($ingredients, 'bahan_baku_id');
        if (count($ids) !== count(array_unique($ids))) {
            throw new InvalidArgumentException('Bahan baku duplikat tidak diperbolehkan dalam satu resep BOM.');
        }

        // Validate positive quantities
        foreach ($ingredients as $ingredient) {
            if ($ingredient['qty_per_unit'] <= 0.0) {
                throw new InvalidArgumentException('Jumlah bahan baku harus lebih dari 0.');
            }
        }

        DB::transaction(function () use ($fg, $ingredients, $actor) {
            // Load old BOM lines for audit trail snapshot
            $oldBoms = $fg->bomLines()->get()->toArray();

            // Atomic replace: Delete existing lines
            $fg->bomLines()->delete();

            // Insert new lines
            foreach ($ingredients as $item) {
                $material = BahanBaku::findOrFail($item['bahan_baku_id']);
                $fg->bomLines()->create([
                    'bahan_baku_id' => $item['bahan_baku_id'],
                    'qty_per_unit' => (float) $item['qty_per_unit'],
                    'satuan' => $material->satuan,
                ]);
            }

            // Log changes
            AuditLogger::log(
                $actor,
                'bom.save',
                $fg,
                ['bom' => $oldBoms],
                ['bom' => $fg->bomLines()->get()->toArray()]
            );

            // Invalidate cache since valuation or metrics might change
            app(DashboardQueryService::class)->invalidateCache();
        });
    }
}
