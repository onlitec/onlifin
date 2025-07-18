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
        // Verifica se a tabela accounts existe antes de tentar modificá-la
        if (Schema::hasTable('accounts')) {
            Schema::table('accounts', function (Blueprint $table) {
                // Verifica se a coluna user_id não existe e a cria
                if (!Schema::hasColumn('accounts', 'user_id')) {
                    $table->foreignId('user_id')->after('active')->constrained()->onDelete('cascade');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Verifica se a tabela accounts existe antes de tentar modificá-la
        if (Schema::hasTable('accounts')) {
            Schema::table('accounts', function (Blueprint $table) {
                // Remove a coluna user_id se ela existir
                if (Schema::hasColumn('accounts', 'user_id')) {
                    $table->dropForeign(['user_id']);
                    $table->dropColumn('user_id');
                }
            });
        }
    }
};
