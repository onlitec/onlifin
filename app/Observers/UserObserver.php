<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Account;

class UserObserver
{
    public function created(User $user)
    {
        Account::create([
            'name' => 'Conta Principal',
            'type' => 'checking',
            'user_id' => $user->id
        ]);
    }
} 