<x-layouts.app>
    <div class="container-app">
        <div class="mb-6 flex items-center justify-between">
            <h1>Contas</h1>
            <a href="{{ route('accounts.create') }}" class="btn btn-primary">
                <i class="ri-add-line mr-2"></i>
                Nova Conta
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-container">
                    <table class="table">
                        <thead class="table-header">
                            <tr>
                                <th class="table-header-cell">Nome</th>
                                <th class="table-header-cell">Tipo</th>
                                <th class="table-header-cell">Saldo</th>
                                <th class="table-header-cell">Status</th>
                                <th class="table-header-cell">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="table-body">
                            @forelse($accounts ?? [] as $account)
                                <tr class="table-row">
                                    <td class="table-cell">{{ $account->name }}</td>
                                    <td class="table-cell">{{ $account->type }}</td>
                                    <td class="table-cell">R$ {{ number_format($account->balance, 2, ',', '.') }}</td>
                                    <td class="table-cell">
                                        <span class="badge {{ $account->active ? 'badge-success' : 'badge-danger' }}">
                                            {{ $account->active ? 'Ativa' : 'Inativa' }}
                                        </span>
                                    </td>
                                    <td class="table-cell">
                                        <div class="flex items-center space-x-2">
                                            <a href="{{ route('accounts.edit', $account) }}" class="text-blue-600 hover:text-blue-800">
                                                <i class="ri-pencil-line"></i>
                                            </a>
                                            <button type="button" class="text-red-600 hover:text-red-800">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="table-cell text-center">
                                        Nenhuma conta encontrada.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app> 