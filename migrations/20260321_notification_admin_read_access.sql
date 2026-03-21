BEGIN;

DROP POLICY IF EXISTS "Admins can view all notifications" ON public.notifications;

CREATE POLICY "Admins can view all notifications" ON public.notifications
FOR SELECT TO authenticated
USING (public.current_app_role() = 'admin');

COMMIT;
