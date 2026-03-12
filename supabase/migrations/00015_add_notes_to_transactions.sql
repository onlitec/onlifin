/*
# Add notes column to transactions table

## Plain English Explanation
This migration adds a `notes` column to the `transactions` table to allow users to store additional information or observations about each transaction. This column was already present in the UI but missing from the database, causing errors when saving transactions.

## Changes Made
- Adds `notes` (text) column to `transactions` table
*/

ALTER TABLE transactions ADD COLUMN IF NOT EXISTS notes text;

COMMENT ON COLUMN transactions.notes IS 'Observações adicionais sobre a transação';
