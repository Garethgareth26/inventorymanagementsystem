<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $ownerRole = Role::where('slug', 'owner')->firstOrFail();
        $karyawanRole = Role::where('slug', 'karyawan')->firstOrFail();

        User::firstOrCreate(
            ['email' => 'owner@akuna.com'],
            [
                'name' => 'Owner User',
                'password' => Hash::make('password'),
                'role_id' => $ownerRole->id,
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'karyawan@akuna.com'],
            [
                'name' => 'Karyawan User',
                'password' => Hash::make('password'),
                'role_id' => $karyawanRole->id,
                'email_verified_at' => now(),
            ]
        );
    }
}
