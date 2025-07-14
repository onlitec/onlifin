<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'type', // 'expense' ou 'income'
        'color',
        'description',
        'icon',
        'user_id'
    ];

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Cria ou retorna uma categoria existente sem duplicatas
     * 
     * @param string $name Nome da categoria
     * @param string $type Tipo da categoria (income ou expense)
     * @param int $userId ID do usuário
     * @param array $extraData Dados extras para a categoria (cor, ícone, etc.)
     * @return Category Categoria existente ou recém-criada
     */
    public static function createOrGet(string $name, string $type, int $userId, array $extraData = []): self
    {
        // Normalizar o nome da categoria
        $normalizedName = trim(ucfirst($name));
        
        // Verificar se a categoria já existe para o usuário
        $existingCategory = self::where('user_id', $userId)
            ->where('name', $normalizedName)
            ->where('type', $type)
            ->first();
            
        if ($existingCategory) {
            \Log::info('Categoria já existe, retornando existente', [
                'category_id' => $existingCategory->id,
                'name' => $normalizedName,
                'type' => $type,
                'user_id' => $userId
            ]);
            return $existingCategory;
        }
        
        // Criar nova categoria
        $categoryData = [
            'name' => $normalizedName,
            'type' => $type,
            'user_id' => $userId,
            'color' => $extraData['color'] ?? '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT),
            'icon' => $extraData['icon'] ?? 'fa-solid fa-tag',
            'description' => $extraData['description'] ?? 'Categoria criada automaticamente'
        ];
        
        $newCategory = self::create($categoryData);
        
        \Log::info('Nova categoria criada', [
            'category_id' => $newCategory->id,
            'name' => $normalizedName,
            'type' => $type,
            'user_id' => $userId
        ]);
        
        return $newCategory;
    }
    
    /**
     * Verifica se uma categoria já existe para o usuário
     * 
     * @param string $name Nome da categoria
     * @param string $type Tipo da categoria
     * @param int $userId ID do usuário
     * @return bool True se existe, false caso contrário
     */
    public static function exists(string $name, string $type, int $userId): bool
    {
        $normalizedName = trim(ucfirst($name));
        
        return self::where('user_id', $userId)
            ->where('name', $normalizedName)
            ->where('type', $type)
            ->exists();
    }
    
    /**
     * Obtém categorias básicas para um usuário
     * 
     * @param int $userId ID do usuário
     * @return array Array com as categorias básicas
     */
    public static function getBasicCategories(int $userId): array
    {
        return [
            ['name' => 'Receita Geral', 'type' => 'income'],
            ['name' => 'Despesa Geral', 'type' => 'expense'],
            ['name' => 'Alimentação', 'type' => 'expense'],
            ['name' => 'Transporte', 'type' => 'expense'],
            ['name' => 'Saúde', 'type' => 'expense'],
            ['name' => 'Educação', 'type' => 'expense'],
            ['name' => 'Lazer', 'type' => 'expense'],
            ['name' => 'Casa', 'type' => 'expense'],
            ['name' => 'Salário', 'type' => 'income'],
            ['name' => 'Freelance', 'type' => 'income'],
            ['name' => 'Investimentos', 'type' => 'income'],
            ['name' => 'Outros', 'type' => 'expense'],
            ['name' => 'Outras Receitas', 'type' => 'income']
        ];
    }
    
    /**
     * Cria categorias básicas para um usuário sem duplicatas
     * 
     * @param int $userId ID do usuário
     * @return int Número de categorias criadas
     */
    public static function createBasicCategoriesForUser(int $userId): int
    {
        $basicCategories = self::getBasicCategories($userId);
        $created = 0;
        
        foreach ($basicCategories as $categoryData) {
            if (!self::exists($categoryData['name'], $categoryData['type'], $userId)) {
                self::createOrGet($categoryData['name'], $categoryData['type'], $userId);
                $created++;
            }
        }
        
        \Log::info("Categorias básicas criadas para usuário", [
            'user_id' => $userId,
            'categorias_criadas' => $created,
            'total_basicas' => count($basicCategories)
        ]);
        
        return $created;
    }
} 