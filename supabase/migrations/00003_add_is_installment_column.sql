
/*
# Adicionar Coluna is_installment

1. Descrição
   - Adiciona a coluna is_installment à tabela transactions
   - Indica se uma transação é parcelada ou não
   - Valor padrão: false

2. Detalhes Técnicos
   - Tipo: boolean
   - Nullable: YES (permite NULL)
   - Default: false
   - Usado pelo frontend para controlar se a transação tem parcelas

3. Notas
   - Esta coluna trabalha em conjunto com installment_number e total_installments
   - Quando is_installment = true, o sistema cria múltiplas transações (uma para cada parcela)
*/

-- Adicionar coluna is_installment à tabela transactions
ALTER TABLE transactions 
ADD COLUMN IF NOT EXISTS is_installment boolean DEFAULT false;

-- Atualizar transações existentes que têm parcelas
UPDATE transactions 
SET is_installment = true 
WHERE total_installments IS NOT NULL AND total_installments > 1;
