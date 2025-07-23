<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        try {
            // =================================================================
            // 1. Criar usuário admin@onlifin.com (principal)
            // =================================================================
            $adminUser = User::firstOrCreate(
                ['email' => 'admin@onlifin.com'],
                [
                    'name' => 'Administrador',
                    'password' => Hash::make('admin123'),
                    'is_admin' => true,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            $this->command->info('✅ Usuário principal criado:');
            $this->command->info('📧 Email: admin@onlifin.com');
            $this->command->info('🔑 Senha: admin123');

            // =================================================================
            // 2. Criar usuário demo@onlifin.com (demonstração)
            // =================================================================
            $demoUser = User::firstOrCreate(
                ['email' => 'demo@onlifin.com'],
                [
                    'name' => 'Usuário Demo',
                    'password' => Hash::make('demo123'),
                    'is_admin' => false,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            $this->command->info('✅ Usuário demo criado:');
            $this->command->info('📧 Email: demo@onlifin.com');
            $this->command->info('🔑 Senha: demo123');

            // =================================================================
            // 3. Criar usuário alfreire@onlifin.com (desenvolvedor)
            // =================================================================
            $alfreireUser = User::firstOrCreate(
                ['email' => 'alfreire@onlifin.com'],
                [
                    'name' => 'Alfredo Freire',
                    'password' => Hash::make('M3a74g20M'),
                    'is_admin' => true,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            $this->command->info('✅ Usuário desenvolvedor criado:');
            $this->command->info('📧 Email: alfreire@onlifin.com');
            $this->command->info('🔑 Senha: M3a74g20M');

            // Mostrar total de usuários
            $totalUsers = User::count();
            $this->command->info("📊 Total de usuários no sistema: {$totalUsers}");

        } catch (\Exception $e) {
            $this->command->error('❌ Erro ao criar usuários: ' . $e->getMessage());
        }
    }
}