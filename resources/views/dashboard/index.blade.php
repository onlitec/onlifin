<x-layouts.app>
    <div class="container-app">
        <!-- Totais Gerais -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="card bg-green-50">
                <div class="card-body">
                    <h3 class="text-lg font-semibold text-green-700 mb-2">Total de Receitas</h3>
                    <p class="text-2xl font-bold text-green-600">
                        R$ {{ number_format($totalIncome / 100, 2, ',', '.') }}
                    </p>
                </div>
            </div>

            <div class="card bg-red-50">
                <div class="card-body">
                    <h3 class="text-lg font-semibold text-red-700 mb-2">Total de Despesas</h3>
                    <p class="text-2xl font-bold text-red-600">
                        R$ {{ number_format($totalExpenses / 100, 2, ',', '.') }}
                    </p>
                </div>
            </div>

            <div class="card bg-blue-50">
                <div class="card-body">
                    <h3 class="text-lg font-semibold text-blue-700 mb-2">Saldo</h3>
                    <p class="text-2xl font-bold {{ ($balance >= 0) ? 'text-green-600' : 'text-red-600' }}">
                        R$ {{ number_format($balance / 100, 2, ',', '.') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Totais do Dia e Próximo Dia -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            <!-- Totais de Receitas -->
            <div class="space-y-4">
                <!-- Receitas Hoje -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="card bg-green-50">
                        <div class="card-body">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-sm font-semibold text-green-700">Receitas Hoje</h3>
                                <span class="text-xs text-gray-600">{{ now()->format('d/m/Y') }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <p class="text-xl font-bold text-green-600">
                                    R$ {{ number_format($todayIncomes->where('status', 'pending')->sum('amount') / 100, 2, ',', '.') }}
                                </p>
                                <span class="text-xs px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full">A Receber</span>
                            </div>
                            <div class="flex items-center justify-between mt-2">
                                <p class="text-xl font-bold text-green-600">
                                    R$ {{ number_format($todayIncomes->where('status', 'paid')->sum('amount') / 100, 2, ',', '.') }}
                                </p>
                                <span class="text-xs px-2 py-1 bg-green-100 text-green-800 rounded-full">Recebido</span>
                            </div>
                        </div>
                    </div>

                    <!-- Receitas Amanhã -->
                    <div class="card bg-green-50/50">
                        <div class="card-body">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-sm font-semibold text-green-700">Receitas Amanhã</h3>
                                <span class="text-xs text-gray-600">{{ now()->addDay()->format('d/m/Y') }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <p class="text-xl font-bold text-green-600">
                                    R$ {{ number_format($tomorrowIncomes->where('status', 'pending')->sum('amount') / 100, 2, ',', '.') }}
                                </p>
                                <span class="text-xs px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full">A Receber</span>
                            </div>
                            <div class="flex items-center justify-between mt-2">
                                <p class="text-xl font-bold text-green-600">
                                    R$ {{ number_format($tomorrowIncomes->where('status', 'paid')->sum('amount') / 100, 2, ',', '.') }}
                                </p>
                                <span class="text-xs px-2 py-1 bg-green-100 text-green-800 rounded-full">Recebido</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Totais de Despesas -->
            <div class="space-y-4">
                <!-- Despesas Hoje -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="card bg-red-50">
                        <div class="card-body">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-sm font-semibold text-red-700">Despesas Hoje</h3>
                                <span class="text-xs text-gray-600">{{ now()->format('d/m/Y') }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <p class="text-xl font-bold text-red-600">
                                    R$ {{ number_format($todayExpenses->where('status', 'pending')->sum('amount') / 100, 2, ',', '.') }}
                                </p>
                                <span class="text-xs px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full">A Pagar</span>
                            </div>
                            <div class="flex items-center justify-between mt-2">
                                <p class="text-xl font-bold text-red-600">
                                    R$ {{ number_format($todayExpenses->where('status', 'paid')->sum('amount') / 100, 2, ',', '.') }}
                                </p>
                                <span class="text-xs px-2 py-1 bg-green-100 text-green-800 rounded-full">Pago</span>
                            </div>
                        </div>
                    </div>

                    <!-- Despesas Amanhã -->
                    <div class="card bg-red-50/50">
                        <div class="card-body">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-sm font-semibold text-red-700">Despesas Amanhã</h3>
                                <span class="text-xs text-gray-600">{{ now()->addDay()->format('d/m/Y') }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <p class="text-xl font-bold text-red-600">
                                    R$ {{ number_format($tomorrowExpenses->where('status', 'pending')->sum('amount') / 100, 2, ',', '.') }}
                                </p>
                                <span class="text-xs px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full">A Pagar</span>
                            </div>
                            <div class="flex items-center justify-between mt-2">
                                <p class="text-xl font-bold text-red-600">
                                    R$ {{ number_format($tomorrowExpenses->where('status', 'paid')->sum('amount') / 100, 2, ',', '.') }}
                                </p>
                                <span class="text-xs px-2 py-1 bg-green-100 text-green-800 rounded-full">Pago</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transações do Dia -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Bloco de Receitas -->
            <div class="space-y-6">
                <h2 class="text-xl font-bold text-gray-900">Receitas</h2>
                
                <!-- Receitas de Hoje -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="border-b border-gray-200 bg-green-50 px-6 py-4">
                        <h3 class="text-lg font-semibold text-green-700">
                            Receitas de Hoje
                            <span class="text-sm font-normal text-gray-600 ml-2">
                                ({{ now()->format('d/m/Y') }})
                            </span>
                        </h3>
                    </div>
                    
                    <div class="p-6">
                        @if($todayIncomes->count() > 0)
                            <div class="space-y-4">
                                @foreach($todayIncomes as $income)
                                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200
                                                {{ $income->isPending() ? 'border-l-4 border-yellow-400' : 'border-l-4 border-green-400' }}">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3">
                                                <span class="text-lg font-medium text-gray-900">
                                                    {{ $income->description }}
                                                </span>
                                                <span class="px-2 py-1 text-xs rounded-full {{ $income->isPaid() ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                    {{ $income->isPaid() ? 'Pago' : 'Pendente' }}
                                                </span>
                                            </div>
                                            <div class="mt-1 text-sm text-gray-600">
                                                <span class="inline-flex items-center">
                                                    <i class="ri-price-tag-3-line mr-1"></i>
                                                    {{ $income->category->name }}
                                                </span>
                                                <span class="mx-2">•</span>
                                                <span class="inline-flex items-center">
                                                    <i class="ri-bank-line mr-1"></i>
                                                    {{ $income->account->name }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <span class="text-lg font-bold {{ $income->isPending() ? 'text-gray-600' : 'text-green-600' }}">
                                                R$ {{ number_format($income->amount / 100, 2, ',', '.') }}
                                            </span>
                                            <div class="flex gap-2">
                                                @if($income->isPending())
                                                    <form action="{{ route('transactions.mark-as-paid', $income->id) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" 
                                                                class="p-2 text-green-600 hover:bg-green-100 rounded-lg transition-colors duration-200 flex items-center gap-1"
                                                                title="Marcar como Recebido">
                                                            <i class="ri-checkbox-circle-line text-xl"></i>
                                                            <span class="text-sm">Receber</span>
                                                        </button>
                                                    </form>
                                                @endif
                                                <a href="{{ route('transactions.edit', $income->id) }}" 
                                                   class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg transition-colors duration-200"
                                                   title="Editar receita">
                                                    <i class="ri-edit-line text-xl"></i>
                                                </a>
                                                <form action="{{ route('transactions.destroy', $income->id) }}" 
                                                      method="POST" 
                                                      class="inline"
                                                      onsubmit="return confirm('Tem certeza que deseja excluir esta transação?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="p-2 text-red-600 hover:bg-red-100 rounded-lg transition-colors duration-200"
                                                            title="Excluir">
                                                        <i class="ri-delete-bin-line text-xl"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8 text-gray-500">
                                <i class="ri-inbox-line text-4xl mb-3"></i>
                                <p>Nenhuma receita para hoje</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Receitas de Amanhã -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="border-b border-gray-200 bg-green-50 px-6 py-4">
                        <h3 class="text-lg font-semibold text-green-700">
                            Receitas de Amanhã
                            <span class="text-sm font-normal text-gray-600 ml-2">
                                ({{ now()->addDay()->format('d/m/Y') }})
                            </span>
                        </h3>
                    </div>
                    
                    <div class="p-6">
                        @if($tomorrowIncomes->count() > 0)
                            <div class="space-y-4">
                                @foreach($tomorrowIncomes as $income)
                                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200
                                                {{ $income->isPending() ? 'border-l-4 border-yellow-400' : 'border-l-4 border-green-400' }}">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3">
                                                <span class="text-lg font-medium text-gray-900">
                                                    {{ $income->description }}
                                                </span>
                                                <span class="px-2 py-1 text-xs rounded-full {{ $income->isPaid() ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                    {{ $income->isPaid() ? 'Pago' : 'Pendente' }}
                                                </span>
                                            </div>
                                            <div class="mt-1 text-sm text-gray-600">
                                                <span class="inline-flex items-center">
                                                    <i class="ri-price-tag-3-line mr-1"></i>
                                                    {{ $income->category->name }}
                                                </span>
                                                <span class="mx-2">•</span>
                                                <span class="inline-flex items-center">
                                                    <i class="ri-bank-line mr-1"></i>
                                                    {{ $income->account->name }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <span class="text-lg font-bold {{ $income->isPending() ? 'text-gray-600' : 'text-green-600' }}">
                                                R$ {{ number_format($income->amount / 100, 2, ',', '.') }}
                                            </span>
                                            <div class="flex gap-2">
                                                @if($income->isPending())
                                                    <form action="{{ route('transactions.mark-as-paid', $income->id) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" 
                                                                class="p-2 text-green-600 hover:bg-green-100 rounded-lg transition-colors duration-200 flex items-center gap-1"
                                                                title="Marcar como Recebido">
                                                            <i class="ri-checkbox-circle-line text-xl"></i>
                                                            <span class="text-sm">Receber</span>
                                                        </button>
                                                    </form>
                                                @endif
                                                <a href="{{ route('transactions.edit', $income->id) }}" 
                                                   class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg transition-colors duration-200"
                                                   title="Editar receita">
                                                    <i class="ri-edit-line text-xl"></i>
                                                </a>
                                                <form action="{{ route('transactions.destroy', $income->id) }}" 
                                                      method="POST" 
                                                      class="inline"
                                                      onsubmit="return confirm('Tem certeza que deseja excluir esta transação?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="p-2 text-red-600 hover:bg-red-100 rounded-lg transition-colors duration-200"
                                                            title="Excluir">
                                                        <i class="ri-delete-bin-line text-xl"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8 text-gray-500">
                                <i class="ri-inbox-line text-4xl mb-3"></i>
                                <p>Nenhuma receita para amanhã</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Bloco de Despesas -->
            <div class="space-y-6">
                <h2 class="text-xl font-bold text-gray-900">Despesas</h2>
                
                <!-- Despesas de Hoje -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="border-b border-gray-200 bg-red-50 px-6 py-4">
                        <h3 class="text-lg font-semibold text-red-700">
                            Despesas de Hoje
                            <span class="text-sm font-normal text-gray-600 ml-2">
                                ({{ now()->format('d/m/Y') }})
                            </span>
                        </h3>
                    </div>
                    
                    <div class="p-6">
                        @if($todayExpenses->count() > 0)
                            <div class="space-y-4">
                                @foreach($todayExpenses as $expense)
                                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200
                                                {{ $expense->isPending() ? 'border-l-4 border-yellow-400' : 'border-l-4 border-red-400' }}">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3">
                                                <span class="text-lg font-medium text-gray-900">
                                                    {{ $expense->description }}
                                                </span>
                                                <span class="px-2 py-1 text-xs rounded-full {{ $expense->isPaid() ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                    {{ $expense->isPaid() ? 'Pago' : 'Pendente' }}
                                                </span>
                                            </div>
                                            <div class="mt-1 text-sm text-gray-600">
                                                <span class="inline-flex items-center">
                                                    <i class="ri-price-tag-3-line mr-1"></i>
                                                    {{ $expense->category->name }}
                                                </span>
                                                <span class="mx-2">•</span>
                                                <span class="inline-flex items-center">
                                                    <i class="ri-bank-line mr-1"></i>
                                                    {{ $expense->account->name }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <span class="text-lg font-bold {{ $expense->isPending() ? 'text-gray-600' : 'text-red-600' }}">
                                                R$ {{ number_format($expense->amount / 100, 2, ',', '.') }}
                                            </span>
                                            <div class="flex gap-2">
                                                @if($expense->isPending())
                                                    <form action="{{ route('transactions.mark-as-paid', $expense->id) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" 
                                                                class="p-2 text-green-600 hover:bg-green-100 rounded-lg transition-colors duration-200 flex items-center gap-1"
                                                                title="Marcar como Pago">
                                                            <i class="ri-checkbox-circle-line text-xl"></i>
                                                            <span class="text-sm">Pagar</span>
                                                        </button>
                                                    </form>
                                                @endif
                                                <a href="{{ route('transactions.edit', $expense->id) }}" 
                                                   class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg transition-colors duration-200"
                                                   title="Editar despesa">
                                                    <i class="ri-edit-line text-xl"></i>
                                                </a>
                                                <form action="{{ route('transactions.destroy', $expense->id) }}" 
                                                      method="POST" 
                                                      class="inline"
                                                      onsubmit="return confirm('Tem certeza que deseja excluir esta transação?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="p-2 text-red-600 hover:bg-red-100 rounded-lg transition-colors duration-200"
                                                            title="Excluir">
                                                        <i class="ri-delete-bin-line text-xl"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8 text-gray-500">
                                <i class="ri-inbox-line text-4xl mb-3"></i>
                                <p>Nenhuma despesa para hoje</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Despesas de Amanhã -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="border-b border-gray-200 bg-red-50 px-6 py-4">
                        <h3 class="text-lg font-semibold text-red-700">
                            Despesas de Amanhã
                            <span class="text-sm font-normal text-gray-600 ml-2">
                                ({{ now()->addDay()->format('d/m/Y') }})
                            </span>
                        </h3>
                    </div>
                    
                    <div class="p-6">
                        @if($tomorrowExpenses->count() > 0)
                            <div class="space-y-4">
                                @foreach($tomorrowExpenses as $expense)
                                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200
                                                {{ $expense->isPending() ? 'border-l-4 border-yellow-400' : 'border-l-4 border-red-400' }}">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3">
                                                <span class="text-lg font-medium text-gray-900">
                                                    {{ $expense->description }}
                                                </span>
                                                <span class="px-2 py-1 text-xs rounded-full {{ $expense->isPaid() ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                    {{ $expense->isPaid() ? 'Pago' : 'Pendente' }}
                                                </span>
                                            </div>
                                            <div class="mt-1 text-sm text-gray-600">
                                                <span class="inline-flex items-center">
                                                    <i class="ri-price-tag-3-line mr-1"></i>
                                                    {{ $expense->category->name }}
                                                </span>
                                                <span class="mx-2">•</span>
                                                <span class="inline-flex items-center">
                                                    <i class="ri-bank-line mr-1"></i>
                                                    {{ $expense->account->name }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <span class="text-lg font-bold {{ $expense->isPending() ? 'text-gray-600' : 'text-red-600' }}">
                                                R$ {{ number_format($expense->amount / 100, 2, ',', '.') }}
                                            </span>
                                            <div class="flex gap-2">
                                                @if($expense->isPending())
                                                    <form action="{{ route('transactions.mark-as-paid', $expense->id) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" 
                                                                class="p-2 text-green-600 hover:bg-green-100 rounded-lg transition-colors duration-200 flex items-center gap-1"
                                                                title="Marcar como Pago">
                                                            <i class="ri-checkbox-circle-line text-xl"></i>
                                                            <span class="text-sm">Pagar</span>
                                                        </button>
                                                    </form>
                                                @endif
                                                <a href="{{ route('transactions.edit', $expense->id) }}" 
                                                   class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg transition-colors duration-200"
                                                   title="Editar despesa">
                                                    <i class="ri-edit-line text-xl"></i>
                                                </a>
                                                <form action="{{ route('transactions.destroy', $expense->id) }}" 
                                                      method="POST" 
                                                      class="inline"
                                                      onsubmit="return confirm('Tem certeza que deseja excluir esta transação?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="p-2 text-red-600 hover:bg-red-100 rounded-lg transition-colors duration-200"
                                                            title="Excluir">
                                                        <i class="ri-delete-bin-line text-xl"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8 text-gray-500">
                                <i class="ri-inbox-line text-4xl mb-3"></i>
                                <p>Nenhuma despesa para amanhã</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Adicione uma nova seção para transações pendentes -->
        <div class="mt-8">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Próximos Vencimentos</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Receitas Pendentes -->
                <div class="card">
                    <div class="card-body">
                        <h3 class="text-lg font-semibold text-green-700 mb-4">
                            Receitas Pendentes
                        </h3>
                        @if($pendingIncomes->count() > 0)
                            <div class="space-y-3">
                                @foreach($pendingIncomes as $income)
                                    <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg group 
                                                {{ $income->isPending() ? 'border border-yellow-400' : '' }}">
                                        <div class="flex-grow">
                                            <div class="flex items-center gap-2">
                                                <p class="font-medium">{{ $income->description }}</p>
                                                <span class="px-2 py-1 text-xs rounded-full {{ $income->isPaid() ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                    {{ $income->isPaid() ? 'Pago' : 'Pendente' }}
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-600">
                                                {{ $income->category->name }}
                                                <span class="text-xs text-gray-500">
                                                    • {{ $income->account->name }}
                                                </span>
                                            </p>
                                        </div>
                                        <div class="flex items-center space-x-3">
                                            <p class="font-bold {{ $income->isPending() ? 'text-gray-600' : 'text-green-600' }}">
                                                R$ {{ number_format($income->amount / 100, 2, ',', '.') }}
                                            </p>
                                            <div class="flex space-x-2">
                                                @if($income->isPending())
                                                    <form action="{{ route('transactions.mark-as-paid', $income->id) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" 
                                                                class="btn btn-sm btn-success opacity-0 group-hover:opacity-100 transition-opacity"
                                                                title="Marcar como Recebido">
                                                            <i class="ri-checkbox-circle-line"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                <a href="{{ route('transactions.edit', $income->id) }}" 
                                                   class="btn btn-sm btn-secondary opacity-0 group-hover:opacity-100 transition-opacity"
                                                   title="Editar receita">
                                                    <i class="ri-edit-line"></i>
                                                </a>
                                                <form action="{{ route('transactions.destroy', $income->id) }}" 
                                                      method="POST" 
                                                      class="inline"
                                                      onsubmit="return confirm('Tem certeza que deseja excluir esta transação?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="btn btn-sm btn-danger opacity-0 group-hover:opacity-100 transition-opacity"
                                                            title="Excluir">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500 text-center">Nenhuma receita pendente</p>
                        @endif
                    </div>
                </div>

                <!-- Despesas Pendentes -->
                <div class="card">
                    <div class="card-body">
                        <h3 class="text-lg font-semibold text-red-700 mb-4">
                            Despesas Pendentes
                        </h3>
                        @if($pendingExpenses->count() > 0)
                            <div class="space-y-3">
                                @foreach($pendingExpenses as $expense)
                                    <div class="flex justify-between items-center p-3 bg-red-50 rounded-lg group 
                                                {{ $expense->isPending() ? 'border border-yellow-400' : '' }}">
                                        <div class="flex-grow">
                                            <div class="flex items-center gap-2">
                                                <p class="font-medium">{{ $expense->description }}</p>
                                                <span class="px-2 py-1 text-xs rounded-full {{ $expense->isPaid() ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                    {{ $expense->isPaid() ? 'Pago' : 'Pendente' }}
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-600">
                                                {{ $expense->category->name }}
                                                <span class="text-xs text-gray-500">
                                                    • {{ $expense->account->name }}
                                                </span>
                                            </p>
                                        </div>
                                        <div class="flex items-center space-x-3">
                                            <p class="font-bold {{ $expense->isPending() ? 'text-gray-600' : 'text-red-600' }}">
                                                R$ {{ number_format($expense->amount / 100, 2, ',', '.') }}
                                            </p>
                                            <div class="flex space-x-2">
                                                @if($expense->isPending())
                                                    <form action="{{ route('transactions.mark-as-paid', $expense->id) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" 
                                                                class="btn btn-sm btn-success opacity-0 group-hover:opacity-100 transition-opacity"
                                                                title="Marcar como Pago">
                                                            <i class="ri-checkbox-circle-line"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                <a href="{{ route('transactions.edit', $expense->id) }}" 
                                                   class="btn btn-sm btn-secondary opacity-0 group-hover:opacity-100 transition-opacity"
                                                   title="Editar despesa">
                                                    <i class="ri-edit-line"></i>
                                                </a>
                                                <form action="{{ route('transactions.destroy', $expense->id) }}" 
                                                      method="POST" 
                                                      class="inline"
                                                      onsubmit="return confirm('Tem certeza que deseja excluir esta transação?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="btn btn-sm btn-danger opacity-0 group-hover:opacity-100 transition-opacity"
                                                            title="Excluir">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500 text-center">Nenhuma despesa pendente</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app> 