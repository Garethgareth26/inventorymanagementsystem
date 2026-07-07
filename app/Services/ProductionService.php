<?php

namespace App\Services;

use App\Models\FinishedGood;
use App\Models\ProductionEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Service to encapsulate high-level production entries, including audits and validation checks.
 */
final class ProductionService
{
    private StockMutationService $stockMutationService;

    public function __construct(StockMutationService $stockMutationService)
    {
        $this->stockMutationService = $stockMutationService;
    }

    /**
     * Orchestrate the recording of a production run:
     *   1. Validate BOM presence and stock levels (ascending-ID deadlock avoidance).
     *   2. Delegate database mutation writes to StockMutationService.
     *   3. Audit log the production run.
     *   4. Invalidate the dashboard cache.
     *
     * @throws RuntimeException If any ingredient has insufficient stock.
     */
    public function recordProduction(
        FinishedGood $fg,
        float $qtyToProduce,
        User $actor,
        string $tanggal,
        ?string $keterangan = null
    ): ProductionEntry {
        return DB::transaction(function () use ($fg, $qtyToProduce, $actor, $tanggal, $keterangan) {
            // Lock and validate via StockMutationService recordProduction
            $productionEntry = $this->stockMutationService->recordProduction(
                $fg,
                $qtyToProduce,
                $tanggal,
                $actor,
                $keterangan
            );

            // Audit logging
            AuditLogger::log(
                $actor,
                'production.record',
                $productionEntry,
                null,
                $productionEntry->toArray()
            );

            // Invalidate cache
            app(DashboardQueryService::class)->invalidateCache();

            return $productionEntry;
        });
    }
}
