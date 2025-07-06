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
            // Campos para autenticação Google
            $table->string('google_id')->nullable()->after('email');
            $table->string('google_avatar')->nullable()->after('google_id');
            
            // Campos para 2FA
            $table->boolean('two_factor_enabled')->default(false)->after('google_avatar');
            $table->string('two_factor_secret')->nullable()->after('two_factor_enabled');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_secret');
            
            // Códigos de recuperação para 2FA
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_confirmed_at');
            
            // Índices para melhor performance
            $table->index('google_id');
            $table->index('two_factor_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['google_id']);
            $table->dropIndex(['two_factor_enabled']);
            $table->dropColumn([
                'google_id',
                'google_avatar',
                'two_factor_enabled',
                'two_factor_secret',
                'two_factor_confirmed_at',
                'two_factor_recovery_codes',
            ]);
        });
    }
};
