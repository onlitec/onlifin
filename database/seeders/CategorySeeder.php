<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\User;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $users = User::all();

        $categories = [
            ['name' => 'Alimentação', 'type' => 'expense'],
            ['name' => 'Transporte', 'type' => 'expense'],
            ['name' => 'Moradia', 'type' => 'expense'],
            ['name' => 'Salário', 'type' => 'income'],
            ['name' => 'Freelance', 'type' => 'income'],
        ];

        foreach ($users as $user) {
            foreach ($categories as $category) {
                Category::create([
                    'name' => $category['name'],
                    'type' => $category['type'],
                    'user_id' => $user->id
                ]);
            }
        }
    }
} 