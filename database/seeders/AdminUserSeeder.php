<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Company;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Garante que a role de Administrador exista e tenha todas as permissões
        $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
        $allPermissions = \Spatie\Permission\Models\Permission::all();
        $adminRole->syncPermissions($allPermissions);

        // =================================================================
        // 1. Criar usuário admin@onlifin.com.br
        // =================================================================
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@onlifin.com.br'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('admin123'),
                'is_admin' => true,
            ]
        );
        $adminUser->assignRole('Administrador');
        $this->command->info('Usuário admin@onlifin.com.br criado com sucesso.');

        // =================================================================
        // 2. Criar usuário alfreire@onlifin.com.br e Empresa Galvatec
        // =================================================================

        // Criar o usuário Alfredo Freire
        $alfreireUser = User::firstOrCreate(
            ['email' => 'alfreire@onlifin.com.br'],
            [
                'name' => 'Alfredo Freire',
                'password' => Hash::make('password'), // Use uma senha padrão
                'is_admin' => true,
            ]
        );

        // Criar a empresa Galvatec, definindo o usuário como proprietário
        $galvatecCompany = Company::firstOrCreate(
            ['name' => 'Galvatec'],
            ['owner_id' => $alfreireUser->id]
        );

        // Associar o usuário à empresa
        $alfreireUser->companies()->syncWithoutDetaching([$galvatecCompany->id]);

        // Definir a empresa Galvatec como a empresa atual do usuário
        $alfreireUser->current_company_id = $galvatecCompany->id;
        $alfreireUser->save();

        // Atribuir a role de Administrador também a este usuário
        $alfreireUser->assignRole('Administrador');

        $this->command->info('Usuário Alfredo Freire e empresa Galvatec criados e configurados com sucesso.');
    }
} 