<x-layouts.app>
    <div class="container-app">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Relatórios</h1>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger mb-4">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Relatório de Transações -->
            <div class="card">
                <div class="card-body">
                    <h3 class="text-lg font-semibold mb-4">Relatório de Transações</h3>
                    <form action="{{ route('settings.reports.transactions') }}" method="POST">
                        @csrf
                        <div class="space-y-4">
                            <div class="form-group">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Data Inicial</label>
                                <input type="date" name="start_date" class="form-input" required value="{{ old('start_date') }}">
                            </div>
                            <div class="form-group">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Data Final</label>
                                <input type="date" name="end_date" class="form-input" required value="{{ old('end_date') }}">
                            </div>
                            <button type="submit" class="btn btn-primary w-full">
                                <i class="ri-download-line mr-2"></i>
                                Gerar Relatório
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Outros tipos de relatórios aqui -->
        </div>
    </div>
</x-layouts.app> 