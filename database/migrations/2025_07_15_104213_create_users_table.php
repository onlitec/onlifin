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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('profile_photo')->nullable();
            $table->boolean('is_admin')->default(false);
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('email_notifications')->default(true);
            $table->boolean('whatsapp_notifications')->default(false);
            $table->boolean('push_notifications')->default(true);
            $table->boolean('due_date_notifications')->default(true);
            $table->string('google_id')->nullable();
            $table->string('google_avatar')->nullable();
            $table->boolean('two_factor_enabled')->default(false);
            $table->string('two_factor_secret')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->rememberToken();
            $table->timestamps();

            // Índices
            $table->index('google_id');
            $table->index('two_factor_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
