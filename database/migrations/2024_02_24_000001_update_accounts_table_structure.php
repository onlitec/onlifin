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
            $table->dropColumn('balance');
            
            // Adiciona as novas colunas
            $table->decimal('initial_balance', 10, 2)->default(0);
            $table->decimal('current_balance', 10, 2)->default(0);
            $table->text('description')->nullable();
        });
    }

    public function down()
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->decimal('balance', 10, 2)->default(0);
            $table->dropColumn(['initial_balance', 'current_balance', 'description']);
        });
    }
}; 