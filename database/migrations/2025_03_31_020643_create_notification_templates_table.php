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
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['expense', 'income', 'system', 'custom']);
            $table->string('event')->nullable();
            $table->text('description')->nullable();
            
            // Conteúdo para cada canal
            $table->text('email_subject')->nullable();
            $table->text('email_content')->nullable();
            $table->text('whatsapp_content')->nullable();
            $table->text('push_title')->nullable();
            $table->text('push_content')->nullable();
            $table->string('push_image')->nullable();
            
            // Variáveis para substituição dinâmica
            $table->json('available_variables')->nullable();
            
            // Configurações
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
        
        // Tabela para configurar quando enviar notificações relacionadas a vencimentos
        Schema::create('due_date_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('notify_expenses')->default(true);
            $table->boolean('notify_incomes')->default(true);
            $table->boolean('notify_on_due_date')->default(true);
            $table->json('notify_days_before')->default(json_encode([1, 3, 7])); // Notificar 1, 3, 7 dias antes
            $table->json('notify_channels')->default(json_encode(['email', 'database']));
            $table->foreignId('expense_template_id')->nullable()->constrained('notification_templates')->nullOnDelete();
            $table->foreignId('income_template_id')->nullable()->constrained('notification_templates')->nullOnDelete();
            $table->boolean('group_notifications')->default(true); // Agrupa múltiplas notificações em uma
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('due_date_notification_settings');
        Schema::dropIfExists('notification_templates');
    }
};
