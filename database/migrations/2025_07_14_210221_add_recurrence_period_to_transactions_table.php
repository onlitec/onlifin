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
        // Verifica se a tabela transactions existe antes de tentar modificá-la
        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->enum('recurrence_period', ['daily', 'weekly', 'fortnightly', 'monthly', 'yearly'])
                      ->nullable()
                      ->after('recurrence_type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Verifica se a tabela transactions existe antes de tentar modificá-la
        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('recurrence_period');
            });
        }
    }
};
