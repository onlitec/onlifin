<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DefaultAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $email = env('DEFAULT_ADMIN_EMAIL', 'admin@admin.com');
        $password = env('DEFAULT_ADMIN_PASSWORD', 'AdminMudar');

        if (!User::where('email', $email)->exists()) {
            User::create([
                'name' => env('DEFAULT_ADMIN_NAME', 'Administrator'),
                'email' => $email,
                'password' => Hash::make($password),
                'is_admin' => true,
            ]);
            $this->command->info("Usuário administrador padrão criado: {$email}");
        } else {
            $this->command->info("Usuário administrador padrão já existe: {$email}");
        }
    }
} 