<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 *
 * Bare scaffold only — required so App\Models\AuditLog's HasFactory
 * trait resolves. Realistic sample data is M-2.3 scope.
 */
class AuditLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => 'stock.mutate',
            'subject_type' => 'App\\Models\\BahanBaku',
            'subject_id' => 1,
            'old_values' => null,
            'new_values' => null,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }
}
