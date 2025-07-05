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
        Schema::create('ssl_error_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action'); // generate, renew, validate
            $table->string('domain');
            $table->string('error_type')->nullable(); // unauthorized, connection, dns, etc
            $table->text('error_message'); // mensagem original do erro
            $table->text('error_detail')->nullable(); // detalhes técnicos
            $table->string('ip_address')->nullable(); // IP que falhou
            $table->text('friendly_message'); // mensagem traduzida para o usuário
            $table->json('metadata')->nullable(); // dados extras
            $table->timestamps();
            
            $table->index(['domain', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ssl_error_logs');
    }
}; 