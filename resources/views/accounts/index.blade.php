<x-app-layout>
    <div class="container-app">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Contas</h1>
            <a href="{{ route('accounts.create') }}" class="btn btn-primary">
                <i class="ri-add-line mr-2"></i>
                Nova Conta
            </a>
        </div>

        {{-- 
        ATENÇÃO: CONFIGURAÇÃO CRÍTICA - NÃO MODIFICAR
        
        A seção abaixo mostra os saldos de conta e é crítica para a visualização financeira.
        Modificar este código pode causar problemas na exibição de saldos.
        Consulte FINANCIAL_RULES.md antes de qualquer alteração.
        --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @forelse($accounts ?? [] as $account)
                <div class="card p-4 shadow hover:shadow-lg transition">
                    <div class="flex items-center justify-between mb-2">
                        <h2 class="font-semibold text-lg">{{ $account->name }}</h2>
                        <span class="text-sm px-2 py-1 rounded {{ $account->active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $account->active ? 'Ativa' : 'Inativa' }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-600">Tipo: {{ $account->type_label }}</p>
                    
                    @if(isset($isAdmin) && $isAdmin)
                        <p class="text-sm text-gray-600 font-semibold mt-1">
                            Usuário: {{ $account->user->name ?? 'N/A' }}
                        </p>
                    @endif
                    
                    {{-- ATENÇÃO: CORREÇÃO CRÍTICA - USAR recalculateBalance() PARA CÁLCULO DE SALDO - NÃO MODIFICAR OU REMOVER ESTE BLOCO --}}
                    @php
                        // Verifica transações pagas e calcula saldo dinâmico
                        $hasTransactions = $account->transactions()->where('status', 'paid')->exists();
                        $currentBalance = $account->recalculateBalance(); // Cálculo do saldo atual
                    @endphp
                    @if($hasTransactions)
                        <p class="text-sm text-gray-600">Saldo Inicial: <strong>R$ {{ number_format($account->initial_balance, 2, ',', '.') }}</strong></p>
                        <p class="text-sm text-gray-600">Saldo Atual: <strong class="{{ $currentBalance < 0 ? 'text-red-600' : 'text-green-600' }}">R$ {{ number_format($currentBalance, 2, ',', '.') }}</strong></p>
                    @else
                        <p class="text-sm text-gray-600">Saldo: <strong>R$ {{ number_format($account->initial_balance, 2, ',', '.') }}</strong></p>
                    @endif
                    <div class="mt-4 flex space-x-3">
                        <a href="{{ route('accounts.edit', $account) }}" class="text-blue-600 hover:text-blue-800">
                            <i class="ri-pencil-line"></i> Editar
                        </a>
                        <form action="{{ route('accounts.destroy', $account) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta conta?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800">
                                <i class="ri-delete-bin-line"></i> Excluir
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center text-gray-500">
                    Nenhuma conta encontrada.
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout> 