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
        Schema::table('replicate_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('replicate_settings', 'endpoint')) {
                $table->string('endpoint')->nullable()->after('api_token');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('replicate_settings', function (Blueprint $table) {
            if (Schema::hasColumn('replicate_settings', 'endpoint')) {
                $table->dropColumn('endpoint');
            }
        });
    }
}; 