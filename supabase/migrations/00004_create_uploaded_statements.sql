/*
# Create uploaded_statements table

1. New Tables
- `uploaded_statements`
  - `id` (uuid, primary key)
  - `user_id` (uuid, references auth.users)
  - `file_name` (text)
  - `file_path` (text)
  - `file_type` (text)
  - `file_size` (integer)
  - `status` (text)
  - `analysis_result` (jsonb)
  - `error_message` (text)
  - `created_at` (timestamptz)
  - `analyzed_at` (timestamptz)
  - `imported_at` (timestamptz)

2. Security
- Enable RLS
- Users can only access their own uploads
- Admins can access all uploads

3. Indexes
- user_id for fast user queries
- status for filtering
*/

CREATE TABLE IF NOT EXISTS uploaded_statements (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid REFERENCES auth.users(id) NOT NULL,
  file_name text NOT NULL,
  file_path text NOT NULL,
  file_type text NOT NULL,
  file_size integer NOT NULL,
  status text NOT NULL DEFAULT 'uploaded',
  analysis_result jsonb,
  error_message text,
  created_at timestamptz DEFAULT now(),
  analyzed_at timestamptz,
  imported_at timestamptz
);

CREATE INDEX idx_uploaded_statements_user_id ON uploaded_statements(user_id);
CREATE INDEX idx_uploaded_statements_status ON uploaded_statements(status);

ALTER TABLE uploaded_statements ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Users can view own uploads" ON uploaded_statements
  FOR SELECT USING (auth.uid() = user_id);

CREATE POLICY "Users can insert own uploads" ON uploaded_statements
  FOR INSERT WITH CHECK (auth.uid() = user_id);

CREATE POLICY "Users can update own uploads" ON uploaded_statements
  FOR UPDATE USING (auth.uid() = user_id);

CREATE POLICY "Admins have full access" ON uploaded_statements
  FOR ALL USING (is_admin(auth.uid()));
