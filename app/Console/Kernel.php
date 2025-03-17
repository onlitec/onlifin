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
        // Executa o comando de notificações todos os dias às 8h da manhã
        $schedule->command('app:send-transaction-reminders --days=1')
            ->dailyAt('08:00')
            ->appendOutputTo(storage_path('logs/notifications.log'));
            
        // Notificação com 3 dias de antecedência
        $schedule->command('app:send-transaction-reminders --days=3')
            ->dailyAt('08:15')
            ->appendOutputTo(storage_path('logs/notifications.log'));
            
        // Notificação com 7 dias de antecedência
        $schedule->command('app:send-transaction-reminders --days=7')
            ->dailyAt('08:30')
            ->appendOutputTo(storage_path('logs/notifications.log'));

        // Enviar notificações de WhatsApp sobre transações com vencimento ou atrasadas diariamente às 8h
        $schedule->command('notifications:whatsapp-transactions')
                ->dailyAt('08:00')
                ->appendOutputTo(storage_path('logs/whatsapp-notifications.log'));
                
        // Notificações WhatsApp com 1 dia de antecedência
        $schedule->command('notifications:whatsapp-future-transactions --days=1')
                ->dailyAt('08:45')
                ->appendOutputTo(storage_path('logs/whatsapp-notifications.log'));
                
        // Notificações WhatsApp com 3 dias de antecedência
        $schedule->command('notifications:whatsapp-future-transactions --days=3')
                ->dailyAt('09:00')
                ->appendOutputTo(storage_path('logs/whatsapp-notifications.log'));
                
        // Notificações WhatsApp com 7 dias de antecedência
        $schedule->command('notifications:whatsapp-future-transactions --days=7')
                ->dailyAt('09:15')
                ->appendOutputTo(storage_path('logs/whatsapp-notifications.log'));
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
