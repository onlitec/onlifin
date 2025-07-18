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
        // Verifica se a tabela transactions existe e se a coluna suggested_category não existe antes de tentar adicioná-la
        if (Schema::hasTable('transactions') && !Schema::hasColumn('transactions', 'suggested_category')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->string('suggested_category', 100)->nullable()->after('category_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Verifica se a tabela transactions existe e se a coluna suggested_category existe antes de tentar removê-la
        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'suggested_category')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('suggested_category');
            });
        }
    }
}; 