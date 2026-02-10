-- Migration: Fix PF (Pessoa Física) data visibility after multi-company update
-- Description: Associates all legacy data (company_id = NULL) to user's default company
-- Issue: #10
-- Date: 2026-01-31

BEGIN;

-- Step 1: Associate legacy accounts to default company
DO $$
DECLARE
    user_record RECORD;
    default_company_id UUID;
    affected_accounts INTEGER;
    affected_transactions INTEGER;
    affected_cards INTEGER;
    affected_bills_pay INTEGER;
    affected_bills_receive INTEGER;
BEGIN
    RAISE NOTICE '=== Starting PF Data Migration ===';
    
    -- For each user with NULL company_id data
    FOR user_record IN 
        SELECT DISTINCT user_id 
        FROM accounts 
        WHERE company_id IS NULL
        AND user_id IS NOT NULL
    LOOP
        -- Get user's default company
        SELECT id INTO default_company_id
        FROM companies
        WHERE user_id = user_record.user_id
        AND is_default = true
        LIMIT 1;

        -- If no default company, get any company from this user
        IF default_company_id IS NULL THEN
            SELECT id INTO default_company_id
            FROM companies
            WHERE user_id = user_record.user_id
            ORDER BY created_at ASC
            LIMIT 1;
        END IF;

        IF default_company_id IS NOT NULL THEN
            -- Migrate accounts
            UPDATE accounts 
            SET company_id = default_company_id,
                updated_at = NOW()
            WHERE user_id = user_record.user_id 
            AND company_id IS NULL;
            GET DIAGNOSTICS affected_accounts = ROW_COUNT;

            -- Migrate transactions
            UPDATE transactions 
            SET company_id = default_company_id,
                updated_at = NOW()
            WHERE user_id = user_record.user_id 
            AND company_id IS NULL;
            GET DIAGNOSTICS affected_transactions = ROW_COUNT;

            -- Migrate cards
            UPDATE cards 
            SET company_id = default_company_id,
                updated_at = NOW()
            WHERE user_id = user_record.user_id 
            AND company_id IS NULL;
            GET DIAGNOSTICS affected_cards = ROW_COUNT;

            -- Migrate bills_to_pay
            UPDATE bills_to_pay 
            SET company_id = default_company_id,
                updated_at = NOW()
            WHERE user_id = user_record.user_id 
            AND company_id IS NULL;
            GET DIAGNOSTICS affected_bills_pay = ROW_COUNT;

            -- Migrate bills_to_receive
            UPDATE bills_to_receive 
            SET company_id = default_company_id,
                updated_at = NOW()
            WHERE user_id = user_record.user_id 
            AND company_id IS NULL;
            GET DIAGNOSTICS affected_bills_receive = ROW_COUNT;

            RAISE NOTICE 'User %: Migrated % accounts, % transactions, % cards, % bills_pay, % bills_receive to company %', 
                user_record.user_id, 
                affected_accounts, 
                affected_transactions, 
                affected_cards,
                affected_bills_pay,
                affected_bills_receive,
                default_company_id;
        ELSE
            RAISE WARNING 'User % has no company - data will remain orphaned', user_record.user_id;
        END IF;
    END LOOP;
END $$;

-- Step 2: Verify migration results
DO $$
DECLARE
    orphan_accounts INTEGER;
    orphan_transactions INTEGER;
    orphan_cards INTEGER;
    total_accounts INTEGER;
    total_transactions INTEGER;
BEGIN
    SELECT COUNT(*) INTO orphan_accounts FROM accounts WHERE company_id IS NULL;
    SELECT COUNT(*) INTO orphan_transactions FROM transactions WHERE company_id IS NULL;
    SELECT COUNT(*) INTO orphan_cards FROM cards WHERE company_id IS NULL;
    SELECT COUNT(*) INTO total_accounts FROM accounts;
    SELECT COUNT(*) INTO total_transactions FROM transactions;

    RAISE NOTICE '=== Migration Results ===';
    RAISE NOTICE 'Total accounts: % (orphans: %)', total_accounts, orphan_accounts;
    RAISE NOTICE 'Total transactions: % (orphans: %)', total_transactions, orphan_transactions;
    RAISE NOTICE 'Total cards: (orphans: %)', orphan_cards;
    
    IF orphan_accounts > 0 OR orphan_transactions > 0 THEN
        RAISE WARNING '⚠️ Some data still has NULL company_id - manual review needed';
    ELSE
        RAISE NOTICE '✅ All legacy data successfully migrated to default companies!';
    END IF;
END $$;

COMMIT;

-- Add helpful comments
COMMENT ON COLUMN accounts.company_id IS 'ID da empresa associada. Dados legados foram migrados para a empresa padrão do usuário.';
COMMENT ON COLUMN transactions.company_id IS 'ID da empresa associada. Dados legados foram migrados para a empresa padrão do usuário.';
