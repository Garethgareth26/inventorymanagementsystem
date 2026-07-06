<?php

namespace Database\Factories;

use App\Models\MutasiStok;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MutasiStok>
 *
 * Bare scaffold only — required so App\Models\MutasiStok's HasFactory
 * trait resolves. Realistic sample data satisfying all CHECK constraints
 * (exactly-one-of bahan_baku/finished_goods, sumber consistency) is
 * M-2.3 scope — this default state deliberately does NOT attempt to
 * satisfy those constraints; callers must set bahan_baku_id XOR
 * finished_goods_id explicitly via ->state().
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
            'jumlah' => fake()->randomFloat(2, 1, 100),
            'tanggal' => fake()->date(),
            'sumber' => MutasiStok::SUMBER_MANUAL,
            'po_id' => null,
            'production_entry_id' => null,
            'dicatat_oleh' => User::factory(),
            'keterangan' => null,
        ];
    }
}
