#!/bin/bash
# ===========================================
# Script para executar migrações no banco de produção
# ===========================================
# Este script deve ser executado no VPS onde o Onlifin está rodando
# Uso: ./run-migration.sh

set -e

echo "=========================================="
echo "🚀 ONLIFIN - Migração de Banco de Dados"
echo "=========================================="

# Diretório onde estão os scripts SQL
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MIGRATIONS_DIR="${SCRIPT_DIR}/../docker/migrations"

# Verificar se o container está rodando
if ! docker ps | grep -q onlifin-database; then
    echo "❌ Container onlifin-database não está rodando!"
    exit 1
fi

echo ""
echo "📦 Fase 1: Adicionando colunas icon e initial_balance..."
docker exec onlifin-database psql -U onlifin -d onlifin << 'EOF'
DO $$
BEGIN
    -- Add icon column to accounts if not exists
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'accounts' AND column_name = 'icon') THEN
        ALTER TABLE accounts ADD COLUMN icon TEXT;
        RAISE NOTICE '✅ Coluna icon adicionada à tabela accounts';
    ELSE
        RAISE NOTICE '⏭️ Coluna icon já existe na tabela accounts';
    END IF;

    -- Add initial_balance column to accounts if not exists  
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'accounts' AND column_name = 'initial_balance') THEN
        ALTER TABLE accounts ADD COLUMN initial_balance NUMERIC DEFAULT 0 NOT NULL;
        RAISE NOTICE '✅ Coluna initial_balance adicionada à tabela accounts';
    ELSE
        RAISE NOTICE '⏭️ Coluna initial_balance já existe na tabela accounts';
    END IF;

    -- Add icon column to cards if not exists
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'cards' AND column_name = 'icon') THEN
        ALTER TABLE cards ADD COLUMN icon TEXT;
        RAISE NOTICE '✅ Coluna icon adicionada à tabela cards';
    ELSE
        RAISE NOTICE '⏭️ Coluna icon já existe na tabela cards';
    END IF;

    -- Add brand column to cards if not exists
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'cards' AND column_name = 'brand') THEN
        ALTER TABLE cards ADD COLUMN brand TEXT;
        RAISE NOTICE '✅ Coluna brand adicionada à tabela cards';
    ELSE
        RAISE NOTICE '⏭️ Coluna brand já existe na tabela cards';
    END IF;
END;
$$;
EOF

echo ""
echo "📦 Fase 2: Adicionando suporte a transferências..."
docker exec onlifin-database psql -U onlifin -d onlifin << 'EOF'
-- Add 'transfer' to transaction_type enum
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_enum WHERE enumlabel = 'transfer' AND enumtypid = 'transaction_type'::regtype) THEN
        ALTER TYPE transaction_type ADD VALUE 'transfer';
        RAISE NOTICE '✅ Valor transfer adicionado ao enum transaction_type';
    ELSE
        RAISE NOTICE '⏭️ Valor transfer já existe no enum transaction_type';
    END IF;
EXCEPTION WHEN duplicate_object THEN
    RAISE NOTICE '⏭️ Valor transfer já existe no enum transaction_type';
END;
$$;

-- Add transfer-related columns to transactions table
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'transactions' AND column_name = 'transfer_destination_account_id') THEN
        ALTER TABLE transactions ADD COLUMN transfer_destination_account_id uuid REFERENCES accounts(id) ON DELETE SET NULL;
        RAISE NOTICE '✅ Coluna transfer_destination_account_id adicionada';
    ELSE
        RAISE NOTICE '⏭️ Coluna transfer_destination_account_id já existe';
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'transactions' AND column_name = 'is_transfer') THEN
        ALTER TABLE transactions ADD COLUMN is_transfer boolean DEFAULT false;
        RAISE NOTICE '✅ Coluna is_transfer adicionada';
    ELSE
        RAISE NOTICE '⏭️ Coluna is_transfer já existe';
    END IF;
END;
$$;

-- Create index for faster transfer queries
CREATE INDEX IF NOT EXISTS idx_transactions_is_transfer ON transactions(is_transfer) WHERE is_transfer = true;
CREATE INDEX IF NOT EXISTS idx_transactions_transfer_destination ON transactions(transfer_destination_account_id) WHERE transfer_destination_account_id IS NOT NULL;

-- Create function to create transfer transactions
CREATE OR REPLACE FUNCTION create_transfer(
  p_user_id uuid,
  p_source_account_id uuid,
  p_destination_account_id uuid,
  p_amount numeric,
  p_date date,
  p_description text
) RETURNS json
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
DECLARE
  v_source_transaction_id uuid;
  v_destination_transaction_id uuid;
  v_source_account accounts;
  v_destination_account accounts;
BEGIN
  -- Validate that both accounts exist and belong to the user
  SELECT * INTO v_source_account FROM accounts WHERE id = p_source_account_id AND user_id = p_user_id;
  SELECT * INTO v_destination_account FROM accounts WHERE id = p_destination_account_id AND user_id = p_user_id;
  
  IF v_source_account.id IS NULL THEN
    RAISE EXCEPTION 'Conta de origem não encontrada ou não pertence ao usuário';
  END IF;
  
  IF v_destination_account.id IS NULL THEN
    RAISE EXCEPTION 'Conta de destino não encontrada ou não pertence ao usuário';
  END IF;
  
  IF p_source_account_id = p_destination_account_id THEN
    RAISE EXCEPTION 'Não é possível transferir para a mesma conta';
  END IF;
  
  IF p_amount <= 0 THEN
    RAISE EXCEPTION 'O valor da transferência deve ser maior que zero';
  END IF;
  
  -- Create source transaction (expense - money going out)
  INSERT INTO transactions (
    user_id,
    account_id,
    type,
    amount,
    date,
    description,
    is_transfer,
    transfer_destination_account_id,
    is_reconciled
  ) VALUES (
    p_user_id,
    p_source_account_id,
    'expense',
    p_amount,
    p_date,
    p_description,
    true,
    p_destination_account_id,
    true
  ) RETURNING id INTO v_source_transaction_id;
  
  -- Create destination transaction (income - money coming in)
  INSERT INTO transactions (
    user_id,
    account_id,
    type,
    amount,
    date,
    description,
    is_transfer,
    parent_transaction_id,
    is_reconciled
  ) VALUES (
    p_user_id,
    p_destination_account_id,
    'income',
    p_amount,
    p_date,
    p_description,
    true,
    v_source_transaction_id,
    true
  ) RETURNING id INTO v_destination_transaction_id;
  
  -- Update source transaction with destination transaction id
  UPDATE transactions 
  SET parent_transaction_id = v_destination_transaction_id 
  WHERE id = v_source_transaction_id;
  
  -- Return both transaction IDs
  RETURN json_build_object(
    'source_transaction_id', v_source_transaction_id,
    'destination_transaction_id', v_destination_transaction_id,
    'success', true
  );
END;
$$;

-- Grant execute permission to authenticated users
GRANT EXECUTE ON FUNCTION create_transfer TO authenticated;
GRANT EXECUTE ON FUNCTION create_transfer TO anon;

RAISE NOTICE '✅ Função create_transfer criada/atualizada';
EOF

echo ""
echo "📦 Fase 3: Configurando triggers de atualização de saldo..."
docker exec onlifin-database psql -U onlifin -d onlifin << 'EOF'
-- Function to update account balance when transaction changes
CREATE OR REPLACE FUNCTION public.update_account_balance_on_transaction()
RETURNS trigger
LANGUAGE plpgsql
AS $$
DECLARE
  old_balance_change NUMERIC := 0;
  new_balance_change NUMERIC := 0;
BEGIN
  -- Calculate old balance change (for UPDATE and DELETE)
  IF TG_OP = 'UPDATE' OR TG_OP = 'DELETE' THEN
    IF OLD.account_id IS NOT NULL THEN
      IF OLD.type = 'income' THEN
        old_balance_change := -OLD.amount; -- Reverse the income
      ELSE
        old_balance_change := OLD.amount; -- Reverse the expense
      END IF;
      
      -- Apply old balance change
      UPDATE accounts
      SET balance = balance + old_balance_change,
          updated_at = NOW()
      WHERE id = OLD.account_id;
    END IF;
  END IF;
  
  -- Calculate new balance change (for INSERT and UPDATE)
  IF TG_OP = 'INSERT' OR TG_OP = 'UPDATE' THEN
    IF NEW.account_id IS NOT NULL THEN
      IF NEW.type = 'income' THEN
        new_balance_change := NEW.amount; -- Add income
      ELSE
        new_balance_change := -NEW.amount; -- Subtract expense
      END IF;
      
      -- Apply new balance change
      UPDATE accounts
      SET balance = balance + new_balance_change,
          updated_at = NOW()
      WHERE id = NEW.account_id;
    END IF;
  END IF;
  
  -- Return appropriate value based on operation
  IF TG_OP = 'DELETE' THEN
    RETURN OLD;
  ELSE
    RETURN NEW;
  END IF;
END;
$$;

-- Trigger for transaction changes
DROP TRIGGER IF EXISTS trigger_update_account_balance ON transactions;
CREATE TRIGGER trigger_update_account_balance
  AFTER INSERT OR DELETE OR UPDATE ON transactions
  FOR EACH ROW
  EXECUTE FUNCTION update_account_balance_on_transaction();

-- Function to recalculate account balance based on initial_balance + transactions
CREATE OR REPLACE FUNCTION public.recalculate_account_balance(account_uuid uuid)
RETURNS numeric
LANGUAGE plpgsql
AS $$
DECLARE
  new_balance NUMERIC := 0;
  income_total NUMERIC := 0;
  expense_total NUMERIC := 0;
  v_initial_balance NUMERIC := 0;
BEGIN
  -- Get initial balance
  SELECT COALESCE(initial_balance, 0) INTO v_initial_balance
  FROM accounts
  WHERE id = account_uuid;
  
  -- Calculate total income
  SELECT COALESCE(SUM(amount), 0)
  INTO income_total
  FROM transactions
  WHERE account_id = account_uuid
    AND type = 'income';
  
  -- Calculate total expenses
  SELECT COALESCE(SUM(amount), 0)
  INTO expense_total
  FROM transactions
  WHERE account_id = account_uuid
    AND type = 'expense';
  
  -- Calculate new balance: initial + income - expenses
  new_balance := v_initial_balance + income_total - expense_total;
  
  -- Update account balance
  UPDATE accounts
  SET balance = new_balance,
      updated_at = NOW()
  WHERE id = account_uuid;
  
  RETURN new_balance;
END;
$$;

-- Function to recalculate all account balances for a user
CREATE OR REPLACE FUNCTION public.recalculate_all_account_balances(user_uuid uuid)
RETURNS TABLE(account_id uuid, old_balance numeric, new_balance numeric)
LANGUAGE plpgsql
AS $$
DECLARE
  account_record RECORD;
  calculated_balance NUMERIC;
BEGIN
  FOR account_record IN
    SELECT id, balance FROM accounts WHERE user_id = user_uuid
  LOOP
    calculated_balance := recalculate_account_balance(account_record.id);
    
    account_id := account_record.id;
    old_balance := account_record.balance;
    new_balance := calculated_balance;
    
    RETURN NEXT;
  END LOOP;
END;
$$;

-- Grant permissions
GRANT EXECUTE ON FUNCTION recalculate_account_balance TO authenticated;
GRANT EXECUTE ON FUNCTION recalculate_account_balance TO anon;
GRANT EXECUTE ON FUNCTION recalculate_all_account_balances TO authenticated;
GRANT EXECUTE ON FUNCTION recalculate_all_account_balances TO anon;

RAISE NOTICE '✅ Triggers de atualização de saldo configurados';
EOF

echo ""
echo "📦 Fase 4: Recalculando saldos de todas as contas..."
docker exec onlifin-database psql -U onlifin -d onlifin << 'EOF'
DO $$
DECLARE
    account_record RECORD;
    new_bal NUMERIC;
BEGIN
    FOR account_record IN SELECT id, name, balance FROM accounts LOOP
        new_bal := recalculate_account_balance(account_record.id);
        RAISE NOTICE 'Conta %: saldo anterior = %, novo saldo = %', account_record.name, account_record.balance, new_bal;
    END LOOP;
END;
$$;
EOF

echo ""
echo "=========================================="
echo "✅ MIGRAÇÃO CONCLUÍDA COM SUCESSO!"
echo "=========================================="
echo ""
echo "📊 Verificando resultados:"
docker exec onlifin-database psql -U onlifin -d onlifin -c "SELECT name, balance, initial_balance FROM accounts ORDER BY name;"

echo ""
echo "🔍 Para verificar as transferências:"
echo "   docker exec onlifin-database psql -U onlifin -d onlifin -c \"SELECT * FROM transactions WHERE is_transfer = true;\""
