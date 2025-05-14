<div class="mb-4 border-b border-gray-200">
    <ul class="flex flex-wrap -mb-px">
        <li class="mr-2">
            <a href="{{ route('settings.logs.index', ['tab' => 'api']) }}" 
               class="inline-block p-4 {{ $activeTab === 'api' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700 border-b-2 border-transparent hover:border-gray-300' }}">
                Logs de API
            </a>
        </li>
        <li class="mr-2">
            <a href="{{ route('settings.logs.index', ['tab' => 'laravel']) }}" 
               class="inline-block p-4 {{ $activeTab === 'laravel' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700 border-b-2 border-transparent hover:border-gray-300' }}">
                Logs do Laravel
            </a>
        </li>
        <li class="mr-2">
            <a href="{{ route('settings.logs.index', ['tab' => 'system']) }}" 
               class="inline-block p-4 {{ $activeTab === 'system' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700 border-b-2 border-transparent hover:border-gray-300' }}">
                Logs do Sistema
            </a>
        </li>
        <li class="mr-2">
            <a href="{{ route('settings.logs.index', ['tab' => 'ai']) }}" 
               class="inline-block p-4 {{ $activeTab === 'ai' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700 border-b-2 border-transparent hover:border-gray-300' }}">
                Logs de IA
            </a>
        </li>
        <li>
            <a href="{{ route('settings.logs.files') }}" 
               class="inline-block p-4 {{ $activeTab === 'files' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700 border-b-2 border-transparent hover:border-gray-300' }}">
                Arquivos de Log
            </a>
        </li>
    </ul>
</div> 