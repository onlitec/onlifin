<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SmartCategorizationService
{
    /**
     * Mapeamento de palavras-chave para categorias
     */
    private array $categoryKeywords = [
        // Alimentação
        'alimentacao' => [
            'keywords' => [
                'padaria', 'bakery', 'panificadora', 'pao', 'paes',
                'restaurante', 'lanchonete', 'pizzaria', 'hamburgueria',
                'supermercado', 'mercado', 'mercadinho', 'hipermercado',
                'acougue', 'carrefour', 'extra', 'pao de acucar',
                'walmart', 'big', 'atacadao', 'assai', 'makro',
                'ifood', 'uber eats', 'rappi', 'delivery',
                'bar', 'boteco', 'cervejaria', 'choperia',
                'sorveteria', 'confeitaria', 'doceria',
                'feira', 'hortifruti', 'sacolao'
            ],
            'type' => 'expense'
        ],

        // Transporte
        'transporte' => [
            'keywords' => [
                'posto', 'combustivel', 'gasolina', 'alcool', 'diesel',
                'shell', 'petrobras', 'ipiranga', 'ale', 'br',
                'uber', 'taxi', '99', 'cabify',
                'onibus', 'metro', 'trem', 'bilhete unico',
                'estacionamento', 'zona azul', 'parking',
                'pedagio', 'viapass', 'sem parar',
                'oficina', 'mecanica', 'pneu', 'oleo',
                'detran', 'ipva', 'licenciamento', 'multa'
            ],
            'type' => 'expense'
        ],

        // Saúde
        'saude' => [
            'keywords' => [
                'farmacia', 'drogaria', 'droga raia', 'pacheco',
                'hospital', 'clinica', 'laboratorio', 'exame',
                'medico', 'dentista', 'psicologo', 'fisioterapeuta',
                'plano de saude', 'unimed', 'bradesco saude',
                'amil', 'sulamerica', 'golden cross',
                'medicamento', 'remedio', 'vacina'
            ],
            'type' => 'expense'
        ],

        // Educação
        'educacao' => [
            'keywords' => [
                'escola', 'colegio', 'universidade', 'faculdade',
                'curso', 'aula', 'professor', 'mensalidade',
                'material escolar', 'livro', 'apostila',
                'udemy', 'coursera', 'alura', 'rocketseat'
            ],
            'type' => 'expense'
        ],

        // Casa
        'casa' => [
            'keywords' => [
                'aluguel', 'condominio', 'iptu', 'agua', 'luz',
                'energia eletrica', 'gas', 'internet', 'telefone',
                'limpeza', 'faxina', 'construcao', 'reforma',
                'material de construcao', 'tinta', 'cimento',
                'moveis', 'eletrodomesticos', 'decoracao',
                'magazine luiza', 'casas bahia', 'ponto frio',
                'leroy merlin', 'c&c', 'telhanorte'
            ],
            'type' => 'expense'
        ],

        // Lazer
        'lazer' => [
            'keywords' => [
                'cinema', 'teatro', 'show', 'evento',
                'netflix', 'spotify', 'amazon prime', 'disney',
                'youtube premium', 'streaming',
                'viagem', 'hotel', 'pousada', 'airbnb',
                'parque', 'zoologico', 'museu',
                'academia', 'ginastica', 'esporte'
            ],
            'type' => 'expense'
        ],

        // Vestuário
        'vestuario' => [
            'keywords' => [
                'roupa', 'vestuario', 'calcado', 'sapato',
                'loja', 'boutique', 'shopping',
                'zara', 'h&m', 'c&a', 'riachuelo',
                'renner', 'marisa', 'centauro', 'netshoes'
            ],
            'type' => 'expense'
        ],

        // Tecnologia
        'tecnologia' => [
            'keywords' => [
                'celular', 'smartphone', 'computador', 'notebook',
                'tablet', 'software', 'aplicativo', 'app',
                'google', 'apple', 'microsoft', 'adobe',
                'amazon', 'mercado livre', 'americanas',
                'fast shop', 'kabum', 'pichau'
            ],
            'type' => 'expense'
        ],

        // Receitas - Salário
        'salario' => [
            'keywords' => [
                'salario', 'ordenado', 'vencimento', 'pagamento',
                'folha', 'empresa', 'empregador', 'trabalho',
                'pix salario', 'deposito salario'
            ],
            'type' => 'income'
        ],

        // Receitas - Freelance
        'freelance' => [
            'keywords' => [
                'freelance', 'freela', 'servico', 'consultoria',
                'projeto', 'trabalho autonomo', 'pix recebido',
                'ted recebida', 'transferencia recebida'
            ],
            'type' => 'income'
        ],

        // Receitas - Vendas
        'vendas' => [
            'keywords' => [
                'venda', 'vendido', 'produto', 'mercadoria',
                'cliente', 'pagamento cliente', 'recebimento'
            ],
            'type' => 'income'
        ]
    ];

    /**
     * Categoriza uma transação baseada na descrição
     */
    public function categorizeTransaction(array $transactionData): array
    {
        $description = strtolower($transactionData['description'] ?? '');
        $type = $transactionData['type'] ?? 'expense';
        
        // Remover acentos e caracteres especiais para melhor matching
        $normalizedDescription = $this->normalizeText($description);
        
        // Buscar categoria baseada em palavras-chave
        $suggestedCategory = $this->findCategoryByKeywords($normalizedDescription, $type);
        
        if ($suggestedCategory) {
            // Verificar se a categoria já existe para o usuário
            $existingCategory = Category::where('user_id', auth()->id())
                ->where('name', $suggestedCategory['name'])
                ->where('type', $suggestedCategory['type'])
                ->first();
            
            if ($existingCategory) {
                return [
                    'category_id' => $existingCategory->id,
                    'category_name' => $existingCategory->name,
                    'is_new_category' => false,
                    'confidence' => $suggestedCategory['confidence']
                ];
            } else {
                // Categoria não existe, será criada
                return [
                    'category_id' => null,
                    'category_name' => $suggestedCategory['name'],
                    'is_new_category' => true,
                    'confidence' => $suggestedCategory['confidence']
                ];
            }
        }
        
        // Se não encontrou categoria específica, usar categoria padrão
        $defaultCategory = $this->getDefaultCategory($type);
        
        return [
            'category_id' => $defaultCategory ? $defaultCategory->id : null,
            'category_name' => $defaultCategory ? $defaultCategory->name : 'Outros',
            'is_new_category' => !$defaultCategory,
            'confidence' => 0.3
        ];
    }

    /**
     * Busca categoria baseada em palavras-chave
     */
    private function findCategoryByKeywords(string $description, string $transactionType): ?array
    {
        $bestMatch = null;
        $highestScore = 0;
        
        foreach ($this->categoryKeywords as $categoryName => $categoryData) {
            // Verificar se o tipo da categoria corresponde ao tipo da transação
            if ($categoryData['type'] !== $transactionType) {
                continue;
            }
            
            $score = 0;
            $matchedKeywords = [];
            
            foreach ($categoryData['keywords'] as $keyword) {
                $normalizedKeyword = $this->normalizeText($keyword);
                
                // Busca exata
                if (strpos($description, $normalizedKeyword) !== false) {
                    $score += 10;
                    $matchedKeywords[] = $keyword;
                }
                
                // Busca por palavras similares (Levenshtein distance)
                $words = explode(' ', $description);
                foreach ($words as $word) {
                    if (strlen($word) > 3 && strlen($normalizedKeyword) > 3) {
                        $distance = levenshtein($word, $normalizedKeyword);
                        $similarity = 1 - ($distance / max(strlen($word), strlen($normalizedKeyword)));
                        
                        if ($similarity > 0.8) {
                            $score += 5;
                            $matchedKeywords[] = $keyword;
                        }
                    }
                }
            }
            
            if ($score > $highestScore) {
                $highestScore = $score;
                $bestMatch = [
                    'name' => ucfirst($categoryName),
                    'type' => $categoryData['type'],
                    'confidence' => min(0.95, $score / 10),
                    'matched_keywords' => array_unique($matchedKeywords)
                ];
            }
        }
        
        return $bestMatch;
    }

    /**
     * Normaliza texto removendo acentos e caracteres especiais
     */
    private function normalizeText(string $text): string
    {
        // Converter para minúsculas
        $text = strtolower($text);
        
        // Remover acentos
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        
        // Remover caracteres especiais, manter apenas letras, números e espaços
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
        
        // Remover espaços extras
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }

    /**
     * Obtém categoria padrão para o tipo
     */
    private function getDefaultCategory(string $type): ?Category
    {
        $defaultNames = $type === 'income' ? ['Outros Recebimentos', 'Receitas Diversas'] : ['Outros Gastos', 'Despesas Diversas'];
        
        foreach ($defaultNames as $name) {
            $category = Category::where('user_id', auth()->id())
                ->where('name', $name)
                ->where('type', $type)
                ->first();
                
            if ($category) {
                return $category;
            }
        }
        
        return null;
    }

    /**
     * Cria uma nova categoria
     */
    public function createCategory(string $name, string $type): Category
    {
        return Category::create([
            'user_id' => auth()->id(),
            'name' => ucfirst($name),
            'type' => $type,
            'system' => false
        ]);
    }

    /**
     * Cria categorias padrão se não existirem
     */
    public function ensureDefaultCategories(): void
    {
        $defaultCategories = [
            // Despesas
            ['name' => 'Alimentação', 'type' => 'expense'],
            ['name' => 'Transporte', 'type' => 'expense'],
            ['name' => 'Saúde', 'type' => 'expense'],
            ['name' => 'Educação', 'type' => 'expense'],
            ['name' => 'Casa', 'type' => 'expense'],
            ['name' => 'Lazer', 'type' => 'expense'],
            ['name' => 'Vestuário', 'type' => 'expense'],
            ['name' => 'Tecnologia', 'type' => 'expense'],
            ['name' => 'Outros Gastos', 'type' => 'expense'],

            // Receitas
            ['name' => 'Salário', 'type' => 'income'],
            ['name' => 'Freelance', 'type' => 'income'],
            ['name' => 'Vendas', 'type' => 'income'],
            ['name' => 'Outros Recebimentos', 'type' => 'income'],
        ];

        foreach ($defaultCategories as $categoryData) {
            Category::firstOrCreate([
                'user_id' => auth()->id(),
                'name' => $categoryData['name'],
                'type' => $categoryData['type']
            ], [
                'system' => false
            ]);
        }
    }

    /**
     * Processa múltiplas transações e aplica categorização
     */
    public function categorizeMultipleTransactions(array $transactions): array
    {
        $categorizedTransactions = [];
        $newCategories = [];
        
        foreach ($transactions as $index => $transaction) {
            $categorization = $this->categorizeTransaction($transaction);
            
            // Se é uma nova categoria, criar
            if ($categorization['is_new_category'] && $categorization['category_name']) {
                $categoryKey = $categorization['category_name'] . '_' . $transaction['type'];
                
                if (!isset($newCategories[$categoryKey])) {
                    try {
                        $newCategory = $this->createCategory($categorization['category_name'], $transaction['type']);
                        $newCategories[$categoryKey] = $newCategory;
                        $categorization['category_id'] = $newCategory->id;
                        $categorization['is_new_category'] = false;
                        
                        Log::info('Nova categoria criada automaticamente', [
                            'category_name' => $categorization['category_name'],
                            'type' => $transaction['type'],
                            'transaction_description' => $transaction['description']
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Erro ao criar categoria automaticamente', [
                            'category_name' => $categorization['category_name'],
                            'error' => $e->getMessage()
                        ]);
                    }
                } else {
                    $categorization['category_id'] = $newCategories[$categoryKey]->id;
                    $categorization['is_new_category'] = false;
                }
            }
            
            $transaction['suggested_category_id'] = $categorization['category_id'];
            $transaction['suggested_category_name'] = $categorization['category_name'];
            $transaction['category_confidence'] = $categorization['confidence'];
            
            $categorizedTransactions[] = $transaction;
        }
        
        return $categorizedTransactions;
    }
}
