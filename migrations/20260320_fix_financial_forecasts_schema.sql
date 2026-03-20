-- Migration: Align financial_forecasts schema with the native frontend

ALTER TABLE public.financial_forecasts
    ADD COLUMN IF NOT EXISTS company_id UUID REFERENCES public.companies(id) ON DELETE CASCADE;

ALTER TABLE public.financial_forecasts
    ADD COLUMN IF NOT EXISTS person_id UUID;

ALTER TABLE public.financial_forecasts
    ADD COLUMN IF NOT EXISTS initial_balance NUMERIC NOT NULL DEFAULT 0;

ALTER TABLE public.financial_forecasts
    ADD COLUMN IF NOT EXISTS forecast_daily JSONB NOT NULL DEFAULT '{}'::jsonb;

ALTER TABLE public.financial_forecasts
    ADD COLUMN IF NOT EXISTS forecast_weekly JSONB NOT NULL DEFAULT '{}'::jsonb;

ALTER TABLE public.financial_forecasts
    ADD COLUMN IF NOT EXISTS forecast_monthly JSONB NOT NULL DEFAULT '{}'::jsonb;

ALTER TABLE public.financial_forecasts
    ADD COLUMN IF NOT EXISTS insights JSONB NOT NULL DEFAULT '[]'::jsonb;

ALTER TABLE public.financial_forecasts
    ADD COLUMN IF NOT EXISTS alerts JSONB NOT NULL DEFAULT '[]'::jsonb;

ALTER TABLE public.financial_forecasts
    ADD COLUMN IF NOT EXISTS risk_negative BOOLEAN NOT NULL DEFAULT false;

ALTER TABLE public.financial_forecasts
    ADD COLUMN IF NOT EXISTS risk_date DATE;

ALTER TABLE public.financial_forecasts
    ADD COLUMN IF NOT EXISTS spending_patterns JSONB NOT NULL DEFAULT '{}'::jsonb;

ALTER TABLE public.financial_forecasts
    ADD COLUMN IF NOT EXISTS created_at TIMESTAMPTZ NOT NULL DEFAULT now();

ALTER TABLE public.financial_forecasts
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMPTZ NOT NULL DEFAULT now();

CREATE INDEX IF NOT EXISTS idx_financial_forecasts_user_scope_date
    ON public.financial_forecasts(user_id, company_id, person_id, calculation_date DESC);

DROP TRIGGER IF EXISTS update_financial_forecasts_updated_at ON public.financial_forecasts;
CREATE TRIGGER update_financial_forecasts_updated_at
    BEFORE UPDATE ON public.financial_forecasts
    FOR EACH ROW
    EXECUTE FUNCTION public.update_updated_at_column();
