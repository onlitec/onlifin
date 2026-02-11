-- Migration: Restrict people RLS to owner only
-- Description: Removes admin bypass from people table to prevent data leakage in PF context

BEGIN;

-- Drop existing policy
DROP POLICY IF EXISTS "people_owner_and_admin" ON public.people;

-- Create restricted policy (Owner ONLY)
CREATE POLICY "people_owner_only_policy" ON public.people 
    FOR ALL 
    TO authenticated 
    USING (user_id = auth.uid())
    WITH CHECK (user_id = auth.uid());

COMMIT;
