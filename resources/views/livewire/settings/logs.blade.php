<div class="p-6">
    {{-- Because she competes with no one, no one can compete with her. --}}
    <!-- Filtros -->
    <div class="mb-6">
        <div class="flex gap-4">
            <!-- Data -->
            <select wire:model="selectedDate" class="flex-1 p-2 border rounded">
                <option value="">Selecione uma data</option>
                @foreach($logFiles as $file)
                    <option value="{{ $file['date'] }}">
                        {{ $file['date'] }} ({{ $file['size'] }})
                    </option>
                @endforeach
            </select>
            
            <!-- Busca -->
            <input type="text" 
                   wire:model.debounce.500ms="search" 
                   placeholder="Buscar no log..." 
                   class="flex-1 p-2 border rounded">
        </div>
    </div>

    <!-- Logs -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-4">
            @if(count($logs) > 0)
                <div class="space-y-2">
                    @foreach($logs as $log)
                        <div class="text-sm">
                            {{ $log }}
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-4 text-gray-500">
                    Nenhum log encontrado para a data selecionada
                </div>
            @endif
        </div>
    </div>
</div>
