<!-- Modal de Revisão de Transações -->
<div id="transaction-review-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-6xl shadow-lg rounded-md bg-white">
        <!-- Header do Modal -->
        <div class="flex items-center justify-between pb-4 border-b">
            <h3 class="text-lg font-medium text-gray-900">
                <i class="ri-file-list-3-line mr-2"></i>
                Revisão de Transações Importadas
            </h3>
            <button type="button" class="text-gray-400 hover:text-gray-600" onclick="closeTransactionModal()">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>

        <!-- Informações Gerais -->
        <div class="mt-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="flex items-center">
                        <i class="ri-file-text-line text-blue-600 text-xl mr-2"></i>
                        <div>
                            <p class="text-sm text-gray-600">Total de Transações</p>
                            <p class="text-lg font-semibold text-blue-600" id="total-transactions">0</p>
                        </div>
                    </div>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="flex items-center">
                        <i class="ri-add-circle-line text-green-600 text-xl mr-2"></i>
                        <div>
                            <p class="text-sm text-gray-600">Novas Transações</p>
                            <p class="text-lg font-semibold text-green-600" id="new-transactions">0</p>
                        </div>
                    </div>
                </div>
                <div class="bg-yellow-50 p-4 rounded-lg">
                    <div class="flex items-center">
                        <i class="ri-alert-line text-yellow-600 text-xl mr-2"></i>
                        <div>
                            <p class="text-sm text-gray-600">Duplicatas Encontradas</p>
                            <p class="text-lg font-semibold text-yellow-600" id="duplicate-transactions">0</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="mb-4 flex flex-wrap gap-2">
            <button type="button" class="filter-btn active" data-filter="all">
                <i class="ri-list-check mr-1"></i>
                Todas (<span id="count-all">0</span>)
            </button>
            <button type="button" class="filter-btn" data-filter="new">
                <i class="ri-add-circle-line mr-1"></i>
                Novas (<span id="count-new">0</span>)
            </button>
            <button type="button" class="filter-btn" data-filter="duplicates">
                <i class="ri-alert-line mr-1"></i>
                Duplicatas (<span id="count-duplicates">0</span>)
            </button>
            <button type="button" class="filter-btn" data-filter="categorized">
                <i class="ri-price-tag-3-line mr-1"></i>
                Categorizadas (<span id="count-categorized">0</span>)
            </button>
            <button type="button" class="filter-btn" data-filter="uncategorized">
                <i class="ri-question-line mr-1"></i>
                Sem Categoria (<span id="count-uncategorized">0</span>)
            </button>
        </div>

        <!-- Tabela de Transações -->
        <div class="max-h-96 overflow-y-auto border rounded-lg">
            <table class="w-full">
                <thead class="bg-gray-50 sticky top-0">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <input type="checkbox" id="select-all" class="rounded border-gray-300">
                        </th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoria</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody id="transactions-table-body" class="bg-white divide-y divide-gray-200">
                    <!-- Transações serão inseridas aqui via JavaScript -->
                </tbody>
            </table>
        </div>

        <!-- Footer do Modal -->
        <div class="mt-6 flex items-center justify-between pt-4 border-t">
            <div class="flex items-center space-x-4">
                <label class="flex items-center">
                    <input type="checkbox" id="create-missing-categories" class="rounded border-gray-300" checked>
                    <span class="ml-2 text-sm text-gray-600">Criar categorias que não existem</span>
                </label>
            </div>
            
            <div class="flex space-x-3">
                <button type="button" class="btn btn-secondary" onclick="closeTransactionModal()">
                    <i class="ri-close-line mr-2"></i>
                    Cancelar
                </button>
                <button type="button" class="btn btn-primary" onclick="registerSelectedTransactions()">
                    <i class="ri-save-line mr-2"></i>
                    Registrar Transações Selecionadas
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.filter-btn {
    @apply px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-50 transition-colors;
}

.filter-btn.active {
    @apply bg-blue-500 text-white border-blue-500 hover:bg-blue-600;
}

.transaction-row.duplicate {
    @apply bg-yellow-50;
}

.transaction-row.new {
    @apply bg-green-50;
}

.category-input {
    @apply w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500;
}

.category-select {
    @apply w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500;
}

.duplicate-info {
    @apply text-xs text-yellow-600 mt-1;
}

.confidence-badge {
    @apply inline-flex items-center px-2 py-0.5 rounded text-xs font-medium;
}

.confidence-high {
    @apply bg-green-100 text-green-800;
}

.confidence-medium {
    @apply bg-yellow-100 text-yellow-800;
}

.confidence-low {
    @apply bg-red-100 text-red-800;
}
</style>
