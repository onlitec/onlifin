@props(['transaction'])

<div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
    <div class="flex items-center">
        {{-- Ícone e Cor Baseado no Tipo e Status --}}
        <div @class([
                'w-10 h-10 rounded-full flex items-center justify-center',
                'bg-green-100' => $transaction->type === 'income',
                'bg-red-100' => $transaction->type === 'expense',
                'opacity-60' => $transaction->status === 'pending'
            ])>
            <i @class([
                    'text-lg',
                    'ri-arrow-up-line text-green-600' => $transaction->type === 'income',
                    'ri-arrow-down-line text-red-600' => $transaction->type === 'expense',
               ])>
            </i>
        </div>
        <div class="ml-3">
            <p class="text-sm font-medium text-gray-800 truncate" title="{{ $transaction->description }}">
                {{ $transaction->description }}
            </p>
            <p class="text-xs text-gray-500">
                {{ $transaction->category->name ?? 'Sem Categoria' }} 
                @if($transaction->status === 'pending')
                    <span class="text-yellow-600">(Pendente)</span>
                @endif
            </p>
        </div>
    </div>
    <div class="text-right flex-shrink-0 ml-4">
        <p @class([
                'text-sm font-medium',
                'text-green-600' => $transaction->type === 'income',
                'text-red-600' => $transaction->type === 'expense',
                 'opacity-60' => $transaction->status === 'pending'
            ])>
            {{ $transaction->type === 'income' ? '+' : '-' }} R$ {{ number_format($transaction->amount / 100, 2, ',', '.') }}
        </p>
        <p class="text-xs text-gray-500">
            {{ $transaction->date->format('d/m/Y') }}
        </p>
    </div>
</div> 