<div class="container">
    <!-- Cabeçalho com botões de ação rápida -->
    <div class="header-actions">
        <!-- Botão de Nova Despesa -->
        <button 
            class="btn-action btn-expense" 
            x-data=""
            x-on:click="$dispatch('open-modal', 'expense')"
        >
            <span class="icon">-</span>
            <span>DESPESA</span>
        </button>

        <!-- Botão de Nova Receita -->
        <button 
            class="btn-action btn-income"
            x-data=""
            x-on:click="$dispatch('open-modal', 'income')"
        >
            <span class="icon">+</span>
            <span>RECEITA</span>
        </button>
    </div>

    <!-- Cabeçalho com Navegação do Mês -->
    <div class="header-section">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
                <div>
                    <h1 class="text-2xl font-semibold">Lançamentos</h1>
                    <div class="text-xs text-gray-500 mt-1">
                        {{ $transactions->count() }} {{ $transactions->count() == 1 ? 'transação' : 'transações' }}
                    </div>
                </div>
                <button class="btn-new-transaction">
                    <span class="plus-icon">+</span>
                </button>
            </div>
            <div class="month-navigation">
                <button class="nav-btn">
                    <span>&lt;</span>
                </button>
                <span class="month-display">Fevereiro 2024</span>
                <button class="nav-btn">
                    <span>&gt;</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Barra de Filtros -->
    <div class="filter-bar">
        <div class="search-box">
            <span class="search-icon">🔍</span>
            <input 
                type="text" 
                placeholder="Filtrar por..." 
                class="search-input"
            >
        </div>
    </div>

    <!-- Lista de Lançamentos -->
    <div class="transactions-list">
        @forelse($transactions as $transaction)
            <div class="transaction-item">
                <livewire:transactions.delete-button :transaction-id="$transaction->id" />
                <div class="transaction-date">
                    {{ $transaction->date->format('d') }}
                    <span class="month">{{ $transaction->date->format('M') }}</span>
                </div>
                <div class="transaction-info">
                    <span class="transaction-title">{{ $transaction->title }}</span>
                    <span class="transaction-category">{{ $transaction->category?->name ?? 'Sem categoria' }}</span>
                </div>
                <div class="transaction-amount {{ $transaction->type === 'expense' ? 'text-danger' : 'text-success' }}">
                    R$ {{ number_format($transaction->amount, 2, ',', '.') }}
                </div>
            </div>
        @empty
            <div class="empty-state">
                <div class="empty-icon">!</div>
                <p>Nenhuma movimentação no período.</p>
            </div>
        @endforelse
    </div>

    <!-- Modal Local -->
    <div x-data="{ 
        open: false,
        transactionType: 'expense',
        init() {
            window.addEventListener('open-modal', (e) => {
                this.transactionType = e.detail;
                this.open = true;
            });
            window.addEventListener('closeModal', () => {
                this.open = false;
            });
        }
    }">
        <div x-show="open">
            <div x-data="{}" x-init="$wire.set('type', transactionType)">
                <livewire:transactions.form-modal />
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos específicos para a página de lançamentos */
.header-section {
    margin-bottom: 2rem;
}

.btn-new-transaction {
    background: var(--danger);
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    transition: transform 0.2s;
}

.btn-new-transaction:hover {
    transform: scale(1.1);
}

.month-navigation {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.nav-btn {
    background: none;
    border: none;
    color: var(--text-light);
    cursor: pointer;
    padding: 0.5rem;
}

.month-display {
    font-size: 1.125rem;
    font-weight: 500;
    color: var(--text-dark);
}

.filter-bar {
    background: white;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    box-shadow: var(--card-shadow);
}

.search-box {
    position: relative;
}

.search-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-light);
}

.search-input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    border: 1px solid var(--border);
    border-radius: 4px;
    font-size: 0.875rem;
}

.transactions-list {
    background: white;
    border-radius: 8px;
    box-shadow: var(--card-shadow);
}

.transaction-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid var(--border);
}

.transaction-date {
    width: 60px;
    text-align: center;
    font-size: 1.25rem;
    font-weight: 500;
    color: var(--text-dark);
}

.transaction-date .month {
    display: block;
    font-size: 0.75rem;
    color: var(--text-light);
    text-transform: uppercase;
}

.transaction-info {
    flex: 1;
    margin-left: 1rem;
}

.transaction-title {
    display: block;
    font-weight: 500;
    color: var(--text-dark);
}

.transaction-category {
    font-size: 0.875rem;
    color: var(--text-light);
}

.transaction-amount {
    font-weight: 600;
    font-size: 1.125rem;
}

.text-danger {
    color: var(--danger);
}

.text-success {
    color: var(--success);
}

.empty-state {
    padding: 3rem;
    text-align: center;
    color: var(--text-light);
}

.empty-icon {
    width: 48px;
    height: 48px;
    background: var(--secondary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 24px;
    color: var(--text-light);
}

@media (max-width: 768px) {
    .transaction-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .transaction-date {
        width: auto;
        text-align: left;
    }

    .transaction-date .month {
        display: inline;
        margin-left: 0.25rem;
    }

    .transaction-info {
        margin-left: 0;
    }

    .transaction-amount {
        align-self: flex-end;
    }
}

.header-actions {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
}

.btn-action {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 4px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-expense {
    background: var(--danger);
    color: white;
}

.btn-income {
    background: var(--success);
    color: white;
}

.icon {
    font-size: 1.25rem;
    font-weight: bold;
}
</style>