/*
# Create Financial Forecast System

## Plain English Explanation
This migration creates the infrastructure for AI-powered financial forecasting, including tables for bills to pay/receive, financial forecasts with predictions and alerts, and a notifications system. The system will automatically analyze transaction patterns and generate predictions for future cash flow.

## Table List & Column Descriptions

### 1. bills_to_pay (Contas a Pagar)
- `id` (uuid, primary key): Bill identifier
- `user_id` (uuid, references profiles): Owner of the bill
- `description` (text, not null): Bill description
- `amount` (numeric, not null): Bill amount
- `due_date` (date, not null): Payment due date
- `category_id` (uuid, references categories): Bill category
- `status` (text, not null): Status (pending, paid, overdue)
- `is_recurring` (boolean, default false): Recurring bill flag
- `recurrence_pattern` (text): Recurrence pattern (monthly, weekly, yearly)
- `account_id` (uuid, references accounts): Account for payment
- `paid_date` (date): Actual payment date
- `notes` (text): Additional notes
- `created_at` (timestamptz, default now()): Creation timestamp
- `updated_at` (timestamptz, default now()): Last update timestamp

### 2. bills_to_receive (Contas a Receber)
- `id` (uuid, primary key): Bill identifier
- `user_id` (uuid, references profiles): Owner of the bill
- `description` (text, not null): Bill description
- `amount` (numeric, not null): Bill amount
- `due_date` (date, not null): Expected receipt date
- `category_id` (uuid, references categories): Bill category
- `status` (text, not null): Status (pending, received, overdue)
- `is_recurring` (boolean, default false): Recurring bill flag
- `recurrence_pattern` (text): Recurrence pattern
- `account_id` (uuid, references accounts): Account for receipt
- `received_date` (date): Actual receipt date
- `notes` (text): Additional notes
- `created_at` (timestamptz, default now()): Creation timestamp
- `updated_at` (timestamptz, default now()): Last update timestamp

### 3. financial_forecasts (Previsões Financeiras)
- `id` (uuid, primary key): Forecast identifier
- `user_id` (uuid, references profiles): Owner of the forecast
- `calculation_date` (timestamptz, not null): When forecast was calculated
- `initial_balance` (numeric, not null): Starting balance
- `forecast_daily` (jsonb, not null): Daily predictions for 30 days
- `forecast_weekly` (jsonb, not null): Weekly predictions for 12 weeks
- `forecast_monthly` (jsonb, not null): Monthly predictions for 6 months
- `insights` (jsonb, not null): AI-generated insights array
- `alerts` (jsonb, not null): Risk alerts array
- `risk_negative` (boolean, default false): Negative balance risk flag
- `risk_date` (date): Date when negative balance is predicted
- `spending_patterns` (jsonb): Detected spending patterns
- `created_at` (timestamptz, default now()): Creation timestamp

### 4. notifications (Notificações)
- `id` (uuid, primary key): Notification identifier
- `user_id` (uuid, references profiles): Recipient user
- `title` (text, not null): Notification title
- `message` (text, not null): Notification message
- `type` (text, not null): Type (alert, info, warning, success)
- `severity` (text): Severity level (low, medium, high)
- `is_read` (boolean, default false): Read status
- `related_forecast_id` (uuid, references financial_forecasts): Related forecast
- `related_bill_id` (uuid): Related bill (generic reference)
- `action_url` (text): URL for action button
- `created_at` (timestamptz, default now()): Creation timestamp

## Security Changes
- Enable RLS on all new tables
- Users can only access their own bills, forecasts, and notifications
- Admins have full access to all data
- Create policies for read/write operations

## Notes
- Bills status automatically updates based on due dates
- Forecasts are generated daily by scheduled job
- Notifications are created when risks are detected
- All monetary values use numeric type for precision
*/

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
CREATE INDEX idx_bills_to_pay_user_id ON bills_to_pay(user_id);
CREATE INDEX idx_bills_to_pay_due_date ON bills_to_pay(due_date);
CREATE INDEX idx_bills_to_pay_status ON bills_to_pay(status);

CREATE INDEX idx_bills_to_receive_user_id ON bills_to_receive(user_id);
CREATE INDEX idx_bills_to_receive_due_date ON bills_to_receive(due_date);
CREATE INDEX idx_bills_to_receive_status ON bills_to_receive(status);

CREATE INDEX idx_financial_forecasts_user_id ON financial_forecasts(user_id);
CREATE INDEX idx_financial_forecasts_calculation_date ON financial_forecasts(calculation_date DESC);

CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_notifications_is_read ON notifications(is_read);
CREATE INDEX idx_notifications_created_at ON notifications(created_at DESC);

-- Enable RLS
ALTER TABLE bills_to_pay ENABLE ROW LEVEL SECURITY;
ALTER TABLE bills_to_receive ENABLE ROW LEVEL SECURITY;
ALTER TABLE financial_forecasts ENABLE ROW LEVEL SECURITY;
ALTER TABLE notifications ENABLE ROW LEVEL SECURITY;

-- RLS Policies for bills_to_pay
CREATE POLICY "Users can view own bills to pay" ON bills_to_pay
  FOR SELECT USING (auth.uid() = user_id);

CREATE POLICY "Users can create own bills to pay" ON bills_to_pay
  FOR INSERT WITH CHECK (auth.uid() = user_id);

CREATE POLICY "Users can update own bills to pay" ON bills_to_pay
  FOR UPDATE USING (auth.uid() = user_id);

CREATE POLICY "Users can delete own bills to pay" ON bills_to_pay
  FOR DELETE USING (auth.uid() = user_id);

CREATE POLICY "Admins have full access to bills to pay" ON bills_to_pay
  FOR ALL USING (is_admin(auth.uid()));

-- RLS Policies for bills_to_receive
CREATE POLICY "Users can view own bills to receive" ON bills_to_receive
  FOR SELECT USING (auth.uid() = user_id);

CREATE POLICY "Users can create own bills to receive" ON bills_to_receive
  FOR INSERT WITH CHECK (auth.uid() = user_id);

CREATE POLICY "Users can update own bills to receive" ON bills_to_receive
  FOR UPDATE USING (auth.uid() = user_id);

CREATE POLICY "Users can delete own bills to receive" ON bills_to_receive
  FOR DELETE USING (auth.uid() = user_id);

CREATE POLICY "Admins have full access to bills to receive" ON bills_to_receive
  FOR ALL USING (is_admin(auth.uid()));

-- RLS Policies for financial_forecasts
CREATE POLICY "Users can view own forecasts" ON financial_forecasts
  FOR SELECT USING (auth.uid() = user_id);

CREATE POLICY "Admins have full access to forecasts" ON financial_forecasts
  FOR ALL USING (is_admin(auth.uid()));

-- RLS Policies for notifications
CREATE POLICY "Users can view own notifications" ON notifications
  FOR SELECT USING (auth.uid() = user_id);

CREATE POLICY "Users can update own notifications" ON notifications
  FOR UPDATE USING (auth.uid() = user_id);

CREATE POLICY "Admins have full access to notifications" ON notifications
  FOR ALL USING (is_admin(auth.uid()));

-- Function to automatically update bills status based on due date
CREATE OR REPLACE FUNCTION update_bills_status()
RETURNS void
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
BEGIN
  -- Update bills_to_pay status to overdue
  UPDATE bills_to_pay
  SET status = 'overdue', updated_at = now()
  WHERE status = 'pending' 
    AND due_date < CURRENT_DATE;

  -- Update bills_to_receive status to overdue
  UPDATE bills_to_receive
  SET status = 'overdue', updated_at = now()
  WHERE status = 'pending' 
    AND due_date < CURRENT_DATE;
END;
$$;

-- Function to get user's total current balance
CREATE OR REPLACE FUNCTION get_user_total_balance(p_user_id uuid)
RETURNS numeric
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
DECLARE
  v_total_balance numeric;
BEGIN
  SELECT COALESCE(SUM(balance), 0)
  INTO v_total_balance
  FROM accounts
  WHERE user_id = p_user_id;
  
  RETURN v_total_balance;
END;
$$;

-- Function to create notification
CREATE OR REPLACE FUNCTION create_notification(
  p_user_id uuid,
  p_title text,
  p_message text,
  p_type text,
  p_severity text DEFAULT NULL,
  p_related_forecast_id uuid DEFAULT NULL,
  p_related_bill_id uuid DEFAULT NULL,
  p_action_url text DEFAULT NULL
)
RETURNS uuid
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
DECLARE
  v_notification_id uuid;
BEGIN
  INSERT INTO notifications (
    user_id,
    title,
    message,
    type,
    severity,
    related_forecast_id,
    related_bill_id,
    action_url
  ) VALUES (
    p_user_id,
    p_title,
    p_message,
    p_type,
    p_severity,
    p_related_forecast_id,
    p_related_bill_id,
    p_action_url
  )
  RETURNING id INTO v_notification_id;
  
  RETURN v_notification_id;
END;
$$;
