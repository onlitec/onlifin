<x-app-layout>
    {{-- Início do formulário de edição de Empresa --}}
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <h1 class="text-2xl font-bold mb-6">Editar Empresa</h1>

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('companies.update', $company) }}" method="POST" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                        Nome da Empresa
                    </label>
                    <input id="name" name="name" type="text"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        value="{{ old('name', $company->name) }}" required>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                        Email da Empresa
                    </label>
                    <input id="email" name="email" type="email"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        value="{{ old('email', $company->profile->email ?? '') }}" required>
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="entity_type">
                        Tipo de Entidade
                    </label>
                    <select id="entity_type" name="entity_type"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        required>
                        <option value="">Selecione um tipo</option>
                        @foreach($entityTypes as $type)
                            <option value="{{ $type->value }}"
                                {{ old('entity_type', $company->profile->entity_type ?? '') == $type->value ? 'selected' : '' }}>
                                {{ $type->value }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-6 flex items-center">
                    <input id="chatbot_enabled" name="chatbot_enabled" type="checkbox" value="1"
                        class="mr-2 leading-tight" {{ $company->profile->chatbot_enabled ? 'checked' : '' }}>
                    <label for="chatbot_enabled" class="text-gray-700 text-sm font-bold">
                        Ativar Chatbot Financeiro para esta Empresa
                    </label>
                </div>

                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Atualizar Empresa
                    </button>
                    <a href="{{ route('companies.index') }}"
                        class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout> 