-- ==============================================================================
-- Migration 002: Fix Account Balance System
-- ==============================================================================
-- Description: Implementa sistema completo de saldo de contas com:
--   - Saldo inicial separado do saldo atual
--   - Triggers automáticos para atualizar saldo em transações
--   - Integração entre contas a pagar/receber e transações
-- ==============================================================================

BEGIN;

-- ==============================================================================
-- 1. ADD COLUMNS (IF NOT EXISTS)
-- ==============================================================================

-- Add initial_balance column to accounts
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'accounts' AND column_name = 'initial_balance'
    ) THEN
        ALTER TABLE accounts ADD COLUMN initial_balance NUMERIC NOT NULL DEFAULT 0;
        
        -- Migrate existing data: set initial_balance based on current balance minus transactions
        UPDATE accounts a 
        SET initial_balance = a.balance - (
            SELECT COALESCE(SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE -t.amount END), 0)
            FROM transactions t 
            WHERE t.account_id = a.id
        );
        
        RAISE NOTICE 'Column initial_balance added and data migrated';
    ELSE
        RAISE NOTICE 'Column initial_balance already exists';
    END IF;
END $$;

-- Add transaction_id to bills_to_pay
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'bills_to_pay' AND column_name = 'transaction_id'
    ) THEN
        ALTER TABLE bills_to_pay ADD COLUMN transaction_id UUID REFERENCES transactions(id) ON DELETE SET NULL;
        RAISE NOTICE 'Column transaction_id added to bills_to_pay';
    ELSE
        RAISE NOTICE 'Column transaction_id already exists in bills_to_pay';
    END IF;
END $$;

-- Add transaction_id to bills_to_receive
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'bills_to_receive' AND column_name = 'transaction_id'
    ) THEN
        ALTER TABLE bills_to_receive ADD COLUMN transaction_id UUID REFERENCES transactions(id) ON DELETE SET NULL;
        RAISE NOTICE 'Column transaction_id added to bills_to_receive';
    ELSE
        RAISE NOTICE 'Column transaction_id already exists in bills_to_receive';
    END IF;
END $$;

-- ==============================================================================
-- 2. CREATE/REPLACE FUNCTIONS
-- ==============================================================================

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
  SELECT initial_balance INTO v_initial_balance
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

-- Function to update balance when initial_balance changes
CREATE OR REPLACE FUNCTION public.update_balance_on_initial_balance_change()
RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
  IF NEW.initial_balance != OLD.initial_balance THEN
    NEW.balance := NEW.balance + (NEW.initial_balance - OLD.initial_balance);
  END IF;
  RETURN NEW;
END;
$$;

-- Function to handle bill payment (creates transaction)
CREATE OR REPLACE FUNCTION public.handle_bill_payment()
RETURNS trigger
LANGUAGE plpgsql
AS $$
DECLARE
  v_transaction_id UUID;
BEGIN
  -- When bill is marked as paid, create transaction
  IF NEW.status = 'paid' AND (OLD.status IS NULL OR OLD.status != 'paid') AND NEW.transaction_id IS NULL THEN
    INSERT INTO transactions (
      user_id, account_id, category_id, type, amount, date, description, is_reconciled
    ) VALUES (
      NEW.user_id,
      NEW.account_id,
      NEW.category_id,
      'expense',
      NEW.amount,
      COALESCE(NEW.paid_date, NEW.due_date),
      NEW.description,
      true
    ) RETURNING id INTO v_transaction_id;
    
    NEW.transaction_id := v_transaction_id;
    
  -- When bill is unmarked as paid, delete transaction
  ELSIF NEW.status != 'paid' AND OLD.status = 'paid' AND NEW.transaction_id IS NOT NULL THEN
    DELETE FROM transactions WHERE id = NEW.transaction_id;
    NEW.transaction_id := NULL;
    
  -- When paid bill is updated, update transaction
  ELSIF NEW.status = 'paid' AND OLD.status = 'paid' AND NEW.transaction_id IS NOT NULL THEN
    UPDATE transactions
    SET account_id = NEW.account_id,
        category_id = NEW.category_id,
        amount = NEW.amount,
        date = COALESCE(NEW.paid_date, NEW.due_date),
        description = NEW.description
    WHERE id = NEW.transaction_id;
  END IF;
  
  RETURN NEW;
END;
$$;

-- Function to handle bill receipt (creates transaction)
CREATE OR REPLACE FUNCTION public.handle_bill_receipt()
RETURNS trigger
LANGUAGE plpgsql
AS $$
DECLARE
  v_transaction_id UUID;
BEGIN
  -- When bill is marked as received, create transaction
  IF NEW.status = 'received' AND (OLD.status IS NULL OR OLD.status != 'received') AND NEW.transaction_id IS NULL THEN
    INSERT INTO transactions (
      user_id, account_id, category_id, type, amount, date, description, is_reconciled
    ) VALUES (
      NEW.user_id,
      NEW.account_id,
      NEW.category_id,
      'income',
      NEW.amount,
      COALESCE(NEW.received_date, NEW.due_date),
      NEW.description,
      true
    ) RETURNING id INTO v_transaction_id;
    
    NEW.transaction_id := v_transaction_id;
    
  -- When bill is unmarked as received, delete transaction
  ELSIF NEW.status != 'received' AND OLD.status = 'received' AND NEW.transaction_id IS NOT NULL THEN
    DELETE FROM transactions WHERE id = NEW.transaction_id;
    NEW.transaction_id := NULL;
    
  -- When received bill is updated, update transaction
  ELSIF NEW.status = 'received' AND OLD.status = 'received' AND NEW.transaction_id IS NOT NULL THEN
    UPDATE transactions
    SET account_id = NEW.account_id,
        category_id = NEW.category_id,
        amount = NEW.amount,
        date = COALESCE(NEW.received_date, NEW.due_date),
        description = NEW.description
    WHERE id = NEW.transaction_id;
  END IF;
  
  RETURN NEW;
END;
$$;

-- Function to delete associated transaction when bill is deleted
CREATE OR REPLACE FUNCTION public.delete_associated_transaction()
RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
  IF OLD.transaction_id IS NOT NULL THEN
    DELETE FROM transactions WHERE id = OLD.transaction_id;
  END IF;
  RETURN OLD;
END;
$$;

-- ==============================================================================
-- 3. CREATE TRIGGERS (DROP FIRST IF EXISTS)
-- ==============================================================================

-- Trigger for transaction changes
DROP TRIGGER IF EXISTS trigger_update_account_balance ON transactions;
CREATE TRIGGER trigger_update_account_balance
  AFTER INSERT OR DELETE OR UPDATE ON transactions
  FOR EACH ROW
  EXECUTE FUNCTION update_account_balance_on_transaction();

-- Trigger for initial_balance changes
DROP TRIGGER IF EXISTS trigger_update_balance_on_initial_balance_change ON accounts;
CREATE TRIGGER trigger_update_balance_on_initial_balance_change
  BEFORE UPDATE ON accounts
  FOR EACH ROW
  EXECUTE FUNCTION update_balance_on_initial_balance_change();

-- Trigger for bills_to_pay
DROP TRIGGER IF EXISTS trigger_handle_bill_payment ON bills_to_pay;
CREATE TRIGGER trigger_handle_bill_payment
  BEFORE UPDATE ON bills_to_pay
  FOR EACH ROW
  EXECUTE FUNCTION handle_bill_payment();

DROP TRIGGER IF EXISTS trigger_delete_bill_to_pay_transaction ON bills_to_pay;
CREATE TRIGGER trigger_delete_bill_to_pay_transaction
  AFTER DELETE ON bills_to_pay
  FOR EACH ROW
  EXECUTE FUNCTION delete_associated_transaction();

-- Trigger for bills_to_receive
DROP TRIGGER IF EXISTS trigger_handle_bill_receipt ON bills_to_receive;
CREATE TRIGGER trigger_handle_bill_receipt
  BEFORE UPDATE ON bills_to_receive
  FOR EACH ROW
  EXECUTE FUNCTION handle_bill_receipt();

DROP TRIGGER IF EXISTS trigger_delete_bill_to_receive_transaction ON bills_to_receive;
CREATE TRIGGER trigger_delete_bill_to_receive_transaction
  AFTER DELETE ON bills_to_receive
  FOR EACH ROW
  EXECUTE FUNCTION delete_associated_transaction();

-- ==============================================================================
-- 4. FIX EXISTING PAID/RECEIVED BILLS
-- ==============================================================================

-- Create transactions for existing paid bills that don't have transaction_id
DO $$
DECLARE
    bill_row RECORD;
    v_transaction_id UUID;
BEGIN
    -- Fix Bills to Pay
    FOR bill_row IN 
        SELECT * FROM bills_to_pay 
        WHERE status = 'paid' AND transaction_id IS NULL AND account_id IS NOT NULL
    LOOP
        INSERT INTO transactions (user_id, account_id, category_id, type, amount, date, description, is_reconciled)
        VALUES (bill_row.user_id, bill_row.account_id, bill_row.category_id, 'expense', bill_row.amount, COALESCE(bill_row.paid_date, bill_row.due_date), bill_row.description, true)
        RETURNING id INTO v_transaction_id;
        
        UPDATE bills_to_pay SET transaction_id = v_transaction_id WHERE id = bill_row.id;
    END LOOP;

    -- Fix Bills to Receive
    FOR bill_row IN 
        SELECT * FROM bills_to_receive 
        WHERE status = 'received' AND transaction_id IS NULL AND account_id IS NOT NULL
    LOOP
        INSERT INTO transactions (user_id, account_id, category_id, type, amount, date, description, is_reconciled)
        VALUES (bill_row.user_id, bill_row.account_id, bill_row.category_id, 'income', bill_row.amount, COALESCE(bill_row.received_date, bill_row.due_date), bill_row.description, true)
        RETURNING id INTO v_transaction_id;
        
        UPDATE bills_to_receive SET transaction_id = v_transaction_id WHERE id = bill_row.id;
    END LOOP;
    
    RAISE NOTICE 'Existing paid/received bills fixed';
END;
$$;

-- ==============================================================================
-- 5. RECALCULATE ALL ACCOUNT BALANCES
-- ==============================================================================

-- Recalculate balances for all accounts
DO $$
DECLARE
    account_record RECORD;
    new_bal NUMERIC;
BEGIN
    FOR account_record IN SELECT id, name FROM accounts LOOP
        new_bal := recalculate_account_balance(account_record.id);
        RAISE NOTICE 'Account % recalculated: new balance = %', account_record.name, new_bal;
    END LOOP;
END;
$$;

COMMIT;

-- ==============================================================================
-- VERIFICATION QUERIES
-- ==============================================================================
-- Run these after migration to verify:
--
-- 1. Check accounts structure:
--    SELECT id, name, balance, initial_balance FROM accounts;
--
-- 2. Check bills have transaction_id:
--    SELECT id, description, status, transaction_id FROM bills_to_pay WHERE status = 'paid';
--    SELECT id, description, status, transaction_id FROM bills_to_receive WHERE status = 'received';
--
-- 3. Verify balance calculation for a specific account:
--    SELECT recalculate_account_balance('account-uuid-here');
--
-- 4. Check triggers:
--    SELECT trigger_name FROM information_schema.triggers WHERE event_object_table IN ('accounts', 'transactions', 'bills_to_pay', 'bills_to_receive');
-- ==============================================================================
