<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Onlifin - Transações</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    <div class="container mt-5">
        <h1>Lista de Transações</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#formModal">
            Nova Despesa
        </button>

        <!-- Tabela ou lista de transações (se houver) -->
        @livewire('transactions-table') <!-- Exemplo, ajuste conforme necessário -->

        <!-- Modal gerenciado pelo Livewire -->
        <livewire:form-modal />
    </div>

    @livewireScripts
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>