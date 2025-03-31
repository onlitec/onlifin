<div>
    <section>
        <form wire:submit.prevent="save" class="space-y-6">
            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            @error('general')
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ $message }}
                </div>
            @enderror

            <div>
                <label for="name" class="block font-medium text-sm text-gray-700">
                    {{ __('Nome') }}
                </label>
                <input wire:model="name" id="name" type="text" 
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                @error('name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="email" class="block font-medium text-sm text-gray-700">
                    {{ __('Email') }}
                </label>
                <input wire:model="email" id="email" type="email" 
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                @error('email') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="phone" class="block font-medium text-sm text-gray-700">
                    {{ __('Telefone') }}
                </label>
                <input wire:model="phone" id="phone" type="text" 
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" 
                    placeholder="(00) 00000-0000" />
                @error('phone') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="role_id" class="block font-medium text-sm text-gray-700">
                    {{ __('Perfil') }}
                </label>
                <select wire:model="role_id" id="role_id" 
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Selecione um perfil</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                    @endforeach
                </select>
                @error('role_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
            </div>

            <div class="flex items-center">
                <input type="checkbox" 
                    wire:model="status" 
                    id="status"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                />
                <label for="status" class="ml-2 block text-sm text-gray-900">
                    {{ __('Usuário Ativo') }}
                </label>
                @error('status') <span class="text-red-600 text-sm ml-2">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="password" class="block font-medium text-sm text-gray-700">
                    {{ __('Senha') }}
                </label>
                <input wire:model="password" id="password" type="password" 
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                @error('password') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block font-medium text-sm text-gray-700">
                    {{ __('Confirmar Senha') }}
                </label>
                <input wire:model="password_confirmation" id="password_confirmation" type="password" 
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
            </div>

            <!-- Para propósitos de debug -->
            <div class="bg-gray-100 p-3 rounded">
                <p><strong>Debug:</strong></p>
                <p>Nome: {{ $name }}</p>
                <p>Email: {{ $email }}</p>
                <p>Telefone: {{ $phone }}</p>
                <p>Perfil ID: {{ $role_id }}</p>
                <p>Status: {{ $status ? 'Ativo' : 'Inativo' }}</p>
            </div>

            <div class="flex items-center gap-4 mt-4">
                <button type="submit" 
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all">
                    {{ __('Salvar') }}
                </button>
                
                <a href="/settings/users" 
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all">
                    {{ __('Cancelar') }}
                </a>
            </div>
        </form>
    </section>
</div>
