<<<<<<< HEAD
<x-app-layout>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Editar Categoria</h1>
        <a href="{{ route('categories.index') }}" class="btn btn-secondary">
            <i class="ri-arrow-left-line mr-2"></i>
            Voltar
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('categories.update', $category) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700">Nome</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}" class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" required>
                    @error('name')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="type" class="block text-sm font-medium text-gray-700">Tipo</label>
                    <select name="type" id="type" class="form-select mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" required>
                        <option value="">Selecione</option>
                        <option value="income" {{ old('type', $category->type) === 'income' ? 'selected' : '' }}>Receita</option>
                        <option value="expense" {{ old('type', $category->type) === 'expense' ? 'selected' : '' }}>Despesa</option>
                    </select>
                    @error('type')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-700">Descrição</label>
                    <textarea name="description" id="description" rows="3" class="form-textarea mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">{{ old('description', $category->description) }}</textarea>
                    @error('description')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="color" class="block text-sm font-medium text-gray-700">Cor</label>
                    <input type="color" name="color" id="color" value="{{ old('color', $category->color ?? '#3b82f6') }}" class="form-input mt-1 block rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 h-10">
                    @error('color')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-line mr-2"></i>
                        Atualizar
=======
<x-layouts.app>
    <div class="container-app max-w-4xl mx-auto">
        <!-- Cabeçalho -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Editar Categoria</h1>
                <p class="mt-1 text-sm text-gray-600">Atualize os dados da categoria</p>
            </div>
            <a href="{{ route('categories.index') }}" class="btn btn-secondary">
                <i class="ri-arrow-left-line mr-2"></i>
                Voltar
            </a>
        </div>

        <!-- Card do Formulário -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <form action="{{ route('categories.update', $category->id) }}" method="POST">
                @csrf
                @method('PUT')
                
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
                                value="{{ old('name', $category->name) }}" 
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
                                <option value="income" {{ old('type', $category->type) === 'income' ? 'selected' : '' }}>Receita</option>
                                <option value="expense" {{ old('type', $category->type) === 'expense' ? 'selected' : '' }}>Despesa</option>
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
                            rows="3">{{ old('description', $category->description) }}</textarea>
                    </div>
                </div>

                <!-- Botões -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3 rounded-b-xl">
                    <a href="{{ route('categories.index') }}" class="btn btn-secondary">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-line mr-2"></i>
                        Atualizar Categoria
>>>>>>> remotes/ONLITEC/fix/campo-valor
                    </button>
                </div>
            </form>
        </div>
    </div>
<<<<<<< HEAD
</x-app-layout> 
=======
</x-layouts.app> 
>>>>>>> remotes/ONLITEC/fix/campo-valor
