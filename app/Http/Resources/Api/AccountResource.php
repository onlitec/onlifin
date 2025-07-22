<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'initial_balance' => (float) $this->initial_balance,
            'current_balance' => (float) $this->current_balance,
            'current_balance_formatted' => 'R$ ' . number_format($this->current_balance, 2, ',', '.'),
            'description' => $this->description,
            'color' => $this->color,
            'active' => $this->active,
            'transactions_count' => $this->when(
                isset($this->transactions_count),
                $this->transactions_count
            ),
            'recent_transactions' => $this->whenLoaded('transactions', function () {
                return TransactionResource::collection($this->transactions->take(5));
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
