<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('accounts', function (Blueprint $table) {
            // Remove a coluna balance existente
            if (Schema::hasColumn('accounts', 'balance')) {
                $table->dropColumn('balance');
            }
            
            // Adiciona as novas colunas
            if (!Schema::hasColumn('accounts', 'initial_balance')) {
                $table->decimal('initial_balance', 10, 2)->default(0);
            }
            
            if (!Schema::hasColumn('accounts', 'current_balance')) {
                $table->decimal('current_balance', 10, 2)->default(0);
            }
            
            if (!Schema::hasColumn('accounts', 'description')) {
                $table->text('description')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (Schema::hasColumn('accounts', 'balance')) {
                $table->decimal('balance', 10, 2)->default(0);
            }
            
            if (Schema::hasColumn('accounts', 'initial_balance')) {
                $table->dropColumn('initial_balance');
            }
            
            if (Schema::hasColumn('accounts', 'current_balance')) {
                $table->dropColumn('current_balance');
            }
            
            if (Schema::hasColumn('accounts', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
}; 