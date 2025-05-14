<?php

namespace Tests\Unit\Models;

use App\Models\Transaction;
use App\Models\Category;
use App\Models\Account;
use App\Models\User;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    /** @test */
    public function it_can_create_a_transaction()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $account = Account::factory()->create();

        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'account_id' => $account->id,
        ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'account_id' => $account->id,
        ]);
    }

    /** @test */
    public function it_can_format_amount()
    {
        $transaction = Transaction::factory()->create([
            'amount' => 10000, // 100.00 em centavos
        ]);

        $this->assertEquals('R$ 100,00', $transaction->formatted_amount);
    }

    /** @test */
    public function it_can_mark_as_paid()
    {
        $transaction = Transaction::factory()->create([
            'status' => 'pending',
        ]);

        $transaction->markAsPaid();

        $this->assertEquals('paid', $transaction->fresh()->status);
    }

    /** @test */
    public function it_validates_transaction_data()
    {
        $transaction = new Transaction();
        $validator = $transaction->validate([
            'type' => 'income',
            'status' => 'paid',
            'date' => '2025-04-10',
            'description' => 'Test Transaction',
            'amount' => 10000,
            'category_id' => 1,
            'account_id' => 1,
            'user_id' => 1,
        ]);

        $this->assertTrue($validator->passes());
    }
}
