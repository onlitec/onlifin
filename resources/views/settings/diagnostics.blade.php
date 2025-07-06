<x-app-layout>
    <div class="container-app">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Diagnóstico do Sistema</h1>
            <p class="mt-1 text-sm text-gray-600">Verifique informações de diagnóstico e status do sistema.</p>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="p-4 bg-white shadow rounded">
                <strong>🔧 PHP Version:</strong> {{ phpversion() }}
            </div>
            <div class="p-4 bg-white shadow rounded">
                <strong>🔧 Laravel Version:</strong> {{ app()->version() }}
            </div>
            <!-- Adicione mais blocos de diagnóstico conforme necessidade -->
        </div>
    </div>
</x-app-layout> 