BEGIN;

DROP POLICY IF EXISTS "Admins manage notification queue" ON public.notification_delivery_queue;

CREATE POLICY "Admins manage notification queue" ON public.notification_delivery_queue
FOR UPDATE TO authenticated
USING (public.current_app_role() = 'admin')
WITH CHECK (public.current_app_role() = 'admin');

GRANT UPDATE ON public.notification_delivery_queue TO authenticated;

COMMIT;
