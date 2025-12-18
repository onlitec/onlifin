-- ===========================================
-- 01 - Schema de Autenticação Standalone
-- ===========================================
-- Substitui Supabase Auth com sistema JWT simples

-- Criar schema auth
CREATE SCHEMA IF NOT EXISTS auth;

-- Extensão para criptografia de senhas
CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- Tabela de usuários
CREATE TABLE auth.users (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    email text UNIQUE NOT NULL,
    password_hash text NOT NULL,
    email_confirmed_at timestamptz DEFAULT now(),
    created_at timestamptz DEFAULT now(),
    updated_at timestamptz DEFAULT now()
);

-- Função para hash de senha
CREATE OR REPLACE FUNCTION auth.hash_password(password text)
RETURNS text AS $$
BEGIN
    RETURN crypt(password, gen_salt('bf', 10));
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Função para verificar senha
CREATE OR REPLACE FUNCTION auth.verify_password(password text, password_hash text)
RETURNS boolean AS $$
BEGIN
    RETURN password_hash = crypt(password, password_hash);
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Função para login (retorna user_id se sucesso)
CREATE OR REPLACE FUNCTION auth.login(p_email text, p_password text)
RETURNS uuid AS $$
DECLARE
    v_user_id uuid;
    v_password_hash text;
BEGIN
    SELECT id, password_hash INTO v_user_id, v_password_hash
    FROM auth.users
    WHERE email = p_email;
    
    IF v_user_id IS NULL THEN
        RETURN NULL;
    END IF;
    
    IF auth.verify_password(p_password, v_password_hash) THEN
        RETURN v_user_id;
    ELSE
        RETURN NULL;
    END IF;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Função para registrar usuário
CREATE OR REPLACE FUNCTION auth.register(p_email text, p_password text)
RETURNS uuid AS $$
DECLARE
    v_user_id uuid;
BEGIN
    INSERT INTO auth.users (email, password_hash)
    VALUES (p_email, auth.hash_password(p_password))
    RETURNING id INTO v_user_id;
    
    RETURN v_user_id;
EXCEPTION
    WHEN unique_violation THEN
        RETURN NULL;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Função auxiliar auth.uid() para compatibilidade
CREATE OR REPLACE FUNCTION auth.uid()
RETURNS uuid AS $$
BEGIN
    -- Retorna o user_id do JWT claim
    RETURN NULLIF(current_setting('request.jwt.claims', true)::json->>'sub', '')::uuid;
EXCEPTION
    WHEN OTHERS THEN
        RETURN NULL;
END;
$$ LANGUAGE plpgsql STABLE;

-- Criar roles para PostgREST
DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'anon') THEN
        CREATE ROLE anon NOLOGIN;
    END IF;
    IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'authenticated') THEN
        CREATE ROLE authenticated NOLOGIN;
    END IF;
END;
$$;

-- Permissões para roles
GRANT USAGE ON SCHEMA public TO anon, authenticated;
GRANT USAGE ON SCHEMA auth TO anon, authenticated;
GRANT EXECUTE ON FUNCTION auth.login(text, text) TO anon;
GRANT EXECUTE ON FUNCTION auth.register(text, text) TO anon;
GRANT SELECT ON auth.users TO authenticated;

-- Comentário
COMMENT ON SCHEMA auth IS 'Schema de autenticação standalone para Onlifin';
