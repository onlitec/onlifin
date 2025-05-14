<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Aparência do Site
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if(session('success'))
                    <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
                        {{ session('success') }}
                    </div>
                @endif
                @if($errors->any())
                    <div class="mb-4 p-4 bg-red-100 text-red-800 rounded">
                        <ul class="list-disc pl-5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form action="{{ route('settings.appearance.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-6">
                        <label for="site_title" class="block text-sm font-medium text-gray-700 mb-1">Título do Site</label>
                        <input type="text" name="site_title" id="site_title" 
                               class="form-input block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                               value="{{ old('site_title', $siteTitle) }}" required>
                    </div>
                    <div class="mb-6">
                        <label for="site_logo" class="block text-sm font-medium text-gray-700 mb-1">Logo do Site</label>
                        <div class="flex items-center space-x-4">
                            <input type="file" name="site_logo" id="site_logo" accept="image/*">
                            @if($siteLogo)
                                <img src="{{ asset($siteLogo) }}" alt="Logo Atual" class="h-12">
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-gray-500">Deixe em branco para manter o logo atual.</p>
                    </div>
                    <div class="mb-6">
                        <label for="site_favicon" class="block text-sm font-medium text-gray-700 mb-1">Favicon</label>
                        <div class="flex items-center space-x-4">
                            <input type="file" name="site_favicon" id="site_favicon" accept="image/png,image/x-icon,image/svg+xml">
                            @if($siteFavicon)
                                <img src="{{ asset($siteFavicon) }}" alt="Favicon Atual" class="h-8 w-8 border rounded">
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-gray-500">Deixe em branco para manter o favicon atual.</p>
                    </div>
                    <div class="mb-6">
                        <label for="site_theme" class="block text-sm font-medium text-gray-700 mb-1">Tema do Site</label>
                        <select name="site_theme" id="site_theme"
                               class="form-select bg-white dark:bg-gray-800 block w-full rounded-lg shadow-sm border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-gray-100 dark:placeholder-gray-400">
                            <option value="light" {{ old('site_theme', $siteTheme) === 'light' ? 'selected' : '' }}>Claro</option>
                            <option value="dark" {{ old('site_theme', $siteTheme) === 'dark' ? 'selected' : '' }}>Escuro</option>
                        </select>
                        <p class="mt-1 text-sm text-gray-500">Selecione o tema claro ou escuro para o site.</p>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout> 