<x-app-layout>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Editar Usuário: {{ $user->name }}</h1>
        <a href="{{ route('settings.users') }}" class="btn btn-secondary">
            <i class="ri-arrow-left-line mr-2"></i>
            Voltar
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('settings.users.update', $user) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label for="name" class="form-label">Nome</label>
                    <input type="text" name="name" id="name" class="form-input @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                    @error('name')
                        <div class="text-red-500 mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" name="email" id="email" class="form-input @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                    @error('email')
                        <div class="text-red-500 mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Nova Senha (deixe em branco para manter a atual)</label>
                    <input type="password" name="password" id="password" class="form-input @error('password') is-invalid @enderror">
                    @error('password')
                        <div class="text-red-500 mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password_confirmation" class="form-label">Confirmar Nova Senha</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-input">
                </div>

                <div class="mb-4">
                    <label class="form-label">Perfis</label>
                    <div class="space-y-2">
                        @foreach($roles as $role)
                            <div class="flex items-center">
                                <input type="checkbox" name="roles[]" id="role{{ $role->id }}" value="{{ $role->id }}" class="form-checkbox" {{ in_array($role->id, old('roles', $userRoles)) ? 'checked' : '' }}>
                                <label for="role{{ $role->id }}" class="ml-2">{{ $role->name }}</label>
                            </div>
                        @endforeach
                    </div>
                    @error('roles')
                        <div class="text-red-500 mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" class="form-checkbox" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                        <label for="is_active" class="ml-2">Ativo</label>
                    </div>
                    @error('is_active')
                        <div class="text-red-500 mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-line mr-2"></i>
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout> 