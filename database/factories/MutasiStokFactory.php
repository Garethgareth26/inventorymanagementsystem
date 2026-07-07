<?php

namespace Database\Factories;

use App\Models\MutasiStok;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MutasiStok>
 *
 * Stock mutation factory. The default state is intentionally minimal —
 * all item FK fields (bahan_baku_id, finished_goods_id) default to null.
 * Use the provided named states for realistic, constraint-compliant rows:
 *
 *   MutasiStok::factory()->forBahanBaku($id)->masuk()->create()
 *   MutasiStok::factory()->forFinishedGood($id)->keluar()->create()
 */
class MutasiStokFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bahan_baku_id' => null,
            'finished_goods_id' => null,
            'jenis_mutasi' => MutasiStok::JENIS_MASUK,
            'jumlah' => fake()->randomFloat(2, 10, 500),
            'tanggal' => fake()->dateTimeBetween('-12 months', 'now')->format('Y-m-d'),
            'sumber' => MutasiStok::SUMBER_MANUAL,
            'po_id' => null,
            'production_entry_id' => null,
            'dicatat_oleh' => User::factory(),
            'keterangan' => null,
        ];
    }

    // ─── Item states (exactly one must be set per row) ─────────────────────────

    /**
     * Target a specific raw material (bahan_baku XOR finished_goods_id).
     *
     * @return Factory<MutasiStok>
     */
    public function forBahanBaku(int $bahanBakuId): static
    {
        return $this->state([
            'bahan_baku_id' => $bahanBakuId,
            'finished_goods_id' => null,
        ]);
    }

    /**
     * Target a specific finished good (bahan_baku XOR finished_goods_id).
     *
     * @return Factory<MutasiStok>
     */
    public function forFinishedGood(int $finishedGoodId): static
    {
        return $this->state([
            'bahan_baku_id' => null,
            'finished_goods_id' => $finishedGoodId,
        ]);
    }

    // ─── Jenis states ──────────────────────────────────────────────────────────

    /**
     * @return Factory<MutasiStok>
     */
    public function masuk(): static
    {
        return $this->state(['jenis_mutasi' => MutasiStok::JENIS_MASUK]);
    }

    /**
     * @return Factory<MutasiStok>
     */
    public function keluar(): static
    {
        return $this->state(['jenis_mutasi' => MutasiStok::JENIS_KELUAR]);
    }

    // ─── Sumber states ─────────────────────────────────────────────────────────

    /**
     * @return Factory<MutasiStok>
     */
    public function manual(): static
    {
        return $this->state([
            'sumber' => MutasiStok::SUMBER_MANUAL,
            'po_id' => null,
            'production_entry_id' => null,
        ]);
    }

    /**
     * @return Factory<MutasiStok>
     */
    public function dariPO(int $poId): static
    {
        return $this->state([
            'sumber' => MutasiStok::SUMBER_PO_PENERIMAAN,
            'po_id' => $poId,
            'production_entry_id' => null,
            'jenis_mutasi' => MutasiStok::JENIS_MASUK,
        ]);
    }

    /**
     * @return Factory<MutasiStok>
     */
    public function dariProduksi(int $productionEntryId): static
    {
        return $this->state([
            'sumber' => MutasiStok::SUMBER_PRODUKSI,
            'po_id' => null,
            'production_entry_id' => $productionEntryId,
        ]);
    }
}
