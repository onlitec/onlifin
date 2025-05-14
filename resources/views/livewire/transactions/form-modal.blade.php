<div>
    <div class="fixed inset-0 overflow-y-auto" x-show="open">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>

            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form wire:submit="save">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <!-- Título do Modal -->
                        <div class="mb-4">
                            <h3 class="text-lg font-medium text-gray-900">
                                {{ $type === 'expense' ? 'Nova Despesa' : 'Nova Receita' }}
                            </h3>
                        </div>

                        <!-- Campos do Formulário -->
                        <div class="space-y-4">
                            <!-- Descrição -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Descrição</label>
                                <input type="text" wire:model="description" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Valor -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Valor</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">R$</span>
                                    </div>
                                    <input 
                                        type="text" 
                                        wire:model.live="amount" 
                                        class="mt-1 block w-full pl-10 pr-12 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="0,00"
                                        inputmode="numeric"
                                        wire:change="formatAmount"
                                    >
                                </div>
                                @error('amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Data -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Data</label>
                                <input type="date" wire:model="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Conta -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Conta</label>
                                <select wire:model="account_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Selecione uma conta</option>
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                                    @endforeach
                                </select>
                                @error('account_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Categoria -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Categoria</label>
                                <select wire:model="category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Selecione uma categoria</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Observação -->
                            <div x-show="showNote">
                                <label class="block text-sm font-medium text-gray-700">Observação</label>
                                <textarea wire:model="note" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Botões de Ação -->
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Salvar
                        </button>
                        <button type="button" wire:click="closeModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div> 