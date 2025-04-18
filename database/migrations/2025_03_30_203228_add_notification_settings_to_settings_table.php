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
        Schema::table('settings', function (Blueprint $table) {
            // Configurações de Email
            $table->boolean('email_notifications_enabled')->default(true);
            $table->boolean('email_notify_new_transactions')->default(true);
            $table->boolean('email_notify_due_dates')->default(true);
            $table->boolean('email_notify_low_balance')->default(true);
            $table->decimal('email_low_balance_threshold', 10, 2)->nullable();
            
            // Configurações de WhatsApp
            $table->boolean('whatsapp_notifications_enabled')->default(false);
            $table->string('whatsapp_number')->nullable();
            $table->boolean('whatsapp_notify_new_transactions')->default(true);
            $table->boolean('whatsapp_notify_due_dates')->default(true);
            $table->boolean('whatsapp_notify_low_balance')->default(true);
            $table->decimal('whatsapp_low_balance_threshold', 10, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'email_notifications_enabled',
                'email_notify_new_transactions',
                'email_notify_due_dates',
                'email_notify_low_balance',
                'email_low_balance_threshold',
                'whatsapp_notifications_enabled',
                'whatsapp_number',
                'whatsapp_notify_new_transactions',
                'whatsapp_notify_due_dates',
                'whatsapp_notify_low_balance',
                'whatsapp_low_balance_threshold',
            ]);
        });
    }
};
