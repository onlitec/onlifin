<?php

namespace Tests\Feature\AI;

use App\Models\Transaction;
use App\Models\Category;
use App\Models\Account;
use App\Models\User;
use Tests\TestCase;

class TransactionCategorizationTest extends TestCase
{
    /** @test */
    public function it_can_categorize_transactions_automatically()
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $category = Category::factory()->create(['name' => 'Supermercado']);

        // Simula uma transação com descrição típica de supermercado
        $transaction = Transaction::factory()->create([
            'account_id' => $account->id,
            'description' => 'Compra no Supermercado ABC',
            'amount' => 15000, // 150.00 em centavos
        ]);

        // Simula a chamada à API de IA
        $this->mock(\App\Services\AIService::class, function ($mock) {
            $mock->shouldReceive('categorizeTransaction')
                ->andReturn(['category' => 'Supermercado']);
        });

        $response = $this->actingAs($user)->post('/transactions/' . $transaction->id . '/ai-categorize');

        $response->assertRedirect('/transactions/' . $transaction->id . '/edit');
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'category_id' => $category->id,
        ]);
    }

    /** @test */
    public function it_can_learn_from_user_feedback()
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $category = Category::factory()->create(['name' => 'Restaurante']);

        // Simula uma transação com descrição ambígua
        $transaction = Transaction::factory()->create([
            'account_id' => $account->id,
            'description' => 'Almoço no Local XYZ',
            'amount' => 8000, // 80.00 em centavos
        ]);

        // Simula o feedback do usuário
        $response = $this->actingAs($user)->post('/transactions/' . $transaction->id . '/feedback', [
            'category_id' => $category->id,
        ]);

        $response->assertRedirect('/transactions/' . $transaction->id . '/edit');
        $this->assertDatabaseHas('transaction_feedbacks', [
            'transaction_id' => $transaction->id,
            'category_id' => $category->id,
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function it_handles_ai_service_errors()
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $transaction = Transaction::factory()->create([
            'account_id' => $account->id,
            'description' => 'Compra no Mercado',
            'amount' => 5000, // 50.00 em centavos
        ]);

        // Simula falha na API de IA
        $this->mock(\App\Services\AIService::class, function ($mock) {
            $mock->shouldReceive('categorizeTransaction')
                ->andThrow(new \Exception('Service unavailable'));
        });

        $response = $this->actingAs($user)->post('/transactions/' . $transaction->id . '/ai-categorize');

        $response->assertRedirect('/transactions/' . $transaction->id . '/edit');
        $response->assertSessionHasErrors(['ai_error']);
    }
}
