<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendScheduledNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-scheduled-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envia notificações agendadas para horários específicos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
