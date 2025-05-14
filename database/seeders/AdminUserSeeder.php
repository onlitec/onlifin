<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Primeiro admin
        User::updateOrCreate(
            ['email' => 'alfreire@onlitec.com.br'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('M3a74g20M'),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );

        // Segundo admin
        User::updateOrCreate(
            ['email' => 'galvatec@onlifin.com.br'],
            [
                'name' => 'Administrador Galvatec',
                'password' => Hash::make('M3a74g20M'),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );
    }
} 