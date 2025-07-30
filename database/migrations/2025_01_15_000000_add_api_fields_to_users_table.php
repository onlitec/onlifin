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
        Schema::table('users', function (Blueprint $table) {
            // Campos para configurações de API
            $table->boolean('api_enabled')->default(true)->after('whatsapp_notifications');
            $table->integer('api_rate_limit')->default(120)->after('api_enabled');
            $table->integer('api_token_expiration_days')->default(30)->after('api_rate_limit');
            
            // Índices para melhor performance
            $table->index('api_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['api_enabled']);
            $table->dropColumn([
                'api_enabled',
                'api_rate_limit',
                'api_token_expiration_days',
            ]);
        });
    }
};