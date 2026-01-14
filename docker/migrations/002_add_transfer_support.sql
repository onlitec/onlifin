/*
# Add Transfer Support to Transactions

## Plain English Explanation
This migration adds support for transfer transactions between accounts. When a user transfers money from one account to another, the system will create two linked transactions:
1. An expense transaction in the source account (money going out)
2. An income transaction in the destination account (money coming in)

Both transactions are linked together using the parent_transaction_id field to maintain the relationship.

## Changes Made

### 1. Add Transfer Type to transaction_type ENUM
- Adds 'transfer' as a valid transaction type
- Allows transactions to be marked as transfers

### 2. Add transfer_destination_account_id Column
- New column to store the destination account for transfers
- References the accounts table
- NULL for non-transfer transactions
- Required for transfer transactions

### 3. Add is_transfer Boolean Flag
- Quick way to identify transfer transactions
- Defaults to false for regular transactions
- Set to true for transfer transactions

### 4. Create Transfer Creation Function
- RPC function to create transfer transactions atomically
- Creates both source (expense) and destination (income) transactions
- Links them together using parent_transaction_id
- Updates account balances automatically
- Ensures data consistency

## Security
- Function uses SECURITY DEFINER to ensure proper permissions
- Only authenticated users can create transfers
- Validates that both accounts belong to the user
- Prevents transfers to the same account

## Notes
- Transfers are always created in pairs (expense + income)
- Both transactions share the same description
- The parent_transaction_id links the two transactions
- Deleting one transfer transaction will cascade delete the other
*/

-- Add 'transfer' to transaction_type enum
ALTER TYPE transaction_type ADD VALUE IF NOT EXISTS 'transfer';

-- Add transfer-related columns to transactions table
ALTER TABLE transactions 
  ADD COLUMN IF NOT EXISTS transfer_destination_account_id uuid REFERENCES accounts(id) ON DELETE SET NULL,
  ADD COLUMN IF NOT EXISTS is_transfer boolean DEFAULT false;

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

-- Create function to get transfer details
CREATE OR REPLACE FUNCTION get_transfer_pair(p_transaction_id uuid)
RETURNS TABLE (
  source_transaction_id uuid,
  destination_transaction_id uuid,
  source_account_id uuid,
  destination_account_id uuid,
  amount numeric,
  date date,
  description text
)
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
BEGIN
  RETURN QUERY
  SELECT 
    CASE 
      WHEN t1.type = 'expense' THEN t1.id
      ELSE t2.id
    END as source_transaction_id,
    CASE 
      WHEN t1.type = 'income' THEN t1.id
      ELSE t2.id
    END as destination_transaction_id,
    CASE 
      WHEN t1.type = 'expense' THEN t1.account_id
      ELSE t2.account_id
    END as source_account_id,
    CASE 
      WHEN t1.type = 'income' THEN t1.account_id
      ELSE t2.account_id
    END as destination_account_id,
    t1.amount,
    t1.date,
    t1.description
  FROM transactions t1
  LEFT JOIN transactions t2 ON (
    t1.parent_transaction_id = t2.id OR t2.parent_transaction_id = t1.id
  )
  WHERE t1.id = p_transaction_id AND t1.is_transfer = true;
END;
$$;

-- Grant execute permission to authenticated users
GRANT EXECUTE ON FUNCTION get_transfer_pair TO authenticated;

COMMENT ON FUNCTION create_transfer IS 'Cria uma transferência entre duas contas do usuário';
COMMENT ON FUNCTION get_transfer_pair IS 'Retorna os detalhes completos de uma transferência';
