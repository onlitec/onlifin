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
        Schema::create('open_router_configs', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('openrouter');
            $table->string('model');
            $table->string('custom_model')->nullable();
            $table->text('api_key');
            $table->string('endpoint')->nullable();
            $table->longText('system_prompt')->nullable();
            $table->longText('chat_prompt')->nullable();
            $table->longText('import_prompt')->nullable();
            $table->decimal('temperature', 3, 2)->default(0.70);
            $table->integer('max_tokens')->default(1000);
            $table->boolean('enabled')->default(true);
            $table->boolean('is_default')->default(false);
            $table->json('settings')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();

            // Índices
            $table->index(['enabled']);
            $table->index(['is_default']);
            $table->index(['user_id', 'enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('open_router_configs');
    }
};
