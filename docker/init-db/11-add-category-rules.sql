-- Category Rules Table for keyword-based auto-categorization
-- This table stores rules that map keywords to categories

CREATE TABLE IF NOT EXISTS category_rules (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id uuid REFERENCES profiles(id) ON DELETE CASCADE NOT NULL,
    category_id uuid REFERENCES categories(id) ON DELETE CASCADE NOT NULL,
    keyword TEXT NOT NULL,
    match_type TEXT DEFAULT 'contains' CHECK (match_type IN ('contains', 'starts_with', 'exact')),
    priority INTEGER DEFAULT 0,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- Index for fast lookups
CREATE INDEX IF NOT EXISTS idx_category_rules_user_id ON category_rules(user_id);
CREATE INDEX IF NOT EXISTS idx_category_rules_keyword ON category_rules(keyword);

-- Permissions
GRANT SELECT, INSERT, UPDATE, DELETE ON category_rules TO authenticated;
GRANT SELECT ON category_rules TO anon;

-- Insert some common default rules (will be associated with user when they use them)
-- These are just examples, the real rules will be created per-user
