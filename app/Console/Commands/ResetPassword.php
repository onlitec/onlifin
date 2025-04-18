<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ResetPassword extends Command
{
    protected $signature = 'user:reset-password {email} {password}';
    protected $description = 'Reset the password for a specific user';

    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error('User not found');
            return 1;
        }

        $user->password = Hash::make($password);
        $user->save();

        $this->info('Password reset successfully!');
        return 0;
    }
}
