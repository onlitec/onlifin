-- Migration: Add color column to people and companies
-- Description: Allows users to categorize profiles by color for better visual distinction

BEGIN;

-- Add color to people
ALTER TABLE public.people ADD COLUMN IF NOT EXISTS color VARCHAR(50);

-- Add color to companies
ALTER TABLE public.companies ADD COLUMN IF NOT EXISTS color VARCHAR(50);

-- Update existing default titulars to a specific color
UPDATE public.people SET color = '#10b981' WHERE is_default = true AND color IS NULL;

COMMIT;
