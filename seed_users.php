<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$ownerRole = Role::firstOrCreate(['slug' => 'owner'], ['name' => 'Owner']);
$karyawanRole = Role::firstOrCreate(['slug' => 'karyawan'], ['name' => 'Karyawan']);

User::firstOrCreate(
    ['email' => 'owner@akuna.com'],
    ['name' => 'Owner', 'password' => Hash::make('password'), 'role_id' => $ownerRole->id, 'email_verified_at' => now()]
);

User::firstOrCreate(
    ['email' => 'karyawan@akuna.com'],
    ['name' => 'Karyawan', 'password' => Hash::make('password'), 'role_id' => $karyawanRole->id, 'email_verified_at' => now()]
);

echo 'Users seeded successfully. Count: '.User::count()."\n";
