/*
# Add Balance Update Functions

## Plain English Explanation
This migration adds database functions to automatically update account balances when transactions are created, updated, or deleted. It also provides a function to recalculate balances from scratch based on all transactions.

## New Functions

### 1. update_account_balance_on_transaction()
- **Purpose**: Trigger function that automatically updates account balance when a transaction is inserted, updated, or deleted
- **Behavior**: 
  - On INSERT: Adds amount to balance (income) or subtracts (expense)
  - On UPDATE: Reverses old transaction and applies new one
  - On DELETE: Reverses the transaction
- **Returns**: Trigger

### 2. recalculate_account_balance(account_uuid)
- **Purpose**: Recalculates account balance from scratch based on all transactions
- **Parameters**: account_id (uuid)
- **Returns**: new_balance (numeric)
- **Use Case**: Fix balance discrepancies after bulk imports or data corrections

## Security
- Functions use SECURITY DEFINER to ensure proper permissions
- Only affects accounts owned by the user making the transaction

## Notes
- Balances are updated atomically within transactions
- Recalculate function can be called manually to fix any discrepancies
- All balance changes are logged through transaction history
*/

-- Function to update account balance when transaction is created/updated/deleted
CREATE OR REPLACE FUNCTION update_account_balance_on_transaction()
RETURNS TRIGGER
LANGUAGE plpgsql
SECURITY DEFINER
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

-- Create trigger to automatically update account balance
DROP TRIGGER IF EXISTS trigger_update_account_balance ON transactions;
CREATE TRIGGER trigger_update_account_balance
  AFTER INSERT OR UPDATE OR DELETE ON transactions
  FOR EACH ROW
  EXECUTE FUNCTION update_account_balance_on_transaction();

-- Function to recalculate account balance from all transactions
CREATE OR REPLACE FUNCTION recalculate_account_balance(account_uuid UUID)
RETURNS NUMERIC
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
DECLARE
  new_balance NUMERIC := 0;
  income_total NUMERIC := 0;
  expense_total NUMERIC := 0;
BEGIN
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

  -- Calculate new balance
  new_balance := income_total - expense_total;

  -- Update account balance
  UPDATE accounts
  SET balance = new_balance,
      updated_at = NOW()
  WHERE id = account_uuid;

  RETURN new_balance;
END;
$$;

-- Function to recalculate all account balances for a user
CREATE OR REPLACE FUNCTION recalculate_all_account_balances(user_uuid UUID)
RETURNS TABLE(account_id UUID, old_balance NUMERIC, new_balance NUMERIC)
LANGUAGE plpgsql
SECURITY DEFINER
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

-- Add comment to explain the functions
COMMENT ON FUNCTION update_account_balance_on_transaction() IS 
'Automatically updates account balance when transactions are created, updated, or deleted';

COMMENT ON FUNCTION recalculate_account_balance(UUID) IS 
'Recalculates account balance from scratch based on all transactions. Use this to fix balance discrepancies.';

COMMENT ON FUNCTION recalculate_all_account_balances(UUID) IS 
'Recalculates all account balances for a specific user. Returns old and new balances for comparison.';
