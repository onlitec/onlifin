-- ===========================================
-- 10 - Recurring Schedules Table
-- ===========================================

CREATE TABLE IF NOT EXISTS recurring_schedules (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id uuid REFERENCES profiles(id) ON DELETE CASCADE NOT NULL,
    description text NOT NULL,
    amount numeric NOT NULL,
    frequency text NOT NULL,
    type text NOT NULL,
    created_at timestamptz DEFAULT now()
);

-- Index for performance
CREATE INDEX IF NOT EXISTS idx_recurring_schedules_user_id ON recurring_schedules(user_id);

-- Permissions
GRANT SELECT, INSERT, UPDATE, DELETE ON recurring_schedules TO authenticated;
GRANT SELECT ON recurring_schedules TO anon;
