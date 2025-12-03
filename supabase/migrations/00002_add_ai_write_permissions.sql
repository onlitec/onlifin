/*
# Add AI Write Permissions

## Plain English Explanation
This migration adds the ability for the AI assistant to create transactions on behalf of users.
It adds a new permission flag that administrators can enable to allow the AI to write data,
not just read it.

## Changes

### 1. ai_configurations table
- Add `can_write_transactions` (boolean, default: false) - Controls if AI can create transactions

### 2. ai_chat_logs table
- Add `action_type` (text, default: 'read') - Tracks if the interaction was read-only or write
- Add `created_transaction_id` (uuid, nullable) - References the transaction created by AI (if any)

## Security
- Write permission is disabled by default for safety
- All AI-created transactions are logged with full audit trail
- Administrators must explicitly enable write permissions
- Each transaction creation is tracked in ai_chat_logs

## Notes
- This enables users to ask AI to create transactions via natural language
- Example: "Registre uma despesa de R$ 150 no supermercado"
- AI will validate data before creating transactions
*/

-- Add can_write_transactions column to ai_configurations
ALTER TABLE ai_configurations 
ADD COLUMN IF NOT EXISTS can_write_transactions boolean DEFAULT false NOT NULL;

-- Add action_type and created_transaction_id to ai_chat_logs
ALTER TABLE ai_chat_logs 
ADD COLUMN IF NOT EXISTS action_type text DEFAULT 'read' NOT NULL,
ADD COLUMN IF NOT EXISTS created_transaction_id uuid REFERENCES transactions(id) ON DELETE SET NULL;

-- Add index for better query performance
CREATE INDEX IF NOT EXISTS idx_ai_chat_logs_action_type ON ai_chat_logs(action_type);
CREATE INDEX IF NOT EXISTS idx_ai_chat_logs_created_transaction ON ai_chat_logs(created_transaction_id) WHERE created_transaction_id IS NOT NULL;

-- Add comment for documentation
COMMENT ON COLUMN ai_configurations.can_write_transactions IS 'Allows AI to create transactions on behalf of users';
COMMENT ON COLUMN ai_chat_logs.action_type IS 'Type of action: read (query only) or write (created data)';
COMMENT ON COLUMN ai_chat_logs.created_transaction_id IS 'Reference to transaction created by AI in this interaction';
