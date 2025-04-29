<!--
***************************************************************
* IMPORTANTE: CONFIGURAÇÃO FIXA - NÃO ALTERAR                 *
* Este componente deve permanecer sem o quadro vermelho        *
* para manter a consistência visual em todas as páginas.       *
* Data da última alteração: 18/04/2025                         *
***************************************************************
-->

<div>
    <button 
        wire:click="$dispatch('swal:confirm', { transactionId: {{ $transactionId }} })" 
        class="text-red-600 hover:text-red-700 p-2 flex items-center justify-center" 
        title="Excluir"
    >
        <i class="ri-delete-bin-line"></i>
    </button>

    <!-- Modal de confirmação -->
    @if ($confirming)
        <div 
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 transition-opacity duration-300"
            x-data="{ show: true }"
            x-show="show"
            x-on:click.self="$wire.cancelDelete()"
            x-transition
        >
            <div class="bg-white rounded-lg shadow-xl max-w-sm w-full p-6 relative transform transition-all duration-300">
                <!-- Cabeçalho do modal -->
                <div class="flex items-center mb-4">
                    <div class="flex-shrink-0 bg-red-100 rounded-full p-2">
                        <i class="ri-delete-bin-line text-red-600 text-xl"></i>
                    </div>
                    <h3 class="ml-3 text-lg font-semibold text-gray-900">Confirmar exclusão</h3>
                </div>
                
                <!-- Conteúdo do modal -->
                <p class="text-gray-700 mb-6">
                    Tem certeza que deseja excluir esta transação?
                    <br>
                    <span class="text-sm text-gray-500">Esta ação não pode ser desfeita.</span>
                </p>
                
                <!-- Botões de ação -->
                <div class="flex justify-end space-x-2">
                    <button 
                        wire:click="cancelDelete" 
                        class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 focus:outline-none transition-colors"
                    >
                        Cancelar
                    </button>
                    <button 
                        wire:click="deleteTransaction" 
                        class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 focus:outline-none transition-colors"
                    >
                        Excluir
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
