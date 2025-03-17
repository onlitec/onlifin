<x-app-layout>
    <div class="container-app">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Relatórios Financeiros</h1>
                <p class="text-gray-600">Gere relatórios detalhados para análise financeira</p>
            </div>
            <a href="{{ route('settings.index') }}" class="btn btn-secondary">
                <i class="ri-arrow-left-line mr-2"></i> Voltar
            </a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger mb-4">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Filtros Avançados -->
        <div class="card mb-6">
            <div class="card-header bg-blue-50">
                <div class="flex items-center">
                    <i class="ri-filter-line text-2xl text-blue-600 mr-3"></i>
                    <h2 class="text-xl font-semibold text-gray-900">Filtros Avançados</h2>
                </div>
            </div>
            <div class="card-body">
                <form id="global-filter-form" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="form-group">
                        <label class="form-label">Período</label>
                        <select id="global-period" class="form-input">
                            <option value="custom">Personalizado</option>
                            <option value="current-month">Mês Atual</option>
                            <option value="last-month">Mês Anterior</option>
                            <option value="current-quarter">Trimestre Atual</option>
                            <option value="current-year">Ano Atual</option>
                            <option value="last-year">Ano Anterior</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Data Inicial</label>
                        <input type="date" id="global-start-date" class="form-input" value="{{ date('Y-m-01') }}">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Data Final</label>
                        <input type="date" id="global-end-date" class="form-input" value="{{ date('Y-m-t') }}">
                    </div>
                </form>
            </div>
        </div>

        <!-- Seção 1: Relatórios de Transações -->
        <h2 class="text-xl font-semibold mb-4 text-gray-800 border-b pb-2">Relatórios de Transações</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
            <!-- Relatório de Transações Completo -->
            <div class="card hover:shadow-md transition-shadow">
                <div class="card-header bg-blue-50">
                    <div class="flex items-center">
                        <i class="ri-file-list-3-line text-2xl text-blue-600 mr-3"></i>
                        <h3 class="text-lg font-semibold">Transações Completo</h3>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-gray-600 mb-4">Exporta todas as transações no período selecionado com detalhes completos.</p>
                    <form action="{{ route('settings.reports.transactions') }}" method="POST" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Data Inicial</label>
                                <input type="date" name="start_date" class="form-input global-start-date" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Data Final</label>
                                <input type="date" name="end_date" class="form-input global-end-date" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-full">
                            <i class="ri-download-line mr-2"></i>
                            Exportar CSV
                        </button>
                    </form>
                </div>
            </div>

            <!-- Relatório de Gastos por Categoria -->
            <div class="card hover:shadow-md transition-shadow">
                <div class="card-header bg-red-50">
                    <div class="flex items-center">
                        <i class="ri-pie-chart-line text-2xl text-red-600 mr-3"></i>
                        <h3 class="text-lg font-semibold">Gastos por Categoria</h3>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-gray-600 mb-4">Análise detalhada dos gastos agrupados por categoria.</p>
                    <form action="{{ route('settings.reports.expenses-by-category') }}" method="POST" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Data Inicial</label>
                                <input type="date" name="start_date" class="form-input global-start-date" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Data Final</label>
                                <input type="date" name="end_date" class="form-input global-end-date" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Ordenar por</label>
                            <select name="sort_by" class="form-input">
                                <option value="amount">Valor (maior para menor)</option>
                                <option value="name">Nome da Categoria</option>
                                <option value="count">Quantidade de Transações</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-full">
                            <i class="ri-download-line mr-2"></i>
                            Exportar CSV
                        </button>
                    </form>
                </div>
            </div>

            <!-- Relatório de Receitas por Categoria -->
            <div class="card hover:shadow-md transition-shadow">
                <div class="card-header bg-green-50">
                    <div class="flex items-center">
                        <i class="ri-bar-chart-line text-2xl text-green-600 mr-3"></i>
                        <h3 class="text-lg font-semibold">Receitas por Categoria</h3>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-gray-600 mb-4">Análise detalhada das receitas agrupadas por categoria.</p>
                    <form action="{{ route('settings.reports.income-by-category') }}" method="POST" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Data Inicial</label>
                                <input type="date" name="start_date" class="form-input global-start-date" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Data Final</label>
                                <input type="date" name="end_date" class="form-input global-end-date" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Ordenar por</label>
                            <select name="sort_by" class="form-input">
                                <option value="amount">Valor (maior para menor)</option>
                                <option value="name">Nome da Categoria</option>
                                <option value="count">Quantidade de Transações</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-full">
                            <i class="ri-download-line mr-2"></i>
                            Exportar CSV
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Seção 2: Análises Financeiras -->
        <h2 class="text-xl font-semibold mb-4 text-gray-800 border-b pb-2">Análises Financeiras</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
            <!-- Fluxo de Caixa -->
            <div class="card hover:shadow-md transition-shadow">
                <div class="card-header bg-purple-50">
                    <div class="flex items-center">
                        <i class="ri-exchange-funds-line text-2xl text-purple-600 mr-3"></i>
                        <h3 class="text-lg font-semibold">Fluxo de Caixa</h3>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-gray-600 mb-4">Relatório detalhado do fluxo de caixa com entradas e saídas diárias.</p>
                    <form action="{{ route('settings.reports.cash-flow') }}" method="POST" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Data Inicial</label>
                                <input type="date" name="start_date" class="form-input global-start-date" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Data Final</label>
                                <input type="date" name="end_date" class="form-input global-end-date" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Agrupar por</label>
                            <select name="group_by" class="form-input">
                                <option value="day">Dia</option>
                                <option value="week">Semana</option>
                                <option value="month">Mês</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-full">
                            <i class="ri-download-line mr-2"></i>
                            Exportar CSV
                        </button>
                    </form>
                </div>
            </div>

            <!-- Análise Comparativa -->
            <div class="card hover:shadow-md transition-shadow">
                <div class="card-header bg-indigo-50">
                    <div class="flex items-center">
                        <i class="ri-line-chart-line text-2xl text-indigo-600 mr-3"></i>
                        <h3 class="text-lg font-semibold">Análise Comparativa</h3>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-gray-600 mb-4">Compare receitas e despesas entre dois períodos distintos.</p>
                    <form action="{{ route('settings.reports.comparative') }}" method="POST" class="space-y-4">
                        @csrf
                        <div class="mb-2">
                            <p class="font-medium text-gray-700">Período 1:</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="form-group">
                                    <label class="form-label">Data Inicial</label>
                                    <input type="date" name="start_date_1" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Data Final</label>
                                    <input type="date" name="end_date_1" class="form-input" required>
                                </div>
                            </div>
                        </div>
                        <div>
                            <p class="font-medium text-gray-700">Período 2:</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="form-group">
                                    <label class="form-label">Data Inicial</label>
                                    <input type="date" name="start_date_2" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Data Final</label>
                                    <input type="date" name="end_date_2" class="form-input" required>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-full">
                            <i class="ri-download-line mr-2"></i>
                            Exportar CSV
                        </button>
                    </form>
                </div>
            </div>

            <!-- Projeção de Receitas e Despesas -->
            <div class="card hover:shadow-md transition-shadow">
                <div class="card-header bg-yellow-50">
                    <div class="flex items-center">
                        <i class="ri-funds-line text-2xl text-yellow-600 mr-3"></i>
                        <h3 class="text-lg font-semibold">Projeção Financeira</h3>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-gray-600 mb-4">Projeção de receitas e despesas para os próximos meses com base em transações recorrentes.</p>
                    <form action="{{ route('settings.reports.projection') }}" method="POST" class="space-y-4">
                        @csrf
                        <div class="form-group">
                            <label class="form-label">Número de meses para projetar</label>
                            <select name="months" class="form-input">
                                <option value="3">3 meses</option>
                                <option value="6">6 meses</option>
                                <option value="12">12 meses</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Incluir transações</label>
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <input type="checkbox" id="include_fixed" name="include_fixed" class="form-checkbox" checked>
                                    <label for="include_fixed" class="ml-2">Fixas</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" id="include_recurring" name="include_recurring" class="form-checkbox" checked>
                                    <label for="include_recurring" class="ml-2">Recorrentes</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" id="include_installments" name="include_installments" class="form-checkbox" checked>
                                    <label for="include_installments" class="ml-2">Parcelas</label>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-full">
                            <i class="ri-download-line mr-2"></i>
                            Exportar CSV
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Seção 3: Relatórios Específicos -->
        <h2 class="text-xl font-semibold mb-4 text-gray-800 border-b pb-2">Relatórios Específicos</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Transações por Conta -->
            <div class="card hover:shadow-md transition-shadow">
                <div class="card-header bg-blue-50">
                    <div class="flex items-center">
                        <i class="ri-bank-card-line text-2xl text-blue-600 mr-3"></i>
                        <h3 class="text-lg font-semibold">Transações por Conta</h3>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-gray-600 mb-4">Relatório de todas as transações agrupadas por conta bancária.</p>
                    <form action="{{ route('settings.reports.by-account') }}" method="POST" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Data Inicial</label>
                                <input type="date" name="start_date" class="form-input global-start-date" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Data Final</label>
                                <input type="date" name="end_date" class="form-input global-end-date" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Conta</label>
                            <select name="account_id" class="form-input">
                                <option value="all">Todas as contas</option>
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-full">
                            <i class="ri-download-line mr-2"></i>
                            Exportar CSV
                        </button>
                    </form>
                </div>
            </div>

            <!-- Pagamentos Pendentes -->
            <div class="card hover:shadow-md transition-shadow">
                <div class="card-header bg-orange-50">
                    <div class="flex items-center">
                        <i class="ri-time-line text-2xl text-orange-600 mr-3"></i>
                        <h3 class="text-lg font-semibold">Pagamentos Pendentes</h3>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-gray-600 mb-4">Lista de todos os pagamentos pendentes com datas de vencimento.</p>
                    <form action="{{ route('settings.reports.pending') }}" method="POST" class="space-y-4">
                        @csrf
                        <div class="form-group">
                            <label class="form-label">Vencimento</label>
                            <select name="due_period" class="form-input">
                                <option value="all">Todos</option>
                                <option value="overdue">Vencidos</option>
                                <option value="next7">Próximos 7 dias</option>
                                <option value="next30">Próximos 30 dias</option>
                                <option value="future">Futuros</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tipo</label>
                            <select name="transaction_type" class="form-input">
                                <option value="all">Todos</option>
                                <option value="income">Receitas</option>
                                <option value="expense">Despesas</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-full">
                            <i class="ri-download-line mr-2"></i>
                            Exportar CSV
                        </button>
                    </form>
                </div>
            </div>

            <!-- Relatório de Lucratividade -->
            <div class="card hover:shadow-md transition-shadow">
                <div class="card-header bg-teal-50">
                    <div class="flex items-center">
                        <i class="ri-coins-line text-2xl text-teal-600 mr-3"></i>
                        <h3 class="text-lg font-semibold">Lucratividade</h3>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-gray-600 mb-4">Análise de lucratividade por período com indicadores financeiros.</p>
                    <form action="{{ route('settings.reports.profitability') }}" method="POST" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Data Inicial</label>
                                <input type="date" name="start_date" class="form-input global-start-date" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Data Final</label>
                                <input type="date" name="end_date" class="form-input global-end-date" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Agrupar por</label>
                            <select name="group_by" class="form-input">
                                <option value="month">Mês</option>
                                <option value="quarter">Trimestre</option>
                                <option value="year">Ano</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-full">
                            <i class="ri-download-line mr-2"></i>
                            Exportar CSV
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Aplicar datas globais para todos os formulários
            const globalStartDate = document.getElementById('global-start-date');
            const globalEndDate = document.getElementById('global-end-date');
            const globalPeriod = document.getElementById('global-period');
            
            // Função para atualizar todos os campos de data
            function updateAllDateFields() {
                document.querySelectorAll('.global-start-date').forEach(input => {
                    input.value = globalStartDate.value;
                });
                document.querySelectorAll('.global-end-date').forEach(input => {
                    input.value = globalEndDate.value;
                });
            }
            
            // Inicializar campos
            updateAllDateFields();
            
            // Adicionar listeners
            globalStartDate.addEventListener('change', updateAllDateFields);
            globalEndDate.addEventListener('change', updateAllDateFields);
            
            // Mudar período
            globalPeriod.addEventListener('change', function() {
                const today = new Date();
                let startDate = new Date();
                let endDate = new Date();
                
                switch(this.value) {
                    case 'current-month':
                        startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                        endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                        break;
                    case 'last-month':
                        startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                        endDate = new Date(today.getFullYear(), today.getMonth(), 0);
                        break;
                    case 'current-quarter':
                        const quarter = Math.floor(today.getMonth() / 3);
                        startDate = new Date(today.getFullYear(), quarter * 3, 1);
                        endDate = new Date(today.getFullYear(), (quarter + 1) * 3, 0);
                        break;
                    case 'current-year':
                        startDate = new Date(today.getFullYear(), 0, 1);
                        endDate = new Date(today.getFullYear(), 11, 31);
                        break;
                    case 'last-year':
                        startDate = new Date(today.getFullYear() - 1, 0, 1);
                        endDate = new Date(today.getFullYear() - 1, 11, 31);
                        break;
                }
                
                globalStartDate.value = startDate.toISOString().split('T')[0];
                globalEndDate.value = endDate.toISOString().split('T')[0];
                
                updateAllDateFields();
            });
        });
    </script>
    @endpush
</x-app-layout> 