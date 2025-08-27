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
        Schema::create('model_api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('provider'); // openai, anthropic, gemini, groq, etc.
            $table->string('model'); // gpt-4, claude-3, gemini-pro, etc.
            $table->text('api_token'); // Chave da API
            $table->longText('system_prompt')->nullable(); // Prompt do sistema
            $table->longText('chat_prompt')->nullable(); // Prompt para chat
            $table->longText('import_prompt')->nullable(); // Prompt para importação
            $table->boolean('is_active')->default(true); // Se está ativo
            $table->timestamps();

            // Índices para melhor performance
            $table->index(['provider', 'model']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_api_keys');
    }
};
