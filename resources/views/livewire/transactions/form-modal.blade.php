<div class="modal fade" id="formModal" tabindex="-1" aria-labelledby="formModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="formModalLabel">Nova Transação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form wire:submit.prevent="saveTransaction">
                    <div class="mb-3">
                        <label for="type" class="form-label">Tipo de Transação</label>
                        <select class="form-control" wire:model="type" id="type">
                            <option value="income">Receita</option>
                            <option value="expense">Despesa</option>
                        </select>
                        @error('type') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Descrição</label>
                        <input type="text" class="form-control" id="description" wire:model="description" placeholder="Ex: Salário, Aluguel, etc.">
                        @error('description') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="amount" class="form-label">Valor</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">R$</span>
                            </div>
                            <input 
                                type="text" 
                                id="amount"
                                wire:model.blur="amount"
                                x-data
                                x-init="initMoneyMask($el)"
                                class="form-control pl-10"
                                placeholder="0,00"
                            >
                        </div>
                        <div class="text-sm text-gray-500">{{ $this->formattedAmount }}</div>
                        @error('amount') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="date" class="form-label">Data</label>
                        <input type="date" class="form-control" id="date" wire:model="date">
                        @error('date') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="category_id" class="form-label">Categoria</label>
                        <select class="form-control" wire:model="category_id" id="category_id">
                            <option value="">Selecione uma categoria</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="account_id" class="form-label">Conta</label>
                        <select class="form-control" wire:model="account_id" id="account_id">
                            <option value="">Selecione uma conta</option>
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                            @endforeach
                        </select>
                        @error('account_id') <span class="text-danger">{{ $message }}</span> @endmodule

                    <div class="mb-3">
                        <label for="observations" class="form-label">Observações</label>
                        <textarea class="form-control" id="observations" wire:model="observations" placeholder="Observações adicionais (opcional)"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-control" wire:model="status" id="status">
                            <option value="pending">Pendente</option>
                            <option value="completed">Concluído</option>
                        </select>
                        @error('status') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" class="btn btn-primary">Salvar</button>
                </form>
            </div>
        </div>
    </div>
</div>