<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'color' => $this->color,
            'icon' => $this->icon,
            'description' => $this->description,
            'statistics' => $this->when(
                isset($this->transactions_count) || isset($this->total_amount),
                [
                    'transactions_count' => $this->transactions_count ?? 0,
                    'total_amount' => isset($this->total_amount) ? $this->total_amount / 100 : 0,
                    'total_amount_formatted' => isset($this->total_amount) 
                        ? 'R$ ' . number_format($this->total_amount / 100, 2, ',', '.') 
                        : 'R$ 0,00',
                    'last_used' => $this->last_used?->format('Y-m-d'),
                ]
            ),
            'recent_transactions' => $this->whenLoaded('transactions', function () {
                return TransactionResource::collection($this->transactions->take(5));
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
