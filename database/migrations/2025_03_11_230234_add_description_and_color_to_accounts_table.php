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
            if (!Schema::hasColumn('accounts', 'description')) {
                $table->text('description')->nullable()->after('active');
            }
            
            if (!Schema::hasColumn('accounts', 'color')) {
                $table->string('color', 7)->nullable()->after('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (Schema::hasColumn('accounts', 'color')) {
                $table->dropColumn('color');
            }
            
            if (Schema::hasColumn('accounts', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
