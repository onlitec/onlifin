-- Migration: Corrigir permissões da tabela alert_preferences
-- Descrição: Habilitar RLS e criar políticas para acesso correto

-- 1. Habilitar RLS na tabela alert_preferences
ALTER TABLE alert_preferences ENABLE ROW LEVEL SECURITY;

-- 2. Dar permissões básicas para os roles
GRANT SELECT, INSERT, UPDATE ON alert_preferences TO anon;
GRANT SELECT, INSERT, UPDATE ON alert_preferences TO authenticated;
GRANT ALL ON alert_preferences TO service_role;

-- 3. Criar políticas RLS

-- Política para usuários anônimos (não deveriam acessar, mas para evitar 403)
CREATE POLICY "Allow anonymous read" ON alert_preferences
    FOR SELECT USING (false);

-- Política para usuários autenticados lerem suas próprias preferências
CREATE POLICY "Users can view own alert preferences" ON alert_preferences
    FOR SELECT USING (auth.uid() = user_id);

-- Política para usuários autenticados inserirem suas próprias preferências
CREATE POLICY "Users can insert own alert preferences" ON alert_preferences
    FOR INSERT WITH CHECK (auth.uid() = user_id);

-- Política para usuários autenticados atualizarem suas próprias preferências
CREATE POLICY "Users can update own alert preferences" ON alert_preferences
    FOR UPDATE USING (auth.uid() = user_id);

-- Política para service role (back-end) ter acesso total
CREATE POLICY "Service role full access" ON alert_preferences
    FOR ALL USING (auth.role() = 'service_role');

-- 4. Verificar se a tabela tem a estrutura correta
SELECT column_name, data_type, is_nullable 
FROM information_schema.columns 
WHERE table_name = 'alert_preferences' 
ORDER BY ordinal_position;

-- 5. Inserir preferências padrão para usuários existentes (se não tiverem)
INSERT INTO alert_preferences (
    user_id,
    days_before_due,
    days_before_overdue,
    alert_due_soon,
    alert_overdue,
    alert_paid,
    alert_received,
    toast_notifications,
    database_notifications,
    email_notifications,
    push_notifications,
    quiet_hours_start,
    quiet_hours_end,
    weekend_notifications
)
SELECT 
    id,
    3,  -- days_before_due
    1,  -- days_before_overdue
    true,  -- alert_due_soon
    true,  -- alert_overdue
    true,  -- alert_paid
    true,  -- alert_received
    true,  -- toast_notifications
    true,  -- database_notifications
    false, -- email_notifications
    false, -- push_notifications
    '22:00:00', -- quiet_hours_start
    '08:00:00', -- quiet_hours_end
    true   -- weekend_notifications
FROM auth.users
WHERE id NOT IN (SELECT user_id FROM alert_preferences);

-- Comentários
COMMENT ON POLICY "Users can view own alert preferences" IS 'Permite usuários verem suas próprias preferências de alerta';
COMMENT ON POLICY "Users can insert own alert preferences" IS 'Permite usuários criarem suas próprias preferências de alerta';
COMMENT ON POLICY "Users can update own alert preferences" IS 'Permite usuários atualizarem suas próprias preferências de alerta';
