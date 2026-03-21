BEGIN;

CREATE TABLE IF NOT EXISTS public.notification_worker_commands (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  command text NOT NULL CHECK (command IN ('process_queue', 'generate_notifications')),
  status text NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'processing', 'completed', 'failed')),
  requested_by uuid REFERENCES auth.users(id) ON DELETE SET NULL,
  payload jsonb NOT NULL DEFAULT '{}'::jsonb,
  result jsonb NOT NULL DEFAULT '{}'::jsonb,
  error_message text,
  requested_at timestamptz NOT NULL DEFAULT now(),
  started_at timestamptz,
  completed_at timestamptz,
  updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_notification_worker_commands_status_requested
  ON public.notification_worker_commands(status, requested_at);

CREATE INDEX IF NOT EXISTS idx_notification_worker_commands_requested_by
  ON public.notification_worker_commands(requested_by, requested_at DESC);

DROP TRIGGER IF EXISTS update_notification_worker_commands_updated_at ON public.notification_worker_commands;
CREATE TRIGGER update_notification_worker_commands_updated_at
BEFORE UPDATE ON public.notification_worker_commands
FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();

ALTER TABLE public.notification_worker_commands ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "Admins manage notification worker commands" ON public.notification_worker_commands;

CREATE POLICY "Admins manage notification worker commands" ON public.notification_worker_commands
FOR ALL TO authenticated
USING (public.current_app_role() = 'admin')
WITH CHECK (public.current_app_role() = 'admin');

GRANT SELECT, INSERT, UPDATE ON public.notification_worker_commands TO authenticated;

COMMENT ON TABLE public.notification_worker_commands IS 'Comandos administrativos assíncronos para o notification worker';

COMMIT;
