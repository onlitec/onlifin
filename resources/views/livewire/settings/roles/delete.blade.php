<div class="p-6">
    <h2 class="text-lg font-medium text-gray-900 mb-4">
        Excluir Perfil
    </h2>

    <p class="mb-4 text-sm text-gray-600">
        Tem certeza que deseja excluir o perfil "{{ $roleName }}"? Esta ação não pode ser desfeita.
    </p>

    <div class="mt-6 flex justify-end space-x-3">
        <button type="button" wire:click="$dispatch('closeModal')" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Cancelar
        </button>
        <button type="button" wire:click="delete" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
            Excluir
        </button>
    </div>
</div> 