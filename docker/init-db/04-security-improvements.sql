-- ===========================================
-- 04 - Melhorias de Segurança
-- ===========================================
-- Adiciona proteção contra força bruta, audit log e validações

-- ===========================================
-- Tabela de tentativas de login (rate limiting no banco)
-- ===========================================
CREATE TABLE IF NOT EXISTS auth.login_attempts (
    id serial PRIMARY KEY,
    email text NOT NULL,
    ip_address text,
    success boolean NOT NULL DEFAULT false,
    attempted_at timestamptz DEFAULT now()
);

-- Índice para consultas rápidas
CREATE INDEX IF NOT EXISTS idx_login_attempts_email_time 
ON auth.login_attempts(email, attempted_at DESC);

-- Limpar tentativas antigas (mais de 24h)
CREATE OR REPLACE FUNCTION auth.cleanup_old_login_attempts()
RETURNS void AS $$
BEGIN
    DELETE FROM auth.login_attempts 
    WHERE attempted_at < now() - interval '24 hours';
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- ===========================================
-- Função para verificar rate limit
-- ===========================================
CREATE OR REPLACE FUNCTION auth.check_login_rate_limit(p_email text, p_max_attempts int DEFAULT 5, p_window_minutes int DEFAULT 5)
RETURNS boolean AS $$
DECLARE
    v_attempts int;
BEGIN
    SELECT COUNT(*) INTO v_attempts
    FROM auth.login_attempts
    WHERE email = p_email
      AND attempted_at > now() - (p_window_minutes || ' minutes')::interval
      AND success = false;
    
    RETURN v_attempts < p_max_attempts;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- ===========================================
-- Função de login aprimorada com rate limiting
-- ===========================================
CREATE OR REPLACE FUNCTION auth.login(p_email text, p_password text)
RETURNS uuid AS $$
DECLARE
    v_user_id uuid;
    v_password_hash text;
    v_is_locked boolean;
BEGIN
    -- Limpar email
    p_email := lower(trim(p_email));
    
    -- Verificar rate limit
    IF NOT auth.check_login_rate_limit(p_email) THEN
        -- Registrar tentativa bloqueada
        INSERT INTO auth.login_attempts (email, success)
        VALUES (p_email, false);
        RETURN NULL;
    END IF;
    
    -- Buscar usuário
    SELECT id, password_hash INTO v_user_id, v_password_hash
    FROM auth.users
    WHERE email = p_email;
    
    -- Usuário não encontrado
    IF v_user_id IS NULL THEN
        INSERT INTO auth.login_attempts (email, success)
        VALUES (p_email, false);
        RETURN NULL;
    END IF;
    
    -- Verificar senha
    IF auth.verify_password(p_password, v_password_hash) THEN
        -- Login sucesso
        INSERT INTO auth.login_attempts (email, success)
        VALUES (p_email, true);
        
        -- Atualizar último acesso
        UPDATE auth.users 
        SET updated_at = now() 
        WHERE id = v_user_id;
        
        RETURN v_user_id;
    ELSE
        -- Senha incorreta
        INSERT INTO auth.login_attempts (email, success)
        VALUES (p_email, false);
        RETURN NULL;
    END IF;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- ===========================================
-- Função de registro aprimorada com validação
-- ===========================================
CREATE OR REPLACE FUNCTION auth.register(p_email text, p_password text)
RETURNS uuid AS $$
DECLARE
    v_user_id uuid;
BEGIN
    -- Limpar email
    p_email := lower(trim(p_email));
    
    -- Validar email
    IF p_email !~ '^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$' THEN
        RAISE EXCEPTION 'Email inválido';
    END IF;
    
    -- Validar senha (mínimo 8 caracteres)
    IF length(p_password) < 8 THEN
        RAISE EXCEPTION 'Senha deve ter no mínimo 8 caracteres';
    END IF;
    
    -- Verificar se email já existe
    IF EXISTS (SELECT 1 FROM auth.users WHERE email = p_email) THEN
        RETURN NULL;
    END IF;
    
    -- Criar usuário
    INSERT INTO auth.users (email, password_hash)
    VALUES (p_email, auth.hash_password(p_password))
    RETURNING id INTO v_user_id;
    
    RETURN v_user_id;
EXCEPTION
    WHEN unique_violation THEN
        RETURN NULL;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- ===========================================
-- Tabela de audit log para ações sensíveis
-- ===========================================
CREATE TABLE IF NOT EXISTS auth.audit_log (
    id bigserial PRIMARY KEY,
    user_id uuid REFERENCES auth.users(id) ON DELETE SET NULL,
    action text NOT NULL,
    details jsonb,
    ip_address text,
    user_agent text,
    created_at timestamptz DEFAULT now()
);

-- Índices para audit log
CREATE INDEX IF NOT EXISTS idx_audit_log_user_id 
ON auth.audit_log(user_id);

CREATE INDEX IF NOT EXISTS idx_audit_log_action 
ON auth.audit_log(action);

CREATE INDEX IF NOT EXISTS idx_audit_log_created_at 
ON auth.audit_log(created_at DESC);

-- ===========================================
-- Função para registrar audit log
-- ===========================================
CREATE OR REPLACE FUNCTION auth.log_action(
    p_user_id uuid,
    p_action text,
    p_details jsonb DEFAULT NULL
)
RETURNS void AS $$
BEGIN
    INSERT INTO auth.audit_log (user_id, action, details)
    VALUES (p_user_id, p_action, p_details);
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- ===========================================
-- Adicionar campo de status à tabela de usuários
-- ===========================================
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'auth' 
        AND table_name = 'users' 
        AND column_name = 'is_active'
    ) THEN
        ALTER TABLE auth.users ADD COLUMN is_active boolean DEFAULT true;
    END IF;
    
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'auth' 
        AND table_name = 'users' 
        AND column_name = 'failed_login_count'
    ) THEN
        ALTER TABLE auth.users ADD COLUMN failed_login_count int DEFAULT 0;
    END IF;
    
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'auth' 
        AND table_name = 'users' 
        AND column_name = 'locked_until'
    ) THEN
        ALTER TABLE auth.users ADD COLUMN locked_until timestamptz;
    END IF;
END
$$;

-- ===========================================
-- Permissões
-- ===========================================
GRANT SELECT ON auth.login_attempts TO authenticated;
-- Audit log apenas para admins (via RLS depois)

-- ===========================================
-- Comentários
-- ===========================================
COMMENT ON TABLE auth.login_attempts IS 'Registro de tentativas de login para rate limiting';
COMMENT ON TABLE auth.audit_log IS 'Log de auditoria para ações sensíveis';
COMMENT ON FUNCTION auth.check_login_rate_limit IS 'Verifica se email está dentro do limite de tentativas';
COMMENT ON FUNCTION auth.log_action IS 'Registra ação no audit log';
