<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class TransactionTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $account;
    protected $category;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->account = Account::factory()->create(['user_id' => $this->user->id]);
        $this->category = Category::factory()->create(['user_id' => $this->user->id, 'type' => 'expense']);
        
        Sanctum::actingAs($this->user);
    }

    /**
     * Test listing transactions
     */
    public function test_user_can_list_transactions()
    {
        Transaction::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id
        ]);

        $response = $this->getJson('/api/transactions');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'transactions' => [
                            '*' => ['id', 'type', 'status', 'date', 'description', 'amount']
                        ],
                        'pagination'
                    ]
                ]);
    }

    /**
     * Test creating a transaction
     */
    public function test_user_can_create_transaction()
    {
        $transactionData = [
            'type' => 'expense',
            'status' => 'paid',
            'date' => now()->format('Y-m-d'),
            'description' => 'Test Transaction',
            'amount' => 100.50,
            'category_id' => $this->category->id,
            'account_id' => $this->account->id,
            'notes' => 'Test notes'
        ];

        $response = $this->postJson('/api/transactions', $transactionData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'transaction' => ['id', 'type', 'status', 'description', 'amount']
                    ]
                ]);

        $this->assertDatabaseHas('transactions', [
            'description' => 'Test Transaction',
            'user_id' => $this->user->id,
            'amount' => 10050 // Stored in cents
        ]);
    }

    /**
     * Test transaction validation
     */
    public function test_transaction_creation_validation()
    {
        $response = $this->postJson('/api/transactions', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'type', 'status', 'date', 'description', 'amount', 'category_id', 'account_id'
                ]);
    }

    /**
     * Test showing a specific transaction
     */
    public function test_user_can_view_transaction()
    {
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id
        ]);

        $response = $this->getJson("/api/transactions/{$transaction->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'transaction' => ['id', 'type', 'status', 'description', 'amount']
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'transaction' => [
                            'id' => $transaction->id
                        ]
                    ]
                ]);
    }

    /**
     * Test updating a transaction
     */
    public function test_user_can_update_transaction()
    {
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id
        ]);

        $updateData = [
            'description' => 'Updated Transaction',
            'amount' => 200.75
        ];

        $response = $this->putJson("/api/transactions/{$transaction->id}", $updateData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'transaction' => ['id', 'description', 'amount']
                    ]
                ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'description' => 'Updated Transaction',
            'amount' => 20075 // Stored in cents
        ]);
    }

    /**
     * Test deleting a transaction
     */
    public function test_user_can_delete_transaction()
    {
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id
        ]);

        $response = $this->deleteJson("/api/transactions/{$transaction->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Transação excluída com sucesso'
                ]);

        $this->assertDatabaseMissing('transactions', [
            'id' => $transaction->id
        ]);
    }

    /**
     * Test transaction summary
     */
    public function test_user_can_get_transaction_summary()
    {
        // Create some test transactions
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'income',
            'status' => 'paid',
            'amount' => 50000 // R$ 500.00 in cents
        ]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'status' => 'paid',
            'amount' => 30000 // R$ 300.00 in cents
        ]);

        $response = $this->getJson('/api/transactions/summary');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'summary' => [
                            'income' => ['paid', 'pending', 'total'],
                            'expense' => ['paid', 'pending', 'total'],
                            'balance' => ['paid', 'pending', 'total']
                        ]
                    ]
                ]);
    }

    /**
     * Test user cannot access other user's transactions
     */
    public function test_user_cannot_access_other_users_transactions()
    {
        $otherUser = User::factory()->create();
        $otherAccount = Account::factory()->create(['user_id' => $otherUser->id]);
        $otherCategory = Category::factory()->create(['user_id' => $otherUser->id]);
        
        $otherTransaction = Transaction::factory()->create([
            'user_id' => $otherUser->id,
            'account_id' => $otherAccount->id,
            'category_id' => $otherCategory->id
        ]);

        $response = $this->getJson("/api/transactions/{$otherTransaction->id}");

        $response->assertStatus(404);
    }

    /**
     * Test filtering transactions by type
     */
    public function test_user_can_filter_transactions_by_type()
    {
        $incomeCategory = Category::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'income'
        ]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'expense'
        ]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $incomeCategory->id,
            'type' => 'income'
        ]);

        $response = $this->getJson('/api/transactions?type=income');

        $response->assertStatus(200);
        
        $transactions = $response->json('data.transactions');
        $this->assertCount(1, $transactions);
        $this->assertEquals('income', $transactions[0]['type']);
    }

    /**
     * Test transaction search
     */
    public function test_user_can_search_transactions()
    {
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'description' => 'Grocery Shopping'
        ]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'description' => 'Gas Station'
        ]);

        $response = $this->getJson('/api/transactions?search=Grocery');

        $response->assertStatus(200);
        
        $transactions = $response->json('data.transactions');
        $this->assertCount(1, $transactions);
        $this->assertStringContainsString('Grocery', $transactions[0]['description']);
    }
}
