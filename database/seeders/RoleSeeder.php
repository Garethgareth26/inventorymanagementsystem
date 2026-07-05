<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(
            ['slug' => 'owner'],
            ['name' => 'Owner']
        );

        Role::firstOrCreate(
            ['slug' => 'karyawan'],
            ['name' => 'Karyawan']
        );
    }
}
