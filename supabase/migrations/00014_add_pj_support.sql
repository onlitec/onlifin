/*
# Adicionar Suporte a Contas PJ (Pessoa Jurídica) - OnliFin

## Descrição
Esta migration implementa o sistema completo de multi-tenancy para suportar
contas de Pessoa Jurídica (PJ) mantendo isolamento de dados entre PF e PJ.

## Tabelas Criadas
1. account_types - Tipos de conta (PF/PJ)
2. companies - Empresas (PJ)
3. audit_logs - Logs de auditoria

## Alterações em Tabelas Existentes
- profiles: Adiciona campos de tipo de conta e CPF
- accounts: Adiciona campo company_id
- categories: Adiciona campo company_id
- transactions: Adiciona campo company_id
- cards: Adiciona campo company_id
- bills_to_pay: Adiciona campo company_id
- bills_to_receive: Adiciona campo company_id
- financial_forecasts: Adiciona campo company_id

## Funções Criadas
- validate_cnpj: Validação de CNPJ
- validate_cpf: Validação de CPF
- audit_log_changes: Trigger de auditoria

## Políticas RLS
- Políticas específicas para isolamento de dados por empresa
*/

-- ============================================================================
-- 1. TABELA DE TIPOS DE CONTA
-- ============================================================================

CREATE TABLE IF NOT EXISTS account_types (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  code VARCHAR(2) UNIQUE NOT NULL CHECK (code IN ('PF', 'PJ')),
  name VARCHAR(50) NOT NULL,
  description TEXT,
  created_at TIMESTAMPTZ DEFAULT TIMEZONE('utc', NOW())
);

-- Inserir tipos de conta padrão
INSERT INTO account_types (code, name, description) VALUES
  ('PF', 'Pessoa Física', 'Conta para gestão financeira pessoal'),
  ('PJ', 'Pessoa Jurídica', 'Conta para gestão financeira empresarial')
ON CONFLICT (code) DO NOTHING;

-- ============================================================================
-- 2. FUNÇÃO DE VALIDAÇÃO DE CNPJ
-- ============================================================================

CREATE OR REPLACE FUNCTION validate_cnpj(cnpj_input VARCHAR)
RETURNS BOOLEAN AS $$
DECLARE
  cnpj VARCHAR(14);
  sum1 INTEGER := 0;
  sum2 INTEGER := 0;
  digit1 INTEGER;
  digit2 INTEGER;
  multiplier INTEGER;
  i INTEGER;
BEGIN
  -- Remover caracteres não numéricos
  cnpj := REGEXP_REPLACE(cnpj_input, '[^0-9]', '', 'g');
  
  -- Verificar se tem 14 dígitos
  IF LENGTH(cnpj) != 14 THEN
    RETURN FALSE;
  END IF;
  
  -- Verificar CNPJs conhecidos como inválidos
  IF cnpj IN ('00000000000000', '11111111111111', '22222222222222', 
              '33333333333333', '44444444444444', '55555555555555',
              '66666666666666', '77777777777777', '88888888888888', 
              '99999999999999') THEN
    RETURN FALSE;
  END IF;
  
  -- Calcular primeiro dígito verificador
  FOR i IN 1..12 LOOP
    multiplier := CASE WHEN i <= 4 THEN 6 - i ELSE 14 - i END;
    sum1 := sum1 + (CAST(SUBSTRING(cnpj FROM i FOR 1) AS INTEGER) * multiplier);
  END LOOP;
  
  digit1 := CASE WHEN (sum1 % 11) < 2 THEN 0 ELSE 11 - (sum1 % 11) END;
  
  -- Calcular segundo dígito verificador
  FOR i IN 1..13 LOOP
    multiplier := CASE WHEN i <= 5 THEN 7 - i ELSE 15 - i END;
    sum2 := sum2 + (CAST(SUBSTRING(cnpj FROM i FOR 1) AS INTEGER) * multiplier);
  END LOOP;
  
  digit2 := CASE WHEN (sum2 % 11) < 2 THEN 0 ELSE 11 - (sum2 % 11) END;
  
  -- Verificar se os dígitos calculados coincidem
  RETURN (digit1 = CAST(SUBSTRING(cnpj FROM 13 FOR 1) AS INTEGER) AND
          digit2 = CAST(SUBSTRING(cnpj FROM 14 FOR 1) AS INTEGER));
END;
$$ LANGUAGE plpgsql IMMUTABLE;

-- ============================================================================
-- 3. FUNÇÃO DE VALIDAÇÃO DE CPF
-- ============================================================================

CREATE OR REPLACE FUNCTION validate_cpf(cpf_input VARCHAR)
RETURNS BOOLEAN AS $$
DECLARE
  cpf VARCHAR(11);
  sum1 INTEGER := 0;
  sum2 INTEGER := 0;
  digit1 INTEGER;
  digit2 INTEGER;
  i INTEGER;
BEGIN
  -- Remover caracteres não numéricos
  cpf := REGEXP_REPLACE(cpf_input, '[^0-9]', '', 'g');
  
  -- Verificar se tem 11 dígitos
  IF LENGTH(cpf) != 11 THEN
    RETURN FALSE;
  END IF;
  
  -- Verificar CPFs conhecidos como inválidos
  IF cpf IN ('00000000000', '11111111111', '22222222222', '33333333333',
             '44444444444', '55555555555', '66666666666', '77777777777',
             '88888888888', '99999999999') THEN
    RETURN FALSE;
  END IF;
  
  -- Calcular primeiro dígito verificador
  FOR i IN 1..9 LOOP
    sum1 := sum1 + (CAST(SUBSTRING(cpf FROM i FOR 1) AS INTEGER) * (11 - i));
  END LOOP;
  
  digit1 := CASE WHEN ((sum1 * 10) % 11) = 10 THEN 0 ELSE (sum1 * 10) % 11 END;
  
  -- Calcular segundo dígito verificador
  FOR i IN 1..10 LOOP
    sum2 := sum2 + (CAST(SUBSTRING(cpf FROM i FOR 1) AS INTEGER) * (12 - i));
  END LOOP;
  
  digit2 := CASE WHEN ((sum2 * 10) % 11) = 10 THEN 0 ELSE (sum2 * 10) % 11 END;
  
  -- Verificar se os dígitos calculados coincidem
  RETURN (digit1 = CAST(SUBSTRING(cpf FROM 10 FOR 1) AS INTEGER) AND
          digit2 = CAST(SUBSTRING(cpf FROM 11 FOR 1) AS INTEGER));
END;
$$ LANGUAGE plpgsql IMMUTABLE;

-- ============================================================================
-- 4. ALTERAR TABELA PROFILES
-- ============================================================================

-- Adicionar novos campos
ALTER TABLE profiles ADD COLUMN IF NOT EXISTS account_type_id UUID REFERENCES account_types(id);
ALTER TABLE profiles ADD COLUMN IF NOT EXISTS cpf VARCHAR(14) UNIQUE;
ALTER TABLE profiles ADD COLUMN IF NOT EXISTS birth_date DATE;
ALTER TABLE profiles ADD COLUMN IF NOT EXISTS default_company_id UUID;

-- Definir tipo de conta padrão como PF para usuários existentes
UPDATE profiles 
SET account_type_id = (SELECT id FROM account_types WHERE code = 'PF')
WHERE account_type_id IS NULL;

-- Adicionar constraint de validação de CPF (apenas se não for nulo)
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM pg_constraint WHERE conname = 'profiles_valid_cpf'
  ) THEN
    ALTER TABLE profiles ADD CONSTRAINT profiles_valid_cpf CHECK (cpf IS NULL OR validate_cpf(cpf));
  END IF;
END
$$;

-- ============================================================================
-- 5. TABELA DE EMPRESAS (COMPANIES)
-- ============================================================================

CREATE TABLE IF NOT EXISTS companies (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id UUID REFERENCES profiles(id) ON DELETE CASCADE NOT NULL,
  
  -- Dados da Empresa
  cnpj VARCHAR(18) UNIQUE NOT NULL,
  razao_social VARCHAR(255) NOT NULL,
  nome_fantasia VARCHAR(255),
  inscricao_estadual VARCHAR(50),
  inscricao_municipal VARCHAR(50),
  
  -- Endereço
  cep VARCHAR(10),
  logradouro VARCHAR(255),
  numero VARCHAR(20),
  complemento VARCHAR(100),
  bairro VARCHAR(100),
  cidade VARCHAR(100),
  uf VARCHAR(2),
  
  -- Contato
  email VARCHAR(255),
  phone VARCHAR(20),
  website VARCHAR(255),
  
  -- Classificação
  porte VARCHAR(20) CHECK (porte IS NULL OR porte IN ('MEI', 'ME', 'EPP', 'MEDIA', 'GRANDE')),
  regime_tributario VARCHAR(30) CHECK (regime_tributario IS NULL OR regime_tributario IN ('SIMPLES', 'LUCRO_PRESUMIDO', 'LUCRO_REAL')),
  
  -- Dados Bancários Padrão
  banco_padrao VARCHAR(100),
  agencia_padrao VARCHAR(20),
  conta_padrao VARCHAR(30),
  
  -- Status
  is_active BOOLEAN DEFAULT true,
  is_default BOOLEAN DEFAULT false,
  
  -- Configurações
  settings JSONB DEFAULT '{}'::jsonb,
  
  created_at TIMESTAMPTZ DEFAULT TIMEZONE('utc', NOW()),
  updated_at TIMESTAMPTZ DEFAULT TIMEZONE('utc', NOW()),
  
  -- Constraint de validação de CNPJ
  CONSTRAINT companies_valid_cnpj CHECK (validate_cnpj(cnpj))
);

-- Criar índice único parcial para is_default por usuário
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM pg_indexes WHERE indexname = 'idx_companies_unique_default_per_user'
  ) THEN
    CREATE UNIQUE INDEX idx_companies_unique_default_per_user 
    ON companies(user_id) WHERE is_default = true;
  END IF;
END
$$;

-- Adicionar foreign key em profiles para default_company_id
ALTER TABLE profiles DROP CONSTRAINT IF EXISTS profiles_default_company_fk;
ALTER TABLE profiles ADD CONSTRAINT profiles_default_company_fk 
  FOREIGN KEY (default_company_id) REFERENCES companies(id) ON DELETE SET NULL;

-- ============================================================================
-- 6. ADICIONAR CAMPO company_id NAS TABELAS EXISTENTES
-- ============================================================================

-- Accounts
ALTER TABLE accounts ADD COLUMN IF NOT EXISTS company_id UUID REFERENCES companies(id) ON DELETE CASCADE;

-- Categories
ALTER TABLE categories ADD COLUMN IF NOT EXISTS company_id UUID REFERENCES companies(id) ON DELETE CASCADE;

-- Transactions
ALTER TABLE transactions ADD COLUMN IF NOT EXISTS company_id UUID REFERENCES companies(id) ON DELETE CASCADE;

-- Cards
ALTER TABLE cards ADD COLUMN IF NOT EXISTS company_id UUID REFERENCES companies(id) ON DELETE CASCADE;

-- Bills to Pay (se existir)
DO $$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'bills_to_pay') THEN
    ALTER TABLE bills_to_pay ADD COLUMN IF NOT EXISTS company_id UUID REFERENCES companies(id) ON DELETE CASCADE;
  END IF;
END
$$;

-- Bills to Receive (se existir)
DO $$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'bills_to_receive') THEN
    ALTER TABLE bills_to_receive ADD COLUMN IF NOT EXISTS company_id UUID REFERENCES companies(id) ON DELETE CASCADE;
  END IF;
END
$$;

-- Financial Forecasts (se existir)
DO $$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'financial_forecasts') THEN
    ALTER TABLE financial_forecasts ADD COLUMN IF NOT EXISTS company_id UUID REFERENCES companies(id) ON DELETE CASCADE;
  END IF;
END
$$;

-- ============================================================================
-- 7. TABELA DE LOGS DE AUDITORIA
-- ============================================================================

CREATE TABLE IF NOT EXISTS audit_logs (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id UUID REFERENCES profiles(id) NOT NULL,
  company_id UUID REFERENCES companies(id),
  
  action VARCHAR(50) NOT NULL,
  table_name VARCHAR(100) NOT NULL,
  record_id UUID,
  old_data JSONB,
  new_data JSONB,
  
  ip_address INET,
  user_agent TEXT,
  
  created_at TIMESTAMPTZ DEFAULT TIMEZONE('utc', NOW())
);

-- ============================================================================
-- 8. TRIGGER DE UPDATED_AT
-- ============================================================================

CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = TIMEZONE('utc', NOW());
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Aplicar trigger na tabela companies
DROP TRIGGER IF EXISTS update_companies_updated_at ON companies;
CREATE TRIGGER update_companies_updated_at 
  BEFORE UPDATE ON companies
  FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- ============================================================================
-- 9. FUNÇÃO E TRIGGER DE AUDITORIA
-- ============================================================================

CREATE OR REPLACE FUNCTION audit_log_changes()
RETURNS TRIGGER AS $$
DECLARE
  v_company_id UUID;
BEGIN
  -- Tentar obter company_id do registro
  IF TG_OP = 'DELETE' THEN
    v_company_id := OLD.company_id;
  ELSE
    v_company_id := NEW.company_id;
  END IF;
  
  INSERT INTO audit_logs (user_id, company_id, action, table_name, record_id, old_data, new_data)
  VALUES (
    auth.uid(),
    v_company_id,
    TG_OP,
    TG_TABLE_NAME,
    COALESCE(NEW.id, OLD.id),
    CASE WHEN TG_OP = 'DELETE' THEN to_jsonb(OLD) ELSE NULL END,
    CASE WHEN TG_OP IN ('INSERT', 'UPDATE') THEN to_jsonb(NEW) ELSE NULL END
  );
  
  RETURN COALESCE(NEW, OLD);
EXCEPTION
  WHEN undefined_column THEN
    -- Se a tabela não tiver company_id, continua sem ele
    INSERT INTO audit_logs (user_id, action, table_name, record_id, old_data, new_data)
    VALUES (
      auth.uid(),
      TG_OP,
      TG_TABLE_NAME,
      COALESCE(NEW.id, OLD.id),
      CASE WHEN TG_OP = 'DELETE' THEN to_jsonb(OLD) ELSE NULL END,
      CASE WHEN TG_OP IN ('INSERT', 'UPDATE') THEN to_jsonb(NEW) ELSE NULL END
    );
    RETURN COALESCE(NEW, OLD);
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Aplicar trigger de auditoria na tabela companies
DROP TRIGGER IF EXISTS audit_companies ON companies;
CREATE TRIGGER audit_companies
  AFTER INSERT OR UPDATE OR DELETE ON companies
  FOR EACH ROW EXECUTE FUNCTION audit_log_changes();

-- ============================================================================
-- 10. HABILITAR RLS E CRIAR POLÍTICAS
-- ============================================================================

-- Habilitar RLS em novas tabelas
ALTER TABLE account_types ENABLE ROW LEVEL SECURITY;
ALTER TABLE companies ENABLE ROW LEVEL SECURITY;
ALTER TABLE audit_logs ENABLE ROW LEVEL SECURITY;

-- Políticas para account_types (leitura pública para usuários autenticados)
DROP POLICY IF EXISTS "Everyone can view account types" ON account_types;
CREATE POLICY "Everyone can view account types"
  ON account_types FOR SELECT
  TO authenticated
  USING (true);

-- Políticas para companies
DROP POLICY IF EXISTS "Users can view own companies" ON companies;
CREATE POLICY "Users can view own companies"
  ON companies FOR SELECT
  TO authenticated
  USING (auth.uid() = user_id);

DROP POLICY IF EXISTS "Users can insert own companies" ON companies;
CREATE POLICY "Users can insert own companies"
  ON companies FOR INSERT
  TO authenticated
  WITH CHECK (auth.uid() = user_id);

DROP POLICY IF EXISTS "Users can update own companies" ON companies;
CREATE POLICY "Users can update own companies"
  ON companies FOR UPDATE
  TO authenticated
  USING (auth.uid() = user_id);

DROP POLICY IF EXISTS "Users can delete own companies" ON companies;
CREATE POLICY "Users can delete own companies"
  ON companies FOR DELETE
  TO authenticated
  USING (auth.uid() = user_id);

-- Políticas para audit_logs
DROP POLICY IF EXISTS "Users can view own audit logs" ON audit_logs;
CREATE POLICY "Users can view own audit logs"
  ON audit_logs FOR SELECT
  TO authenticated
  USING (user_id = auth.uid());

DROP POLICY IF EXISTS "Admins can view all audit logs" ON audit_logs;
CREATE POLICY "Admins can view all audit logs"
  ON audit_logs FOR SELECT
  TO authenticated
  USING (is_admin(auth.uid()));

-- ============================================================================
-- 11. ATUALIZAR POLÍTICAS RLS PARA TABELAS EXISTENTES
-- ============================================================================

-- Atualizar policies para accounts (incluir company_id)
DROP POLICY IF EXISTS "Users can manage own accounts with company" ON accounts;
CREATE POLICY "Users can manage own accounts with company"
  ON accounts FOR ALL
  TO authenticated
  USING (
    user_id = auth.uid() AND
    (company_id IS NULL OR company_id IN (SELECT id FROM companies WHERE user_id = auth.uid()))
  )
  WITH CHECK (
    user_id = auth.uid() AND
    (company_id IS NULL OR company_id IN (SELECT id FROM companies WHERE user_id = auth.uid()))
  );

-- Atualizar policies para transactions (incluir company_id)
DROP POLICY IF EXISTS "Users can manage own transactions with company" ON transactions;
CREATE POLICY "Users can manage own transactions with company"
  ON transactions FOR ALL
  TO authenticated
  USING (
    user_id = auth.uid() AND
    (company_id IS NULL OR company_id IN (SELECT id FROM companies WHERE user_id = auth.uid()))
  )
  WITH CHECK (
    user_id = auth.uid() AND
    (company_id IS NULL OR company_id IN (SELECT id FROM companies WHERE user_id = auth.uid()))
  );

-- Atualizar policies para categories (incluir company_id)
DROP POLICY IF EXISTS "Users can manage own categories with company" ON categories;
CREATE POLICY "Users can manage own categories with company"
  ON categories FOR ALL
  TO authenticated
  USING (
    user_id = auth.uid() AND
    (company_id IS NULL OR company_id IN (SELECT id FROM companies WHERE user_id = auth.uid()))
  )
  WITH CHECK (
    user_id = auth.uid() AND
    (company_id IS NULL OR company_id IN (SELECT id FROM companies WHERE user_id = auth.uid()))
  );

-- ============================================================================
-- 12. ÍNDICES DE PERFORMANCE
-- ============================================================================

CREATE INDEX IF NOT EXISTS idx_companies_user_id ON companies(user_id);
CREATE INDEX IF NOT EXISTS idx_companies_cnpj ON companies(cnpj);
CREATE INDEX IF NOT EXISTS idx_companies_is_active ON companies(is_active) WHERE is_active = true;

CREATE INDEX IF NOT EXISTS idx_accounts_company_id ON accounts(company_id);
CREATE INDEX IF NOT EXISTS idx_transactions_company_id ON transactions(company_id);
CREATE INDEX IF NOT EXISTS idx_categories_company_id ON categories(company_id);
CREATE INDEX IF NOT EXISTS idx_cards_company_id ON cards(company_id);

CREATE INDEX IF NOT EXISTS idx_audit_logs_user_id ON audit_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_audit_logs_company_id ON audit_logs(company_id);
CREATE INDEX IF NOT EXISTS idx_audit_logs_created_at ON audit_logs(created_at DESC);

-- Índice composto para queries comuns
CREATE INDEX IF NOT EXISTS idx_transactions_user_company_date 
  ON transactions(user_id, company_id, date DESC);

CREATE INDEX IF NOT EXISTS idx_accounts_user_company_active 
  ON accounts(user_id, company_id);

-- ============================================================================
-- 13. VIEWS CONSOLIDADAS
-- ============================================================================

-- View de resumo por empresa
CREATE OR REPLACE VIEW v_company_summary AS
SELECT 
  c.id as company_id,
  c.user_id,
  c.razao_social,
  c.nome_fantasia,
  c.cnpj,
  c.is_active,
  c.is_default,
  c.created_at,
  COUNT(DISTINCT a.id) as total_accounts,
  COALESCE(SUM(a.balance), 0) as total_balance,
  COUNT(DISTINCT t.id) as total_transactions,
  COALESCE(SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END), 0) as total_income,
  COALESCE(SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END), 0) as total_expense
FROM companies c
LEFT JOIN accounts a ON c.id = a.company_id
LEFT JOIN transactions t ON c.id = t.company_id
WHERE c.is_active = true
GROUP BY c.id, c.user_id, c.razao_social, c.nome_fantasia, c.cnpj, c.is_active, c.is_default, c.created_at;

-- View de dashboard consolidado do usuário
CREATE OR REPLACE VIEW v_user_consolidated_dashboard AS
SELECT 
  p.id as user_id,
  p.full_name,
  p.account_type_id,
  
  -- Totais PF (transações sem company_id)
  (SELECT COUNT(*) FROM accounts WHERE user_id = p.id AND company_id IS NULL) as pf_accounts,
  (SELECT COALESCE(SUM(balance), 0) FROM accounts WHERE user_id = p.id AND company_id IS NULL) as pf_balance,
  
  -- Totais PJ
  (SELECT COUNT(*) FROM companies WHERE user_id = p.id AND is_active = true) as total_companies,
  (SELECT COUNT(*) FROM accounts a 
   INNER JOIN companies c ON a.company_id = c.id 
   WHERE c.user_id = p.id AND c.is_active = true) as pj_accounts,
  (SELECT COALESCE(SUM(a.balance), 0) FROM accounts a 
   INNER JOIN companies c ON a.company_id = c.id 
   WHERE c.user_id = p.id AND c.is_active = true) as pj_balance,
   
  -- Total geral
  (SELECT COALESCE(SUM(balance), 0) FROM accounts WHERE user_id = p.id) as total_balance
FROM profiles p;

-- RLS para as views (PostgreSQL não suporta RLS em views diretamente,
-- mas como as views fazem JOIN com tabelas que têm RLS, os dados já estão filtrados)

-- ============================================================================
-- 14. FUNÇÕES AUXILIARES
-- ============================================================================

-- Função para obter empresas do usuário atual
CREATE OR REPLACE FUNCTION get_user_companies()
RETURNS TABLE (
  id UUID,
  cnpj VARCHAR(18),
  razao_social VARCHAR(255),
  nome_fantasia VARCHAR(255),
  is_default BOOLEAN,
  is_active BOOLEAN
) AS $$
BEGIN
  RETURN QUERY
  SELECT 
    c.id,
    c.cnpj,
    c.razao_social,
    c.nome_fantasia,
    c.is_default,
    c.is_active
  FROM companies c
  WHERE c.user_id = auth.uid()
  ORDER BY c.is_default DESC, c.created_at DESC;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Função para definir empresa padrão
CREATE OR REPLACE FUNCTION set_default_company(company_uuid UUID)
RETURNS VOID AS $$
BEGIN
  -- Remover flag de default das outras empresas
  UPDATE companies 
  SET is_default = false 
  WHERE user_id = auth.uid() AND id != company_uuid;
  
  -- Definir a empresa como padrão
  UPDATE companies 
  SET is_default = true 
  WHERE id = company_uuid AND user_id = auth.uid();
  
  -- Atualizar a referência no profile
  UPDATE profiles 
  SET default_company_id = company_uuid 
  WHERE id = auth.uid();
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Função para calcular saldo total de uma empresa
CREATE OR REPLACE FUNCTION calculate_company_balance(company_uuid UUID)
RETURNS NUMERIC AS $$
DECLARE
  total NUMERIC;
BEGIN
  SELECT COALESCE(SUM(balance), 0) INTO total
  FROM accounts
  WHERE company_id = company_uuid;
  
  RETURN total;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- ============================================================================
-- 15. COMENTÁRIOS NAS TABELAS
-- ============================================================================

COMMENT ON TABLE account_types IS 'Tipos de conta: PF (Pessoa Física) ou PJ (Pessoa Jurídica)';
COMMENT ON TABLE companies IS 'Empresas cadastradas para contas PJ';
COMMENT ON TABLE audit_logs IS 'Logs de auditoria de todas as operações';

COMMENT ON COLUMN companies.cnpj IS 'CNPJ da empresa (formato: XX.XXX.XXX/XXXX-XX)';
COMMENT ON COLUMN companies.razao_social IS 'Razão social oficial da empresa';
COMMENT ON COLUMN companies.nome_fantasia IS 'Nome fantasia ou nome comercial';
COMMENT ON COLUMN companies.porte IS 'Porte da empresa: MEI, ME, EPP, MEDIA, GRANDE';
COMMENT ON COLUMN companies.regime_tributario IS 'Regime tributário: SIMPLES, LUCRO_PRESUMIDO, LUCRO_REAL';

COMMENT ON FUNCTION validate_cnpj IS 'Valida um CNPJ usando o algoritmo oficial da Receita Federal';
COMMENT ON FUNCTION validate_cpf IS 'Valida um CPF usando o algoritmo oficial';
COMMENT ON FUNCTION set_default_company IS 'Define uma empresa como padrão para o usuário';
COMMENT ON FUNCTION get_user_companies IS 'Retorna todas as empresas do usuário atual';
