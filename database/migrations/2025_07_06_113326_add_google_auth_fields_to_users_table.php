<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Verifica se a tabela users existe antes de tentar modificá-la
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                // Verificar se as colunas já existem antes de adicioná-las
                if (!Schema::hasColumn('users', 'google_id')) {
                    $table->string('google_id')->nullable()->after('email');
                }
                if (!Schema::hasColumn('users', 'google_avatar')) {
                    $table->string('google_avatar')->nullable()->after('google_id');
                }

                // Campos para 2FA
                if (!Schema::hasColumn('users', 'two_factor_enabled')) {
                    $table->boolean('two_factor_enabled')->default(false)->after('google_avatar');
                }
                if (!Schema::hasColumn('users', 'two_factor_secret')) {
                    $table->string('two_factor_secret')->nullable()->after('two_factor_enabled');
                }
                if (!Schema::hasColumn('users', 'two_factor_confirmed_at')) {
                    $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_secret');
                }

                // Códigos de recuperação para 2FA
                if (!Schema::hasColumn('users', 'two_factor_recovery_codes')) {
                    $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_confirmed_at');
                }

                // Índices para melhor performance (verificar se já existem)
                // Verificar se índices já existem usando SQL direto
                $indexExists = \DB::select("SHOW INDEX FROM users WHERE Key_name = 'users_google_id_index'");
                if (empty($indexExists)) {
                    $table->index('google_id');
                }

                $indexExists2 = \DB::select("SHOW INDEX FROM users WHERE Key_name = 'users_two_factor_enabled_index'");
                if (empty($indexExists2)) {
                    $table->index('two_factor_enabled');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Verifica se a tabela users existe antes de tentar modificá-la
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['google_id']);
                $table->dropIndex(['two_factor_enabled']);
                $table->dropColumn([
                    'google_id',
                    'google_avatar',
                    'two_factor_enabled',
                    'two_factor_secret',
                    'two_factor_confirmed_at',
                    'two_factor_recovery_codes',
                ]);
            });
        }
    }
};
