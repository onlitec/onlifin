<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\OpenRouterConfig;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('open_router_configs', function (Blueprint $table) {
            // Adicionar novos campos
            $table->text('chat_prompt')->nullable()->after('system_prompt');
            $table->text('import_prompt')->nullable()->after('chat_prompt');
        });
        
        // Transferir dados do campo system_prompt para chat_prompt usando o modelo
        $configs = OpenRouterConfig::all();
        foreach ($configs as $config) {
            if (!empty($config->system_prompt)) {
                $config->chat_prompt = $config->system_prompt;
                $config->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('open_router_configs', function (Blueprint $table) {
            $table->dropColumn(['chat_prompt', 'import_prompt']);
        });
    }
};
