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
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('provider'); // google, facebook, twitter, github, etc.
            $table->string('provider_id'); // ID do usuário no provedor
            $table->string('provider_email')->nullable();
            $table->string('provider_name')->nullable();
            $table->string('provider_avatar')->nullable();
            $table->text('access_token')->nullable(); // Token de acesso
            $table->text('refresh_token')->nullable(); // Token de refresh
            $table->timestamp('token_expires_at')->nullable();
            $table->json('provider_data')->nullable(); // Dados extras do provedor
            $table->timestamps();

            // Índices
            $table->unique(['provider', 'provider_id']);
            $table->index(['user_id', 'provider']);
            $table->index('provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
