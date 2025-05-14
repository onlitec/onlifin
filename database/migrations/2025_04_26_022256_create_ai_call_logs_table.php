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
        Schema::create('ai_call_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Usuário que iniciou (opcional)
            $table->string('provider')->nullable()->index(); // Ex: gemini, replicate
            $table->string('model')->nullable()->index();    // Ex: gemini-1.5-pro
            $table->integer('duration_ms')->nullable();     // Duração da chamada em milissegundos
            $table->integer('status_code')->nullable()->index(); // Código HTTP da resposta
            $table->text('prompt_preview')->nullable();     // Preview do prompt enviado
            $table->text('response_preview')->nullable();    // Preview da resposta recebida
            $table->text('error_message')->nullable();      // Mensagem de erro, se houver
            $table->timestamps(); // created_at será o timestamp da chamada
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_call_logs');
    }
};
