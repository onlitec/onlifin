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
        Schema::create('chatbot_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name')->default('Configuração Padrão');
            $table->string('provider'); // openai, anthropic, gemini, groq, etc.
            $table->string('model');
            $table->text('api_key');
            $table->string('endpoint')->nullable();
            $table->longText('system_prompt');
            $table->decimal('temperature', 3, 2)->default(0.70);
            $table->integer('max_tokens')->default(1000);
            $table->boolean('enabled')->default(true);
            $table->boolean('is_default')->default(false);
            $table->json('settings')->nullable(); // Configurações específicas do provedor
            $table->timestamps();

            // Índices
            $table->index(['user_id', 'enabled']);
            $table->index(['user_id', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_configs');
    }
};
