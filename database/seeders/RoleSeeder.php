<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

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
