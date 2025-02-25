<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Renomeia a coluna amount para value se necessário
            if (Schema::hasColumn('transactions', 'amount')) {
                $table->renameColumn('amount', 'value');
            } else {
                $table->decimal('value', 10, 2)->after('description');
            }
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'value')) {
                $table->renameColumn('value', 'amount');
            }
        });
    }
}; 