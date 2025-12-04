/*
# Enable AI Write Permissions

## Plain English Explanation
This migration enables write permissions for the AI assistant, allowing it to create and modify
transactions on behalf of users. This is necessary for the AI to perform tasks like:
- Creating new transactions when users ask
- Categorizing existing transactions
- Updating transaction details
- Managing financial data

## Changes

### 1. Update ai_configurations table
- Set `can_write_transactions` to true for all active configurations
- Set `permission_level` to 'read_full' to allow complete data access needed for write operations

## Security
- Write operations are still logged in ai_chat_logs for full audit trail
- All transactions created by AI are tracked with created_transaction_id
- Users can disable write permissions at any time through the admin panel

## Notes
- This enables natural language commands like "registre uma despesa de R$ 150"
- AI will validate all data before creating transactions
- Failed operations are logged and reported to users
*/

-- Enable write permissions for active AI configurations
UPDATE ai_configurations 
SET 
  can_write_transactions = true,
  permission_level = 'read_full'
WHERE is_active = true;

-- If no active configuration exists, create one with write permissions enabled
INSERT INTO ai_configurations (
  model_name,
  endpoint,
  permission_level,
  can_write_transactions,
  is_active,
  created_at,
  updated_at
)
SELECT 
  'gemini-2.5-flash',
  '',
  'read_full',
  true,
  true,
  now(),
  now()
WHERE NOT EXISTS (
  SELECT 1 FROM ai_configurations WHERE is_active = true
);