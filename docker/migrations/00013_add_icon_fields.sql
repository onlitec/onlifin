-- Migration: Add icon fields to accounts and cards tables
-- This allows storing bank icons for accounts and card brand icons for cards

-- Add icon column to accounts table
ALTER TABLE accounts ADD COLUMN IF NOT EXISTS icon TEXT;

-- Add icon and brand columns to cards table
ALTER TABLE cards ADD COLUMN IF NOT EXISTS icon TEXT;
ALTER TABLE cards ADD COLUMN IF NOT EXISTS brand TEXT;

-- Add comment for documentation
COMMENT ON COLUMN accounts.icon IS 'Bank icon identifier (e.g., bb, itau, nubank)';
COMMENT ON COLUMN cards.icon IS 'Card brand icon identifier (e.g., visa, mastercard, elo)';
COMMENT ON COLUMN cards.brand IS 'Card brand name';
