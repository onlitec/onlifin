-- ===========================================
-- 09 - Add icon fields to accounts and cards
-- ===========================================
-- This migration adds icon and brand fields that were missing
-- Safe to run multiple times (uses IF NOT EXISTS pattern)

-- Add icon and initial_balance columns to accounts table
DO $$
BEGIN
    -- Add icon column to accounts if not exists
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'accounts' AND column_name = 'icon'
    ) THEN
        ALTER TABLE accounts ADD COLUMN icon TEXT;
        COMMENT ON COLUMN accounts.icon IS 'Bank icon identifier (e.g., bb, itau, nubank)';
    END IF;

    -- Add initial_balance column to accounts if not exists  
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'accounts' AND column_name = 'initial_balance'
    ) THEN
        ALTER TABLE accounts ADD COLUMN initial_balance NUMERIC DEFAULT 0 NOT NULL;
        COMMENT ON COLUMN accounts.initial_balance IS 'Initial balance when account was created';
    END IF;
END;
$$;

-- Add icon and brand columns to cards table
DO $$
BEGIN
    -- Add icon column to cards if not exists
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'cards' AND column_name = 'icon'
    ) THEN
        ALTER TABLE cards ADD COLUMN icon TEXT;
        COMMENT ON COLUMN cards.icon IS 'Card brand icon identifier (e.g., visa, mastercard, elo)';
    END IF;

    -- Add brand column to cards if not exists
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'cards' AND column_name = 'brand'
    ) THEN
        ALTER TABLE cards ADD COLUMN brand TEXT;
        COMMENT ON COLUMN cards.brand IS 'Card brand name';
    END IF;
END;
$$;

-- Grant permissions
GRANT SELECT, INSERT, UPDATE, DELETE ON accounts TO authenticated;
GRANT SELECT, INSERT, UPDATE, DELETE ON cards TO authenticated;
