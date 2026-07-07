<?php

namespace App\Services;

use App\Models\BahanBaku;
use App\Models\FinishedGood;
use App\Models\MutasiStok;
use App\Models\ProductionEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Single choke point for all stock-affecting write operations.
 *
 * ARCHITECTURE RULE (from ADR-004):
 *   This service is the ONLY code path in the entire codebase that writes
 *   to `mutasi_stok` or mutates `bahan_baku.stok_saat_ini` /
 *   `finished_goods.stok_saat_ini`. No other class may perform these writes.
 *
 * All operations are wrapped in DB::transaction() with lockForUpdate() on the
 * target item row — ensuring atomicity and preventing lost-update race conditions.
 *
 * The service does NOT call AuditLogger itself — audit logging of higher-level
 * business operations (e.g. "PO Diterima", "Material Created") is the
 * responsibility of the calling service (PurchaseOrderService, etc.).
 * StockMutationService logs only at the mutation granularity.
 *
 * Negative stock is rejected hard at the service level as defence-in-depth
 * (the UI enforces this earlier, but the service is the authoritative guard).
 */
final class StockMutationService
{
    /**
     * Record a generic stock mutation (masuk or keluar) for a raw material or finished good.
     *
     * @param  string  $itemType  'bahan_baku' or 'finished_good'
     * @param  int  $itemId  ID of the bahan_baku or finished_good row
     * @param  string  $jenisMutasi  'masuk' or 'keluar'
     * @param  float  $jumlah  Positive quantity to add/remove
     * @param  string  $tanggal  Date string 'Y-m-d'
     * @param  User  $actor  Authenticated user recording the mutation
     * @param  string  $sumber  'manual' | 'po_penerimaan' | 'produksi'
     * @param  int|null  $poId  Set only when sumber = 'po_penerimaan'
     * @param  int|null  $productionEntryId  Set only when sumber = 'produksi'
     * @param  string|null  $keterangan  Optional note
     *
     * @throws InvalidArgumentException When itemType is unknown or jumlah ≤ 0
     * @throws RuntimeException When a keluar would produce negative stock
     */
    public function recordMutation(
        string $itemType,
        int $itemId,
        string $jenisMutasi,
        float $jumlah,
        string $tanggal,
        User $actor,
        string $sumber = MutasiStok::SUMBER_MANUAL,
        ?int $poId = null,
        ?int $productionEntryId = null,
        ?string $keterangan = null
    ): MutasiStok {
        if ($jumlah <= 0) {
            throw new InvalidArgumentException("Jumlah mutasi harus lebih dari 0, diberikan: {$jumlah}");
        }

        $this->validateSumberConsistency($sumber, $poId, $productionEntryId);

        return DB::transaction(function () use (
            $itemType, $itemId, $jenisMutasi, $jumlah, $tanggal,
            $actor, $sumber, $poId, $productionEntryId, $keterangan
        ): MutasiStok {
            [$bahanBakuId, $finishedGoodsId] = $this->resolveItemIds($itemType, $itemId);

            // Lock the item row to prevent concurrent mutations
            $this->applyStockDelta(
                $itemType, $itemId, $jenisMutasi, $jumlah
            );

            $mutation = MutasiStok::create([
                'bahan_baku_id' => $bahanBakuId,
                'finished_goods_id' => $finishedGoodsId,
                'jenis_mutasi' => $jenisMutasi,
                'jumlah' => $jumlah,
                'tanggal' => $tanggal,
                'sumber' => $sumber,
                'po_id' => $poId,
                'production_entry_id' => $productionEntryId,
                'dicatat_oleh' => $actor->id,
                'keterangan' => $keterangan,
            ]);

            app(DashboardQueryService::class)->invalidateCache();

            return $mutation;
        });
    }

    /**
     * Record a PO receipt: creates a masuk mutation and links it to the PO.
     *
     * This is a semantic alias over recordMutation for PO-receiving workflows.
     *
     * @param  BahanBaku  $bahanBaku  Target raw material
     * @param  float  $jumlah  Quantity received
     * @param  string  $tanggal  Receipt date 'Y-m-d'
     * @param  User  $actor  User accepting the PO
     * @param  int  $poId  The PO being received
     */
    public function recordPoReceipt(
        BahanBaku $bahanBaku,
        float $jumlah,
        string $tanggal,
        User $actor,
        int $poId
    ): MutasiStok {
        return $this->recordMutation(
            itemType: 'bahan_baku',
            itemId: $bahanBaku->id,
            jenisMutasi: MutasiStok::JENIS_MASUK,
            jumlah: $jumlah,
            tanggal: $tanggal,
            actor: $actor,
            sumber: MutasiStok::SUMBER_PO_PENERIMAAN,
            poId: $poId,
            keterangan: "Penerimaan PO #{$poId}"
        );
    }

    /**
     * Record a production run with BOM explosion.
     *
     * Creates:
     *   - 1 ProductionEntry header row
     *   - N mutasi_stok keluar rows (one per BOM line × jumlah_diproduksi)
     *   - 1 mutasi_stok masuk row for the finished good
     *
     * All N+1 mutations are created in a single DB::transaction().
     * BOM lines are locked in ascending bahan_baku_id order to prevent
     * deadlocks under concurrent production entries.
     *
     * @param  FinishedGood  $fg  The finished good being produced
     * @param  float  $jumlahDiproduksi  Units of finished good produced
     * @param  string  $tanggal  Production date 'Y-m-d'
     * @param  User  $actor  Karyawan recording the run
     * @param  string|null  $keterangan  Optional note
     *
     * @throws RuntimeException When any BOM line has insufficient stock
     */
    public function recordProduction(
        FinishedGood $fg,
        float $jumlahDiproduksi,
        string $tanggal,
        User $actor,
        ?string $keterangan = null
    ): ProductionEntry {
        if ($jumlahDiproduksi <= 0) {
            throw new InvalidArgumentException('Jumlah produksi harus lebih dari 0.');
        }

        return DB::transaction(function () use ($fg, $jumlahDiproduksi, $tanggal, $actor, $keterangan): ProductionEntry {
            // Load BOM lines sorted ascending by bahan_baku_id (deadlock avoidance)
            $bomLines = $fg->bomLines()->orderBy('bahan_baku_id')->with('bahanBaku')->get();

            if ($bomLines->isEmpty()) {
                throw new RuntimeException("Barang jadi '{$fg->nama}' tidak memiliki BOM yang terdefinisi.");
            }

            // Lock all required bahan_baku rows in ascending ID order
            $bahanBakuIds = $bomLines->pluck('bahan_baku_id')->sort()->values()->toArray();

            /** @var array<int, BahanBaku> $lockedItems */
            $lockedItems = BahanBaku::whereIn('id', $bahanBakuIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id')
                ->toArray();

            // Validate sufficient stock for every BOM line before writing anything
            foreach ($bomLines as $line) {
                $required = (float) $line->qty_per_unit * $jumlahDiproduksi;
                $bb = BahanBaku::find($line->bahan_baku_id);
                if ($bb === null) {
                    throw new RuntimeException("Bahan baku ID {$line->bahan_baku_id} tidak ditemukan.");
                }
                $available = (float) $bb->stok_saat_ini;

                if ($available < $required) {
                    throw new RuntimeException(
                        "Stok tidak mencukupi untuk '{$bb->nama}': dibutuhkan {$required} {$bb->satuan}, tersedia {$available} {$bb->satuan}."
                    );
                }
            }

            // Create the ProductionEntry header row
            $entry = ProductionEntry::create([
                'finished_goods_id' => $fg->id,
                'jumlah_diproduksi' => $jumlahDiproduksi,
                'tanggal_produksi' => $tanggal,
                'dicatat_oleh' => $actor->id,
            ]);

            // Deduct each BOM ingredient (keluar mutations)
            foreach ($bomLines as $line) {
                $required = (float) $line->qty_per_unit * $jumlahDiproduksi;
                $bb = BahanBaku::find($line->bahan_baku_id);

                if ($bb === null) {
                    continue;
                }

                // Direct stock decrement (within the same transaction/lock)
                $bb->decrement('stok_saat_ini', $required);

                MutasiStok::create([
                    'bahan_baku_id' => $bb->id,
                    'finished_goods_id' => null,
                    'jenis_mutasi' => MutasiStok::JENIS_KELUAR,
                    'jumlah' => $required,
                    'tanggal' => $tanggal,
                    'sumber' => MutasiStok::SUMBER_PRODUKSI,
                    'po_id' => null,
                    'production_entry_id' => $entry->id,
                    'dicatat_oleh' => $actor->id,
                    'keterangan' => "Bahan untuk produksi {$fg->nama} (entry #{$entry->id})",
                ]);
            }

            // Credit the finished good (masuk mutation)
            $fg->increment('stok_saat_ini', $jumlahDiproduksi);

            MutasiStok::create([
                'bahan_baku_id' => null,
                'finished_goods_id' => $fg->id,
                'jenis_mutasi' => MutasiStok::JENIS_MASUK,
                'jumlah' => $jumlahDiproduksi,
                'tanggal' => $tanggal,
                'sumber' => MutasiStok::SUMBER_PRODUKSI,
                'po_id' => null,
                'production_entry_id' => $entry->id,
                'dicatat_oleh' => $actor->id,
                'keterangan' => $keterangan ?? "Hasil produksi: {$fg->nama} (entry #{$entry->id})",
            ]);

            app(DashboardQueryService::class)->invalidateCache();

            return $entry;
        });
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    /**
     * Apply stock delta (increment/decrement) with lockForUpdate().
     * Validates negative-stock prevention for keluar mutations.
     *
     * @throws RuntimeException When a keluar would result in negative stock
     */
    private function applyStockDelta(
        string $itemType,
        int $itemId,
        string $jenisMutasi,
        float $jumlah
    ): void {
        if ($itemType === 'bahan_baku') {
            /** @var BahanBaku $item */
            $item = BahanBaku::where('id', $itemId)->lockForUpdate()->firstOrFail();
            $stokKini = (float) $item->stok_saat_ini;

            if ($jenisMutasi === MutasiStok::JENIS_KELUAR && $stokKini < $jumlah) {
                throw new RuntimeException(
                    "Stok tidak mencukupi untuk '{$item->nama}': dibutuhkan {$jumlah} {$item->satuan}, tersedia {$stokKini} {$item->satuan}."
                );
            }

            if ($jenisMutasi === MutasiStok::JENIS_MASUK) {
                $item->increment('stok_saat_ini', $jumlah);
            } else {
                $item->decrement('stok_saat_ini', $jumlah);
            }
        } elseif ($itemType === 'finished_good') {
            /** @var FinishedGood $item */
            $item = FinishedGood::where('id', $itemId)->lockForUpdate()->firstOrFail();
            $stokKini = (float) $item->stok_saat_ini;

            if ($jenisMutasi === MutasiStok::JENIS_KELUAR && $stokKini < $jumlah) {
                throw new RuntimeException(
                    "Stok tidak mencukupi untuk '{$item->nama}': dibutuhkan {$jumlah} {$item->satuan}, tersedia {$stokKini} {$item->satuan}."
                );
            }

            if ($jenisMutasi === MutasiStok::JENIS_MASUK) {
                $item->increment('stok_saat_ini', $jumlah);
            } else {
                $item->decrement('stok_saat_ini', $jumlah);
            }
        } else {
            throw new InvalidArgumentException("itemType tidak dikenal: '{$itemType}'. Gunakan 'bahan_baku' atau 'finished_good'.");
        }
    }

    /**
     * Resolve bahan_baku_id / finished_goods_id from itemType + itemId.
     *
     * @param  string  $itemType  'bahan_baku' or 'finished_good'
     * @param  int  $itemId  Primary key of the item
     * @return array{0: int|null, 1: int|null} [bahanBakuId, finishedGoodsId]
     *
     * @throws InvalidArgumentException When itemType is unknown
     */
    private function resolveItemIds(string $itemType, int $itemId): array
    {
        return match ($itemType) {
            'bahan_baku' => [$itemId, null],
            'finished_good' => [null, $itemId],
            default => throw new InvalidArgumentException("itemType tidak dikenal: '{$itemType}'."),
        };
    }

    /**
     * Validate that sumber, po_id, and production_entry_id are mutually consistent.
     *
     * Mirrors the PostgreSQL CHECK constraint `chk_mutasi_sumber_consistency`
     * at the application layer (SQLite in tests does not enforce CHECK constraints).
     *
     * @throws InvalidArgumentException When the combination is invalid
     */
    private function validateSumberConsistency(
        string $sumber,
        ?int $poId,
        ?int $productionEntryId
    ): void {
        $valid = match ($sumber) {
            MutasiStok::SUMBER_MANUAL => ($poId === null && $productionEntryId === null),
            MutasiStok::SUMBER_PO_PENERIMAAN => ($poId !== null && $productionEntryId === null),
            MutasiStok::SUMBER_PRODUKSI => ($productionEntryId !== null && $poId === null),
            default => false,
        };

        if (! $valid) {
            throw new InvalidArgumentException(
                "Kombinasi sumber='{$sumber}', po_id='{$poId}', production_entry_id='{$productionEntryId}' tidak valid."
            );
        }
    }
}
