<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;
use App\Models\Transaction;
use Carbon\Carbon;

class FinancialFileImport implements ToCollection
{
    protected $userId;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            if ($index === 0) continue; // Skip header
            $date = Carbon::parse($row[0])->format('Y-m-d');
            $description = (string) $row[1];
            $amount = (float) $row[2];
            $type = $amount >= 0 ? 'income' : 'expense';
            // Tenta extrair o nome da categoria da coluna 3 ou 4
            $categoryName = isset($row[3]) ? trim($row[3]) : null;
            if (!$categoryName && isset($row[4])) {
                $categoryName = trim($row[4]);
            }
            $categoryId = null;
            if ($categoryName) {
                $category = \App\Models\Category::firstOrCreate(
                    [
                        'name' => ucfirst($categoryName),
                        'user_id' => $this->userId
                    ],
                    [
                        'type' => $type
                    ]
                );
                $categoryId = $category->id;
            }
            Transaction::create([
                'user_id' => $this->userId,
                'date' => $date,
                'description' => $description,
                'amount' => (int) round(abs($amount) * 100),
                'type' => $type,
                'status' => 'paid',
                'category_id' => $categoryId
            ]);
        }
    }
} 