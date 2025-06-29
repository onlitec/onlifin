<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // PermissionsSeeder::class, // REMOVIDO
            AdminUserSeeder::class,
            CategorySeeder::class,
            AccountSeeder::class,
            RoleAndPermissionSeeder::class,
            DefaultAdminSeeder::class,
        ]);
    }
}
