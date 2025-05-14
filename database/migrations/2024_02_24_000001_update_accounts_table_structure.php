<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Verifica se a coluna balance existe antes de tentar removê-la
        $columns = Schema::getColumnListing('accounts');
        if (in_array('balance', $columns)) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->dropColumn('balance');
            });
        }
        
        // Adiciona as novas colunas se ainda não existirem
        Schema::table('accounts', function (Blueprint $table) {
            if (!in_array('initial_balance', Schema::getColumnListing('accounts'))) {
                $table->decimal('initial_balance', 10, 2)->default(0);
            }
            if (!in_array('current_balance', Schema::getColumnListing('accounts'))) {
                $table->decimal('current_balance', 10, 2)->default(0);
            }
            if (!in_array('description', Schema::getColumnListing('accounts'))) {
                $table->text('description')->nullable();
            }
        });
    }

    public function down()
    {
        // Remove as colunas adicionadas
        Schema::table('accounts', function (Blueprint $table) {
            if (in_array('initial_balance', Schema::getColumnListing('accounts'))) {
                $table->dropColumn('initial_balance');
            }
            if (in_array('current_balance', Schema::getColumnListing('accounts'))) {
                $table->dropColumn('current_balance');
            }
            if (in_array('description', Schema::getColumnListing('accounts'))) {
                $table->dropColumn('description');
            }
        });

        // Adiciona a coluna balance de volta
        Schema::table('accounts', function (Blueprint $table) {
            if (!in_array('balance', Schema::getColumnListing('accounts'))) {
                $table->decimal('balance', 10, 2)->default(0);
            }
        });
    }
}; 