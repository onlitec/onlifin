# 📋 Planejamento: Sistema de Contas para Pessoas Jurídicas - OnliFin

## 🎯 Objetivo
Implementar um sistema completo de gestão financeira para Pessoas Jurídicas (PJ) na plataforma OnliFin, mantendo a separação de dados entre contas PF e PJ, permitindo gestão multi-empresa e visualização consolidada.

---

## 📊 Análise da Plataforma Atual

### Stack Tecnológico Identificado
- **Frontend**: React + TypeScript + Vite
- **Backend**: Supabase (PostgreSQL)
- **UI**: shadcn/ui + Tailwind CSS
- **Autenticação**: Supabase Auth
- **Deploy**: Docker + GitHub Actions
- **PWA**: Service Workers implementado

### Estrutura de Diretórios
```
src/
├── components/     # Componentes reutilizáveis
├── context/        # Contextos React
├── db/            # Configurações de banco
├── hooks/         # Custom hooks
├── layout/        # Layouts da aplicação
├── pages/         # Páginas da aplicação
├── services/      # Serviços de API
└── types/         # Definições TypeScript
```

---

## 🏗️ Arquitetura Proposta

### 1. Modelo de Dados Multi-Tenant

#### Estratégia: **Row-Level Security (RLS) com Tenant Isolation**

**Vantagens:**
- Máximo isolamento de dados
- Segurança nativa do PostgreSQL
- Performance otimizada
- Escalabilidade horizontal

#### Estrutura de Tabelas

```sql
-- 1. Tabela de Tipos de Conta
CREATE TABLE account_types (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  code VARCHAR(2) UNIQUE NOT NULL CHECK (code IN ('PF', 'PJ')),
  name VARCHAR(50) NOT NULL,
  description TEXT,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW())
);

-- 2. Tabela de Perfis de Usuário (estendida)
CREATE TABLE user_profiles (
  id UUID PRIMARY KEY REFERENCES auth.users(id) ON DELETE CASCADE,
  email VARCHAR(255) NOT NULL,
  full_name VARCHAR(255),
  account_type_id UUID REFERENCES account_types(id) NOT NULL,
  
  -- Dados PF
  cpf VARCHAR(14) UNIQUE,
  birth_date DATE,
  phone VARCHAR(20),
  
  -- Preferências
  default_company_id UUID, -- Para PJ: empresa padrão ao logar
  theme VARCHAR(20) DEFAULT 'light',
  language VARCHAR(5) DEFAULT 'pt-BR',
  
  created_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW()),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW()),
  
  -- Constraints
  CONSTRAINT valid_pf_data CHECK (
    (account_type_id = (SELECT id FROM account_types WHERE code = 'PF') AND cpf IS NOT NULL)
    OR account_type_id != (SELECT id FROM account_types WHERE code = 'PF')
  )
);

-- 3. Tabela de Empresas (PJ)
CREATE TABLE companies (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  user_id UUID REFERENCES user_profiles(id) ON DELETE CASCADE NOT NULL,
  
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
  porte VARCHAR(20) CHECK (porte IN ('MEI', 'ME', 'EPP', 'MEDIA', 'GRANDE')),
  regime_tributario VARCHAR(30) CHECK (regime_tributario IN ('SIMPLES', 'LUCRO_PRESUMIDO', 'LUCRO_REAL')),
  
  -- Dados Bancários Padrão
  banco_padrao VARCHAR(100),
  agencia_padrao VARCHAR(20),
  conta_padrao VARCHAR(30),
  
  -- Status
  is_active BOOLEAN DEFAULT true,
  is_default BOOLEAN DEFAULT false,
  
  -- Configurações
  settings JSONB DEFAULT '{}'::jsonb,
  
  created_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW()),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW()),
  
  -- Indexes
  CONSTRAINT unique_default_per_user UNIQUE (user_id, is_default) WHERE is_default = true
);

-- 4. Tabela de Contas Bancárias (adaptada)
CREATE TABLE bank_accounts (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  user_id UUID REFERENCES user_profiles(id) ON DELETE CASCADE NOT NULL,
  company_id UUID REFERENCES companies(id) ON DELETE CASCADE,
  
  -- Dados da Conta
  bank_name VARCHAR(100) NOT NULL,
  account_type VARCHAR(50) NOT NULL,
  account_number VARCHAR(50) NOT NULL,
  agency VARCHAR(20),
  
  -- Saldo
  initial_balance DECIMAL(15,2) DEFAULT 0,
  current_balance DECIMAL(15,2) DEFAULT 0,
  
  -- Cor e Ícone para UI
  color VARCHAR(20) DEFAULT '#000000',
  icon VARCHAR(50),
  
  -- Status
  is_active BOOLEAN DEFAULT true,
  is_main BOOLEAN DEFAULT false,
  
  created_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW()),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW()),
  
  -- Constraints
  CONSTRAINT pf_no_company CHECK (
    (company_id IS NULL AND user_id IN (SELECT id FROM user_profiles WHERE account_type_id = (SELECT id FROM account_types WHERE code = 'PF')))
    OR company_id IS NOT NULL
  ),
  CONSTRAINT unique_main_per_scope UNIQUE (user_id, company_id, is_main) WHERE is_main = true
);

-- 5. Tabela de Categorias (adaptada)
CREATE TABLE categories (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  user_id UUID REFERENCES user_profiles(id) ON DELETE CASCADE NOT NULL,
  company_id UUID REFERENCES companies(id) ON DELETE CASCADE,
  
  name VARCHAR(100) NOT NULL,
  type VARCHAR(20) NOT NULL CHECK (type IN ('income', 'expense')),
  color VARCHAR(20) DEFAULT '#000000',
  icon VARCHAR(50),
  parent_id UUID REFERENCES categories(id),
  
  -- Sistema de tags
  tags TEXT[],
  
  is_system BOOLEAN DEFAULT false,
  is_active BOOLEAN DEFAULT true,
  
  created_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW()),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW())
);

-- 6. Tabela de Transações (adaptada)
CREATE TABLE transactions (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  user_id UUID REFERENCES user_profiles(id) ON DELETE CASCADE NOT NULL,
  company_id UUID REFERENCES companies(id) ON DELETE CASCADE,
  
  account_id UUID REFERENCES bank_accounts(id) ON DELETE CASCADE NOT NULL,
  category_id UUID REFERENCES categories(id),
  
  -- Dados da Transação
  description TEXT NOT NULL,
  amount DECIMAL(15,2) NOT NULL,
  type VARCHAR(20) NOT NULL CHECK (type IN ('income', 'expense', 'transfer')),
  date DATE NOT NULL,
  
  -- Transferências
  transfer_account_id UUID REFERENCES bank_accounts(id),
  transfer_transaction_id UUID REFERENCES transactions(id),
  
  -- Recorrência
  is_recurring BOOLEAN DEFAULT false,
  recurrence_pattern JSONB,
  parent_transaction_id UUID REFERENCES transactions(id),
  
  -- Status
  status VARCHAR(20) DEFAULT 'completed' CHECK (status IN ('pending', 'completed', 'cancelled')),
  is_paid BOOLEAN DEFAULT false,
  
  -- Metadata
  tags TEXT[],
  notes TEXT,
  attachments JSONB,
  
  created_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW()),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW())
);

-- 7. Tabela de Metas Financeiras
CREATE TABLE financial_goals (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  user_id UUID REFERENCES user_profiles(id) ON DELETE CASCADE NOT NULL,
  company_id UUID REFERENCES companies(id) ON DELETE CASCADE,
  
  name VARCHAR(255) NOT NULL,
  description TEXT,
  target_amount DECIMAL(15,2) NOT NULL,
  current_amount DECIMAL(15,2) DEFAULT 0,
  deadline DATE,
  
  category_id UUID REFERENCES categories(id),
  
  is_active BOOLEAN DEFAULT true,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW()),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW())
);

-- 8. Tabela de Orçamentos
CREATE TABLE budgets (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  user_id UUID REFERENCES user_profiles(id) ON DELETE CASCADE NOT NULL,
  company_id UUID REFERENCES companies(id) ON DELETE CASCADE,
  
  category_id UUID REFERENCES categories(id) NOT NULL,
  month INTEGER NOT NULL CHECK (month BETWEEN 1 AND 12),
  year INTEGER NOT NULL,
  
  planned_amount DECIMAL(15,2) NOT NULL,
  spent_amount DECIMAL(15,2) DEFAULT 0,
  
  notes TEXT,
  
  created_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW()),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW()),
  
  CONSTRAINT unique_budget_per_category_month UNIQUE (user_id, company_id, category_id, month, year)
);

-- 9. Tabela de Relatórios Salvos
CREATE TABLE saved_reports (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  user_id UUID REFERENCES user_profiles(id) ON DELETE CASCADE NOT NULL,
  company_id UUID REFERENCES companies(id) ON DELETE CASCADE,
  
  name VARCHAR(255) NOT NULL,
  type VARCHAR(50) NOT NULL,
  filters JSONB NOT NULL,
  configuration JSONB,
  
  is_scheduled BOOLEAN DEFAULT false,
  schedule_config JSONB,
  
  created_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW()),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW())
);

-- 10. Tabela de Logs de Auditoria
CREATE TABLE audit_logs (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  user_id UUID REFERENCES user_profiles(id) NOT NULL,
  company_id UUID REFERENCES companies(id),
  
  action VARCHAR(50) NOT NULL,
  table_name VARCHAR(100) NOT NULL,
  record_id UUID,
  old_data JSONB,
  new_data JSONB,
  
  ip_address INET,
  user_agent TEXT,
  
  created_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc', NOW())
);
```

---

### 2. Row-Level Security (RLS) Policies

```sql
-- Habilitar RLS em todas as tabelas
ALTER TABLE user_profiles ENABLE ROW LEVEL SECURITY;
ALTER TABLE companies ENABLE ROW LEVEL SECURITY;
ALTER TABLE bank_accounts ENABLE ROW LEVEL SECURITY;
ALTER TABLE categories ENABLE ROW LEVEL SECURITY;
ALTER TABLE transactions ENABLE ROW LEVEL SECURITY;
ALTER TABLE financial_goals ENABLE ROW LEVEL SECURITY;
ALTER TABLE budgets ENABLE ROW LEVEL SECURITY;
ALTER TABLE saved_reports ENABLE ROW LEVEL SECURITY;
ALTER TABLE audit_logs ENABLE ROW LEVEL SECURITY;

-- Policies para user_profiles
CREATE POLICY "Users can view own profile"
  ON user_profiles FOR SELECT
  USING (auth.uid() = id);

CREATE POLICY "Users can update own profile"
  ON user_profiles FOR UPDATE
  USING (auth.uid() = id);

-- Policies para companies
CREATE POLICY "Users can view own companies"
  ON companies FOR SELECT
  USING (auth.uid() = user_id);

CREATE POLICY "Users can insert own companies"
  ON companies FOR INSERT
  WITH CHECK (auth.uid() = user_id);

CREATE POLICY "Users can update own companies"
  ON companies FOR UPDATE
  USING (auth.uid() = user_id);

CREATE POLICY "Users can delete own companies"
  ON companies FOR DELETE
  USING (auth.uid() = user_id);

-- Policies para bank_accounts
CREATE POLICY "Users can view own accounts"
  ON bank_accounts FOR SELECT
  USING (
    auth.uid() = user_id AND
    (company_id IS NULL OR company_id IN (SELECT id FROM companies WHERE user_id = auth.uid()))
  );

CREATE POLICY "Users can insert own accounts"
  ON bank_accounts FOR INSERT
  WITH CHECK (
    auth.uid() = user_id AND
    (company_id IS NULL OR company_id IN (SELECT id FROM companies WHERE user_id = auth.uid()))
  );

CREATE POLICY "Users can update own accounts"
  ON bank_accounts FOR UPDATE
  USING (
    auth.uid() = user_id AND
    (company_id IS NULL OR company_id IN (SELECT id FROM companies WHERE user_id = auth.uid()))
  );

-- Policies para transactions
CREATE POLICY "Users can view own transactions"
  ON transactions FOR SELECT
  USING (
    auth.uid() = user_id AND
    (company_id IS NULL OR company_id IN (SELECT id FROM companies WHERE user_id = auth.uid()))
  );

CREATE POLICY "Users can insert own transactions"
  ON transactions FOR INSERT
  WITH CHECK (
    auth.uid() = user_id AND
    (company_id IS NULL OR company_id IN (SELECT id FROM companies WHERE user_id = auth.uid()))
  );

CREATE POLICY "Users can update own transactions"
  ON transactions FOR UPDATE
  USING (
    auth.uid() = user_id AND
    (company_id IS NULL OR company_id IN (SELECT id FROM companies WHERE user_id = auth.uid()))
  );

-- (Repetir padrão similar para outras tabelas)
```

---

### 3. Functions e Triggers

```sql
-- Function para atualizar updated_at automaticamente
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = TIMEZONE('utc', NOW());
  RETURN NEW;
END;
$$ language 'plpgsql';

-- Aplicar trigger em todas as tabelas relevantes
CREATE TRIGGER update_user_profiles_updated_at BEFORE UPDATE ON user_profiles
  FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_companies_updated_at BEFORE UPDATE ON companies
  FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_bank_accounts_updated_at BEFORE UPDATE ON bank_accounts
  FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_transactions_updated_at BEFORE UPDATE ON transactions
  FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Function para calcular saldo de conta
CREATE OR REPLACE FUNCTION calculate_account_balance(account_uuid UUID)
RETURNS DECIMAL AS $$
DECLARE
  total_income DECIMAL;
  total_expense DECIMAL;
  initial_bal DECIMAL;
  current_bal DECIMAL;
BEGIN
  -- Buscar saldo inicial
  SELECT initial_balance INTO initial_bal
  FROM bank_accounts
  WHERE id = account_uuid;
  
  -- Calcular total de entradas
  SELECT COALESCE(SUM(amount), 0) INTO total_income
  FROM transactions
  WHERE account_id = account_uuid 
    AND type = 'income' 
    AND status = 'completed';
  
  -- Calcular total de saídas
  SELECT COALESCE(SUM(amount), 0) INTO total_expense
  FROM transactions
  WHERE account_id = account_uuid 
    AND type = 'expense' 
    AND status = 'completed';
  
  current_bal := initial_bal + total_income - total_expense;
  
  RETURN current_bal;
END;
$$ LANGUAGE plpgsql;

-- Function para atualizar saldo após transação
CREATE OR REPLACE FUNCTION update_account_balance()
RETURNS TRIGGER AS $$
BEGIN
  -- Atualizar saldo da conta
  UPDATE bank_accounts
  SET current_balance = calculate_account_balance(NEW.account_id)
  WHERE id = NEW.account_id;
  
  -- Se for transferência, atualizar conta destino
  IF NEW.type = 'transfer' AND NEW.transfer_account_id IS NOT NULL THEN
    UPDATE bank_accounts
    SET current_balance = calculate_account_balance(NEW.transfer_account_id)
    WHERE id = NEW.transfer_account_id;
  END IF;
  
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger para atualizar saldo
CREATE TRIGGER update_balance_after_transaction
  AFTER INSERT OR UPDATE OR DELETE ON transactions
  FOR EACH ROW EXECUTE FUNCTION update_account_balance();

-- Function para auditoria
CREATE OR REPLACE FUNCTION audit_log_changes()
RETURNS TRIGGER AS $$
BEGIN
  INSERT INTO audit_logs (
    user_id,
    company_id,
    action,
    table_name,
    record_id,
    old_data,
    new_data
  ) VALUES (
    auth.uid(),
    COALESCE(NEW.company_id, OLD.company_id),
    TG_OP,
    TG_TABLE_NAME,
    COALESCE(NEW.id, OLD.id),
    CASE WHEN TG_OP = 'DELETE' THEN row_to_json(OLD) ELSE NULL END,
    CASE WHEN TG_OP IN ('INSERT', 'UPDATE') THEN row_to_json(NEW) ELSE NULL END
  );
  
  RETURN COALESCE(NEW, OLD);
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Aplicar trigger de auditoria nas tabelas críticas
CREATE TRIGGER audit_companies
  AFTER INSERT OR UPDATE OR DELETE ON companies
  FOR EACH ROW EXECUTE FUNCTION audit_log_changes();

CREATE TRIGGER audit_transactions
  AFTER INSERT OR UPDATE OR DELETE ON transactions
  FOR EACH ROW EXECUTE FUNCTION audit_log_changes();

-- Function para validar CNPJ
CREATE OR REPLACE FUNCTION validate_cnpj(cnpj_input VARCHAR)
RETURNS BOOLEAN AS $$
DECLARE
  cnpj VARCHAR(14);
  sum1 INTEGER := 0;
  sum2 INTEGER := 0;
  digit1 INTEGER;
  digit2 INTEGER;
  multiplier INTEGER;
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
$$ LANGUAGE plpgsql;

-- Adicionar constraint de validação de CNPJ
ALTER TABLE companies ADD CONSTRAINT valid_cnpj
  CHECK (validate_cnpj(cnpj));

-- Function para validar CPF
CREATE OR REPLACE FUNCTION validate_cpf(cpf_input VARCHAR)
RETURNS BOOLEAN AS $$
DECLARE
  cpf VARCHAR(11);
  sum1 INTEGER := 0;
  sum2 INTEGER := 0;
  digit1 INTEGER;
  digit2 INTEGER;
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
$$ LANGUAGE plpgsql;

-- Adicionar constraint de validação de CPF
ALTER TABLE user_profiles ADD CONSTRAINT valid_cpf
  CHECK (cpf IS NULL OR validate_cpf(cpf));

-- Views para relatórios consolidados
CREATE OR REPLACE VIEW v_company_summary AS
SELECT 
  c.id as company_id,
  c.user_id,
  c.razao_social,
  c.nome_fantasia,
  c.cnpj,
  COUNT(DISTINCT ba.id) as total_accounts,
  SUM(ba.current_balance) as total_balance,
  COUNT(DISTINCT t.id) as total_transactions,
  SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END) as total_income,
  SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END) as total_expense
FROM companies c
LEFT JOIN bank_accounts ba ON c.id = ba.company_id
LEFT JOIN transactions t ON c.id = t.company_id
WHERE c.is_active = true
GROUP BY c.id, c.user_id, c.razao_social, c.nome_fantasia, c.cnpj;

-- View para dashboard consolidado (todas as empresas)
CREATE OR REPLACE VIEW v_user_consolidated_dashboard AS
SELECT 
  up.id as user_id,
  up.full_name,
  up.account_type_id,
  
  -- Totais PF
  (SELECT COUNT(*) FROM bank_accounts WHERE user_id = up.id AND company_id IS NULL) as pf_accounts,
  (SELECT COALESCE(SUM(current_balance), 0) FROM bank_accounts WHERE user_id = up.id AND company_id IS NULL) as pf_balance,
  
  -- Totais PJ
  (SELECT COUNT(*) FROM companies WHERE user_id = up.id AND is_active = true) as total_companies,
  (SELECT COUNT(*) FROM bank_accounts ba 
   INNER JOIN companies c ON ba.company_id = c.id 
   WHERE c.user_id = up.id AND c.is_active = true) as pj_accounts,
  (SELECT COALESCE(SUM(ba.current_balance), 0) FROM bank_accounts ba 
   INNER JOIN companies c ON ba.company_id = c.id 
   WHERE c.user_id = up.id AND c.is_active = true) as pj_balance,
  
  -- Total geral
  (SELECT COALESCE(SUM(current_balance), 0) FROM bank_accounts WHERE user_id = up.id) as total_balance
FROM user_profiles up;
```

---

### 4. Índices para Performance

```sql
-- Índices para melhorar performance de queries
CREATE INDEX idx_companies_user_id ON companies(user_id);
CREATE INDEX idx_companies_cnpj ON companies(cnpj);
CREATE INDEX idx_companies_active ON companies(is_active) WHERE is_active = true;

CREATE INDEX idx_bank_accounts_user_id ON bank_accounts(user_id);
CREATE INDEX idx_bank_accounts_company_id ON bank_accounts(company_id);
CREATE INDEX idx_bank_accounts_active ON bank_accounts(is_active) WHERE is_active = true;

CREATE INDEX idx_transactions_user_id ON transactions(user_id);
CREATE INDEX idx_transactions_company_id ON transactions(company_id);
CREATE INDEX idx_transactions_account_id ON transactions(account_id);
CREATE INDEX idx_transactions_date ON transactions(date DESC);
CREATE INDEX idx_transactions_type ON transactions(type);
CREATE INDEX idx_transactions_status ON transactions(status);
CREATE INDEX idx_transactions_category_id ON transactions(category_id);

CREATE INDEX idx_categories_user_id ON categories(user_id);
CREATE INDEX idx_categories_company_id ON categories(company_id);
CREATE INDEX idx_categories_type ON categories(type);

CREATE INDEX idx_audit_logs_user_id ON audit_logs(user_id);
CREATE INDEX idx_audit_logs_company_id ON audit_logs(company_id);
CREATE INDEX idx_audit_logs_created_at ON audit_logs(created_at DESC);

-- Índices compostos para queries comuns
CREATE INDEX idx_transactions_user_company_date 
  ON transactions(user_id, company_id, date DESC);

CREATE INDEX idx_bank_accounts_user_company_active 
  ON bank_accounts(user_id, company_id) WHERE is_active = true;

-- Índices para buscas full-text
CREATE INDEX idx_companies_search 
  ON companies USING gin(to_tsvector('portuguese', 
    coalesce(razao_social, '') || ' ' || 
    coalesce(nome_fantasia, '') || ' ' || 
    coalesce(cnpj, '')));

CREATE INDEX idx_transactions_search 
  ON transactions USING gin(to_tsvector('portuguese', 
    coalesce(description, '') || ' ' || 
    coalesce(notes, '')));
```

---

## 🎨 Frontend - Componentes e Páginas

### 1. Context API para Gestão de Estado

```typescript
// src/context/CompanyContext.tsx
import { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import { supabase } from '@/db/supabase';

interface Company {
  id: string;
  cnpj: string;
  razao_social: string;
  nome_fantasia?: string;
  porte?: string;
  regime_tributario?: string;
  is_active: boolean;
  is_default: boolean;
  settings?: Record<string, any>;
}

interface CompanyContextType {
  companies: Company[];
  selectedCompany: Company | null;
  isLoadingCompanies: boolean;
  selectCompany: (companyId: string) => void;
  refreshCompanies: () => Promise<void>;
  createCompany: (data: Partial<Company>) => Promise<Company>;
  updateCompany: (id: string, data: Partial<Company>) => Promise<void>;
  deleteCompany: (id: string) => Promise<void>;
}

const CompanyContext = createContext<CompanyContextType | undefined>(undefined);

export function CompanyProvider({ children }: { children: ReactNode }) {
  const [companies, setCompanies] = useState<Company[]>([]);
  const [selectedCompany, setSelectedCompany] = useState<Company | null>(null);
  const [isLoadingCompanies, setIsLoadingCompanies] = useState(true);

  // Carregar empresas do usuário
  const loadCompanies = async () => {
    setIsLoadingCompanies(true);
    try {
      const { data, error } = await supabase
        .from('companies')
        .select('*')
        .eq('is_active', true)
        .order('is_default', { ascending: false })
        .order('created_at', { ascending: false });

      if (error) throw error;

      setCompanies(data || []);

      // Selecionar empresa padrão ou primeira
      const defaultCompany = data?.find(c => c.is_default) || data?.[0];
      if (defaultCompany) {
        setSelectedCompany(defaultCompany);
      }
    } catch (error) {
      console.error('Erro ao carregar empresas:', error);
    } finally {
      setIsLoadingCompanies(false);
    }
  };

  useEffect(() => {
    loadCompanies();
  }, []);

  const selectCompany = (companyId: string) => {
    const company = companies.find(c => c.id === companyId);
    if (company) {
      setSelectedCompany(company);
      // Salvar preferência no localStorage
      localStorage.setItem('selectedCompanyId', companyId);
    }
  };

  const createCompany = async (data: Partial<Company>): Promise<Company> => {
    const { data: newCompany, error } = await supabase
      .from('companies')
      .insert([data])
      .select()
      .single();

    if (error) throw error;

    await loadCompanies();
    return newCompany;
  };

  const updateCompany = async (id: string, data: Partial<Company>) => {
    const { error } = await supabase
      .from('companies')
      .update(data)
      .eq('id', id);

    if (error) throw error;

    await loadCompanies();
  };

  const deleteCompany = async (id: string) => {
    const { error } = await supabase
      .from('companies')
      .update({ is_active: false })
      .eq('id', id);

    if (error) throw error;

    await loadCompanies();
  };

  return (
    <CompanyContext.Provider
      value={{
        companies,
        selectedCompany,
        isLoadingCompanies,
        selectCompany,
        refreshCompanies: loadCompanies,
        createCompany,
        updateCompany,
        deleteCompany,
      }}
    >
      {children}
    </CompanyContext.Provider>
  );
}

export const useCompany = () => {
  const context = useContext(CompanyContext);
  if (!context) {
    throw new Error('useCompany deve ser usado dentro de CompanyProvider');
  }
  return context;
};
```

### 2. Hook Customizado para Filtros Multi-Empresa

```typescript
// src/hooks/useMultiCompanyFilter.ts
import { useState, useMemo } from 'react';
import { useCompany } from '@/context/CompanyContext';

interface FilterOptions {
  includeAllCompanies?: boolean;
  selectedCompanyIds?: string[];
  dateRange?: { start: Date; end: Date };
  categories?: string[];
}

export function useMultiCompanyFilter() {
  const { companies, selectedCompany } = useCompany();
  const [filterOptions, setFilterOptions] = useState<FilterOptions>({
    includeAllCompanies: false,
    selectedCompanyIds: selectedCompany ? [selectedCompany.id] : [],
  });

  const activeCompanyIds = useMemo(() => {
    if (filterOptions.includeAllCompanies) {
      return companies.map(c => c.id);
    }
    return filterOptions.selectedCompanyIds || [];
  }, [filterOptions, companies]);

  const buildSupabaseFilter = (query: any) => {
    if (activeCompanyIds.length > 0) {
      query = query.in('company_id', activeCompanyIds);
    }
    
    if (filterOptions.dateRange) {
      query = query
        .gte('date', filterOptions.dateRange.start.toISOString())
        .lte('date', filterOptions.dateRange.end.toISOString());
    }
    
    if (filterOptions.categories && filterOptions.categories.length > 0) {
      query = query.in('category_id', filterOptions.categories);
    }
    
    return query;
  };

  return {
    filterOptions,
    setFilterOptions,
    activeCompanyIds,
    buildSupabaseFilter,
  };
}
```

### 3. Componente de Seletor de Empresas

```typescript
// src/components/company/CompanySelector.tsx
import { useState } from 'react';
import { useCompany } from '@/context/CompanyContext';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { Plus, Building2 } from 'lucide-react';
import { CompanyDialog } from './CompanyDialog';

export function CompanySelector() {
  const { companies, selectedCompany, selectCompany } = useCompany();
  const [showDialog, setShowDialog] = useState(false);

  return (
    <div className="flex items-center gap-2">
      <Select
        value={selectedCompany?.id}
        onValueChange={selectCompany}
      >
        <SelectTrigger className="w-[300px]">
          <Building2 className="mr-2 h-4 w-4" />
          <SelectValue placeholder="Selecione uma empresa" />
        </SelectTrigger>
        <SelectContent>
          {companies.map((company) => (
            <SelectItem key={company.id} value={company.id}>
              <div className="flex flex-col">
                <span className="font-medium">
                  {company.nome_fantasia || company.razao_social}
                </span>
                <span className="text-xs text-muted-foreground">
                  {company.cnpj}
                </span>
              </div>
            </SelectItem>
          ))}
        </SelectContent>
      </Select>

      <Button
        variant="outline"
        size="icon"
        onClick={() => setShowDialog(true)}
      >
        <Plus className="h-4 w-4" />
      </Button>

      <CompanyDialog
        open={showDialog}
        onOpenChange={setShowDialog}
      />
    </div>
  );
}
```

### 4. Dashboard Consolidado Multi-Empresa

```typescript
// src/pages/dashboard/ConsolidatedDashboard.tsx
import { useEffect, useState } from 'react';
import { useCompany } from '@/context/CompanyContext';
import { supabase } from '@/db/supabase';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { 
  BarChart, 
  Bar, 
  XAxis, 
  YAxis, 
  CartesianGrid, 
  Tooltip, 
  Legend,
  ResponsiveContainer,
  PieChart,
  Pie,
  Cell
} from 'recharts';

interface CompanyMetrics {
  company_id: string;
  razao_social: string;
  total_balance: number;
  total_income: number;
  total_expense: number;
  profit_margin: number;
}

export function ConsolidatedDashboard() {
  const { companies } = useCompany();
  const [metrics, setMetrics] = useState<CompanyMetrics[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    loadMetrics();
  }, [companies]);

  const loadMetrics = async () => {
    setIsLoading(true);
    try {
      const { data, error } = await supabase
        .from('v_company_summary')
        .select('*');

      if (error) throw error;

      const metricsData: CompanyMetrics[] = data.map(d => ({
        company_id: d.company_id,
        razao_social: d.razao_social,
        total_balance: d.total_balance || 0,
        total_income: d.total_income || 0,
        total_expense: d.total_expense || 0,
        profit_margin: d.total_income > 0 
          ? ((d.total_income - d.total_expense) / d.total_income) * 100 
          : 0,
      }));

      setMetrics(metricsData);
    } catch (error) {
      console.error('Erro ao carregar métricas:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const totalBalance = metrics.reduce((sum, m) => sum + m.total_balance, 0);
  const totalIncome = metrics.reduce((sum, m) => sum + m.total_income, 0);
  const totalExpense = metrics.reduce((sum, m) => sum + m.total_expense, 0);

  const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8'];

  return (
    <div className="space-y-6">
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">
              Saldo Total (Todas as Empresas)
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {totalBalance.toLocaleString('pt-BR', { 
                style: 'currency', 
                currency: 'BRL' 
              })}
            </div>
            <p className="text-xs text-muted-foreground">
              {companies.length} {companies.length === 1 ? 'empresa' : 'empresas'}
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">
              Receitas Totais
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-green-600">
              {totalIncome.toLocaleString('pt-BR', { 
                style: 'currency', 
                currency: 'BRL' 
              })}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">
              Despesas Totais
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-red-600">
              {totalExpense.toLocaleString('pt-BR', { 
                style: 'currency', 
                currency: 'BRL' 
              })}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">
              Lucro Líquido
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className={`text-2xl font-bold ${
              (totalIncome - totalExpense) >= 0 ? 'text-green-600' : 'text-red-600'
            }`}>
              {(totalIncome - totalExpense).toLocaleString('pt-BR', { 
                style: 'currency', 
                currency: 'BRL' 
              })}
            </div>
          </CardContent>
        </Card>
      </div>

      <Tabs defaultValue="comparison" className="space-y-4">
        <TabsList>
          <TabsTrigger value="comparison">Comparação</TabsTrigger>
          <TabsTrigger value="distribution">Distribuição</TabsTrigger>
          <TabsTrigger value="performance">Performance</TabsTrigger>
        </TabsList>

        <TabsContent value="comparison" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Comparação de Receitas e Despesas por Empresa</CardTitle>
            </CardHeader>
            <CardContent>
              <ResponsiveContainer width="100%" height={400}>
                <BarChart data={metrics}>
                  <CartesianGrid strokeDasharray="3 3" />
                  <XAxis dataKey="razao_social" />
                  <YAxis />
                  <Tooltip 
                    formatter={(value: number) => 
                      value.toLocaleString('pt-BR', { 
                        style: 'currency', 
                        currency: 'BRL' 
                      })
                    }
                  />
                  <Legend />
                  <Bar dataKey="total_income" name="Receitas" fill="#10b981" />
                  <Bar dataKey="total_expense" name="Despesas" fill="#ef4444" />
                </BarChart>
              </ResponsiveContainer>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="distribution" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Distribuição de Saldo por Empresa</CardTitle>
            </CardHeader>
            <CardContent>
              <ResponsiveContainer width="100%" height={400}>
                <PieChart>
                  <Pie
                    data={metrics}
                    dataKey="total_balance"
                    nameKey="razao_social"
                    cx="50%"
                    cy="50%"
                    outerRadius={150}
                    label={(entry) => 
                      entry.total_balance.toLocaleString('pt-BR', { 
                        style: 'currency', 
                        currency: 'BRL' 
                      })
                    }
                  >
                    {metrics.map((entry, index) => (
                      <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                    ))}
                  </Pie>
                  <Tooltip 
                    formatter={(value: number) => 
                      value.toLocaleString('pt-BR', { 
                        style: 'currency', 
                        currency: 'BRL' 
                      })
                    }
                  />
                  <Legend />
                </PieChart>
              </ResponsiveContainer>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="performance" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Margem de Lucro por Empresa</CardTitle>
            </CardHeader>
            <CardContent>
              <ResponsiveContainer width="100%" height={400}>
                <BarChart data={metrics}>
                  <CartesianGrid strokeDasharray="3 3" />
                  <XAxis dataKey="razao_social" />
                  <YAxis />
                  <Tooltip formatter={(value: number) => `${value.toFixed(2)}%`} />
                  <Legend />
                  <Bar 
                    dataKey="profit_margin" 
                    name="Margem de Lucro (%)" 
                    fill="#3b82f6"
                  />
                </BarChart>
              </ResponsiveContainer>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}
```

### 5. Página de Gestão de Empresas

```typescript
// src/pages/companies/CompaniesPage.tsx
import { useState } from 'react';
import { useCompany } from '@/context/CompanyContext';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Plus, Edit, Trash2, Building2, CheckCircle } from 'lucide-react';
import { CompanyDialog } from '@/components/company/CompanyDialog';
import { Badge } from '@/components/ui/badge';

export function CompaniesPage() {
  const { companies, deleteCompany, updateCompany } = useCompany();
  const [selectedCompanyId, setSelectedCompanyId] = useState<string | null>(null);
  const [showDialog, setShowDialog] = useState(false);

  const handleSetDefault = async (companyId: string) => {
    await updateCompany(companyId, { is_default: true });
  };

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold">Minhas Empresas</h1>
        <Button onClick={() => {
          setSelectedCompanyId(null);
          setShowDialog(true);
        }}>
          <Plus className="mr-2 h-4 w-4" />
          Nova Empresa
        </Button>
      </div>

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        {companies.map((company) => (
          <Card key={company.id}>
            <CardHeader>
              <div className="flex items-start justify-between">
                <div className="flex items-center gap-2">
                  <Building2 className="h-5 w-5" />
                  <CardTitle className="text-lg">
                    {company.nome_fantasia || company.razao_social}
                  </CardTitle>
                </div>
                {company.is_default && (
                  <Badge variant="secondary">
                    <CheckCircle className="mr-1 h-3 w-3" />
                    Padrão
                  </Badge>
                )}
              </div>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                <div>
                  <p className="text-sm text-muted-foreground">Razão Social</p>
                  <p className="font-medium">{company.razao_social}</p>
                </div>

                <div>
                  <p className="text-sm text-muted-foreground">CNPJ</p>
                  <p className="font-mono text-sm">{company.cnpj}</p>
                </div>

                {company.porte && (
                  <div>
                    <p className="text-sm text-muted-foreground">Porte</p>
                    <Badge variant="outline">{company.porte}</Badge>
                  </div>
                )}

                {company.regime_tributario && (
                  <div>
                    <p className="text-sm text-muted-foreground">Regime Tributário</p>
                    <Badge variant="outline">{company.regime_tributario}</Badge>
                  </div>
                )}

                <div className="flex gap-2 pt-4">
                  {!company.is_default && (
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => handleSetDefault(company.id)}
                    >
                      Tornar Padrão
                    </Button>
                  )}

                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => {
                      setSelectedCompanyId(company.id);
                      setShowDialog(true);
                    }}
                  >
                    <Edit className="h-4 w-4" />
                  </Button>

                  <Button
                    variant="destructive"
                    size="sm"
                    onClick={() => {
                      if (confirm('Deseja realmente excluir esta empresa?')) {
                        deleteCompany(company.id);
                      }
                    }}
                  >
                    <Trash2 className="h-4 w-4" />
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      <CompanyDialog
        open={showDialog}
        onOpenChange={setShowDialog}
        companyId={selectedCompanyId || undefined}
      />
    </div>
  );
}
```

---

## 📱 Migração de Dados

### Script de Migração de PF para Sistema Unificado

```sql
-- Script de migração para adicionar suporte a PJ mantendo dados PF

-- 1. Inserir tipos de conta
INSERT INTO account_types (code, name, description) VALUES
  ('PF', 'Pessoa Física', 'Conta para pessoa física'),
  ('PJ', 'Pessoa Jurídica', 'Conta para pessoa jurídica');

-- 2. Atualizar user_profiles existentes para PF
UPDATE user_profiles
SET account_type_id = (SELECT id FROM account_types WHERE code = 'PF')
WHERE account_type_id IS NULL;

-- 3. Atualizar bank_accounts existentes (manter como PF, company_id = NULL)
-- Nenhuma ação necessária - company_id já é NULL

-- 4. Atualizar categories existentes (manter como PF)
-- Nenhuma ação necessária - company_id já é NULL

-- 5. Atualizar transactions existentes (manter como PF)
-- Nenhuma ação necessária - company_id já é NULL

-- 6. Criar categorias padrão para PJ
INSERT INTO categories (user_id, company_id, name, type, color, icon, is_system)
SELECT 
  up.id,
  NULL, -- Will be filled when company is created
  c.name,
  c.type,
  c.color,
  c.icon,
  true
FROM user_profiles up
CROSS JOIN (
  VALUES 
    ('Vendas', 'income', '#10b981', 'trending-up'),
    ('Prestação de Serviços', 'income', '#059669', 'briefcase'),
    ('Recebimento de Clientes', 'income', '#34d399', 'users'),
    ('Folha de Pagamento', 'expense', '#ef4444', 'user-check'),
    ('Impostos', 'expense', '#dc2626', 'receipt'),
    ('Aluguel', 'expense', '#f87171', 'home'),
    ('Fornecedores', 'expense', '#fb923c', 'truck'),
    ('Marketing', 'expense', '#fbbf24', 'megaphone'),
    ('Tecnologia', 'expense', '#60a5fa', 'laptop'),
    ('Despesas Operacionais', 'expense', '#a78bfa', 'settings')
) AS c(name, type, color, icon)
WHERE up.account_type_id = (SELECT id FROM account_types WHERE code = 'PJ');
```

---

## 🔒 Segurança e Validações

### 1. Validações Frontend

```typescript
// src/utils/validators.ts
export const validateCNPJ = (cnpj: string): boolean => {
  // Remover caracteres não numéricos
  const cleanCNPJ = cnpj.replace(/[^\d]/g, '');

  // Verificar se tem 14 dígitos
  if (cleanCNPJ.length !== 14) return false;

  // Verificar CNPJs conhecidos como inválidos
  if (/^(\d)\1{13}$/.test(cleanCNPJ)) return false;

  // Validar dígitos verificadores
  let sum = 0;
  let pos = 5;

  for (let i = 0; i < 12; i++) {
    sum += parseInt(cleanCNPJ.charAt(i)) * pos--;
    if (pos < 2) pos = 9;
  }

  let result = sum % 11 < 2 ? 0 : 11 - (sum % 11);
  if (result !== parseInt(cleanCNPJ.charAt(12))) return false;

  sum = 0;
  pos = 6;

  for (let i = 0; i < 13; i++) {
    sum += parseInt(cleanCNPJ.charAt(i)) * pos--;
    if (pos < 2) pos = 9;
  }

  result = sum % 11 < 2 ? 0 : 11 - (sum % 11);
  return result === parseInt(cleanCNPJ.charAt(13));
};

export const validateCPF = (cpf: string): boolean => {
  const cleanCPF = cpf.replace(/[^\d]/g, '');

  if (cleanCPF.length !== 11) return false;
  if (/^(\d)\1{10}$/.test(cleanCPF)) return false;

  let sum = 0;
  for (let i = 0; i < 9; i++) {
    sum += parseInt(cleanCPF.charAt(i)) * (10 - i);
  }

  let result = (sum * 10) % 11;
  if (result === 10) result = 0;
  if (result !== parseInt(cleanCPF.charAt(9))) return false;

  sum = 0;
  for (let i = 0; i < 10; i++) {
    sum += parseInt(cleanCPF.charAt(i)) * (11 - i);
  }

  result = (sum * 10) % 11;
  if (result === 10) result = 0;
  return result === parseInt(cleanCPF.charAt(10));
};

export const formatCNPJ = (value: string): string => {
  const clean = value.replace(/[^\d]/g, '');
  return clean
    .replace(/^(\d{2})(\d)/, '$1.$2')
    .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
    .replace(/\.(\d{3})(\d)/, '.$1/$2')
    .replace(/(\d{4})(\d)/, '$1-$2')
    .substring(0, 18);
};

export const formatCPF = (value: string): string => {
  const clean = value.replace(/[^\d]/g, '');
  return clean
    .replace(/(\d{3})(\d)/, '$1.$2')
    .replace(/(\d{3})(\d)/, '$1.$2')
    .replace(/(\d{3})(\d{1,2})$/, '$1-$2')
    .substring(0, 14);
};
```

### 2. Middleware de Autorização

```typescript
// src/middleware/authorization.ts
import { supabase } from '@/db/supabase';

export async function checkCompanyAccess(companyId: string): Promise<boolean> {
  const { data: { user } } = await supabase.auth.getUser();
  
  if (!user) return false;

  const { data: company } = await supabase
    .from('companies')
    .select('user_id')
    .eq('id', companyId)
    .single();

  return company?.user_id === user.id;
}

export async function getUserAccountType(): Promise<'PF' | 'PJ' | null> {
  const { data: { user } } = await supabase.auth.getUser();
  
  if (!user) return null;

  const { data: profile } = await supabase
    .from('user_profiles')
    .select('account_type_id, account_types(code)')
    .eq('id', user.id)
    .single();

  return profile?.account_types?.code as 'PF' | 'PJ' || null;
}
```

---

## 📊 Relatórios Multi-Empresa

### Componente de Relatório Consolidado

```typescript
// src/components/reports/MultiCompanyReport.tsx
import { useState, useEffect } from 'react';
import { useCompany } from '@/context/CompanyContext';
import { supabase } from '@/db/supabase';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Download, FileText } from 'lucide-react';
import { DateRangePicker } from '@/components/ui/date-range-picker';

interface ReportData {
  company_id: string;
  company_name: string;
  total_income: number;
  total_expense: number;
  net_profit: number;
  transaction_count: number;
}

export function MultiCompanyReport() {
  const { companies } = useCompany();
  const [selectedCompanies, setSelectedCompanies] = useState<string[]>([]);
  const [dateRange, setDateRange] = useState<{ from: Date; to: Date }>({
    from: new Date(new Date().getFullYear(), 0, 1),
    to: new Date(),
  });
  const [reportData, setReportData] = useState<ReportData[]>([]);
  const [isLoading, setIsLoading] = useState(false);

  const handleCompanyToggle = (companyId: string) => {
    setSelectedCompanies(prev =>
      prev.includes(companyId)
        ? prev.filter(id => id !== companyId)
        : [...prev, companyId]
    );
  };

  const generateReport = async () => {
    setIsLoading(true);
    try {
      const { data, error } = await supabase
        .from('transactions')
        .select(`
          company_id,
          companies(razao_social, nome_fantasia),
          amount,
          type
        `)
        .in('company_id', selectedCompanies)
        .gte('date', dateRange.from.toISOString())
        .lte('date', dateRange.to.toISOString());

      if (error) throw error;

      // Agregar dados por empresa
      const aggregated = data.reduce((acc, transaction) => {
        const companyId = transaction.company_id;
        if (!acc[companyId]) {
          acc[companyId] = {
            company_id: companyId,
            company_name: transaction.companies?.nome_fantasia || 
                         transaction.companies?.razao_social || '',
            total_income: 0,
            total_expense: 0,
            net_profit: 0,
            transaction_count: 0,
          };
        }

        acc[companyId].transaction_count++;
        
        if (transaction.type === 'income') {
          acc[companyId].total_income += transaction.amount;
        } else if (transaction.type === 'expense') {
          acc[companyId].total_expense += transaction.amount;
        }

        acc[companyId].net_profit = 
          acc[companyId].total_income - acc[companyId].total_expense;

        return acc;
      }, {} as Record<string, ReportData>);

      setReportData(Object.values(aggregated));
    } catch (error) {
      console.error('Erro ao gerar relatório:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const exportToPDF = () => {
    // Implementar exportação para PDF
    console.log('Exportar para PDF');
  };

  const exportToExcel = () => {
    // Implementar exportação para Excel
    console.log('Exportar para Excel');
  };

  return (
    <div className="space-y-6">
      <Card>
        <CardHeader>
          <CardTitle>Relatório Consolidado Multi-Empresa</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div>
            <Label className="mb-2">Selecione as Empresas:</Label>
            <div className="grid grid-cols-2 gap-4">
              {companies.map(company => (
                <div key={company.id} className="flex items-center space-x-2">
                  <Checkbox
                    id={company.id}
                    checked={selectedCompanies.includes(company.id)}
                    onCheckedChange={() => handleCompanyToggle(company.id)}
                  />
                  <Label htmlFor={company.id} className="cursor-pointer">
                    {company.nome_fantasia || company.razao_social}
                  </Label>
                </div>
              ))}
            </div>
          </div>

          <div>
            <Label className="mb-2">Período:</Label>
            <DateRangePicker
              value={dateRange}
              onChange={setDateRange}
            />
          </div>

          <div className="flex gap-2">
            <Button
              onClick={generateReport}
              disabled={selectedCompanies.length === 0 || isLoading}
            >
              <FileText className="mr-2 h-4 w-4" />
              Gerar Relatório
            </Button>

            {reportData.length > 0 && (
              <>
                <Button variant="outline" onClick={exportToPDF}>
                  <Download className="mr-2 h-4 w-4" />
                  Exportar PDF
                </Button>
                <Button variant="outline" onClick={exportToExcel}>
                  <Download className="mr-2 h-4 w-4" />
                  Exportar Excel
                </Button>
              </>
            )}
          </div>
        </CardContent>
      </Card>

      {reportData.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle>Resultados</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead>
                  <tr className="border-b">
                    <th className="text-left p-2">Empresa</th>
                    <th className="text-right p-2">Receitas</th>
                    <th className="text-right p-2">Despesas</th>
                    <th className="text-right p-2">Lucro Líquido</th>
                    <th className="text-right p-2">Transações</th>
                  </tr>
                </thead>
                <tbody>
                  {reportData.map(row => (
                    <tr key={row.company_id} className="border-b">
                      <td className="p-2">{row.company_name}</td>
                      <td className="text-right p-2 text-green-600">
                        {row.total_income.toLocaleString('pt-BR', {
                          style: 'currency',
                          currency: 'BRL',
                        })}
                      </td>
                      <td className="text-right p-2 text-red-600">
                        {row.total_expense.toLocaleString('pt-BR', {
                          style: 'currency',
                          currency: 'BRL',
                        })}
                      </td>
                      <td className={`text-right p-2 font-bold ${
                        row.net_profit >= 0 ? 'text-green-600' : 'text-red-600'
                      }`}>
                        {row.net_profit.toLocaleString('pt-BR', {
                          style: 'currency',
                          currency: 'BRL',
                        })}
                      </td>
                      <td className="text-right p-2">
                        {row.transaction_count}
                      </td>
                    </tr>
                  ))}
                  <tr className="font-bold bg-muted">
                    <td className="p-2">TOTAL</td>
                    <td className="text-right p-2 text-green-600">
                      {reportData.reduce((sum, r) => sum + r.total_income, 0)
                        .toLocaleString('pt-BR', {
                          style: 'currency',
                          currency: 'BRL',
                        })}
                    </td>
                    <td className="text-right p-2 text-red-600">
                      {reportData.reduce((sum, r) => sum + r.total_expense, 0)
                        .toLocaleString('pt-BR', {
                          style: 'currency',
                          currency: 'BRL',
                        })}
                    </td>
                    <td className="text-right p-2">
                      {reportData.reduce((sum, r) => sum + r.net_profit, 0)
                        .toLocaleString('pt-BR', {
                          style: 'currency',
                          currency: 'BRL',
                        })}
                    </td>
                    <td className="text-right p-2">
                      {reportData.reduce((sum, r) => sum + r.transaction_count, 0)}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
```

---

## 🚀 Estratégia de Implementação

### Fase 1: Preparação e Modelagem (Semana 1-2)
1. ✅ Criar nova branch no Git: `feature/pj-accounts`
2. ✅ Implementar migrations do banco de dados
3. ✅ Configurar RLS policies
4. ✅ Criar functions e triggers
5. ✅ Testar migrations em ambiente de desenvolvimento

### Fase 2: Backend e API (Semana 3-4)
1. ✅ Criar services para gestão de empresas
2. ✅ Implementar validações de CNPJ
3. ✅ Configurar hooks customizados
4. ✅ Implementar Context API para empresas
5. ✅ Criar testes unitários

### Fase 3: Frontend - Componentes Base (Semana 5-6)
1. ✅ Criar componente CompanySelector
2. ✅ Implementar CompanyDialog para CRUD
3. ✅ Desenvolver página de gestão de empresas
4. ✅ Criar filtros multi-empresa
5. ✅ Adaptar componentes existentes

### Fase 4: Dashboards e Relatórios (Semana 7-8)
1. ✅ Implementar dashboard consolidado
2. ✅ Criar visualizações comparativas
3. ✅ Desenvolver sistema de relatórios multi-empresa
4. ✅ Implementar exportação de dados
5. ✅ Criar gráficos e métricas

### Fase 5: Migração e Testes (Semana 9-10)
1. ✅ Executar script de migração de dados PF
2. ✅ Testar isolamento de dados
3. ✅ Validar performance de queries
4. ✅ Realizar testes de segurança
5. ✅ Corrigir bugs identificados

### Fase 6: Documentação e Deploy (Semana 11-12)
1. ✅ Criar documentação técnica
2. ✅ Elaborar guia do usuário
3. ✅ Preparar release notes
4. ✅ Deploy em staging
5. ✅ Deploy em produção
6. ✅ Monitoramento pós-deploy

---

## 📈 Métricas de Sucesso

### KPIs Técnicos
- ✅ Tempo de carregamento de dashboard < 2s
- ✅ Queries com RLS < 500ms
- ✅ 100% de cobertura de testes em funções críticas
- ✅ Zero vazamento de dados entre contas
- ✅ Disponibilidade 99.9%

### KPIs de Negócio
- ✅ Taxa de adoção de contas PJ > 20% no primeiro mês
- ✅ Redução de 50% no tempo de gestão multi-empresa
- ✅ Satisfação do usuário > 4.5/5
- ✅ Suporte a até 50 empresas por usuário
- ✅ Crescimento de 30% em usuários ativos

---

## 🔐 Considerações de Segurança

1. **Isolamento de Dados**
   - RLS garante que cada usuário veja apenas suas próprias empresas
   - Validações em múltiplas camadas (DB, API, Frontend)
   - Auditoria completa de todas as operações

2. **Validações**
   - CNPJ validado tanto no frontend quanto no backend
   - Constraints de banco garantem integridade
   - Rate limiting para prevenir abuso

3. **Auditoria**
   - Log de todas as operações em empresas
   - Rastreabilidade completa de mudanças
   - Histórico de acessos

4. **Backup e Recuperação**
   - Backups automáticos diários
   - Point-in-time recovery
   - Testes regulares de restore

---

## 🎯 Próximos Passos

1. **Integração com Receita Federal**
   - Consulta automática de dados via CNPJ
   - Atualização de informações cadastrais
   - Validação de situação fiscal

2. **Notas Fiscais**
   - Importação de XML de NF-e
   - Categorização automática
   - Conciliação bancária

3. **Integrações Bancárias**
   - Open Banking
   - Sincronização automática de extratos
   - Previsão de fluxo de caixa

4. **BI Avançado**
   - Análises preditivas
   - Alertas inteligentes
   - Benchmarking setorial

5. **Mobile App**
   - App nativo iOS/Android
   - Notificações push
   - Acesso offline

---

## 📚 Recursos e Referências

### Documentação
- [Supabase RLS](https://supabase.com/docs/guides/auth/row-level-security)
- [PostgreSQL Multi-tenancy](https://www.postgresql.org/docs/current/ddl-rowsecurity.html)
- [React Context API](https://react.dev/reference/react/useContext)
- [shadcn/ui Components](https://ui.shadcn.com/)

### Ferramentas
- Supabase Studio para gerenciamento de BD
- Postman para testes de API
- React DevTools para debug
- PostgreSQL Explain para otimização de queries

---

## ✅ Checklist de Implementação

### Banco de Dados
- [ ] Criar tabelas account_types, companies
- [ ] Estender tabelas existentes com company_id
- [ ] Implementar RLS policies
- [ ] Criar functions e triggers
- [ ] Criar views consolidadas
- [ ] Adicionar índices de performance
- [ ] Testar validações de CNPJ/CPF

### Backend
- [ ] Criar services de empresas
- [ ] Implementar hooks customizados
- [ ] Configurar Context API
- [ ] Desenvolver middleware de autorização
- [ ] Criar utilitários de validação

### Frontend
- [ ] Componente CompanySelector
- [ ] Componente CompanyDialog
- [ ] Página de gestão de empresas
- [ ] Dashboard consolidado
- [ ] Sistema de filtros multi-empresa
- [ ] Relatórios consolidados
- [ ] Exportação de dados

### Testes
- [ ] Testes unitários de validações
- [ ] Testes de integração
- [ ] Testes de segurança (RLS)
- [ ] Testes de performance
- [ ] Testes E2E de fluxos principais

### Documentação
- [ ] README técnico
- [ ] Guia do usuário
- [ ] API documentation
- [ ] Migration guide
- [ ] Troubleshooting guide

### Deploy
- [ ] Configurar CI/CD
- [ ] Preparar scripts de migração
- [ ] Executar testes em staging
- [ ] Deploy em produção
- [ ] Monitoramento pós-deploy
- [ ] Rollback plan

---

**Última atualização:** 30 de Janeiro de 2026
**Versão:** 1.0
**Autor:** Planejamento OnliFin PJ
