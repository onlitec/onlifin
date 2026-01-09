-- ===========================================
-- Migration 001: Create Bills Tables
-- ===========================================
-- This migration creates the bills_to_pay and bills_to_receive tables
-- if they don't exist. Safe to run multiple times.

-- Create bills_to_pay table
CREATE TABLE IF NOT EXISTS bills_to_pay (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid NOT NULL REFERENCES profiles(id) ON DELETE CASCADE,
  description text NOT NULL,
  amount numeric NOT NULL CHECK (amount > 0),
  due_date date NOT NULL,
  category_id uuid REFERENCES categories(id) ON DELETE SET NULL,
  status text NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'paid', 'overdue')),
  is_recurring boolean DEFAULT false,
  recurrence_pattern text,
  account_id uuid REFERENCES accounts(id) ON DELETE SET NULL,
  paid_date date,
  notes text,
  created_at timestamptz DEFAULT now(),
  updated_at timestamptz DEFAULT now()
);

-- Create bills_to_receive table
CREATE TABLE IF NOT EXISTS bills_to_receive (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid NOT NULL REFERENCES profiles(id) ON DELETE CASCADE,
  description text NOT NULL,
  amount numeric NOT NULL CHECK (amount > 0),
  due_date date NOT NULL,
  category_id uuid REFERENCES categories(id) ON DELETE SET NULL,
  status text NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'received', 'overdue')),
  is_recurring boolean DEFAULT false,
  recurrence_pattern text,
  account_id uuid REFERENCES accounts(id) ON DELETE SET NULL,
  received_date date,
  notes text,
  created_at timestamptz DEFAULT now(),
  updated_at timestamptz DEFAULT now()
);

-- Create financial_forecasts table
CREATE TABLE IF NOT EXISTS financial_forecasts (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid NOT NULL REFERENCES profiles(id) ON DELETE CASCADE,
  calculation_date timestamptz NOT NULL DEFAULT now(),
  initial_balance numeric NOT NULL,
  forecast_daily jsonb NOT NULL DEFAULT '{}',
  forecast_weekly jsonb NOT NULL DEFAULT '{}',
  forecast_monthly jsonb NOT NULL DEFAULT '{}',
  insights jsonb NOT NULL DEFAULT '[]',
  alerts jsonb NOT NULL DEFAULT '[]',
  risk_negative boolean DEFAULT false,
  risk_date date,
  spending_patterns jsonb DEFAULT '{}',
  created_at timestamptz DEFAULT now()
);

-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid NOT NULL REFERENCES profiles(id) ON DELETE CASCADE,
  title text NOT NULL,
  message text NOT NULL,
  type text NOT NULL CHECK (type IN ('alert', 'info', 'warning', 'success')),
  severity text CHECK (severity IN ('low', 'medium', 'high')),
  is_read boolean DEFAULT false,
  related_forecast_id uuid REFERENCES financial_forecasts(id) ON DELETE SET NULL,
  related_bill_id uuid,
  action_url text,
  created_at timestamptz DEFAULT now()
);

-- Create indexes for performance
CREATE INDEX IF NOT EXISTS idx_bills_to_pay_user_id ON bills_to_pay(user_id);
CREATE INDEX IF NOT EXISTS idx_bills_to_pay_due_date ON bills_to_pay(due_date);
CREATE INDEX IF NOT EXISTS idx_bills_to_pay_status ON bills_to_pay(status);

CREATE INDEX IF NOT EXISTS idx_bills_to_receive_user_id ON bills_to_receive(user_id);
CREATE INDEX IF NOT EXISTS idx_bills_to_receive_due_date ON bills_to_receive(due_date);
CREATE INDEX IF NOT EXISTS idx_bills_to_receive_status ON bills_to_receive(status);

CREATE INDEX IF NOT EXISTS idx_financial_forecasts_user_id ON financial_forecasts(user_id);
CREATE INDEX IF NOT EXISTS idx_financial_forecasts_calculation_date ON financial_forecasts(calculation_date DESC);

CREATE INDEX IF NOT EXISTS idx_notifications_user_id ON notifications(user_id);
CREATE INDEX IF NOT EXISTS idx_notifications_is_read ON notifications(is_read);
CREATE INDEX IF NOT EXISTS idx_notifications_created_at ON notifications(created_at DESC);

-- Grant permissions to authenticated role
GRANT SELECT, INSERT, UPDATE, DELETE ON bills_to_pay TO authenticated;
GRANT SELECT, INSERT, UPDATE, DELETE ON bills_to_receive TO authenticated;
GRANT SELECT, INSERT, UPDATE, DELETE ON financial_forecasts TO authenticated;
GRANT SELECT, INSERT, UPDATE, DELETE ON notifications TO authenticated;

-- Notify PostgREST to reload schema cache
NOTIFY pgrst, 'reload config';

-- Log migration completion
DO $$
BEGIN
  RAISE NOTICE 'Migration 001: Bills tables created/verified successfully!';
END $$;
