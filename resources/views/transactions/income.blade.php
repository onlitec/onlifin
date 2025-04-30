<x-app-layout>
    @livewire('transactions.income')
    <div class="mb-4">
        <label for="cliente" class="block text-sm font-medium text-gray-700">Cliente</label>
        <input type="text" name="cliente" id="cliente" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="Nome do cliente que pagou">
    </div>
</x-app-layout>