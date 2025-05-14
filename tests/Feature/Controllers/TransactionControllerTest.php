<?php

namespace Tests\Feature\Controllers;

use App\Models\Transaction;
use App\Models\Category;
use App\Models\Account;
use App\Models\User;
use Tests\TestCase;

class TransactionControllerTest extends TestCase
{
    /** @test */
    public function it_can_list_transactions()
    {
        $user = User::factory()->create();
        Transaction::factory(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/transactions');

        $response->assertStatus(200);
        $response->assertViewIs('transactions.index');
        $response->assertViewHas('transactions');
    }

    /** @test */
    public function it_can_create_a_transaction()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $account = Account::factory()->create();

        $response = $this->actingAs($user)->post('/transactions', [
            'type' => 'income',
            'status' => 'paid',
            'date' => '2025-04-10',
            'description' => 'Test Transaction',
            'amount' => '100.00',
            'category_id' => $category->id,
            'account_id' => $account->id,
        ]);

        $response->assertRedirect('/transactions');
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => 'income',
            'status' => 'paid',
            'amount' => 10000, // 100.00 em centavos
        ]);
    }

    /** @test */
    public function it_validates_transaction_creation()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/transactions', [
            'type' => '', // campo obrigatório vazio
            'amount' => '-100.00', // valor negativo
        ]);

        $response->assertSessionHasErrors(['type', 'amount']);
    }

    /** @test */
    public function it_can_update_a_transaction()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $account = Account::factory()->create();
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->put('/transactions/' . $transaction->id, [
            'type' => 'expense',
            'status' => 'pending',
            'date' => '2025-04-10',
            'description' => 'Updated Transaction',
            'amount' => '200.00',
            'category_id' => $category->id,
            'account_id' => $account->id,
        ]);

        $response->assertRedirect('/transactions');
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'type' => 'expense',
            'status' => 'pending',
            'amount' => 20000,
        ]);
    }

    /** @test */
    public function it_can_delete_a_transaction()
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->delete('/transactions/' . $transaction->id);

        $response->assertRedirect('/transactions');
        $this->assertDatabaseMissing('transactions', [
            'id' => $transaction->id,
        ]);
    }
}
