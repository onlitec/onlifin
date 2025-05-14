<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Para todos os usuários com is_active = true, garantir que email_verified_at esteja definido
        DB::table('users')
            ->where('is_active', true)
            ->update(['email_verified_at' => now()]);

        // Para todos os usuários com is_active = false, garantir que email_verified_at seja nulo
        DB::table('users')
            ->where('is_active', false)
            ->update(['email_verified_at' => null]);
            
        // Para todos os usuários com email_verified_at não nulo, garantir que is_active = true
        DB::table('users')
            ->whereNotNull('email_verified_at')
            ->update(['is_active' => true]);
            
        // Para todos os usuários com email_verified_at nulo, garantir que is_active = false
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['is_active' => false]);
            
        // Atualização específica para o usuário 'Alessandro'
        DB::table('users')
            ->where('name', 'like', '%Alessandro%')
            ->orWhere('name', 'like', '%alessandro%')
            ->update(['is_active' => true, 'email_verified_at' => now()]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Não é possível reverter esta migração com precisão
    }
};
