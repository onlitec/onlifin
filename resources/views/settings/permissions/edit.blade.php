<x-app-layout>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Editar Permissão: {{ $permission->name }}</h1>
        <a href="{{ route('settings.permissions') }}" class="btn btn-secondary">
            <i class="ri-arrow-left-line mr-2"></i>
            Voltar
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('settings.permissions.update', $permission) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label for="name" class="form-label">Nome</label>
                    <input type="text" name="name" id="name" class="form-input @error('name') is-invalid @enderror" value="{{ old('name', $permission->name) }}" required>
                    <div class="form-text">Utilize um formato como 'modulo.acao' (ex: users.create, transactions.edit)</div>
                    @error('name')
                        <div class="text-red-500 mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="description" class="form-label">Descrição</label>
                    <textarea name="description" id="description" class="form-input @error('description') is-invalid @enderror" rows="3">{{ old('description', $permission->description) }}</textarea>
                    <div class="form-text">Descrição detalhada do que esta permissão permite fazer</div>
                    @error('description')
                        <div class="text-red-500 mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="category" class="form-label">Categoria</label>
                    <select name="category" id="category" class="form-select @error('category') is-invalid @enderror" required>
                        <option value="">Selecione uma categoria</option>
                        @foreach($categories as $key => $category)
                            <option value="{{ $key }}" {{ old('category', $permission->category) == $key ? 'selected' : '' }}>
                                {{ $category }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Categoria para agrupar esta permissão na interface</div>
                    @error('category')
                        <div class="text-red-500 mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-line mr-2"></i>
                        Atualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout> 