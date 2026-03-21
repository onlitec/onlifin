BEGIN;

DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_type WHERE typname = 'transaction_type') THEN
        ALTER TYPE transaction_type ADD VALUE IF NOT EXISTS 'transfer';
    END IF;
END
$$;

ALTER TABLE public.transactions
    ADD COLUMN IF NOT EXISTS transfer_destination_account_id uuid REFERENCES public.accounts(id) ON DELETE SET NULL,
    ADD COLUMN IF NOT EXISTS notes text;

CREATE INDEX IF NOT EXISTS idx_transactions_transfer_destination_account_id
    ON public.transactions (transfer_destination_account_id);

CREATE OR REPLACE FUNCTION public.recalculate_account_balance(account_uuid uuid)
RETURNS numeric
LANGUAGE plpgsql
AS $$
DECLARE
  new_balance numeric := 0;
  income_total numeric := 0;
  expense_total numeric := 0;
  transfer_out_total numeric := 0;
  transfer_in_total numeric := 0;
  v_initial_balance numeric := 0;
BEGIN
  SELECT initial_balance
    INTO v_initial_balance
  FROM public.accounts
  WHERE id = account_uuid;

  IF v_initial_balance IS NULL THEN
    RETURN 0;
  END IF;

  SELECT COALESCE(SUM(amount), 0)
    INTO income_total
  FROM public.transactions
  WHERE account_id = account_uuid
    AND type = 'income'
    AND COALESCE(is_transfer, false) = false;

  SELECT COALESCE(SUM(amount), 0)
    INTO expense_total
  FROM public.transactions
  WHERE account_id = account_uuid
    AND type = 'expense'
    AND COALESCE(is_transfer, false) = false;

  SELECT COALESCE(SUM(amount), 0)
    INTO transfer_out_total
  FROM public.transactions
  WHERE account_id = account_uuid
    AND COALESCE(is_transfer, false) = true;

  SELECT COALESCE(SUM(amount), 0)
    INTO transfer_in_total
  FROM public.transactions
  WHERE transfer_destination_account_id = account_uuid
    AND COALESCE(is_transfer, false) = true;

  new_balance := v_initial_balance + income_total - expense_total - transfer_out_total + transfer_in_total;

  UPDATE public.accounts
  SET balance = new_balance,
      updated_at = now()
  WHERE id = account_uuid;

  RETURN new_balance;
END;
$$;

CREATE OR REPLACE FUNCTION public.update_account_balance_on_transaction()
RETURNS trigger
LANGUAGE plpgsql
AS $$
DECLARE
  v_account_id uuid;
BEGIN
  IF TG_OP = 'INSERT' THEN
    FOR v_account_id IN
      SELECT DISTINCT affected.account_id
      FROM (VALUES (NEW.account_id), (NEW.transfer_destination_account_id)) AS affected(account_id)
      WHERE affected.account_id IS NOT NULL
    LOOP
      PERFORM public.recalculate_account_balance(v_account_id);
    END LOOP;

    RETURN NEW;
  END IF;

  IF TG_OP = 'UPDATE' THEN
    FOR v_account_id IN
      SELECT DISTINCT affected.account_id
      FROM (
        VALUES
          (OLD.account_id),
          (OLD.transfer_destination_account_id),
          (NEW.account_id),
          (NEW.transfer_destination_account_id)
      ) AS affected(account_id)
      WHERE affected.account_id IS NOT NULL
    LOOP
      PERFORM public.recalculate_account_balance(v_account_id);
    END LOOP;

    RETURN NEW;
  END IF;

  FOR v_account_id IN
    SELECT DISTINCT affected.account_id
    FROM (VALUES (OLD.account_id), (OLD.transfer_destination_account_id)) AS affected(account_id)
    WHERE affected.account_id IS NOT NULL
  LOOP
    PERFORM public.recalculate_account_balance(v_account_id);
  END LOOP;

  RETURN OLD;
END;
$$;

DO $$
DECLARE
  account_record record;
BEGIN
  FOR account_record IN
    SELECT id
    FROM public.accounts
  LOOP
    PERFORM public.recalculate_account_balance(account_record.id);
  END LOOP;
END
$$;

NOTIFY pgrst, 'reload schema';

COMMIT;
