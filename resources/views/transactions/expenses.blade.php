<x-app-layout>
    @livewire('transactions.expenses')
    <div class="mb-4">
        <label for="fornecedor" class="block text-sm font-medium text-gray-700">Fornecedor</label>
        <input type="text" name="fornecedor" id="fornecedor" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="Nome do fornecedor que recebeu">
    </div>
</x-app-layout>