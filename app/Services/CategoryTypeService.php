<?php

namespace App\Services;

class CategoryTypeService
{
    /**
     * Mapeia o tipo correto da categoria baseado no nome
     */
    public static function getCategoryType(string $categoryName): string
    {
        $categoryName = strtolower(trim($categoryName));
        
        // Categorias que são sempre de RECEITA (income)
        $incomeCategories = [
            'salário', 'salario', 'receitas de trabalho', 'trabalho',
            'freelance', 'freelancer', 'consultoria',
            'vendas', 'receitas de vendas', 'comissões', 'comissao',
            'investimentos', 'dividendos', 'juros',
            'aluguéis', 'alugueis', 'renda',
            'outros recebimentos', 'outras receitas',
            'bonificação', 'bonificacao', 'prêmio', 'premio',
            'restituição', 'restituicao', 'reembolso'
        ];
        
        // Categorias que são sempre de DESPESA (expense)
        $expenseCategories = [
            'alimentação', 'alimentacao', 'comida', 'restaurante',
            'transporte', 'combustível', 'combustivel', 'gasolina',
            'saúde', 'saude', 'médico', 'medico', 'farmácia', 'farmacia',
            'educação', 'educacao', 'escola', 'curso',
            'casa', 'moradia', 'aluguel', 'condomínio', 'condominio',
            'lazer', 'entretenimento', 'cinema', 'viagem',
            'vestuário', 'vestuario', 'roupa', 'calçado', 'calcado',
            'tecnologia', 'eletrônicos', 'eletronicos', 'software',
            'serviços financeiros', 'servicos financeiros', 'banco', 'taxa',
            'outros gastos', 'outras despesas', 'despesas diversas',
            'utilidades', 'luz', 'água', 'agua', 'internet', 'telefone'
        ];
        
        // Categorias neutras (podem ser tanto receita quanto despesa)
        $neutralCategories = [
            'transferências', 'transferencias', 'pix', 'ted', 'doc'
        ];
        
        // Verificar se é categoria de receita
        foreach ($incomeCategories as $income) {
            if (strpos($categoryName, $income) !== false) {
                return 'income';
            }
        }
        
        // Verificar se é categoria de despesa
        foreach ($expenseCategories as $expense) {
            if (strpos($categoryName, $expense) !== false) {
                return 'expense';
            }
        }
        
        // Verificar se é categoria neutra
        foreach ($neutralCategories as $neutral) {
            if (strpos($categoryName, $neutral) !== false) {
                return 'expense'; // Transferências são tratadas como expense por padrão
            }
        }
        
        // Fallback: se não conseguir identificar, usar 'expense' como padrão
        return 'expense';
    }
    
    /**
     * Valida se o tipo da categoria está correto para o tipo da transação
     */
    public static function validateCategoryForTransaction(string $categoryName, string $transactionType): bool
    {
        $categoryType = self::getCategoryType($categoryName);
        
        // Transferências são sempre válidas
        if (strpos(strtolower($categoryName), 'transferência') !== false) {
            return true;
        }
        
        // Para outras categorias, o tipo deve coincidir
        return $categoryType === $transactionType;
    }
    
    /**
     * Sugere categoria correta baseada no tipo da transação
     */
    public static function suggestCategoryForTransaction(string $transactionType): string
    {
        if ($transactionType === 'income') {
            return 'Outros Recebimentos';
        } else {
            return 'Outros Gastos';
        }
    }
    
    /**
     * Corrige o tipo da categoria se necessário
     */
    public static function getCorrectCategoryType(string $categoryName, string $transactionType): string
    {
        $categoryType = self::getCategoryType($categoryName);
        
        // Se é transferência, usar o tipo da transação
        if (strpos(strtolower($categoryName), 'transferência') !== false) {
            return $transactionType;
        }
        
        // Caso contrário, usar o tipo correto da categoria
        return $categoryType;
    }
    
    /**
     * Lista de categorias padrão por tipo
     */
    public static function getDefaultCategories(): array
    {
        return [
            'income' => [
                'Salário',
                'Freelance',
                'Vendas',
                'Investimentos',
                'Outros Recebimentos'
            ],
            'expense' => [
                'Alimentação',
                'Transporte',
                'Saúde',
                'Educação',
                'Casa',
                'Lazer',
                'Vestuário',
                'Tecnologia',
                'Transferências',
                'Serviços Financeiros',
                'Outros Gastos'
            ]
        ];
    }
}
