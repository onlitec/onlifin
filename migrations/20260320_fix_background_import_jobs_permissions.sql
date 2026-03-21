-- Garantir acesso seguro do usuário autenticado aos seus próprios jobs de importação

ALTER TABLE public.background_import_jobs ENABLE ROW LEVEL SECURITY;

REVOKE ALL ON public.background_import_jobs FROM anon;
GRANT SELECT, INSERT, UPDATE, DELETE ON public.background_import_jobs TO authenticated;

DROP POLICY IF EXISTS background_import_jobs_owner_select ON public.background_import_jobs;
CREATE POLICY background_import_jobs_owner_select
ON public.background_import_jobs
FOR SELECT
TO authenticated
USING (auth.uid() = user_id);

DROP POLICY IF EXISTS background_import_jobs_owner_insert ON public.background_import_jobs;
CREATE POLICY background_import_jobs_owner_insert
ON public.background_import_jobs
FOR INSERT
TO authenticated
WITH CHECK (auth.uid() = user_id);

DROP POLICY IF EXISTS background_import_jobs_owner_update ON public.background_import_jobs;
CREATE POLICY background_import_jobs_owner_update
ON public.background_import_jobs
FOR UPDATE
TO authenticated
USING (auth.uid() = user_id)
WITH CHECK (auth.uid() = user_id);

DROP POLICY IF EXISTS background_import_jobs_owner_delete ON public.background_import_jobs;
CREATE POLICY background_import_jobs_owner_delete
ON public.background_import_jobs
FOR DELETE
TO authenticated
USING (auth.uid() = user_id);
