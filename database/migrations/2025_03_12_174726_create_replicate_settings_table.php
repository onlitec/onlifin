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
        Schema::create('replicate_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('openai');
            $table->string('api_token')->nullable();
            $table->string('model_version')->default('gpt-4-turbo-preview');
            $table->text('system_prompt')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('replicate_settings');
    }
};
