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
        // Esta migração não é mais necessária pois a tabela transactions já é criada com recurrence_period
        // Mantida apenas para compatibilidade com histórico de migrações
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Esta migração não é mais necessária pois a tabela transactions já é criada com recurrence_period
        // Mantida apenas para compatibilidade com histórico de migrações
    }
};
