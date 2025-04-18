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
        if (!Schema::hasColumn('transactions', 'amount')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->bigInteger('amount')->after('description'); // Armazena em centavos
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('transactions', 'amount')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('amount');
            });
        }
    }
};
