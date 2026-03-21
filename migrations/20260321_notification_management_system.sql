BEGIN;

CREATE OR REPLACE FUNCTION public.current_app_role()
RETURNS text
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  v_claims jsonb := COALESCE(NULLIF(current_setting('request.jwt.claims', true), ''), '{}')::jsonb;
BEGIN
  RETURN COALESCE(
    NULLIF(v_claims ->> 'app_role', ''),
    NULLIF(v_claims -> 'app_metadata' ->> 'role', ''),
    NULLIF(v_claims -> 'user_metadata' ->> 'role', ''),
    NULLIF(v_claims ->> 'role', ''),
    'user'
  );
END;
$$;

CREATE OR REPLACE FUNCTION public.update_updated_at_column()
RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
  NEW.updated_at = now();
  RETURN NEW;
END;
$$;

ALTER TABLE public.notifications
  ADD COLUMN IF NOT EXISTS event_key text NOT NULL DEFAULT 'custom',
  ADD COLUMN IF NOT EXISTS action_url text,
  ADD COLUMN IF NOT EXISTS related_forecast_id uuid REFERENCES public.financial_forecasts(id) ON DELETE SET NULL,
  ADD COLUMN IF NOT EXISTS metadata jsonb NOT NULL DEFAULT '{}'::jsonb,
  ADD COLUMN IF NOT EXISTS person_id uuid;

ALTER TABLE public.alert_preferences
  ADD COLUMN IF NOT EXISTS whatsapp_notifications boolean NOT NULL DEFAULT false,
  ADD COLUMN IF NOT EXISTS system_critical_notifications boolean NOT NULL DEFAULT true;

CREATE TABLE IF NOT EXISTS public.notification_settings (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  settings_key text NOT NULL UNIQUE DEFAULT 'global',
  is_active boolean NOT NULL DEFAULT true,
  toast_enabled boolean NOT NULL DEFAULT true,
  database_enabled boolean NOT NULL DEFAULT true,
  email_enabled boolean NOT NULL DEFAULT false,
  whatsapp_enabled boolean NOT NULL DEFAULT false,
  allow_user_channel_overrides boolean NOT NULL DEFAULT true,
  days_before_due_default integer NOT NULL DEFAULT 3,
  days_before_overdue_default integer NOT NULL DEFAULT 1,
  quiet_hours_start_default time NOT NULL DEFAULT '22:00:00',
  quiet_hours_end_default time NOT NULL DEFAULT '08:00:00',
  weekend_notifications_default boolean NOT NULL DEFAULT true,
  alert_due_soon_default boolean NOT NULL DEFAULT true,
  alert_overdue_default boolean NOT NULL DEFAULT true,
  alert_paid_default boolean NOT NULL DEFAULT true,
  alert_received_default boolean NOT NULL DEFAULT true,
  system_critical_default boolean NOT NULL DEFAULT true,
  email_from_name text,
  email_from_address text,
  email_test_destination text,
  whatsapp_test_destination text,
  created_at timestamptz NOT NULL DEFAULT now(),
  updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS public.notification_templates (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  event_key text NOT NULL,
  channel text NOT NULL CHECK (channel IN ('toast', 'email', 'whatsapp')),
  title_template text NOT NULL DEFAULT '',
  subject_template text,
  body_template text NOT NULL,
  is_active boolean NOT NULL DEFAULT true,
  created_at timestamptz NOT NULL DEFAULT now(),
  updated_at timestamptz NOT NULL DEFAULT now(),
  UNIQUE(event_key, channel)
);

CREATE TABLE IF NOT EXISTS public.notification_delivery_queue (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  notification_id uuid REFERENCES public.notifications(id) ON DELETE SET NULL,
  user_id uuid NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
  channel text NOT NULL CHECK (channel IN ('email', 'whatsapp')),
  destination text NOT NULL,
  subject text,
  content text NOT NULL,
  template_id uuid REFERENCES public.notification_templates(id) ON DELETE SET NULL,
  payload jsonb NOT NULL DEFAULT '{}'::jsonb,
  status text NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'processing', 'retrying', 'sent', 'failed')),
  attempts integer NOT NULL DEFAULT 0,
  max_attempts integer NOT NULL DEFAULT 5,
  next_attempt_at timestamptz NOT NULL DEFAULT now(),
  last_error text,
  provider_response jsonb NOT NULL DEFAULT '{}'::jsonb,
  sent_at timestamptz,
  created_at timestamptz NOT NULL DEFAULT now(),
  updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS public.notification_deliveries (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  queue_id uuid REFERENCES public.notification_delivery_queue(id) ON DELETE SET NULL,
  notification_id uuid REFERENCES public.notifications(id) ON DELETE SET NULL,
  user_id uuid REFERENCES auth.users(id) ON DELETE SET NULL,
  channel text NOT NULL CHECK (channel IN ('email', 'whatsapp')),
  destination text NOT NULL,
  provider text NOT NULL,
  status text NOT NULL CHECK (status IN ('sent', 'failed')),
  error_message text,
  provider_response jsonb NOT NULL DEFAULT '{}'::jsonb,
  attempted_at timestamptz NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_notifications_event_key ON public.notifications(event_key);
CREATE INDEX IF NOT EXISTS idx_notification_templates_event_channel ON public.notification_templates(event_key, channel);
CREATE INDEX IF NOT EXISTS idx_notification_queue_status_next_attempt ON public.notification_delivery_queue(status, next_attempt_at);
CREATE INDEX IF NOT EXISTS idx_notification_queue_user_created ON public.notification_delivery_queue(user_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_notification_deliveries_user_attempted ON public.notification_deliveries(user_id, attempted_at DESC);

DROP TRIGGER IF EXISTS update_notification_settings_updated_at ON public.notification_settings;
CREATE TRIGGER update_notification_settings_updated_at
BEFORE UPDATE ON public.notification_settings
FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();

DROP TRIGGER IF EXISTS update_notification_templates_updated_at ON public.notification_templates;
CREATE TRIGGER update_notification_templates_updated_at
BEFORE UPDATE ON public.notification_templates
FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();

DROP TRIGGER IF EXISTS update_notification_delivery_queue_updated_at ON public.notification_delivery_queue;
CREATE TRIGGER update_notification_delivery_queue_updated_at
BEFORE UPDATE ON public.notification_delivery_queue
FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();

INSERT INTO public.notification_settings (
  settings_key,
  is_active,
  toast_enabled,
  database_enabled,
  email_enabled,
  whatsapp_enabled,
  allow_user_channel_overrides,
  email_from_name,
  email_from_address
)
VALUES (
  'global',
  true,
  true,
  true,
  false,
  false,
  true,
  'OnliFin',
  NULL
)
ON CONFLICT (settings_key) DO NOTHING;

INSERT INTO public.notification_templates (event_key, channel, title_template, subject_template, body_template)
VALUES
  ('bill_due_soon', 'toast', 'Conta vencendo em breve', NULL, '{{description}} vence em {{days_until_due}} dia(s) no valor de R$ {{amount}}.'),
  ('bill_due_soon', 'email', 'Conta vencendo em breve', 'OnliFin: {{description}} vence em breve', '{{description}} vence em {{days_until_due}} dia(s) no valor de R$ {{amount}}. Vencimento: {{due_date}}.'),
  ('bill_due_soon', 'whatsapp', 'Conta vencendo em breve', NULL, 'OnliFin: {{description}} vence em {{days_until_due}} dia(s) no valor de R$ {{amount}}.'),
  ('bill_overdue', 'toast', 'Conta vencida', NULL, '{{description}} está vencida há {{days_overdue}} dia(s) no valor de R$ {{amount}}.'),
  ('bill_overdue', 'email', 'Conta vencida', 'OnliFin: {{description}} está vencida', '{{description}} está vencida há {{days_overdue}} dia(s) no valor de R$ {{amount}}. Vencimento: {{due_date}}.'),
  ('bill_overdue', 'whatsapp', 'Conta vencida', NULL, 'OnliFin: {{description}} está vencida há {{days_overdue}} dia(s) no valor de R$ {{amount}}.'),
  ('bill_paid', 'toast', 'Conta paga', NULL, '{{description}} foi paga no valor de R$ {{amount}}.'),
  ('bill_paid', 'email', 'Conta paga', 'OnliFin: pagamento registrado', '{{description}} foi paga no valor de R$ {{amount}}.'),
  ('bill_paid', 'whatsapp', 'Conta paga', NULL, 'OnliFin: {{description}} foi paga no valor de R$ {{amount}}.'),
  ('bill_to_receive_due_soon', 'toast', 'Recebimento em breve', NULL, '{{description}} será recebido em {{days_until_due}} dia(s) no valor de R$ {{amount}}.'),
  ('bill_to_receive_due_soon', 'email', 'Recebimento em breve', 'OnliFin: recebimento próximo', '{{description}} será recebido em {{days_until_due}} dia(s) no valor de R$ {{amount}}.'),
  ('bill_to_receive_due_soon', 'whatsapp', 'Recebimento em breve', NULL, 'OnliFin: {{description}} será recebido em {{days_until_due}} dia(s) no valor de R$ {{amount}}.'),
  ('bill_to_receive_received', 'toast', 'Valor recebido', NULL, '{{description}} foi recebido no valor de R$ {{amount}}.'),
  ('bill_to_receive_received', 'email', 'Valor recebido', 'OnliFin: valor recebido', '{{description}} foi recebido no valor de R$ {{amount}}.'),
  ('bill_to_receive_received', 'whatsapp', 'Valor recebido', NULL, 'OnliFin: {{description}} foi recebido no valor de R$ {{amount}}.'),
  ('system_critical', 'toast', 'Alerta do sistema', NULL, '{{message}}'),
  ('system_critical', 'email', 'Alerta do sistema', 'OnliFin: alerta crítico', '{{message}}'),
  ('system_critical', 'whatsapp', 'Alerta do sistema', NULL, 'OnliFin: {{message}}')
ON CONFLICT (event_key, channel) DO NOTHING;

ALTER TABLE public.notifications ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.notification_settings ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.notification_templates ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.notification_delivery_queue ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.notification_deliveries ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "Users can view own notifications" ON public.notifications;
DROP POLICY IF EXISTS "Users can update own notifications" ON public.notifications;
DROP POLICY IF EXISTS "Users can delete own notifications" ON public.notifications;
DROP POLICY IF EXISTS "Users can insert own notifications" ON public.notifications;

CREATE POLICY "Users can view own notifications" ON public.notifications
FOR SELECT TO authenticated
USING (auth.uid() = user_id);

CREATE POLICY "Users can update own notifications" ON public.notifications
FOR UPDATE TO authenticated
USING (auth.uid() = user_id)
WITH CHECK (auth.uid() = user_id);

CREATE POLICY "Users can delete own notifications" ON public.notifications
FOR DELETE TO authenticated
USING (auth.uid() = user_id);

CREATE POLICY "Users can insert own notifications" ON public.notifications
FOR INSERT TO authenticated
WITH CHECK (auth.uid() = user_id);

DROP POLICY IF EXISTS "Authenticated can view global notification settings" ON public.notification_settings;
DROP POLICY IF EXISTS "Admins manage global notification settings" ON public.notification_settings;

CREATE POLICY "Authenticated can view global notification settings" ON public.notification_settings
FOR SELECT TO authenticated
USING (true);

CREATE POLICY "Admins manage global notification settings" ON public.notification_settings
FOR ALL TO authenticated
USING (public.current_app_role() = 'admin')
WITH CHECK (public.current_app_role() = 'admin');

DROP POLICY IF EXISTS "Admins manage notification templates" ON public.notification_templates;

CREATE POLICY "Admins manage notification templates" ON public.notification_templates
FOR ALL TO authenticated
USING (public.current_app_role() = 'admin')
WITH CHECK (public.current_app_role() = 'admin');

DROP POLICY IF EXISTS "Admins view notification queue" ON public.notification_delivery_queue;
DROP POLICY IF EXISTS "Admins view notification deliveries" ON public.notification_deliveries;

CREATE POLICY "Admins view notification queue" ON public.notification_delivery_queue
FOR SELECT TO authenticated
USING (public.current_app_role() = 'admin');

CREATE POLICY "Admins view notification deliveries" ON public.notification_deliveries
FOR SELECT TO authenticated
USING (public.current_app_role() = 'admin');

GRANT SELECT, INSERT, UPDATE, DELETE ON public.notifications TO authenticated;
GRANT SELECT, INSERT, UPDATE ON public.alert_preferences TO authenticated;
GRANT SELECT, INSERT, UPDATE, DELETE ON public.notification_settings TO authenticated;
GRANT SELECT, INSERT, UPDATE, DELETE ON public.notification_templates TO authenticated;
GRANT SELECT ON public.notification_delivery_queue TO authenticated;
GRANT SELECT ON public.notification_deliveries TO authenticated;

COMMENT ON TABLE public.notification_settings IS 'Configuracao global do sistema de notificacoes';
COMMENT ON TABLE public.notification_templates IS 'Templates por evento e canal';
COMMENT ON TABLE public.notification_delivery_queue IS 'Fila de entregas externas de notificacao';
COMMENT ON TABLE public.notification_deliveries IS 'Historico de tentativas de entrega por canal';

COMMIT;
