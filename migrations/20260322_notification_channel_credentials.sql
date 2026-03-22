CREATE TABLE IF NOT EXISTS public.notification_channel_credentials (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  credentials_key text NOT NULL UNIQUE DEFAULT 'global',
  smtp_host text,
  smtp_port integer NOT NULL DEFAULT 587,
  smtp_secure boolean NOT NULL DEFAULT false,
  smtp_user text,
  smtp_pass text,
  whatsapp_provider text NOT NULL DEFAULT 'generic',
  whatsapp_api_base_url text,
  whatsapp_api_token text,
  whatsapp_sender text,
  created_at timestamptz NOT NULL DEFAULT now(),
  updated_at timestamptz NOT NULL DEFAULT now()
);

INSERT INTO public.notification_channel_credentials (credentials_key)
VALUES ('global')
ON CONFLICT (credentials_key) DO NOTHING;

ALTER TABLE public.notification_channel_credentials ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "Admins manage notification channel credentials" ON public.notification_channel_credentials;

CREATE POLICY "Admins manage notification channel credentials" ON public.notification_channel_credentials
USING (public.current_app_role() = 'admin')
WITH CHECK (public.current_app_role() = 'admin');

DROP TRIGGER IF EXISTS update_notification_channel_credentials_updated_at ON public.notification_channel_credentials;
CREATE TRIGGER update_notification_channel_credentials_updated_at
BEFORE UPDATE ON public.notification_channel_credentials
FOR EACH ROW
EXECUTE FUNCTION public.set_updated_at();

GRANT SELECT, INSERT, UPDATE, DELETE ON public.notification_channel_credentials TO authenticated;

COMMENT ON TABLE public.notification_channel_credentials IS 'Credenciais globais de entrega externa (SMTP e WhatsApp) restritas a admins da plataforma';
