<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Delegates entirely to DomainSeeder which orchestrates all seeders
     * in the correct FK dependency order. Individual seeders can also
     * be called directly (e.g., php artisan db:seed --class=SupplierSeeder).
     */
    public function run(): void
    {
        $this->call(DomainSeeder::class);
    }
}
