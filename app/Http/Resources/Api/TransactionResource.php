<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'type' => $this->type,
            'status' => $this->status,
            'date' => $this->date?->format('Y-m-d'),
            'description' => $this->description,
            'amount' => $this->amount / 100, // Converter de centavos para reais
            'amount_formatted' => 'R$ ' . number_format($this->amount / 100, 2, ',', '.'),
            'notes' => $this->notes,
            'cliente' => $this->cliente,
            'fornecedor' => $this->fornecedor,
            'recurrence' => [
                'type' => $this->recurrence_type,
                'period' => $this->recurrence_period,
                'installment_number' => $this->installment_number,
                'total_installments' => $this->total_installments,
                'next_date' => $this->next_date?->format('Y-m-d'),
            ],
            'category' => $this->whenLoaded('category', function () {
                return new CategoryResource($this->category);
            }),
            'account' => $this->whenLoaded('account', function () {
                return new AccountResource($this->account);
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
