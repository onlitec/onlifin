<x-app-layout>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Editar Conta</h1>
        <a href="{{ route('accounts.index') }}" class="btn btn-secondary">
            <i class="ri-arrow-left-line mr-2"></i>
            Voltar
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('accounts.update', $account) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700">Nome</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $account->name) }}" class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" required>
                    @error('name')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="type" class="block text-sm font-medium text-gray-700">Tipo</label>
                    <select name="type" id="type" class="form-select mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" required>
                        <option value="">Selecione</option>
                        <option value="checking" {{ old('type', $account->type) === 'checking' ? 'selected' : '' }}>Conta Corrente</option>
                        <option value="savings" {{ old('type', $account->type) === 'savings' ? 'selected' : '' }}>Conta Poupança</option>
                        <option value="investment" {{ old('type', $account->type) === 'investment' ? 'selected' : '' }}>Investimento</option>
                        <option value="credit_card" {{ old('type', $account->type) === 'credit_card' ? 'selected' : '' }}>Cartão de Crédito</option>
                        <option value="cash" {{ old('type', $account->type) === 'cash' ? 'selected' : '' }}>Dinheiro</option>
                        <option value="other" {{ old('type', $account->type) === 'other' ? 'selected' : '' }}>Outro</option>
                    </select>
                    @error('type')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="initial_balance" class="block text-sm font-medium text-gray-700">Saldo Inicial</label>
                    <input type="number" name="initial_balance" id="initial_balance" value="{{ old('initial_balance', $account->initial_balance) }}" step="0.01" class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" required>
                    @error('initial_balance')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-700">Descrição</label>
                    <textarea name="description" id="description" rows="3" class="form-textarea mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">{{ old('description', $account->description) }}</textarea>
                    @error('description')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="color" class="block text-sm font-medium text-gray-700">Cor</label>
                    <input type="color" name="color" id="color" value="{{ old('color', $account->color ?? '#6366f1') }}" class="form-input mt-1 block rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 h-10">
                    @error('color')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                @if(isset($isAdmin) && $isAdmin)
                    <!-- Campo de seleção de usuário (apenas para administradores) -->
                    <div class="mb-4">
                        <label for="user_id" class="block text-sm font-medium text-gray-700">Usuário</label>
                        <select name="user_id" id="user_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ $account->user_id == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} ({{ $user->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

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