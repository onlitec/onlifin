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
        Schema::table('accounts', function (Blueprint $table) {
            // Add current_balance column if it doesn't exist
            if (!Schema::hasColumn('accounts', 'current_balance')) {
                $table->decimal('current_balance', 10, 2)->default(0)->after('initial_balance');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            // Remove the column if it exists
            if (Schema::hasColumn('accounts', 'current_balance')) {
                $table->dropColumn('current_balance');
            }
        });
    }
};
