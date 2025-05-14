<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        
        // Verificar diariamente as transações com vencimento próximo e enviar notificações
        $schedule->command('app:send-due-date-notifications')->dailyAt('08:00');
        
        // Limpar sessões expiradas diariamente às 3 da manhã
        $schedule->command('sessions:fix --delete-expired')->dailyAt('03:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
} 