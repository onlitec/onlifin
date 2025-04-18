<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Cria uma tabela para armazenar chaves API específicas por modelo
     */
    public function up(): void
    {
        Schema::create('model_api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50)->comment('Provedor de IA (openai, anthropic, gemini, etc)');
            $table->string('model', 100)->comment('Nome do modelo específico');
            $table->text('api_token')->comment('Chave API específica para este modelo');
            $table->text('system_prompt')->nullable()->comment('Prompt do sistema específico para este modelo');
            $table->boolean('is_active')->default(true)->comment('Se esta configuração está ativa');
            $table->timestamps();
            
            // Índices e chaves únicas
            $table->unique(['provider', 'model']);
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
