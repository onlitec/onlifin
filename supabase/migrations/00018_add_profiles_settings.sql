-- Migration: Add settings column to profiles for PF preferences

ALTER TABLE public.profiles
    ADD COLUMN IF NOT EXISTS settings JSONB NOT NULL DEFAULT '{}'::jsonb;

UPDATE public.profiles
SET settings = '{}'::jsonb
WHERE settings IS NULL;
