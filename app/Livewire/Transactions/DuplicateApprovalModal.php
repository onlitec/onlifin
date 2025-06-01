<?php

namespace App\Livewire\Transactions;

use Livewire\Component;
use App\Services\StatementImportService;
use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

class DuplicateApprovalModal extends Component
{
    
    public $duplicates = [];
    public $newTransactions = [];
    public $accountId;
    public $selectedTransactions = [];
    public $showModal = false;
    
    protected $listeners = [
        'showDuplicateApproval' => 'showApprovalModal'
    ];
    
    public function mount()
    {
        $this->selectedTransactions = [];
    }
    
    public function showApprovalModal($data)
    {
        $this->duplicates = $data['duplicates'] ?? [];
        $this->newTransactions = $data['new_transactions'] ?? [];
        $this->accountId = $data['account_id'] ?? null;
        
        // Inicializar seleções - por padrão, não selecionar duplicatas
        $this->selectedTransactions = [];
        
        // Selecionar automaticamente todas as transações novas (sem duplicatas)
        foreach ($this->newTransactions as $index => $transaction) {
            $this->selectedTransactions['new_' . $index] = true;
        }
        
        $this->showModal = true;
    }
    
    public function toggleTransaction($key)
    {
        $this->selectedTransactions[$key] = !($this->selectedTransactions[$key] ?? false);
    }
    
    public function selectAll()
    {
        // Selecionar todas as transações (novas e duplicatas)
        foreach ($this->newTransactions as $index => $transaction) {
            $this->selectedTransactions['new_' . $index] = true;
        }
        
        foreach ($this->duplicates as $index => $duplicate) {
            $this->selectedTransactions['duplicate_' . $index] = true;
        }
    }
    
    public function deselectAll()
    {
        $this->selectedTransactions = [];
    }
    
    public function selectOnlyNew()
    {
        $this->selectedTransactions = [];
        
        // Selecionar apenas transações novas
        foreach ($this->newTransactions as $index => $transaction) {
            $this->selectedTransactions['new_' . $index] = true;
        }
    }
    
    public function processSelectedTransactions()
    {
        try {
            $approvedTransactions = [];
            
            // Adicionar transações novas selecionadas
            foreach ($this->newTransactions as $index => $transaction) {
                if ($this->selectedTransactions['new_' . $index] ?? false) {
                    $approvedTransactions[] = $transaction;
                }
            }
            
            // Adicionar duplicatas selecionadas
            foreach ($this->duplicates as $index => $duplicate) {
                if ($this->selectedTransactions['duplicate_' . $index] ?? false) {
                    $approvedTransactions[] = $duplicate['new_transaction'];
                }
            }
            
            if (empty($approvedTransactions)) {
                LivewireAlert::title('Aviso')
                    ->text('Nenhuma transação foi selecionada para importação.')
                    ->warning()
                    ->show();
                return;
            }
            
            // Processar transações aprovadas
            $statementService = new StatementImportService();
            $result = $statementService->processApprovedTransactions($approvedTransactions, $this->accountId);
            
            if ($result['success']) {
                $message = "Importação concluída! {$result['transactions_saved']} transações foram importadas.";
                if ($result['transactions_failed'] > 0) {
                    $message .= " {$result['transactions_failed']} transações apresentaram erro.";
                }
                if ($result['categories_created'] > 0) {
                    $message .= " {$result['categories_created']} novas categorias foram criadas.";
                }
                
                LivewireAlert::title('Sucesso')
                    ->text($message)
                    ->success()
                    ->show();
                $this->closeModal();
                
                // Emitir evento para atualizar a lista de transações
                $this->dispatch('transactionsImported');
            } else {
                LivewireAlert::title('Erro')
                    ->text($result['message'] ?? 'Erro ao processar transações.')
                    ->error()
                    ->show();
            }
            
        } catch (\Exception $e) {
            Log::error('Erro ao processar transações aprovadas', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            LivewireAlert::title('Erro')
                ->text('Erro interno ao processar transações.')
                ->error()
                ->show();
        }
    }
    
    public function closeModal()
    {
        $this->showModal = false;
        $this->duplicates = [];
        $this->newTransactions = [];
        $this->selectedTransactions = [];
        $this->accountId = null;
    }
    
    public function getSelectedCountProperty()
    {
        return count(array_filter($this->selectedTransactions));
    }
    
    public function getTotalTransactionsProperty()
    {
        return count($this->newTransactions) + count($this->duplicates);
    }
    
    public function render()
    {
        return view('livewire.transactions.duplicate-approval-modal');
    }
}