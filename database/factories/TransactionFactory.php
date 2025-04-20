<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(['income', 'expense']),
            'status' => $this->faker->randomElement(['paid', 'pending']),
            'date' => $this->faker->date(),
            'description' => $this->faker->sentence,
            'amount' => $this->faker->numberBetween(100, 10000),
            'category_id' => 1,
            'account_id' => 1,
            'user_id' => 1,
        ];
    }
}
