<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$ownerRole = App\Models\Role::firstOrCreate(['slug' => 'owner'], ['name' => 'Owner']);
$karyawanRole = App\Models\Role::firstOrCreate(['slug' => 'karyawan'], ['name' => 'Karyawan']);

App\Models\User::firstOrCreate(
    ['email' => 'owner@akuna.com'], 
    ['name' => 'Owner', 'password' => Hash::make('password'), 'role_id' => $ownerRole->id, 'email_verified_at' => now()]
);

App\Models\User::firstOrCreate(
    ['email' => 'karyawan@akuna.com'], 
    ['name' => 'Karyawan', 'password' => Hash::make('password'), 'role_id' => $karyawanRole->id, 'email_verified_at' => now()]
);

echo "Users seeded successfully. Count: " . App\Models\User::count() . "\n";
