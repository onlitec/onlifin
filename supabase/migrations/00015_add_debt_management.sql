-- ===========================================
-- Add Debt Management
-- ===========================================

CREATE TABLE IF NOT EXISTS debts (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid NOT NULL REFERENCES profiles(id) ON DELETE CASCADE,
  company_id uuid REFERENCES companies(id) ON DELETE SET NULL,
  person_id uuid,
  description text NOT NULL,
  creditor text NOT NULL,
  original_amount numeric(20,2) NOT NULL CHECK (original_amount > 0),
  current_balance numeric(20,2) NOT NULL CHECK (current_balance >= 0),
  interest_rate numeric(12,6) NOT NULL DEFAULT 0,
  interest_type text NOT NULL DEFAULT 'COMPOSTO' CHECK (interest_type IN ('SIMPLES', 'COMPOSTO')),
  penalty_rate numeric(12,6) NOT NULL DEFAULT 0,
  due_date date NOT NULL,
  status text NOT NULL DEFAULT 'PENDENTE' CHECK (status IN ('PENDENTE', 'VENCIDO', 'RENEGOCIADO', 'PAGO', 'CANCELADO')),
  category text DEFAULT 'GERAL',
  notes text,
  total_paid numeric(20,2) NOT NULL DEFAULT 0,
  total_abated numeric(20,2) NOT NULL DEFAULT 0,
  created_at timestamptz DEFAULT now(),
  updated_at timestamptz DEFAULT now()
);

CREATE TABLE IF NOT EXISTS debt_payments (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  debt_id uuid NOT NULL REFERENCES debts(id) ON DELETE CASCADE,
  user_id uuid NOT NULL REFERENCES profiles(id) ON DELETE CASCADE,
  amount numeric(20,2) NOT NULL CHECK (amount > 0),
  payment_date date NOT NULL DEFAULT current_date,
  method text NOT NULL DEFAULT 'PIX' CHECK (method IN ('PIX', 'BOLETO', 'CARTAO_CREDITO', 'CARTAO_DEBITO', 'TRANSFERENCIA', 'DINHEIRO', 'DEBITO_AUTOMATICO', 'OUTRO')),
  reference text,
  notes text,
  created_at timestamptz DEFAULT now()
);

CREATE TABLE IF NOT EXISTS debt_agreements (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  debt_id uuid NOT NULL REFERENCES debts(id) ON DELETE CASCADE,
  user_id uuid NOT NULL REFERENCES profiles(id) ON DELETE CASCADE,
  balance_at_agreement numeric(20,2) NOT NULL CHECK (balance_at_agreement >= 0),
  agreed_amount numeric(20,2) NOT NULL CHECK (agreed_amount >= 0),
  discount_applied numeric(20,2) NOT NULL DEFAULT 0,
  installments integer NOT NULL DEFAULT 1 CHECK (installments > 0),
  installment_value numeric(20,2) NOT NULL CHECK (installment_value >= 0),
  new_interest_rate numeric(12,6) NOT NULL DEFAULT 0,
  start_date date NOT NULL DEFAULT current_date,
  end_date date,
  status text NOT NULL DEFAULT 'ATIVO' CHECK (status IN ('ATIVO', 'CONCLUIDO', 'CANCELADO', 'INADIMPLENTE')),
  terms text,
  created_at timestamptz DEFAULT now(),
  updated_at timestamptz DEFAULT now()
);

CREATE TABLE IF NOT EXISTS debt_abatements (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  debt_id uuid NOT NULL REFERENCES debts(id) ON DELETE CASCADE,
  user_id uuid NOT NULL REFERENCES profiles(id) ON DELETE CASCADE,
  abatement_type text NOT NULL DEFAULT 'JUROS' CHECK (abatement_type IN ('JUROS', 'MULTA', 'AMBOS', 'VALOR_PRINCIPAL')),
  amount numeric(20,2) NOT NULL CHECK (amount > 0),
  reason text NOT NULL,
  applied_at timestamptz DEFAULT now()
);

ALTER TABLE debts
  ADD COLUMN IF NOT EXISTS company_id uuid,
  ADD COLUMN IF NOT EXISTS person_id uuid;

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM pg_constraint
    WHERE conname = 'debts_company_id_fkey'
  ) THEN
    ALTER TABLE debts
      ADD CONSTRAINT debts_company_id_fkey
      FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL;
  END IF;

  IF NOT EXISTS (
    SELECT 1
    FROM pg_constraint
    WHERE conname = 'debts_person_id_fkey'
  )
  AND EXISTS (
    SELECT 1
    FROM pg_class c
    JOIN pg_namespace n ON n.oid = c.relnamespace
    WHERE n.nspname = 'public'
      AND c.relname = 'people'
      AND c.relkind IN ('r', 'p')
  ) THEN
    ALTER TABLE debts
      ADD CONSTRAINT debts_person_id_fkey
      FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE SET NULL;
  END IF;
END $$;

CREATE INDEX IF NOT EXISTS idx_debts_user_id ON debts(user_id);
CREATE INDEX IF NOT EXISTS idx_debts_company_id ON debts(company_id);
CREATE INDEX IF NOT EXISTS idx_debts_person_id ON debts(person_id);
CREATE INDEX IF NOT EXISTS idx_debts_status ON debts(status);
CREATE INDEX IF NOT EXISTS idx_debts_due_date ON debts(due_date);
CREATE INDEX IF NOT EXISTS idx_debt_payments_debt_id ON debt_payments(debt_id);
CREATE INDEX IF NOT EXISTS idx_debt_agreements_debt_id ON debt_agreements(debt_id);
CREATE INDEX IF NOT EXISTS idx_debt_abatements_debt_id ON debt_abatements(debt_id);

ALTER TABLE debts ENABLE ROW LEVEL SECURITY;
ALTER TABLE debt_payments ENABLE ROW LEVEL SECURITY;
ALTER TABLE debt_agreements ENABLE ROW LEVEL SECURITY;
ALTER TABLE debt_abatements ENABLE ROW LEVEL SECURITY;

REVOKE ALL ON debts FROM anon;
REVOKE ALL ON debt_payments FROM anon;
REVOKE ALL ON debt_agreements FROM anon;
REVOKE ALL ON debt_abatements FROM anon;

GRANT SELECT, INSERT, UPDATE, DELETE ON debts TO authenticated;
GRANT SELECT, INSERT, UPDATE, DELETE ON debt_payments TO authenticated;
GRANT SELECT, INSERT, UPDATE, DELETE ON debt_agreements TO authenticated;
GRANT SELECT, INSERT, UPDATE, DELETE ON debt_abatements TO authenticated;

DROP POLICY IF EXISTS debts_user_policy ON debts;
DROP POLICY IF EXISTS debts_owner_policy ON debts;
CREATE POLICY debts_owner_policy
ON debts
FOR ALL
TO authenticated
USING (
  user_id = auth.uid()
  AND (company_id IS NULL OR company_id IN (SELECT id FROM companies WHERE user_id = auth.uid()))
  AND (person_id IS NULL OR person_id IN (SELECT id FROM people WHERE user_id = auth.uid()))
)
WITH CHECK (
  user_id = auth.uid()
  AND (company_id IS NULL OR company_id IN (SELECT id FROM companies WHERE user_id = auth.uid()))
  AND (person_id IS NULL OR person_id IN (SELECT id FROM people WHERE user_id = auth.uid()))
);

DROP POLICY IF EXISTS payments_user_policy ON debt_payments;
DROP POLICY IF EXISTS debt_payments_owner_policy ON debt_payments;
CREATE POLICY debt_payments_owner_policy
ON debt_payments
FOR ALL
TO authenticated
USING (
  user_id = auth.uid()
  AND EXISTS (
    SELECT 1
    FROM debts
    WHERE debts.id = debt_payments.debt_id
      AND debts.user_id = auth.uid()
  )
)
WITH CHECK (
  user_id = auth.uid()
  AND EXISTS (
    SELECT 1
    FROM debts
    WHERE debts.id = debt_payments.debt_id
      AND debts.user_id = auth.uid()
  )
);

DROP POLICY IF EXISTS agreements_user_policy ON debt_agreements;
DROP POLICY IF EXISTS debt_agreements_owner_policy ON debt_agreements;
CREATE POLICY debt_agreements_owner_policy
ON debt_agreements
FOR ALL
TO authenticated
USING (
  user_id = auth.uid()
  AND EXISTS (
    SELECT 1
    FROM debts
    WHERE debts.id = debt_agreements.debt_id
      AND debts.user_id = auth.uid()
  )
)
WITH CHECK (
  user_id = auth.uid()
  AND EXISTS (
    SELECT 1
    FROM debts
    WHERE debts.id = debt_agreements.debt_id
      AND debts.user_id = auth.uid()
  )
);

DROP POLICY IF EXISTS abatements_user_policy ON debt_abatements;
DROP POLICY IF EXISTS debt_abatements_owner_policy ON debt_abatements;
CREATE POLICY debt_abatements_owner_policy
ON debt_abatements
FOR ALL
TO authenticated
USING (
  user_id = auth.uid()
  AND EXISTS (
    SELECT 1
    FROM debts
    WHERE debts.id = debt_abatements.debt_id
      AND debts.user_id = auth.uid()
  )
)
WITH CHECK (
  user_id = auth.uid()
  AND EXISTS (
    SELECT 1
    FROM debts
    WHERE debts.id = debt_abatements.debt_id
      AND debts.user_id = auth.uid()
  )
);

CREATE OR REPLACE FUNCTION public.touch_debts_updated_at()
RETURNS trigger AS $$
BEGIN
  NEW.updated_at = now();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS update_debts_updated_at ON debts;
CREATE TRIGGER update_debts_updated_at
BEFORE UPDATE ON debts
FOR EACH ROW
EXECUTE FUNCTION public.touch_debts_updated_at();

DROP TRIGGER IF EXISTS update_debt_agreements_updated_at ON debt_agreements;
CREATE TRIGGER update_debt_agreements_updated_at
BEFORE UPDATE ON debt_agreements
FOR EACH ROW
EXECUTE FUNCTION public.touch_debts_updated_at();

CREATE OR REPLACE FUNCTION public.process_debt_payment(
  p_debt_id uuid,
  p_amount numeric,
  p_method text,
  p_reference text DEFAULT NULL,
  p_notes text DEFAULT NULL
)
RETURNS json AS $$
DECLARE
  v_debt debts%ROWTYPE;
  v_new_balance numeric(20,2);
  v_new_status text;
BEGIN
  SELECT * INTO v_debt
  FROM debts
  WHERE id = p_debt_id
  FOR UPDATE;

  IF NOT FOUND THEN
    RAISE EXCEPTION 'Divida nao encontrada';
  END IF;

  INSERT INTO debt_payments (debt_id, user_id, amount, method, reference, notes)
  VALUES (
    p_debt_id,
    v_debt.user_id,
    p_amount,
    p_method::payment_method,
    p_reference,
    p_notes
  );

  v_new_balance := GREATEST(0, COALESCE(v_debt.current_balance, 0) - p_amount);
  v_new_status := CASE
    WHEN v_new_balance = 0 THEN 'PAGO'
    WHEN v_debt.status = 'RENEGOCIADO' THEN 'RENEGOCIADO'
    WHEN current_date > v_debt.due_date THEN 'VENCIDO'
    ELSE 'PENDENTE'
  END;

  UPDATE debts
  SET
    current_balance = v_new_balance,
    total_paid = COALESCE(total_paid, 0) + p_amount,
    status = v_new_status::debt_status,
    updated_at = now()
  WHERE id = p_debt_id;

  RETURN json_build_object(
    'success', true,
    'debt_id', p_debt_id,
    'new_balance', v_new_balance,
    'status', v_new_status
  );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

GRANT EXECUTE ON FUNCTION public.process_debt_payment(uuid, numeric, text, text, text) TO authenticated;

CREATE OR REPLACE FUNCTION public.apply_debt_abatement(
  p_debt_id uuid,
  p_type text,
  p_amount numeric,
  p_reason text
)
RETURNS json AS $$
DECLARE
  v_debt debts%ROWTYPE;
  v_new_balance numeric(20,2);
  v_new_status text;
BEGIN
  SELECT * INTO v_debt
  FROM debts
  WHERE id = p_debt_id
  FOR UPDATE;

  IF NOT FOUND THEN
    RAISE EXCEPTION 'Divida nao encontrada';
  END IF;

  INSERT INTO debt_abatements (debt_id, user_id, abatement_type, amount, reason)
  VALUES (p_debt_id, v_debt.user_id, p_type, p_amount, p_reason);

  v_new_balance := GREATEST(0, COALESCE(v_debt.current_balance, 0) - p_amount);
  v_new_status := CASE
    WHEN v_new_balance = 0 THEN 'PAGO'
    WHEN v_debt.status = 'RENEGOCIADO' THEN 'RENEGOCIADO'
    WHEN current_date > v_debt.due_date THEN 'VENCIDO'
    ELSE 'PENDENTE'
  END;

  UPDATE debts
  SET
    current_balance = v_new_balance,
    total_abated = COALESCE(total_abated, 0) + p_amount,
    status = v_new_status,
    updated_at = now()
  WHERE id = p_debt_id;

  RETURN json_build_object(
    'success', true,
    'debt_id', p_debt_id,
    'new_balance', v_new_balance,
    'status', v_new_status
  );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

GRANT EXECUTE ON FUNCTION public.apply_debt_abatement(uuid, text, numeric, text) TO authenticated;

NOTIFY pgrst, 'reload schema';
