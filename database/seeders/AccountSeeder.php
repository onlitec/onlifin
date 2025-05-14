<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;
use App\Models\User;

class AccountSeeder extends Seeder
{
    public function run()
    {
        $admin = User::where('email', 'alfreire@onlitec.com.br')->first();

        if ($admin) {
            Account::create([
                'name' => 'Conta Corrente',
                'type' => 'checking',
                'initial_balance' => 0,
                'active' => true,
                'user_id' => $admin->id,
            ]);

            Account::create([
                'name' => 'Poupança',
                'type' => 'savings',
                'initial_balance' => 0,
                'active' => true,
                'user_id' => $admin->id,
            ]);

            Account::create([
                'name' => 'Cartão de Crédito',
                'type' => 'credit_card',
                'initial_balance' => 0,
                'active' => true,
                'user_id' => $admin->id,
            ]);
        }
    }
} 