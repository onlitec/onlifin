-- Migration: Add company_id to financial tables
-- Description: Adds company_id foreign key to all relevant tables for PJ support
-- Run this migration after 001_create_companies_table.sql

-- Add company_id to accounts table
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'accounts' AND column_name = 'company_id'
    ) THEN
        ALTER TABLE accounts ADD COLUMN company_id UUID REFERENCES companies(id) ON DELETE SET NULL;
        CREATE INDEX idx_accounts_company_id ON accounts(company_id);
        COMMENT ON COLUMN accounts.company_id IS 'ID da empresa associada (NULL = conta pessoal PF)';
    END IF;
END $$;

-- Add company_id to transactions table
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'transactions' AND column_name = 'company_id'
    ) THEN
        ALTER TABLE transactions ADD COLUMN company_id UUID REFERENCES companies(id) ON DELETE SET NULL;
        CREATE INDEX idx_transactions_company_id ON transactions(company_id);
        COMMENT ON COLUMN transactions.company_id IS 'ID da empresa associada (NULL = transação pessoal PF)';
    END IF;
END $$;

-- Add company_id to cards table
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'cards' AND column_name = 'company_id'
    ) THEN
        ALTER TABLE cards ADD COLUMN company_id UUID REFERENCES companies(id) ON DELETE SET NULL;
        CREATE INDEX idx_cards_company_id ON cards(company_id);
        COMMENT ON COLUMN cards.company_id IS 'ID da empresa associada (NULL = cartão pessoal PF)';
    END IF;
END $$;

-- Add company_id to categories table
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'categories' AND column_name = 'company_id'
    ) THEN
        ALTER TABLE categories ADD COLUMN company_id UUID REFERENCES companies(id) ON DELETE SET NULL;
        CREATE INDEX idx_categories_company_id ON categories(company_id);
        COMMENT ON COLUMN categories.company_id IS 'ID da empresa associada (NULL = categoria pessoal PF ou global)';
    END IF;
END $$;

-- Add company_id to bills_to_pay table
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'bills_to_pay' AND column_name = 'company_id'
    ) THEN
        ALTER TABLE bills_to_pay ADD COLUMN company_id UUID REFERENCES companies(id) ON DELETE SET NULL;
        CREATE INDEX idx_bills_to_pay_company_id ON bills_to_pay(company_id);
        COMMENT ON COLUMN bills_to_pay.company_id IS 'ID da empresa associada (NULL = conta a pagar pessoal PF)';
    END IF;
END $$;

-- Add company_id to bills_to_receive table
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'bills_to_receive' AND column_name = 'company_id'
    ) THEN
        ALTER TABLE bills_to_receive ADD COLUMN company_id UUID REFERENCES companies(id) ON DELETE SET NULL;
        CREATE INDEX idx_bills_to_receive_company_id ON bills_to_receive(company_id);
        COMMENT ON COLUMN bills_to_receive.company_id IS 'ID da empresa associada (NULL = conta a receber pessoal PF)';
    END IF;
END $$;

-- Update RLS policies for multi-tenant support
-- The user should see records where:
-- 1. They own the record directly (user_id = auth.uid())
-- 2. They own a company that owns the record

-- Accounts policy
DROP POLICY IF EXISTS "accounts_multi_tenant_policy" ON accounts;
CREATE POLICY "accounts_multi_tenant_policy" ON accounts
    FOR ALL
    USING (
        user_id = auth.uid() OR
        company_id IN (SELECT id FROM companies WHERE user_id = auth.uid())
    )
    WITH CHECK (
        user_id = auth.uid() OR
        company_id IN (SELECT id FROM companies WHERE user_id = auth.uid())
    );

-- Transactions policy  
DROP POLICY IF EXISTS "transactions_multi_tenant_policy" ON transactions;
CREATE POLICY "transactions_multi_tenant_policy" ON transactions
    FOR ALL
    USING (
        user_id = auth.uid() OR
        company_id IN (SELECT id FROM companies WHERE user_id = auth.uid())
    )
    WITH CHECK (
        user_id = auth.uid() OR
        company_id IN (SELECT id FROM companies WHERE user_id = auth.uid())
    );

-- Cards policy
DROP POLICY IF EXISTS "cards_multi_tenant_policy" ON cards;
CREATE POLICY "cards_multi_tenant_policy" ON cards
    FOR ALL
    USING (
        user_id = auth.uid() OR
        company_id IN (SELECT id FROM companies WHERE user_id = auth.uid())
    )
    WITH CHECK (
        user_id = auth.uid() OR
        company_id IN (SELECT id FROM companies WHERE user_id = auth.uid())
    );

-- Bills to pay policy
DROP POLICY IF EXISTS "bills_to_pay_multi_tenant_policy" ON bills_to_pay;
CREATE POLICY "bills_to_pay_multi_tenant_policy" ON bills_to_pay
    FOR ALL
    USING (
        user_id = auth.uid() OR
        company_id IN (SELECT id FROM companies WHERE user_id = auth.uid())
    )
    WITH CHECK (
        user_id = auth.uid() OR
        company_id IN (SELECT id FROM companies WHERE user_id = auth.uid())
    );

-- Bills to receive policy
DROP POLICY IF EXISTS "bills_to_receive_multi_tenant_policy" ON bills_to_receive;
CREATE POLICY "bills_to_receive_multi_tenant_policy" ON bills_to_receive
    FOR ALL
    USING (
        user_id = auth.uid() OR
        company_id IN (SELECT id FROM companies WHERE user_id = auth.uid())
    )
    WITH CHECK (
        user_id = auth.uid() OR
        company_id IN (SELECT id FROM companies WHERE user_id = auth.uid())
    );

COMMENT ON TABLE companies IS 'Tabela de empresas (PJ) dos usuários';
