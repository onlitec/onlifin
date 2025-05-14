<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\Category;
use App\Models\Account;
use App\Models\User;
use Tests\TestCase;

class StatementImportTest extends TestCase
{
    /** @test */
    public function it_can_upload_statement_file()
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        $file = $this->createTestStatementFile();

        $response = $this->actingAs($user)->post('/statements/upload', [
            'account_id' => $account->id,
            'file' => $file,
        ]);

        $response->assertRedirect('/statements/mapping');
        $this->assertDatabaseHas('statement_imports', [
            'account_id' => $account->id,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_can_map_transactions()
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $category = Category::factory()->create();

        $import = $this->createTestImport($account);

        $response = $this->actingAs($user)->post('/statements/save', [
            'import_id' => $import->id,
            'mappings' => [
                [
                    'description' => 'Test Transaction',
                    'category_id' => $category->id,
                    'type' => 'expense',
                    'amount' => '100.00',
                ],
            ],
        ]);

        $response->assertRedirect('/transactions');
        $this->assertDatabaseHas('transactions', [
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 10000,
        ]);
    }

    /** @test */
    public function it_validates_statement_mapping()
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        $import = $this->createTestImport($account);

        $response = $this->actingAs($user)->post('/statements/save', [
            'import_id' => $import->id,
            'mappings' => [
                [
                    'description' => '', // descrição vazia
                    'category_id' => null, // categoria não selecionada
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['mappings.0.description', 'mappings.0.category_id']);
    }

    /** @test */
    public function it_can_save_transaction_mapping()
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $category = Category::factory()->create();

        $import = $this->createTestImport($account);

        $response = $this->actingAs($user)->post('/statements/save', [
            'import_id' => $import->id,
            'mappings' => [
                [
                    'description' => 'Test Transaction',
                    'category_id' => $category->id,
                    'type' => 'expense',
                    'amount' => '100.00',
                ],
            ],
        ]);

        $response->assertRedirect('/transactions');
        $this->assertDatabaseHas('transactions', [
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 10000,
        ]);
    }

    protected function createTestStatementFile()
    {
        return \Illuminate\Http\UploadedFile::fake()->create(
            'statement.csv',
            100,
            'text/csv'
        );
    }

    protected function createTestImport($account)
    {
        $import = \App\Models\StatementImport::factory()->create([
            'account_id' => $account->id,
            'status' => 'pending',
        ]);

        // Simula a criação de transações no banco de dados
        \App\Models\StatementTransaction::factory()->create([
            'import_id' => $import->id,
            'description' => 'Test Transaction',
            'amount' => 10000,
            'date' => now(),
        ]);

        return $import;
    }
}
