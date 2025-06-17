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
                            <div class="flex flex-col w-full">
                                <input type="file" name="site_logo" id="site_logo" 
                                       accept="image/png,.png,image/jpeg,.jpg,.jpeg,image/svg+xml,.svg"
                                       class="block w-full text-sm text-gray-500
                                       file:mr-4 file:py-2 file:px-4
                                       file:rounded-md file:border-0
                                       file:text-sm file:font-semibold
                                       file:bg-blue-50 file:text-blue-700
                                       hover:file:bg-blue-100">
                                <p class="mt-1 text-sm text-gray-500">Formatos aceitos: PNG, JPG, JPEG, SVG</p>
                            </div>
                            @if($siteLogo)
                                <div class="relative group">
                                    <img src="{{ asset($siteLogo) }}" alt="Logo Atual" class="h-12 object-contain border border-gray-200 rounded p-1">
                                    <div class="absolute inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity rounded">
                                        <span class="text-white text-xs">Logo atual</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-gray-500">Deixe em branco para manter o logo atual. <strong>Recomendamos usar PNG com fundo transparente.</strong></p>
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
                    <div class="mb-6">
                        <label for="root_font_size" class="block text-sm font-medium text-gray-700 mb-1">Tamanho Base da Fonte (px)</label>
                        <select name="root_font_size" id="root_font_size"
                                class="form-select bg-white dark:bg-gray-800 block w-full rounded-lg shadow-sm border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-gray-100 dark:placeholder-gray-400">
                            <option value="14" {{ old('root_font_size', $rootFontSize) === '14' ? 'selected' : '' }}>14</option>
                            <option value="16" {{ old('root_font_size', $rootFontSize) === '16' ? 'selected' : '' }}>16</option>
                            <option value="18" {{ old('root_font_size', $rootFontSize) === '18' ? 'selected' : '' }}>18</option>
                            <option value="20" {{ old('root_font_size', $rootFontSize) === '20' ? 'selected' : '' }}>20</option>
                        </select>
                        <p class="mt-1 text-sm text-gray-500">Selecione o tamanho base da fonte em pixels para todo o site.</p>
                    </div>
                    <div class="mb-6">
                        <label for="card_font_size" class="block text-sm font-medium text-gray-700 mb-1">Tamanho da Fonte dos Cards</label>
                        <select name="card_font_size" id="card_font_size"
                                class="form-select bg-white dark:bg-gray-800 block w-full rounded-lg shadow-sm border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-gray-100 dark:placeholder-gray-400">
                            <option value="lg" {{ old('card_font_size', $cardFontSize) === 'lg' ? 'selected' : '' }}>Pequeno</option>
                            <option value="xl" {{ old('card_font_size', $cardFontSize) === 'xl' ? 'selected' : '' }}>Médio</option>
                            <option value="2xl" {{ old('card_font_size', $cardFontSize) === '2xl' ? 'selected' : '' }}>Grande</option>
                            <option value="3xl" {{ old('card_font_size', $cardFontSize) === '3xl' ? 'selected' : '' }}>Muito Grande</option>
                        </select>
                        <p class="mt-1 text-sm text-gray-500">Selecione o tamanho da fonte para os valores nos cards da dashboard.</p>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout> 