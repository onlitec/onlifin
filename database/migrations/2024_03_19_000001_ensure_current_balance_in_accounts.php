<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('accounts', 'current_balance')) {
                $table->decimal('current_balance', 10, 2)->default(0)->after('initial_balance');
            }
        });

        // Atualiza o current_balance para ser igual ao initial_balance em todas as contas
        DB::statement('UPDATE accounts SET current_balance = initial_balance WHERE current_balance = 0');
    }

    public function down()
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (Schema::hasColumn('accounts', 'current_balance')) {
                $table->dropColumn('current_balance');
            }
        });
    }
}; 