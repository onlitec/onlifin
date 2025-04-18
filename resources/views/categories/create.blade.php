<x-app-layout>
    <div class="container-app max-w-4xl mx-auto">
        <!-- Cabeçalho -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Nova Categoria</h1>
                <p class="mt-1 text-sm text-gray-600">Crie uma nova categoria para organizar suas transações</p>
            </div>
            <a href="{{ route('categories.index') }}" class="btn btn-secondary">
                <i class="ri-arrow-left-line mr-2"></i>
                Voltar
            </a>
        </div>

        <!-- Card do Formulário -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <form action="{{ route('categories.store') }}" method="POST">
                @csrf
                
                <div class="p-6 space-y-6">
                    <!-- Nome e Tipo -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Nome -->
                        <div class="form-group">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                Nome
                            </label>
                            <input type="text" name="name" id="name" 
                                class="form-input block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                value="{{ old('name') }}" 
                                required>
                        </div>

                        <!-- Tipo -->
                        <div class="form-group">
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">
                                Tipo
                            </label>
                            <select name="type" id="type" 
                                class="form-select block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                required>
                                <option value="income" {{ old('type') === 'income' ? 'selected' : '' }}>Receita</option>
                                <option value="expense" {{ old('type') === 'expense' ? 'selected' : '' }}>Despesa</option>
                            </select>
                        </div>
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

                    <!-- Cor -->
                    <div class="form-group">
                        <label for="color" class="block text-sm font-medium text-gray-700 mb-1">
                            Cor
                        </label>
                        <input type="color" name="color" id="color" 
                            class="form-input block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            value="{{ old('color', '#3b82f6') }}" 
                            required>
                    </div>
                </div>

                <!-- Botões -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3 rounded-b-xl">
                    <a href="{{ route('categories.index') }}" class="btn btn-secondary">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-line mr-2"></i>
                        Criar Categoria
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
