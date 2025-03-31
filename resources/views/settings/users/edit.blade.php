<x-app-layout>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Editar Usuário: {{ $user->name }}</h1>
        <a href="{{ route('settings.users') }}" class="btn btn-secondary">
            <i class="ri-arrow-left-line mr-2"></i>
            Voltar
        </a>
    </div>

    <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
        <div class="max-w-xl">
            <section>
                <header>
                    <h2 class="text-lg font-medium text-gray-900">
                        {{ __('Editar Usuário') }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ __('Atualize as informações do usuário.') }}
                    </p>
                </header>

                @livewire('settings.users.edit-user', ['userId' => $user->id])
            </section>
        </div>
    </div>
</x-app-layout> 