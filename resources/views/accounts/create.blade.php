<x-layouts.app>
    <div class="container-app max-w-4xl mx-auto">
        <!-- Cabeçalho -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Nova Conta</h1>
                <p class="mt-1 text-sm text-gray-600">Adicione uma nova conta bancária</p>
            </div>
            <a href="{{ route('accounts') }}" class="btn btn-secondary">
                <i class="ri-arrow-left-line mr-2"></i>
                Voltar
            </a>
        </div>

        <!-- Card do Formulário -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <form action="{{ route('accounts.store') }}" method="POST">
                @csrf
                
                <div class="p-6 space-y-6">
                    <!-- Nome e Tipo -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Nome -->
                        <div class="form-group">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                Nome da Conta
                            </label>
                            <input type="text" name="name" id="name" 
                                class="form-input block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                value="{{ old('name') }}" 
                                required>
                        </div>

                        <!-- Tipo -->
                        <div class="form-group">
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">
                                Tipo de Conta
                            </label>
                            <select name="type" id="type" 
                                class="form-select block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                required>
                                <option value="checking" {{ old('type') === 'checking' ? 'selected' : '' }}>Conta Corrente</option>
                                <option value="savings" {{ old('type') === 'savings' ? 'selected' : '' }}>Conta Poupança</option>
                                <option value="investment" {{ old('type') === 'investment' ? 'selected' : '' }}>Conta Investimento</option>
                                <option value="credit" {{ old('type') === 'credit' ? 'selected' : '' }}>Cartão de Crédito</option>
                                <option value="other" {{ old('type') === 'other' ? 'selected' : '' }}>Outra</option>
                            </select>
                        </div>
                    </div>

                    <!-- Saldo Inicial -->
                    <div class="form-group">
                        <label for="initial_balance" class="block text-sm font-medium text-gray-700 mb-1">
                            Saldo Inicial
                        </label>
                        <input type="text" 
                               name="initial_balance" 
                               id="initial_balance" 
                               x-data="moneyMask" 
                               x-ref="input"
                               class="form-input block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                               placeholder="Saldo Inicial"
                               required>
                    </div>

                    <!-- Descrição -->
                    <div class="form-group">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                            Descrição
                        </label>
                        <textarea name="description" id="description" 
                            class="form-textarea block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            rows="3">{{ old('description') }}</textarea>
                    </div>

                    <!-- Status -->
                    <div class="form-group">
                        <label class="flex items-center">
                            <input type="checkbox" name="active" value="1" 
                                class="form-checkbox rounded text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                {{ old('active', true) ? 'checked' : '' }}>
                            <span class="ml-2 text-sm text-gray-600">Conta Ativa</span>
                        </label>
                    </div>
                </div>

                <!-- Botões -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3 rounded-b-xl">
                    <a href="{{ route('accounts') }}" class="btn btn-secondary">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-line mr-2"></i>
                        Criar Conta
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app> 