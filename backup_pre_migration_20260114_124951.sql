--
-- PostgreSQL database dump
--

\restrict AZwFacS2dAZGoJw3zF49jcqfTesrnVyBEucvaXP3pQaykiPZUbVePM0KJ1LBhbR

-- Dumped from database version 16.11
-- Dumped by pg_dump version 16.11

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: auth; Type: SCHEMA; Schema: -; Owner: onlifin
--

CREATE SCHEMA auth;


ALTER SCHEMA auth OWNER TO onlifin;

--
-- Name: SCHEMA auth; Type: COMMENT; Schema: -; Owner: onlifin
--

COMMENT ON SCHEMA auth IS 'Schema de autenticação standalone para Onlifin';


--
-- Name: pgcrypto; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pgcrypto WITH SCHEMA public;


--
-- Name: EXTENSION pgcrypto; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION pgcrypto IS 'cryptographic functions';


--
-- Name: ai_permission_level; Type: TYPE; Schema: public; Owner: onlifin
--

CREATE TYPE public.ai_permission_level AS ENUM (
    'read_aggregated',
    'read_transactional',
    'read_full'
);


ALTER TYPE public.ai_permission_level OWNER TO onlifin;

--
-- Name: transaction_type; Type: TYPE; Schema: public; Owner: onlifin
--

CREATE TYPE public.transaction_type AS ENUM (
    'income',
    'expense',
    'transfer'
);


ALTER TYPE public.transaction_type OWNER TO onlifin;

--
-- Name: user_role; Type: TYPE; Schema: public; Owner: onlifin
--

CREATE TYPE public.user_role AS ENUM (
    'user',
    'financeiro',
    'admin'
);


ALTER TYPE public.user_role OWNER TO onlifin;

--
-- Name: base64url_encode(bytea); Type: FUNCTION; Schema: auth; Owner: onlifin
--

CREATE FUNCTION auth.base64url_encode(data bytea) RETURNS text
    LANGUAGE sql IMMUTABLE
    AS $$
  SELECT translate(encode(data, 'base64'), E'+/=\n', '-_');
$$;


ALTER FUNCTION auth.base64url_encode(data bytea) OWNER TO onlifin;

--
-- Name: check_login_rate_limit(text, integer, integer); Type: FUNCTION; Schema: auth; Owner: onlifin
--

CREATE FUNCTION auth.check_login_rate_limit(p_email text, p_max_attempts integer DEFAULT 5, p_window_minutes integer DEFAULT 5) RETURNS boolean
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
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
$$;


ALTER FUNCTION auth.check_login_rate_limit(p_email text, p_max_attempts integer, p_window_minutes integer) OWNER TO onlifin;

--
-- Name: FUNCTION check_login_rate_limit(p_email text, p_max_attempts integer, p_window_minutes integer); Type: COMMENT; Schema: auth; Owner: onlifin
--

COMMENT ON FUNCTION auth.check_login_rate_limit(p_email text, p_max_attempts integer, p_window_minutes integer) IS 'Verifica se email está dentro do limite de tentativas';


--
-- Name: cleanup_old_login_attempts(); Type: FUNCTION; Schema: auth; Owner: onlifin
--

CREATE FUNCTION auth.cleanup_old_login_attempts() RETURNS void
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
BEGIN
    DELETE FROM auth.login_attempts 
    WHERE attempted_at < now() - interval '24 hours';
END;
$$;


ALTER FUNCTION auth.cleanup_old_login_attempts() OWNER TO onlifin;

--
-- Name: generate_jwt(uuid, text, text, integer); Type: FUNCTION; Schema: auth; Owner: onlifin
--

CREATE FUNCTION auth.generate_jwt(p_user_id uuid, p_email text, p_app_role text DEFAULT 'user'::text, p_exp_hours integer DEFAULT 24) RETURNS text
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
DECLARE
  v_header json;
  v_payload json;
  v_signature text;
  v_secret text;
  v_token_parts text;
BEGIN
  v_secret := 'MpeW4RhMCXAsfQV8Eat5Lh8aC1eQR89DP2YJqOmxfE/HhFZdrhUxVG2//popoeGxFJvTOaLQDZIDoWW7kJUiKg==';

  v_header := json_build_object(
    'alg', 'HS256',
    'typ', 'JWT'
  );

  -- Include user_id and email for frontend compatibility
  v_payload := json_build_object(
    'sub', p_user_id::text,
    'user_id', p_user_id::text,
    'email', p_email,
    'role', 'authenticated',
    'app_role', p_app_role,
    'iat', extract(epoch from now())::integer,
    'exp', extract(epoch from now() + (p_exp_hours || ' hours')::interval)::integer
  );

  v_token_parts := auth.base64url_encode(v_header::text::bytea) || '.' || 
                   auth.base64url_encode(v_payload::text::bytea);

  v_signature := auth.base64url_encode(
    hmac(v_token_parts, v_secret, 'sha256')
  );

  RETURN v_token_parts || '.' || v_signature;
END;
$$;


ALTER FUNCTION auth.generate_jwt(p_user_id uuid, p_email text, p_app_role text, p_exp_hours integer) OWNER TO onlifin;

--
-- Name: hash_password(text); Type: FUNCTION; Schema: auth; Owner: onlifin
--

CREATE FUNCTION auth.hash_password(password text) RETURNS text
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
BEGIN
    RETURN crypt(password, gen_salt('bf', 10));
END;
$$;


ALTER FUNCTION auth.hash_password(password text) OWNER TO onlifin;

--
-- Name: is_admin(); Type: FUNCTION; Schema: auth; Owner: onlifin
--

CREATE FUNCTION auth.is_admin() RETURNS boolean
    LANGUAGE sql STABLE
    AS $$
  SELECT auth.role() = 'admin';
$$;


ALTER FUNCTION auth.is_admin() OWNER TO onlifin;

--
-- Name: log_action(uuid, text, jsonb); Type: FUNCTION; Schema: auth; Owner: onlifin
--

CREATE FUNCTION auth.log_action(p_user_id uuid, p_action text, p_details jsonb DEFAULT NULL::jsonb) RETURNS void
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
BEGIN
    INSERT INTO auth.audit_log (user_id, action, details)
    VALUES (p_user_id, p_action, p_details);
END;
$$;


ALTER FUNCTION auth.log_action(p_user_id uuid, p_action text, p_details jsonb) OWNER TO onlifin;

--
-- Name: FUNCTION log_action(p_user_id uuid, p_action text, p_details jsonb); Type: COMMENT; Schema: auth; Owner: onlifin
--

COMMENT ON FUNCTION auth.log_action(p_user_id uuid, p_action text, p_details jsonb) IS 'Registra ação no audit log';


--
-- Name: login(text, text); Type: FUNCTION; Schema: auth; Owner: onlifin
--

CREATE FUNCTION auth.login(p_email text, p_password text) RETURNS json
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
DECLARE
    v_user_id uuid;
    v_password_hash text;
    v_user_role text;
    v_jwt_token text;
BEGIN
    -- Clean email
    p_email := lower(trim(p_email));
    
    -- Check rate limit
    IF NOT auth.check_login_rate_limit(p_email) THEN
        INSERT INTO auth.login_attempts (email, success)
        VALUES (p_email, false);
        
        RETURN json_build_object(
            'success', false,
            'error', 'Too many login attempts. Please try again later.'
        );
    END IF;
    
    -- Get user
    SELECT u.id, u.password_hash, p.role 
    INTO v_user_id, v_password_hash, v_user_role
    FROM auth.users u
    LEFT JOIN public.profiles p ON p.id = u.id
    WHERE u.email = p_email;
    
    -- User not found
    IF v_user_id IS NULL THEN
        INSERT INTO auth.login_attempts (email, success)
        VALUES (p_email, false);
        
        RETURN json_build_object(
            'success', false,
            'error', 'Invalid email or password'
        );
    END IF;
    
    -- Verify password
    IF auth.verify_password(p_password, v_password_hash) THEN
        -- Login success
        INSERT INTO auth.login_attempts (email, success)
        VALUES (p_email, true);
        
        -- Update last access
        UPDATE auth.users
        SET updated_at = now()
        WHERE id = v_user_id;
        
        -- Generate JWT token with email included
        v_jwt_token := auth.generate_jwt(v_user_id, p_email, COALESCE(v_user_role, 'user'), 24);
        
        -- Return success with token
        RETURN json_build_object(
            'success', true,
            'token', v_jwt_token,
            'user_id', v_user_id,
            'role', COALESCE(v_user_role, 'user')
        );
    ELSE
        -- Wrong password
        INSERT INTO auth.login_attempts (email, success)
        VALUES (p_email, false);
        
        RETURN json_build_object(
            'success', false,
            'error', 'Invalid email or password'
        );
    END IF;
END;
$$;


ALTER FUNCTION auth.login(p_email text, p_password text) OWNER TO onlifin;

--
-- Name: register(text, text); Type: FUNCTION; Schema: auth; Owner: onlifin
--

CREATE FUNCTION auth.register(p_email text, p_password text) RETURNS uuid
    LANGUAGE plpgsql SECURITY DEFINER
    AS $_$
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
$_$;


ALTER FUNCTION auth.register(p_email text, p_password text) OWNER TO onlifin;

--
-- Name: role(); Type: FUNCTION; Schema: auth; Owner: onlifin
--

CREATE FUNCTION auth.role() RETURNS text
    LANGUAGE sql STABLE
    AS $$
  SELECT NULLIF(current_setting('request.jwt.claims', true)::json->>'app_role', '');
$$;


ALTER FUNCTION auth.role() OWNER TO onlifin;

--
-- Name: uid(); Type: FUNCTION; Schema: auth; Owner: onlifin
--

CREATE FUNCTION auth.uid() RETURNS uuid
    LANGUAGE plpgsql STABLE
    AS $$
BEGIN
    -- Retorna o user_id do JWT claim
    RETURN NULLIF(current_setting('request.jwt.claims', true)::json->>'sub', '')::uuid;
EXCEPTION
    WHEN OTHERS THEN
        RETURN NULL;
END;
$$;


ALTER FUNCTION auth.uid() OWNER TO onlifin;

--
-- Name: user_id(); Type: FUNCTION; Schema: auth; Owner: onlifin
--

CREATE FUNCTION auth.user_id() RETURNS uuid
    LANGUAGE sql STABLE
    AS $$
  SELECT NULLIF(current_setting('request.jwt.claims', true)::json->>'sub', '')::uuid;
$$;


ALTER FUNCTION auth.user_id() OWNER TO onlifin;

--
-- Name: verify_password(text, text); Type: FUNCTION; Schema: auth; Owner: onlifin
--

CREATE FUNCTION auth.verify_password(password text, password_hash text) RETURNS boolean
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
BEGIN
    RETURN password_hash = crypt(password, password_hash);
END;
$$;


ALTER FUNCTION auth.verify_password(password text, password_hash text) OWNER TO onlifin;

--
-- Name: admin_create_user(text, text, text, text); Type: FUNCTION; Schema: public; Owner: onlifin
--

CREATE FUNCTION public.admin_create_user(p_username text, p_password text, p_full_name text DEFAULT NULL::text, p_role text DEFAULT 'user'::text) RETURNS json
    LANGUAGE plpgsql SECURITY DEFINER
    SET search_path TO 'public', 'auth'
    AS $$
DECLARE
    v_user_id uuid;
    v_role_enum user_role;
    v_email text;
BEGIN
    -- Check if caller is admin
    IF current_setting('request.jwt.claims', true)::json->>'app_role' != 'admin' THEN
        RETURN json_build_object('success', false, 'error', 'Acesso negado.');
    END IF;

    -- Treat username as email if it has @
    v_email := lower(trim(p_username));
    
    IF position('@' in v_email) = 0 THEN
        v_email := v_email || '@onlifin.com';
    END IF;

    IF length(p_password) < 6 THEN
        RETURN json_build_object('success', false, 'error', 'Senha curta demais.');
    END IF;

    IF EXISTS (SELECT 1 FROM auth.users WHERE email = v_email) THEN
        RETURN json_build_object('success', false, 'error', 'Usuário já existe.');
    END IF;

    INSERT INTO auth.users (email, password_hash)
    VALUES (v_email, auth.hash_password(p_password))
    RETURNING id INTO v_user_id;

    INSERT INTO profiles (id, username, full_name, role)
    VALUES (v_user_id, split_part(v_email, '@', 1), COALESCE(p_full_name, split_part(v_email, '@', 1)), p_role::user_role);

    RETURN json_build_object(
        'success', true,
        'user_id', v_user_id,
        'message', 'Usuário ' || v_email || ' criado!'
    );
END;
$$;


ALTER FUNCTION public.admin_create_user(p_username text, p_password text, p_full_name text, p_role text) OWNER TO onlifin;

--
-- Name: admin_delete_user(uuid); Type: FUNCTION; Schema: public; Owner: onlifin
--

CREATE FUNCTION public.admin_delete_user(p_user_id uuid) RETURNS json
    LANGUAGE plpgsql SECURITY DEFINER
    SET search_path TO 'public', 'auth'
    AS $$
BEGIN
    IF current_setting('request.jwt.claims', true)::json->>'app_role' != 'admin' THEN
        RETURN json_build_object('success', false, 'error', 'Acesso negado.');
    END IF;

    DELETE FROM auth.users WHERE id = p_user_id;
    -- Profiles será deletado automaticamente se houver FK com CASCADE, 
    -- caso contrário deletamos manualmente:
    DELETE FROM public.profiles WHERE id = p_user_id;

    RETURN json_build_object('success', true, 'message', 'Usuário removido.');
END;
$$;


ALTER FUNCTION public.admin_delete_user(p_user_id uuid) OWNER TO onlifin;

--
-- Name: admin_list_users(); Type: FUNCTION; Schema: public; Owner: onlifin
--

CREATE FUNCTION public.admin_list_users() RETURNS TABLE(id uuid, email text, username text, full_name text, role public.user_role, created_at timestamp with time zone)
    LANGUAGE plpgsql SECURITY DEFINER
    SET search_path TO 'public', 'auth'
    AS $$
BEGIN
    IF current_setting('request.jwt.claims', true)::json->>'app_role' != 'admin' THEN
        RAISE EXCEPTION 'Acesso negado.';
    END IF;

    RETURN QUERY
    SELECT 
        u.id,
        u.email::text,
        p.username,
        p.full_name,
        p.role,
        u.created_at
    FROM auth.users u
    JOIN public.profiles p ON u.id = p.id
    ORDER BY u.created_at DESC;
END;
$$;


ALTER FUNCTION public.admin_list_users() OWNER TO onlifin;

--
-- Name: admin_reset_password(uuid, text); Type: FUNCTION; Schema: public; Owner: onlifin
--

CREATE FUNCTION public.admin_reset_password(p_user_id uuid, p_new_password text) RETURNS json
    LANGUAGE plpgsql SECURITY DEFINER
    SET search_path TO 'public', 'auth'
    AS $$
BEGIN
    IF current_setting('request.jwt.claims', true)::json->>'app_role' != 'admin' THEN
        RETURN json_build_object('success', false, 'error', 'Acesso negado.');
    END IF;

    IF length(p_new_password) < 6 THEN
        RETURN json_build_object('success', false, 'error', 'Senha muito curta.');
    END IF;

    UPDATE auth.users 
    SET password_hash = auth.hash_password(p_new_password), 
        updated_at = now() 
    WHERE id = p_user_id;

    RETURN json_build_object('success', true, 'message', 'Senha alterada com sucesso.');
END;
$$;


ALTER FUNCTION public.admin_reset_password(p_user_id uuid, p_new_password text) OWNER TO onlifin;

--
-- Name: admin_update_user(uuid, text, text, text); Type: FUNCTION; Schema: public; Owner: onlifin
--

CREATE FUNCTION public.admin_update_user(p_user_id uuid, p_email text, p_full_name text, p_role text) RETURNS json
    LANGUAGE plpgsql SECURITY DEFINER
    SET search_path TO 'public', 'auth'
    AS $$
BEGIN
    IF current_setting('request.jwt.claims', true)::json->>'app_role' != 'admin' THEN
        RETURN json_build_object('success', false, 'error', 'Acesso negado.');
    END IF;

    -- Atualizar Auth
    UPDATE auth.users SET email = lower(trim(p_email)), updated_at = now() WHERE id = p_user_id;
    
    -- Atualizar Profile
    UPDATE public.profiles SET 
        full_name = p_full_name,
        role = p_role::user_role,
        username = split_part(p_email, '@', 1)
    WHERE id = p_user_id;

    RETURN json_build_object('success', true, 'message', 'Usuário atualizado com sucesso.');
END;
$$;


ALTER FUNCTION public.admin_update_user(p_user_id uuid, p_email text, p_full_name text, p_role text) OWNER TO onlifin;

--
-- Name: create_notification(uuid, text, text, text, text, uuid, uuid, text); Type: FUNCTION; Schema: public; Owner: onlifin
--

CREATE FUNCTION public.create_notification(p_user_id uuid, p_title text, p_message text, p_type text, p_severity text DEFAULT NULL::text, p_related_forecast_id uuid DEFAULT NULL::uuid, p_related_bill_id uuid DEFAULT NULL::uuid, p_action_url text DEFAULT NULL::text) RETURNS uuid
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
DECLARE
  v_notification_id uuid;
BEGIN
  INSERT INTO notifications (
    user_id,
    title,
    message,
    type,
    severity,
    related_forecast_id,
    related_bill_id,
    action_url
  ) VALUES (
    p_user_id,
    p_title,
    p_message,
    p_type,
    p_severity,
    p_related_forecast_id,
    p_related_bill_id,
    p_action_url
  )
  RETURNING id INTO v_notification_id;
  
  RETURN v_notification_id;
END;
$$;


ALTER FUNCTION public.create_notification(p_user_id uuid, p_title text, p_message text, p_type text, p_severity text, p_related_forecast_id uuid, p_related_bill_id uuid, p_action_url text) OWNER TO onlifin;

--
-- Name: create_transfer(uuid, uuid, uuid, numeric, date, text); Type: FUNCTION; Schema: public; Owner: onlifin
--

CREATE FUNCTION public.create_transfer(p_user_id uuid, p_source_account_id uuid, p_destination_account_id uuid, p_amount numeric, p_date date, p_description text) RETURNS json
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
DECLARE
  v_source_transaction_id uuid;
  v_destination_transaction_id uuid;
  v_source_account accounts;
  v_destination_account accounts;
BEGIN
  -- Validate that both accounts exist and belong to the user
  SELECT * INTO v_source_account FROM accounts WHERE id = p_source_account_id AND user_id = p_user_id;
  SELECT * INTO v_destination_account FROM accounts WHERE id = p_destination_account_id AND user_id = p_user_id;
  
  IF v_source_account.id IS NULL THEN
    RAISE EXCEPTION 'Conta de origem não encontrada ou não pertence ao usuário';
  END IF;
  
  IF v_destination_account.id IS NULL THEN
    RAISE EXCEPTION 'Conta de destino não encontrada ou não pertence ao usuário';
  END IF;
  
  IF p_source_account_id = p_destination_account_id THEN
    RAISE EXCEPTION 'Não é possível transferir para a mesma conta';
  END IF;
  
  IF p_amount <= 0 THEN
    RAISE EXCEPTION 'O valor da transferência deve ser maior que zero';
  END IF;
  
  -- Create source transaction (expense - money going out)
  INSERT INTO transactions (
    user_id,
    account_id,
    type,
    amount,
    date,
    description,
    is_transfer,
    transfer_destination_account_id,
    is_reconciled
  ) VALUES (
    p_user_id,
    p_source_account_id,
    'expense',
    p_amount,
    p_date,
    p_description,
    true,
    p_destination_account_id,
    true
  ) RETURNING id INTO v_source_transaction_id;
  
  -- Create destination transaction (income - money coming in)
  INSERT INTO transactions (
    user_id,
    account_id,
    type,
    amount,
    date,
    description,
    is_transfer,
    parent_transaction_id,
    is_reconciled
  ) VALUES (
    p_user_id,
    p_destination_account_id,
    'income',
    p_amount,
    p_date,
    p_description,
    true,
    v_source_transaction_id,
    true
  ) RETURNING id INTO v_destination_transaction_id;
  
  -- Update source transaction with destination transaction id
  UPDATE transactions 
  SET parent_transaction_id = v_destination_transaction_id 
  WHERE id = v_source_transaction_id;
  
  -- Return both transaction IDs
  RETURN json_build_object(
    'source_transaction_id', v_source_transaction_id,
    'destination_transaction_id', v_destination_transaction_id,
    'success', true
  );
END;
$$;


ALTER FUNCTION public.create_transfer(p_user_id uuid, p_source_account_id uuid, p_destination_account_id uuid, p_amount numeric, p_date date, p_description text) OWNER TO onlifin;

--
-- Name: FUNCTION create_transfer(p_user_id uuid, p_source_account_id uuid, p_destination_account_id uuid, p_amount numeric, p_date date, p_description text); Type: COMMENT; Schema: public; Owner: onlifin
--

COMMENT ON FUNCTION public.create_transfer(p_user_id uuid, p_source_account_id uuid, p_destination_account_id uuid, p_amount numeric, p_date date, p_description text) IS 'Cria uma transferência entre duas contas do usuário';


--
-- Name: delete_associated_transaction(); Type: FUNCTION; Schema: public; Owner: onlifin
--

CREATE FUNCTION public.delete_associated_transaction() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
  IF OLD.transaction_id IS NOT NULL THEN
    DELETE FROM transactions WHERE id = OLD.transaction_id;
  END IF;
  RETURN OLD;
END;
$$;


ALTER FUNCTION public.delete_associated_transaction() OWNER TO onlifin;

--
-- Name: get_transfer_pair(uuid); Type: FUNCTION; Schema: public; Owner: onlifin
--

CREATE FUNCTION public.get_transfer_pair(p_transaction_id uuid) RETURNS TABLE(source_transaction_id uuid, destination_transaction_id uuid, source_account_id uuid, destination_account_id uuid, amount numeric, date date, description text)
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
BEGIN
  RETURN QUERY
  SELECT 
    CASE 
      WHEN t1.type = 'expense' THEN t1.id
      ELSE t2.id
    END as source_transaction_id,
    CASE 
      WHEN t1.type = 'income' THEN t1.id
      ELSE t2.id
    END as destination_transaction_id,
    CASE 
      WHEN t1.type = 'expense' THEN t1.account_id
      ELSE t2.account_id
    END as source_account_id,
    CASE 
      WHEN t1.type = 'income' THEN t1.account_id
      ELSE t2.account_id
    END as destination_account_id,
    t1.amount,
    t1.date,
    t1.description
  FROM transactions t1
  LEFT JOIN transactions t2 ON (
    t1.parent_transaction_id = t2.id OR t2.parent_transaction_id = t1.id
  )
  WHERE t1.id = p_transaction_id AND t1.is_transfer = true;
END;
$$;


ALTER FUNCTION public.get_transfer_pair(p_transaction_id uuid) OWNER TO onlifin;

--
-- Name: FUNCTION get_transfer_pair(p_transaction_id uuid); Type: COMMENT; Schema: public; Owner: onlifin
--

COMMENT ON FUNCTION public.get_transfer_pair(p_transaction_id uuid) IS 'Retorna os detalhes completos de uma transferência';


--
-- Name: get_user_total_balance(uuid); Type: FUNCTION; Schema: public; Owner: onlifin
--

CREATE FUNCTION public.get_user_total_balance(p_user_id uuid) RETURNS numeric
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
DECLARE
  v_total_balance numeric;
BEGIN
  SELECT COALESCE(SUM(balance), 0)
  INTO v_total_balance
  FROM accounts
  WHERE user_id = p_user_id;
  
  RETURN v_total_balance;
END;
$$;


ALTER FUNCTION public.get_user_total_balance(p_user_id uuid) OWNER TO onlifin;

--
-- Name: handle_bill_payment(); Type: FUNCTION; Schema: public; Owner: onlifin
--

CREATE FUNCTION public.handle_bill_payment() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
  v_transaction_id UUID;
BEGIN
  -- When bill is marked as paid, create transaction
  IF NEW.status = 'paid' AND (OLD.status IS NULL OR OLD.status != 'paid') AND NEW.transaction_id IS NULL THEN
    INSERT INTO transactions (
      user_id, account_id, category_id, type, amount, date, description, is_reconciled
    ) VALUES (
      NEW.user_id,
      NEW.account_id,
      NEW.category_id,
      'expense',
      NEW.amount,
      COALESCE(NEW.paid_date, NEW.due_date),
      NEW.description,
      true
    ) RETURNING id INTO v_transaction_id;
    
    NEW.transaction_id := v_transaction_id;
    
  -- When bill is unmarked as paid, delete transaction
  ELSIF NEW.status != 'paid' AND OLD.status = 'paid' AND NEW.transaction_id IS NOT NULL THEN
    DELETE FROM transactions WHERE id = NEW.transaction_id;
    NEW.transaction_id := NULL;
    
  -- When paid bill is updated, update transaction
  ELSIF NEW.status = 'paid' AND OLD.status = 'paid' AND NEW.transaction_id IS NOT NULL THEN
    UPDATE transactions
    SET account_id = NEW.account_id,
        category_id = NEW.category_id,
        amount = NEW.amount,
        date = COALESCE(NEW.paid_date, NEW.due_date),
        description = NEW.description
    WHERE id = NEW.transaction_id;
  END IF;
  
  RETURN NEW;
END;
$$;


ALTER FUNCTION public.handle_bill_payment() OWNER TO onlifin;

--
-- Name: handle_bill_receipt(); Type: FUNCTION; Schema: public; Owner: onlifin
--

CREATE FUNCTION public.handle_bill_receipt() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
  v_transaction_id UUID;
BEGIN
  -- When bill is marked as received, create transaction
  IF NEW.status = 'received' AND (OLD.status IS NULL OR OLD.status != 'received') AND NEW.transaction_id IS NULL THEN
    INSERT INTO transactions (
      user_id, account_id, category_id, type, amount, date, description, is_reconciled
    ) VALUES (
      NEW.user_id,
      NEW.account_id,
      NEW.category_id,
      'income',
      NEW.amount,
      COALESCE(NEW.received_date, NEW.due_date),
      NEW.description,
      true
    ) RETURNING id INTO v_transaction_id;
    
    NEW.transaction_id := v_transaction_id;
    
  -- When bill is unmarked as received, delete transaction
  ELSIF NEW.status != 'received' AND OLD.status = 'received' AND NEW.transaction_id IS NOT NULL THEN
    DELETE FROM transactions WHERE id = NEW.transaction_id;
    NEW.transaction_id := NULL;
    
  -- When received bill is updated, update transaction
  ELSIF NEW.status = 'received' AND OLD.status = 'received' AND NEW.transaction_id IS NOT NULL THEN
    UPDATE transactions
    SET account_id = NEW.account_id,
        category_id = NEW.category_id,
        amount = NEW.amount,
        date = COALESCE(NEW.received_date, NEW.due_date),
        description = NEW.description
    WHERE id = NEW.transaction_id;
  END IF;
  
  RETURN NEW;
END;
$$;


ALTER FUNCTION public.handle_bill_receipt() OWNER TO onlifin;

--
-- Name: is_admin(uuid); Type: FUNCTION; Schema: public; Owner: onlifin
--

CREATE FUNCTION public.is_admin(uid uuid) RETURNS boolean
    LANGUAGE sql SECURITY DEFINER
    AS $$ SELECT EXISTS ( SELECT 1 FROM profiles p WHERE p.id = uid AND p.role = 'admin'::user_role ); $$;


ALTER FUNCTION public.is_admin(uid uuid) OWNER TO onlifin;

--
-- Name: login(text, text); Type: FUNCTION; Schema: public; Owner: onlifin
--

CREATE FUNCTION public.login(p_email text, p_password text) RETURNS text
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
DECLARE
    v_auth_result json;
    v_token text;
BEGIN
    -- Call auth.login which returns JSON
    v_auth_result := auth.login(p_email, p_password);
    
    -- Check if login was successful
    IF (v_auth_result->>'success')::boolean = true THEN
        -- Return ONLY the token for frontend compatibility
        v_token := v_auth_result->>'token';
        RETURN v_token;
    ELSE
        -- Return NULL on failure (frontend expects this)
        RAISE EXCEPTION 'Credenciais inválidas';
    END IF;
END;
$$;


ALTER FUNCTION public.login(p_email text, p_password text) OWNER TO onlifin;

--
-- Name: onlifin_auth_token(); Type: FUNCTION; Schema: public; Owner: onlifin
--

CREATE FUNCTION public.onlifin_auth_token() RETURNS json
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
DECLARE
    v_email text;
    v_password text;
    v_result json;
BEGIN
    v_email := current_setting('request.jwt.claims', true)::json->>'email';
    -- Esta função é apenas um placeholder para manter a estrutura do app
    RETURN json_build_object('access_token', 'temp', 'user', json_build_object('id', 'temp'));
END;
$$;


ALTER FUNCTION public.onlifin_auth_token() OWNER TO onlifin;

--
-- Name: onlifin_auth_user(); Type: FUNCTION; Schema: public; Owner: onlifin
--

CREATE FUNCTION public.onlifin_auth_user() RETURNS json
    LANGUAGE plpgsql SECURITY DEFINER
    SET search_path TO 'public', 'auth'
    AS $$
DECLARE
    v_claims json;
    v_user_id uuid;
    v_user_data json;
BEGIN
    v_claims := current_setting('request.jwt.claims', true)::json;
    v_user_id := (v_claims->>'sub')::uuid;

    IF v_user_id IS NULL THEN
        RAISE EXCEPTION 'Não autenticado';
    END IF;

    SELECT json_build_object(
        'id', u.id,
        'email', u.email,
        'app_metadata', json_build_object('provider', 'onlifin', 'role', p.role),
        'user_metadata', json_build_object('full_name', p.full_name, 'username', p.username),
        'aud', 'authenticated',
        'created_at', u.created_at
    ) INTO v_user_data
    FROM auth.users u
    JOIN public.profiles p ON u.id = p.id
    WHERE u.id = v_user_id;

    RETURN v_user_data;
END;
$$;


ALTER FUNCTION public.onlifin_auth_user() OWNER TO onlifin;

--
-- Name: recalculate_account_balance(uuid); Type: FUNCTION; Schema: public; Owner: onlifin
--

CREATE FUNCTION public.recalculate_account_balance(account_uuid uuid) RETURNS numeric
    LANGUAGE plpgsql
    AS $$
DECLARE
  new_balance NUMERIC := 0;
  income_total NUMERIC := 0;
  expense_total NUMERIC := 0;
  v_initial_balance NUMERIC := 0;
BEGIN
  -- Get initial balance
  SELECT initial_balance INTO v_initial_balance
  FROM accounts
  WHERE id = account_uuid;
  
  -- Calculate total income
  SELECT COALESCE(SUM(amount), 0)
  INTO income_total
  FROM transactions
  WHERE account_id = account_uuid
    AND type = 'income';
  
  -- Calculate total expenses
  SELECT COALESCE(SUM(amount), 0)
  INTO expense_total
  FROM transactions
  WHERE account_id = account_uuid
    AND type = 'expense';
  
  -- Calculate new balance: initial + income - expenses
  new_balance := v_initial_balance + income_total - expense_total;
  
  -- Update account balance
  UPDATE accounts
  SET balance = new_balance,
      updated_at = NOW()
  WHERE id = account_uuid;
  
  RETURN new_balance;
END;
$$;


ALTER FUNCTION public.recalculate_account_balance(account_uuid uuid) OWNER TO onlifin;

--
-- Name: recalculate_all_account_balances(uuid); Type: FUNCTION; Schema: public; Owner: onlifin
--

CREATE FUNCTION public.recalculate_all_account_balances(user_uuid uuid) RETURNS TABLE(account_id uuid, old_balance numeric, new_balance numeric)
    LANGUAGE plpgsql
    AS $$
DECLARE
  account_record RECORD;
  calculated_balance NUMERIC;
BEGIN
  FOR account_record IN
    SELECT id, balance FROM accounts WHERE user_id = user_uuid
  LOOP
    calculated_balance := recalculate_account_balance(account_record.id);
    
    account_id := account_record.id;
    old_balance := account_record.balance;
    new_balance := calculated_balance;
    
    RETURN NEXT;
  END LOOP;
END;
$$;


ALTER FUNCTION public.recalculate_all_account_balances(user_uuid uuid) OWNER TO onlifin;

--
-- Name: register(text, text); Type: FUNCTION; Schema: public; Owner: onlifin
--

CREATE FUNCTION public.register(p_email text, p_password text) RETURNS uuid
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$ BEGIN RETURN auth.register(p_email, p_password); END; $$;


ALTER FUNCTION public.register(p_email text, p_password text) OWNER TO onlifin;

--
-- Name: sign(json, text); Type: FUNCTION; Schema: public; Owner: onlifin
--

CREATE FUNCTION public.sign(payload json, secret text) RETURNS text
    LANGUAGE plpgsql
    AS $$
DECLARE
    header text;
    payload_encoded text;
    signature text;
    secret_bytes bytea;
    data_bytes bytea;
BEGIN
    header := url_encode_nopad(convert_to('{"alg":"HS256","typ":"JWT"}', 'utf8'));
    payload_encoded := url_encode_nopad(convert_to(payload::text, 'utf8'));
    data_bytes := convert_to(header || '.' || payload_encoded, 'utf8');
    
    -- USAR SECRET COMO TEXTO PURO (não decodificar base64)
    secret_bytes := convert_to(secret, 'utf8');
    
    signature := url_encode_nopad(hmac(data_bytes, secret_bytes, 'sha256'));
    
    RETURN header || '.' || payload_encoded || '.' || signature;
END;
$$;


ALTER FUNCTION public.sign(payload json, secret text) OWNER TO onlifin;

--
-- Name: supabase_auth_token(text, text, text); Type: FUNCTION; Schema: public; Owner: onlifin
--

CREATE FUNCTION public.supabase_auth_token(p_email text DEFAULT NULL::text, p_password text DEFAULT NULL::text, p_grant_type text DEFAULT 'password'::text) RETURNS json
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
DECLARE
    v_result json;
    v_user_id uuid;
    v_user_role text;
    v_jwt_token text;
BEGIN
    -- Handle password grant type
    IF p_grant_type = 'password' THEN
        -- Call existing login function
        v_result := auth.login(p_email, p_password);
        
        IF v_result->>'success' = 'true' THEN
            v_user_id := (v_result->>'user_id')::uuid;
            v_jwt_token := v_result->>'token';
            v_user_role := v_result->>'role';
            
            -- Return Supabase-compatible response
            RETURN json_build_object(
                'access_token', v_jwt_token,
                'token_type', 'bearer',
                'expires_in', 86400,
                'refresh_token', v_jwt_token,
                'user', json_build_object(
                    'id', v_user_id,
                    'aud', 'authenticated',
                    'role', v_user_role,
                    'email', p_email,
                    'email_confirmed_at', now(),
                    'created_at', now(),
                    'updated_at', now(),
                    'app_metadata', json_build_object('provider', 'email'),
                    'user_metadata', '{}'::json
                )
            );
        ELSE
            -- Return error in Supabase format
            RETURN json_build_object(
                'error', 'invalid_grant',
                'error_description', v_result->>'error'
            );
        END IF;
    ELSE
        RETURN json_build_object(
            'error', 'unsupported_grant_type',
            'error_description', 'Only password grant type is supported'
        );
    END IF;
END;
$$;


ALTER FUNCTION public.supabase_auth_token(p_email text, p_password text, p_grant_type text) OWNER TO onlifin;

--
-- Name: update_account_balance_on_transaction(); Type: FUNCTION; Schema: public; Owner: onlifin
--

CREATE FUNCTION public.update_account_balance_on_transaction() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
  old_balance_change NUMERIC := 0;
  new_balance_change NUMERIC := 0;
BEGIN
  -- Calculate old balance change (for UPDATE and DELETE)
  IF TG_OP = 'UPDATE' OR TG_OP = 'DELETE' THEN
    IF OLD.account_id IS NOT NULL THEN
      IF OLD.type = 'income' THEN
        old_balance_change := -OLD.amount; -- Reverse the income
      ELSE
        old_balance_change := OLD.amount; -- Reverse the expense
      END IF;
      
      -- Apply old balance change
      UPDATE accounts
      SET balance = balance + old_balance_change,
          updated_at = NOW()
      WHERE id = OLD.account_id;
    END IF;
  END IF;
  
  -- Calculate new balance change (for INSERT and UPDATE)
  IF TG_OP = 'INSERT' OR TG_OP = 'UPDATE' THEN
    IF NEW.account_id IS NOT NULL THEN
      IF NEW.type = 'income' THEN
        new_balance_change := NEW.amount; -- Add income
      ELSE
        new_balance_change := -NEW.amount; -- Subtract expense
      END IF;
      
      -- Apply new balance change
      UPDATE accounts
      SET balance = balance + new_balance_change,
          updated_at = NOW()
      WHERE id = NEW.account_id;
    END IF;
  END IF;
  
  -- Return appropriate value based on operation
  IF TG_OP = 'DELETE' THEN
    RETURN OLD;
  ELSE
    RETURN NEW;
  END IF;
END;
$$;


ALTER FUNCTION public.update_account_balance_on_transaction() OWNER TO onlifin;

--
-- Name: update_balance_on_initial_balance_change(); Type: FUNCTION; Schema: public; Owner: onlifin
--

CREATE FUNCTION public.update_balance_on_initial_balance_change() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
  IF NEW.initial_balance != OLD.initial_balance THEN
    NEW.balance := NEW.balance + (NEW.initial_balance - OLD.initial_balance);
  END IF;
  RETURN NEW;
END;
$$;


ALTER FUNCTION public.update_balance_on_initial_balance_change() OWNER TO onlifin;

--
-- Name: update_bills_status(); Type: FUNCTION; Schema: public; Owner: onlifin
--

CREATE FUNCTION public.update_bills_status() RETURNS void
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
BEGIN
  -- Update bills_to_pay status to overdue
  UPDATE bills_to_pay
  SET status = 'overdue', updated_at = now()
  WHERE status = 'pending' 
    AND due_date < CURRENT_DATE;

  -- Update bills_to_receive status to overdue
  UPDATE bills_to_receive
  SET status = 'overdue', updated_at = now()
  WHERE status = 'pending' 
    AND due_date < CURRENT_DATE;
END;
$$;


ALTER FUNCTION public.update_bills_status() OWNER TO onlifin;

--
-- Name: url_encode(bytea); Type: FUNCTION; Schema: public; Owner: onlifin
--

CREATE FUNCTION public.url_encode(data bytea) RETURNS text
    LANGUAGE sql
    AS $$
    SELECT translate(encode(data, 'base64'), E'+/\n', '-_');
$$;


ALTER FUNCTION public.url_encode(data bytea) OWNER TO onlifin;

--
-- Name: url_encode_nopad(bytea); Type: FUNCTION; Schema: public; Owner: onlifin
--

CREATE FUNCTION public.url_encode_nopad(data bytea) RETURNS text
    LANGUAGE sql
    AS $$
    SELECT rtrim(translate(encode(data, 'base64'), E'+/\n', '-_'), '=');
$$;


ALTER FUNCTION public.url_encode_nopad(data bytea) OWNER TO onlifin;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: audit_log; Type: TABLE; Schema: auth; Owner: onlifin
--

CREATE TABLE auth.audit_log (
    id bigint NOT NULL,
    user_id uuid,
    action text NOT NULL,
    details jsonb,
    ip_address text,
    user_agent text,
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE auth.audit_log OWNER TO onlifin;

--
-- Name: TABLE audit_log; Type: COMMENT; Schema: auth; Owner: onlifin
--

COMMENT ON TABLE auth.audit_log IS 'Log de auditoria para ações sensíveis';


--
-- Name: audit_log_id_seq; Type: SEQUENCE; Schema: auth; Owner: onlifin
--

CREATE SEQUENCE auth.audit_log_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE auth.audit_log_id_seq OWNER TO onlifin;

--
-- Name: audit_log_id_seq; Type: SEQUENCE OWNED BY; Schema: auth; Owner: onlifin
--

ALTER SEQUENCE auth.audit_log_id_seq OWNED BY auth.audit_log.id;


--
-- Name: login_attempts; Type: TABLE; Schema: auth; Owner: onlifin
--

CREATE TABLE auth.login_attempts (
    id integer NOT NULL,
    email text NOT NULL,
    ip_address text,
    success boolean DEFAULT false NOT NULL,
    attempted_at timestamp with time zone DEFAULT now()
);


ALTER TABLE auth.login_attempts OWNER TO onlifin;

--
-- Name: TABLE login_attempts; Type: COMMENT; Schema: auth; Owner: onlifin
--

COMMENT ON TABLE auth.login_attempts IS 'Registro de tentativas de login para rate limiting';


--
-- Name: login_attempts_id_seq; Type: SEQUENCE; Schema: auth; Owner: onlifin
--

CREATE SEQUENCE auth.login_attempts_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE auth.login_attempts_id_seq OWNER TO onlifin;

--
-- Name: login_attempts_id_seq; Type: SEQUENCE OWNED BY; Schema: auth; Owner: onlifin
--

ALTER SEQUENCE auth.login_attempts_id_seq OWNED BY auth.login_attempts.id;


--
-- Name: users; Type: TABLE; Schema: auth; Owner: onlifin
--

CREATE TABLE auth.users (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    email text NOT NULL,
    password_hash text NOT NULL,
    email_confirmed_at timestamp with time zone DEFAULT now(),
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    is_active boolean DEFAULT true,
    failed_login_count integer DEFAULT 0,
    locked_until timestamp with time zone
);


ALTER TABLE auth.users OWNER TO onlifin;

--
-- Name: accounts; Type: TABLE; Schema: public; Owner: onlifin
--

CREATE TABLE public.accounts (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    user_id uuid NOT NULL,
    name text NOT NULL,
    bank text,
    agency text,
    account_number text,
    currency text DEFAULT 'BRL'::text NOT NULL,
    balance numeric DEFAULT 0 NOT NULL,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    initial_balance numeric DEFAULT 0 NOT NULL,
    icon text
);


ALTER TABLE public.accounts OWNER TO onlifin;

--
-- Name: ai_chat_logs; Type: TABLE; Schema: public; Owner: onlifin
--

CREATE TABLE public.ai_chat_logs (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    user_id uuid NOT NULL,
    message text NOT NULL,
    response text,
    data_accessed jsonb,
    permission_level public.ai_permission_level NOT NULL,
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE public.ai_chat_logs OWNER TO onlifin;

--
-- Name: ai_configurations; Type: TABLE; Schema: public; Owner: onlifin
--

CREATE TABLE public.ai_configurations (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    model_name text NOT NULL,
    endpoint text,
    permission_level public.ai_permission_level DEFAULT 'read_aggregated'::public.ai_permission_level NOT NULL,
    can_write_transactions boolean DEFAULT false,
    is_active boolean DEFAULT true,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now()
);


ALTER TABLE public.ai_configurations OWNER TO onlifin;

--
-- Name: bills_to_pay; Type: TABLE; Schema: public; Owner: onlifin
--

CREATE TABLE public.bills_to_pay (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    user_id uuid NOT NULL,
    description text NOT NULL,
    amount numeric NOT NULL,
    due_date date NOT NULL,
    category_id uuid,
    status text DEFAULT 'pending'::text NOT NULL,
    is_recurring boolean DEFAULT false,
    recurrence_pattern text,
    account_id uuid,
    paid_date date,
    notes text,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    transaction_id uuid,
    CONSTRAINT bills_to_pay_amount_check CHECK ((amount > (0)::numeric)),
    CONSTRAINT bills_to_pay_status_check CHECK ((status = ANY (ARRAY['pending'::text, 'paid'::text, 'overdue'::text])))
);


ALTER TABLE public.bills_to_pay OWNER TO onlifin;

--
-- Name: bills_to_receive; Type: TABLE; Schema: public; Owner: onlifin
--

CREATE TABLE public.bills_to_receive (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    user_id uuid NOT NULL,
    description text NOT NULL,
    amount numeric NOT NULL,
    due_date date NOT NULL,
    category_id uuid,
    status text DEFAULT 'pending'::text NOT NULL,
    is_recurring boolean DEFAULT false,
    recurrence_pattern text,
    account_id uuid,
    received_date date,
    notes text,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    transaction_id uuid,
    CONSTRAINT bills_to_receive_amount_check CHECK ((amount > (0)::numeric)),
    CONSTRAINT bills_to_receive_status_check CHECK ((status = ANY (ARRAY['pending'::text, 'received'::text, 'overdue'::text])))
);


ALTER TABLE public.bills_to_receive OWNER TO onlifin;

--
-- Name: cards; Type: TABLE; Schema: public; Owner: onlifin
--

CREATE TABLE public.cards (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    user_id uuid NOT NULL,
    account_id uuid,
    name text NOT NULL,
    card_limit numeric NOT NULL,
    available_limit numeric DEFAULT 0,
    closing_day integer,
    due_day integer,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    icon text,
    brand text,
    CONSTRAINT cards_closing_day_check CHECK (((closing_day >= 1) AND (closing_day <= 31))),
    CONSTRAINT cards_due_day_check CHECK (((due_day >= 1) AND (due_day <= 31)))
);


ALTER TABLE public.cards OWNER TO onlifin;

--
-- Name: categories; Type: TABLE; Schema: public; Owner: onlifin
--

CREATE TABLE public.categories (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    user_id uuid,
    name text NOT NULL,
    type public.transaction_type NOT NULL,
    icon text,
    color text,
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE public.categories OWNER TO onlifin;

--
-- Name: financial_forecasts; Type: TABLE; Schema: public; Owner: onlifin
--

CREATE TABLE public.financial_forecasts (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    user_id uuid NOT NULL,
    calculation_date timestamp with time zone DEFAULT now() NOT NULL,
    initial_balance numeric NOT NULL,
    forecast_daily jsonb DEFAULT '{}'::jsonb NOT NULL,
    forecast_weekly jsonb DEFAULT '{}'::jsonb NOT NULL,
    forecast_monthly jsonb DEFAULT '{}'::jsonb NOT NULL,
    insights jsonb DEFAULT '[]'::jsonb NOT NULL,
    alerts jsonb DEFAULT '[]'::jsonb NOT NULL,
    risk_negative boolean DEFAULT false,
    risk_date date,
    spending_patterns jsonb DEFAULT '{}'::jsonb,
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE public.financial_forecasts OWNER TO onlifin;

--
-- Name: import_history; Type: TABLE; Schema: public; Owner: onlifin
--

CREATE TABLE public.import_history (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    user_id uuid NOT NULL,
    filename text NOT NULL,
    format text NOT NULL,
    status text NOT NULL,
    imported_count integer DEFAULT 0,
    error_message text,
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE public.import_history OWNER TO onlifin;

--
-- Name: notifications; Type: TABLE; Schema: public; Owner: onlifin
--

CREATE TABLE public.notifications (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    user_id uuid NOT NULL,
    title text NOT NULL,
    message text NOT NULL,
    type text NOT NULL,
    severity text,
    is_read boolean DEFAULT false,
    related_forecast_id uuid,
    related_bill_id uuid,
    action_url text,
    created_at timestamp with time zone DEFAULT now(),
    CONSTRAINT notifications_severity_check CHECK ((severity = ANY (ARRAY['low'::text, 'medium'::text, 'high'::text]))),
    CONSTRAINT notifications_type_check CHECK ((type = ANY (ARRAY['alert'::text, 'info'::text, 'warning'::text, 'success'::text])))
);


ALTER TABLE public.notifications OWNER TO onlifin;

--
-- Name: profiles; Type: TABLE; Schema: public; Owner: onlifin
--

CREATE TABLE public.profiles (
    id uuid NOT NULL,
    username text NOT NULL,
    full_name text,
    role public.user_role DEFAULT 'user'::public.user_role NOT NULL,
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE public.profiles OWNER TO onlifin;

--
-- Name: transactions; Type: TABLE; Schema: public; Owner: onlifin
--

CREATE TABLE public.transactions (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    user_id uuid NOT NULL,
    account_id uuid,
    card_id uuid,
    category_id uuid,
    type public.transaction_type NOT NULL,
    amount numeric NOT NULL,
    date date NOT NULL,
    description text,
    tags text[],
    is_recurring boolean DEFAULT false,
    recurrence_pattern text,
    is_installment boolean DEFAULT false,
    installment_number integer,
    total_installments integer,
    parent_transaction_id uuid,
    is_reconciled boolean DEFAULT false,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    transfer_destination_account_id uuid,
    is_transfer boolean DEFAULT false
);


ALTER TABLE public.transactions OWNER TO onlifin;

--
-- Name: uploaded_statements; Type: TABLE; Schema: public; Owner: onlifin
--

CREATE TABLE public.uploaded_statements (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    user_id uuid NOT NULL,
    file_name text NOT NULL,
    file_path text NOT NULL,
    file_type text NOT NULL,
    file_size integer NOT NULL,
    status text DEFAULT 'uploaded'::text NOT NULL,
    analysis_result jsonb,
    error_message text,
    created_at timestamp with time zone DEFAULT now(),
    analyzed_at timestamp with time zone,
    imported_at timestamp with time zone
);


ALTER TABLE public.uploaded_statements OWNER TO onlifin;

--
-- Name: audit_log id; Type: DEFAULT; Schema: auth; Owner: onlifin
--

ALTER TABLE ONLY auth.audit_log ALTER COLUMN id SET DEFAULT nextval('auth.audit_log_id_seq'::regclass);


--
-- Name: login_attempts id; Type: DEFAULT; Schema: auth; Owner: onlifin
--

ALTER TABLE ONLY auth.login_attempts ALTER COLUMN id SET DEFAULT nextval('auth.login_attempts_id_seq'::regclass);


--
-- Data for Name: audit_log; Type: TABLE DATA; Schema: auth; Owner: onlifin
--

COPY auth.audit_log (id, user_id, action, details, ip_address, user_agent, created_at) FROM stdin;
\.


--
-- Data for Name: login_attempts; Type: TABLE DATA; Schema: auth; Owner: onlifin
--

COPY auth.login_attempts (id, email, ip_address, success, attempted_at) FROM stdin;
1	onlifinadmin@miaoda.com	\N	t	2025-12-23 04:42:12.315634+00
2	onlifinadmin@miaoda.com	\N	f	2025-12-23 04:42:34.282156+00
3	onlifinadmin@miaoda.com	\N	f	2025-12-23 04:42:37.793865+00
4	onlifinadmin@miaoda.com	\N	t	2025-12-23 04:43:18.01605+00
5	onlifinadmin@miaoda.com	\N	t	2025-12-23 04:44:01.550138+00
6	onlifinadmin@miaoda.com	\N	t	2025-12-23 04:48:21.741613+00
7	onlifinadmin@miaoda.com	\N	t	2025-12-23 04:56:01.633267+00
8	onlifinadmin@miaoda.com	\N	t	2025-12-23 04:56:07.939971+00
9	onlifinadmin@miaoda.com	\N	t	2025-12-23 04:56:24.966748+00
10	onlifinadmin@miaoda.com	\N	t	2025-12-23 04:59:55.666032+00
11	onlifinadmin@miaoda.com	\N	t	2025-12-23 05:00:47.888142+00
12	onlifinadmin@miaoda.com	\N	t	2025-12-23 05:04:23.840667+00
13	onlifinadmin@miaoda.com	\N	t	2025-12-23 05:23:58.390981+00
14	onlifinadmin@miaoda.com	\N	t	2025-12-23 05:24:26.827165+00
15	onlifinadmin@miaoda.com	\N	t	2025-12-23 05:25:24.240435+00
16	onlifinadmin@miaoda.com	\N	t	2025-12-23 11:15:14.250252+00
17	onlifinadmin@miaoda.com	\N	t	2025-12-23 11:16:43.319142+00
18	onlifinadmin@miaoda.com	\N	t	2025-12-24 12:23:50.031513+00
19	onlifinadmin@miaoda.com	\N	t	2025-12-24 12:25:23.420214+00
20	onlifinadmin@miaoda.com	\N	t	2025-12-29 22:33:05.122708+00
21	onlifinadmin@miaoda.com	\N	t	2026-01-05 19:13:54.272461+00
23	onlifinadmin@miaoda.com	\N	t	2026-01-05 23:11:30.961269+00
24	onlifinadmin@miaoda.com	\N	t	2026-01-06 01:34:27.69889+00
25	onlifinadmin@miaoda.com	\N	t	2026-01-06 01:45:25.026654+00
26	onlifinadmin@miaoda.com	\N	t	2026-01-06 01:48:23.344366+00
27	onlifinadmin@miaoda.com	\N	t	2026-01-06 01:49:04.689338+00
28	onlifinadmin@miaoda.com	\N	t	2026-01-06 01:56:16.067272+00
29	onlifinadmin@miaoda.com	\N	t	2026-01-06 01:57:26.405989+00
34	onlifinadmin@miaoda.com	\N	t	2026-01-08 18:54:12.074631+00
35	onlifinadmin@miaoda.com	\N	t	2026-01-08 18:58:07.801824+00
36	onlifinadmin@miaoda.com	\N	t	2026-01-08 19:01:26.399148+00
37	onlifinadmin@miaoda.com	\N	t	2026-01-08 19:02:19.359872+00
38	admin@onlifin.com	\N	f	2026-01-08 20:22:29.093855+00
39	admin@onlifin.com	\N	t	2026-01-08 20:22:49.892433+00
40	admin@onlifin.com	\N	t	2026-01-08 20:23:35.976948+00
41	admin@onlifin.com	\N	t	2026-01-08 20:26:50.865943+00
44	admin@onlifin.com	\N	t	2026-01-08 20:28:13.78586+00
45	admin@onlifin.com	\N	t	2026-01-08 20:28:25.286226+00
46	onlifinadmin@miaoda.com	\N	t	2026-01-08 20:34:10.367958+00
47	admin@onlifin.com	\N	t	2026-01-08 20:47:37.02031+00
48	admin@onlifin.com	\N	t	2026-01-08 20:48:40.316576+00
49	admin@onlifin.com	\N	t	2026-01-08 20:48:54.198202+00
50	onlifinadmin@miaoda.com	\N	t	2026-01-08 20:51:29.891303+00
51	admin@onlifin.com	\N	t	2026-01-08 21:11:33.092286+00
52	admin@onlifin.com	\N	t	2026-01-08 21:11:53.484741+00
53	onlifinadmin@miaoda.com	\N	t	2026-01-08 21:24:03.835155+00
56	beatriz@onlifin.local	\N	t	2026-01-08 21:26:05.758273+00
57	beatriz@onlifin.local	\N	t	2026-01-08 21:27:06.559654+00
58	onlifinadmin@miaoda.com	\N	t	2026-01-08 21:36:15.585615+00
59	onlifinadmin@miaoda.com	\N	t	2026-01-08 21:38:34.678794+00
60	onlifinadmin@miaoda.com	\N	t	2026-01-08 21:41:06.658348+00
61	onlifinadmin@miaoda.com	\N	t	2026-01-08 21:46:59.229463+00
62	onlifinadmin@miaoda.com	\N	t	2026-01-09 00:10:47.96796+00
63	onlifinadmin@miaoda.com	\N	t	2026-01-09 01:31:30.234126+00
64	admin@onlifin.com	\N	t	2026-01-09 01:50:02.669734+00
65	admin@onlifin.com	\N	t	2026-01-09 01:50:11.046896+00
66	admin@onlifin.com	\N	t	2026-01-09 01:50:20.087024+00
67	onlifinadmin@miaoda.com	\N	t	2026-01-09 03:10:50.114516+00
102	onlifinadmin@miaoda.com	\N	t	2026-01-09 14:50:01.714908+00
113	onlifinadmin@miaoda.com	\N	t	2026-01-09 14:59:11.553302+00
150	onlifinadmin@miaoda.com	\N	t	2026-01-09 15:49:58.291763+00
152	onlifinadmin@miaoda.com	\N	t	2026-01-10 03:00:04.050519+00
186	onlifinadmin@miaoda.com	\N	t	2026-01-10 15:54:36.326419+00
219	onlifinadmin@miaoda.com	\N	t	2026-01-11 01:39:19.382715+00
220	onlifinadmin@miaoda.com	\N	t	2026-01-11 01:40:05.951845+00
223	onlifinadmin@miaoda.com	\N	t	2026-01-12 23:46:21.095831+00
224	onlifinadmin@miaoda.com	\N	t	2026-01-14 04:05:56.549197+00
225	onlifinadmin@miaoda.com	\N	t	2026-01-14 04:20:52.18724+00
226	onlifinadmin@miaoda.com	\N	t	2026-01-14 14:06:50.186289+00
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: auth; Owner: onlifin
--

COPY auth.users (id, email, password_hash, email_confirmed_at, created_at, updated_at, is_active, failed_login_count, locked_until) FROM stdin;
fb0c86eb-8692-436e-93bd-cdf169b0a96f	admin@onlifin.com	$2a$10$BCpdZ1jeOrKFnLh02tXnu.CeulLa1pdceXiGGGPe6jCR0BdVrXsuC	2025-12-23 04:40:45.433329+00	2025-12-23 04:40:45.433329+00	2026-01-09 01:50:20.087024+00	t	0	\N
ebc266d6-4743-4b5d-8287-afbd6ce078a1	beatriz@onlifin.local	$2a$10$DTv8oNpo/35QaFfYCMqCzuRy21KAhKjP.dhPhe6tAjiOHgF8l.7iW	2026-01-06 11:11:59.590439+00	2026-01-06 11:11:59.590439+00	2026-01-09 14:58:02.107993+00	t	0	\N
8b4c02fb-6511-473e-ae04-d3ee312f342b	onlifinadmin@miaoda.com	$2a$10$Q7KH/47zMHXKAPFxZJi/.Ot5S9jPzNNiS98A0SLTO2jqP/PazqHQu	2025-12-23 04:41:50.917911+00	2025-12-23 04:41:50.917911+00	2026-01-14 14:06:50.186289+00	t	0	\N
\.


--
-- Data for Name: accounts; Type: TABLE DATA; Schema: public; Owner: onlifin
--

COPY public.accounts (id, user_id, name, bank, agency, account_number, currency, balance, created_at, updated_at, initial_balance, icon) FROM stdin;
deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	8b4c02fb-6511-473e-ae04-d3ee312f342b	CORA MARCIA	CORA	00001	000001000	BRL	2468.52	2026-01-09 02:12:29.137397+00	2026-01-14 15:39:00.242979+00	2768.63	\N
facf775d-1332-488c-a1e2-5cda10cd5d1a	8b4c02fb-6511-473e-ae04-d3ee312f342b	NUBANK PF ALESSANDRO	NUBANK	00001	14124942-3	BRL	24.98	2026-01-09 03:54:19.646351+00	2026-01-14 15:39:00.242979+00	-90.02	nubank
a016056f-7996-4887-9be3-821826dc7d65	8b4c02fb-6511-473e-ae04-d3ee312f342b	DINHEIRO				BRL	0	2026-01-09 14:21:44.764914+00	2026-01-14 15:39:00.242979+00	-280	\N
cc5d48aa-21b5-4fd3-ae95-6288fe428be9	8b4c02fb-6511-473e-ae04-d3ee312f342b	INFINITY	INFINITY	00001	000002233	BRL	3702.87	2026-01-14 14:50:03.980051+00	2026-01-14 15:39:00.242979+00	1100	default
af92b43d-2e99-4cbc-9ef8-b5c8cd765427	8b4c02fb-6511-473e-ae04-d3ee312f342b	PJ NUBANK ALESSANDRO	NUBANK	00001	000002233	BRL	1000	2026-01-14 15:26:17.402175+00	2026-01-14 15:39:00.242979+00	0	nubank
037f48e4-92dd-45c0-8579-903255881517	8b4c02fb-6511-473e-ae04-d3ee312f342b	NUBANK MARCIA PF	NUBANK	00001	000002233	BRL	130	2026-01-14 15:29:27.985802+00	2026-01-14 15:39:00.242979+00	0	nubank
16e9a200-aafc-47c5-a783-1523f5eddd40	fb0c86eb-8692-436e-93bd-cdf169b0a96f	Conta Corrente Principal	Banco Exemplo	0001	12345-6	BRL	0	2025-12-23 04:40:45.433329+00	2026-01-14 15:39:00.242979+00	0	\N
\.


--
-- Data for Name: ai_chat_logs; Type: TABLE DATA; Schema: public; Owner: onlifin
--

COPY public.ai_chat_logs (id, user_id, message, response, data_accessed, permission_level, created_at) FROM stdin;
\.


--
-- Data for Name: ai_configurations; Type: TABLE DATA; Schema: public; Owner: onlifin
--

COPY public.ai_configurations (id, model_name, endpoint, permission_level, can_write_transactions, is_active, created_at, updated_at) FROM stdin;
83b36045-6355-471c-b76a-11bc713acb14	llama3.2:3b	http://ollama:11434	read_aggregated	f	t	2025-12-23 04:40:45.433329+00	2025-12-23 04:40:45.433329+00
\.


--
-- Data for Name: bills_to_pay; Type: TABLE DATA; Schema: public; Owner: onlifin
--

COPY public.bills_to_pay (id, user_id, description, amount, due_date, category_id, status, is_recurring, recurrence_pattern, account_id, paid_date, notes, created_at, updated_at, transaction_id) FROM stdin;
ba1658f2-7496-4428-9aae-f48e16af7641	8b4c02fb-6511-473e-ae04-d3ee312f342b	Conta de luz	448	2026-01-26	c238f56b-1bbb-4808-a451-9d87411f4248	pending	f	\N	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	\N	2026-01-09 18:03:45.300569+00	2026-01-09 18:03:45.300569+00	\N
081da801-79ec-45f1-b7ea-5d92e341ca95	8b4c02fb-6511-473e-ae04-d3ee312f342b	Internet	112.89	2026-01-10	\N	pending	f	\N	\N	\N	Próxima parcela	2026-01-09 18:14:29.752373+00	2026-01-09 18:14:39.498+00	\N
db6a7527-1e88-4a94-90cf-42ecd771ff3f	8b4c02fb-6511-473e-ae04-d3ee312f342b	Gás	120	2026-02-05	c238f56b-1bbb-4808-a451-9d87411f4248	pending	f	\N	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	data aproximada	2026-01-09 18:15:27.599021+00	2026-01-09 18:15:27.599021+00	\N
12b00a51-ccdc-4321-a7e7-5531bb73e466	8b4c02fb-6511-473e-ae04-d3ee312f342b	Convênio Médico	38	2026-02-12	c238f56b-1bbb-4808-a451-9d87411f4248	pending	f	\N	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	\N	2026-01-09 18:18:13.908828+00	2026-01-09 18:19:18.834+00	\N
229252ea-b4f9-4c19-a3c7-20d91cda7710	8b4c02fb-6511-473e-ae04-d3ee312f342b	Internet	123.45	2025-12-10	c238f56b-1bbb-4808-a451-9d87411f4248	paid	f	\N	\N	2026-01-13	Atrasada	2026-01-09 18:14:09.385832+00	2026-01-13 13:46:56.2+00	\N
7dc8a0af-3bdb-4dc7-88ea-d6064fe1a3d0	8b4c02fb-6511-473e-ae04-d3ee312f342b	Água	69.48	2026-01-01	c238f56b-1bbb-4808-a451-9d87411f4248	paid	f	\N	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	2026-01-09	\N	2026-01-09 18:12:19.132665+00	2026-01-09 18:12:18.882+00	da0facee-cd50-4a46-b6d9-ca1e31bdbbcf
348400a0-e8f9-43e6-b544-837b78412eed	8b4c02fb-6511-473e-ae04-d3ee312f342b	Telefone	232	2026-02-20	c238f56b-1bbb-4808-a451-9d87411f4248	paid	f	\N	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	2026-01-09	datas e valores variáveis	2026-01-09 18:17:11.568433+00	2026-01-09 18:18:26.77+00	cfe211e2-ada2-4dbb-aae9-d789e276918f
\.


--
-- Data for Name: bills_to_receive; Type: TABLE DATA; Schema: public; Owner: onlifin
--

COPY public.bills_to_receive (id, user_id, description, amount, due_date, category_id, status, is_recurring, recurrence_pattern, account_id, received_date, notes, created_at, updated_at, transaction_id) FROM stdin;
1707abf5-7736-4315-8d22-5c678fd046cb	8b4c02fb-6511-473e-ae04-d3ee312f342b	SALARIO	200	2026-01-14	b88c3abc-e26e-4b37-969a-f5b0dfbca0b4	pending	f	\N	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	\N	2026-01-09 03:53:41.91487+00	2026-01-09 03:53:41.91487+00	\N
\.


--
-- Data for Name: cards; Type: TABLE DATA; Schema: public; Owner: onlifin
--

COPY public.cards (id, user_id, account_id, name, card_limit, available_limit, closing_day, due_day, created_at, updated_at, icon, brand) FROM stdin;
56e44498-262a-4bdb-a836-a0d183ddae24	fb0c86eb-8692-436e-93bd-cdf169b0a96f	\N	Cartão Principal	5000.00	5000.00	10	20	2025-12-23 04:40:45.433329+00	2025-12-23 04:40:45.433329+00	\N	\N
\.


--
-- Data for Name: categories; Type: TABLE DATA; Schema: public; Owner: onlifin
--

COPY public.categories (id, user_id, name, type, icon, color, created_at) FROM stdin;
59b8e1d6-a10c-4bed-9ef0-59f62f1badf0	fb0c86eb-8692-436e-93bd-cdf169b0a96f	Salário	income	Briefcase	#10b981	2025-12-23 04:40:45.433329+00
a136beb2-75c6-4a1e-a18d-7f29d0cb38e9	fb0c86eb-8692-436e-93bd-cdf169b0a96f	Freelance	income	Code	#3b82f6	2025-12-23 04:40:45.433329+00
6819fa54-96d3-4297-b83a-04f8438b83bf	fb0c86eb-8692-436e-93bd-cdf169b0a96f	Investimentos	income	TrendingUp	#8b5cf6	2025-12-23 04:40:45.433329+00
e441c89c-dac1-4683-86a7-fcf580705af3	fb0c86eb-8692-436e-93bd-cdf169b0a96f	Vendas	income	ShoppingCart	#06b6d4	2025-12-23 04:40:45.433329+00
f871d167-424c-4237-a302-aaa656b0a4ad	fb0c86eb-8692-436e-93bd-cdf169b0a96f	Outros	income	DollarSign	#6b7280	2025-12-23 04:40:45.433329+00
d582310b-4d5b-4d52-892e-bbe6d475ac46	fb0c86eb-8692-436e-93bd-cdf169b0a96f	Alimentação	expense	Utensils	#ef4444	2025-12-23 04:40:45.433329+00
cd53054c-0b7f-46ef-a303-4d6ea660162a	fb0c86eb-8692-436e-93bd-cdf169b0a96f	Transporte	expense	Car	#f97316	2025-12-23 04:40:45.433329+00
83b4337d-8dd9-4243-834e-42256a3e4643	fb0c86eb-8692-436e-93bd-cdf169b0a96f	Moradia	expense	Home	#eab308	2025-12-23 04:40:45.433329+00
bd5e88d3-42e7-4e06-b97a-f45c0b9bc6d9	fb0c86eb-8692-436e-93bd-cdf169b0a96f	Saúde	expense	Heart	#ec4899	2025-12-23 04:40:45.433329+00
e41adc06-d9a8-4220-95f3-a4d375168301	fb0c86eb-8692-436e-93bd-cdf169b0a96f	Educação	expense	BookOpen	#8b5cf6	2025-12-23 04:40:45.433329+00
93329092-5f7f-4173-84f8-f554772732da	fb0c86eb-8692-436e-93bd-cdf169b0a96f	Lazer	expense	Film	#06b6d4	2025-12-23 04:40:45.433329+00
37e37423-85d4-4453-aee2-a5c1b5045156	fb0c86eb-8692-436e-93bd-cdf169b0a96f	Compras	expense	ShoppingBag	#f43f5e	2025-12-23 04:40:45.433329+00
1eac3cf1-baed-4423-9c81-dfb8b6cd21f5	fb0c86eb-8692-436e-93bd-cdf169b0a96f	Contas	expense	FileText	#64748b	2025-12-23 04:40:45.433329+00
1d0689fe-a6e4-442e-aa4e-d948257c79b1	fb0c86eb-8692-436e-93bd-cdf169b0a96f	Outros	expense	MoreHorizontal	#6b7280	2025-12-23 04:40:45.433329+00
b88c3abc-e26e-4b37-969a-f5b0dfbca0b4	\N	Salário	income	💰	#27AE60	2025-12-23 04:40:45.53201+00
038590ba-7a96-4efd-9ced-caf259a56c12	\N	Freelance	income	💼	#27AE60	2025-12-23 04:40:45.53201+00
aea1b30f-b292-4a8f-b95c-ef5a1b3e7398	\N	Investimentos	income	📈	#27AE60	2025-12-23 04:40:45.53201+00
e0ea9a39-a4c9-4181-ac69-099f27bbec72	\N	Outros Rendimentos	income	💵	#27AE60	2025-12-23 04:40:45.53201+00
3cdf4d81-8952-455f-827e-94c6c79fcdad	\N	Alimentação	expense	🍔	#E74C3C	2025-12-23 04:40:45.53201+00
2fe18e44-23d5-465d-b8af-78e19bc8e109	\N	Transporte	expense	🚗	#E74C3C	2025-12-23 04:40:45.53201+00
a6fb0652-1940-485b-8dca-74047bd9e08d	\N	Moradia	expense	🏠	#E74C3C	2025-12-23 04:40:45.53201+00
9607a5b7-4044-4f23-91c4-dcdd71e0b78d	\N	Saúde	expense	🏥	#E74C3C	2025-12-23 04:40:45.53201+00
c80056c6-f321-41dc-9b1b-2a982275d89b	\N	Educação	expense	📚	#E74C3C	2025-12-23 04:40:45.53201+00
3b1281ae-36f6-41d4-8a98-5cfba435573e	\N	Lazer	expense	🎮	#E74C3C	2025-12-23 04:40:45.53201+00
ed477b98-661b-4420-ac20-889bbc1a2a42	\N	Compras	expense	🛒	#E74C3C	2025-12-23 04:40:45.53201+00
c238f56b-1bbb-4808-a451-9d87411f4248	\N	Contas	expense	📄	#E74C3C	2025-12-23 04:40:45.53201+00
4e33ec1d-da11-4c5c-967b-18fc769797a7	\N	Outros Gastos	expense	💸	#E74C3C	2025-12-23 04:40:45.53201+00
49f74363-0a11-4e08-a61e-eadd9631ad4e	8b4c02fb-6511-473e-ae04-d3ee312f342b	Celular Beatriz	expense	📱	#27AE60	2026-01-09 19:23:07.696865+00
\.


--
-- Data for Name: financial_forecasts; Type: TABLE DATA; Schema: public; Owner: onlifin
--

COPY public.financial_forecasts (id, user_id, calculation_date, initial_balance, forecast_daily, forecast_weekly, forecast_monthly, insights, alerts, risk_negative, risk_date, spending_patterns, created_at) FROM stdin;
\.


--
-- Data for Name: import_history; Type: TABLE DATA; Schema: public; Owner: onlifin
--

COPY public.import_history (id, user_id, filename, format, status, imported_count, error_message, created_at) FROM stdin;
\.


--
-- Data for Name: notifications; Type: TABLE DATA; Schema: public; Owner: onlifin
--

COPY public.notifications (id, user_id, title, message, type, severity, is_read, related_forecast_id, related_bill_id, action_url, created_at) FROM stdin;
\.


--
-- Data for Name: profiles; Type: TABLE DATA; Schema: public; Owner: onlifin
--

COPY public.profiles (id, username, full_name, role, created_at) FROM stdin;
fb0c86eb-8692-436e-93bd-cdf169b0a96f	admin	Administrador	admin	2025-12-23 04:40:45.433329+00
8b4c02fb-6511-473e-ae04-d3ee312f342b	onlifinadmin	Administrador Onlifin	admin	2025-12-23 04:41:50.917911+00
ebc266d6-4743-4b5d-8287-afbd6ce078a1	beatriz	beatriz	admin	2026-01-06 11:11:59.590439+00
\.


--
-- Data for Name: transactions; Type: TABLE DATA; Schema: public; Owner: onlifin
--

COPY public.transactions (id, user_id, account_id, card_id, category_id, type, amount, date, description, tags, is_recurring, recurrence_pattern, is_installment, installment_number, total_installments, parent_transaction_id, is_reconciled, created_at, updated_at, transfer_destination_account_id, is_transfer) FROM stdin;
81bf1f73-b370-4efc-9290-996adfe0bc56	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	\N	expense	25	2026-01-03	Transferência p/ Nubank Marcia 03/01	\N	f	\N	f	\N	\N	dc57bc50-1149-428e-9155-11a268f9b94d	t	2026-01-13 00:00:39.252785+00	2026-01-13 00:00:39.252785+00	facf775d-1332-488c-a1e2-5cda10cd5d1a	t
050ce432-0fe3-452e-a4d4-7fe9d7134cf7	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	b88c3abc-e26e-4b37-969a-f5b0dfbca0b4	income	720	2026-01-07	Gerencial Contrato	\N	t	monthly	f	\N	\N	\N	f	2026-01-09 12:12:02.703997+00	2026-01-09 12:12:02.703997+00	\N	f
bbbf88fc-d47d-468d-9427-1fca2102d780	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	b88c3abc-e26e-4b37-969a-f5b0dfbca0b4	income	50	2026-01-07	GRAMAVALE VPN	\N	t	monthly	f	\N	\N	\N	f	2026-01-09 12:12:59.217686+00	2026-01-09 12:12:59.217686+00	\N	f
166389fb-b8cc-402e-b92a-7d6b6990f360	8b4c02fb-6511-473e-ae04-d3ee312f342b	a016056f-7996-4887-9be3-821826dc7d65	\N	038590ba-7a96-4efd-9ced-caf259a56c12	income	280	2026-01-04	SERVIÇOS TARCISO MERCADINHO	\N	f	\N	f	\N	\N	\N	f	2026-01-09 14:22:22.621419+00	2026-01-09 14:22:22.621419+00	\N	f
2248d981-a064-4cb1-8883-e39b9e5d71c1	8b4c02fb-6511-473e-ae04-d3ee312f342b	facf775d-1332-488c-a1e2-5cda10cd5d1a	\N	f871d167-424c-4237-a302-aaa656b0a4ad	income	50	2026-01-02	Transferência Recebida - Márcia Aparecida Domingos Freire - •••.569.868-•• - NU PAGAMENTOS - IP (0260) Agência: 1 Conta: 15572782-4	{importado}	f	\N	f	\N	\N	\N	f	2026-01-09 15:57:19.18213+00	2026-01-09 15:57:19.18213+00	\N	f
bdefd8fa-78bc-41ce-880c-703fc9831bb3	8b4c02fb-6511-473e-ae04-d3ee312f342b	facf775d-1332-488c-a1e2-5cda10cd5d1a	\N	1d0689fe-a6e4-442e-aa4e-d948257c79b1	expense	30	2026-01-02	Compra no débito - BRASIL GAS ADMINISTRAC	{importado}	f	\N	f	\N	\N	\N	f	2026-01-09 15:57:19.862368+00	2026-01-09 15:57:19.862368+00	\N	f
87e2fc9e-14da-41de-a304-677ea067df2e	8b4c02fb-6511-473e-ae04-d3ee312f342b	facf775d-1332-488c-a1e2-5cda10cd5d1a	\N	1d0689fe-a6e4-442e-aa4e-d948257c79b1	expense	30	2026-01-03	Transferência enviada pelo Pix - Márcia Aparecida Domingos Freire - •••.569.868-•• - NU PAGAMENTOS - IP (0260) Agência: 1 Conta: 15572782-4	{importado}	f	\N	f	\N	\N	\N	f	2026-01-09 15:57:20.196117+00	2026-01-09 15:57:20.196117+00	\N	f
6c3e4fc0-b467-4dec-87b5-cfacdb5ee143	8b4c02fb-6511-473e-ae04-d3ee312f342b	facf775d-1332-488c-a1e2-5cda10cd5d1a	\N	1d0689fe-a6e4-442e-aa4e-d948257c79b1	expense	3	2026-01-05	Transferência enviada pelo Pix - Márcia Aparecida Domingos Freire - •••.569.868-•• - NU PAGAMENTOS - IP (0260) Agência: 1 Conta: 15572782-4	{importado}	f	\N	f	\N	\N	\N	f	2026-01-09 15:57:21.270685+00	2026-01-09 15:57:21.270685+00	\N	f
900f85a1-0e5f-4721-923c-a85853db2b86	8b4c02fb-6511-473e-ae04-d3ee312f342b	facf775d-1332-488c-a1e2-5cda10cd5d1a	\N	f871d167-424c-4237-a302-aaa656b0a4ad	income	30	2026-01-06	Transferência recebida pelo Pix - MICHELLE GALVAO FREIRE - •••.234.998-•• - BCO C6 S.A. (0336) Agência: 1 Conta: 27968388-0	{importado}	f	\N	f	\N	\N	\N	f	2026-01-09 15:57:21.827281+00	2026-01-09 15:57:21.827281+00	\N	f
97768dde-268f-4a78-b3f0-12ca4bc7c10d	8b4c02fb-6511-473e-ae04-d3ee312f342b	facf775d-1332-488c-a1e2-5cda10cd5d1a	\N	3cdf4d81-8952-455f-827e-94c6c79fcdad	expense	21	2026-01-09	CALDO DE CANA 	\N	f	\N	f	\N	\N	\N	f	2026-01-09 19:22:00.986343+00	2026-01-09 19:22:19.764+00	\N	f
07ab4b0c-ed46-41bf-be44-7f0f420b3196	8b4c02fb-6511-473e-ae04-d3ee312f342b	facf775d-1332-488c-a1e2-5cda10cd5d1a	\N	c80056c6-f321-41dc-9b1b-2a982275d89b	expense	7	2026-01-08	Transferência enviada pelo Pix - Beatriz Domingos Galvão Freire - •••.630.298-•• - NU PAGAMENTOS - IP (0260) Agência: 1 Conta: 238189961-1	{importado}	f	\N	f	\N	\N	\N	f	2026-01-09 15:57:22.473569+00	2026-01-09 19:22:33.941+00	\N	f
a3f07976-2f8c-4f2e-ae08-5c7f1e4013f6	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	038590ba-7a96-4efd-9ced-caf259a56c12	income	2000	2026-01-06	Quallit	\N	f	\N	f	\N	\N	\N	f	2026-01-09 12:11:08.866994+00	2026-01-12 23:50:21.497+00	\N	f
0a979f92-9f2e-4122-95e7-382a6237a7e5	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	b88c3abc-e26e-4b37-969a-f5b0dfbca0b4	income	500	2026-01-02	Venda de um computador	\N	f	\N	f	\N	\N	\N	f	2026-01-12 23:51:44.662475+00	2026-01-12 23:51:44.662475+00	\N	f
4ea76767-acf0-4501-aa0d-b6664b356488	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	2fe18e44-23d5-465d-b8af-78e19bc8e109	expense	30	2026-01-03	Gasolina 03/01	\N	f	\N	f	\N	\N	\N	f	2026-01-13 00:03:42.186797+00	2026-01-13 00:03:42.186797+00	\N	f
df35a0c5-334d-45a1-b45f-272134045d6e	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	3cdf4d81-8952-455f-827e-94c6c79fcdad	expense	22.97	2026-01-03	Mercadinho 03/01	\N	f	\N	f	\N	\N	\N	f	2026-01-13 00:04:01.608896+00	2026-01-13 00:04:01.608896+00	\N	f
6bc2435f-1b4d-4c44-be2c-6db3a4e40320	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	4e33ec1d-da11-4c5c-967b-18fc769797a7	expense	36	2026-01-03	Empréstimo 03/01	\N	f	\N	f	\N	\N	\N	f	2026-01-13 00:03:16.796713+00	2026-01-13 00:05:28.394+00	\N	f
b572e43c-1a3f-4025-98d5-ce50f559e428	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	3cdf4d81-8952-455f-827e-94c6c79fcdad	expense	15.77	2026-01-02	Mercadinho 02/01	\N	f	\N	f	\N	\N	\N	f	2026-01-12 23:56:12.121961+00	2026-01-12 23:56:12.121961+00	\N	f
dee2a5f8-a6ee-4f8f-8980-7cea68c21cfe	8b4c02fb-6511-473e-ae04-d3ee312f342b	facf775d-1332-488c-a1e2-5cda10cd5d1a	\N	\N	income	32	2026-01-02	Transferência p/ Nubank Marcia 02/01	\N	f	\N	f	\N	\N	adccca76-0f93-44d3-8ffa-144e8481373e	t	2026-01-12 23:58:20.530423+00	2026-01-12 23:58:20.530423+00	\N	t
adccca76-0f93-44d3-8ffa-144e8481373e	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	\N	expense	32	2026-01-02	Transferência p/ Nubank Marcia 02/01	\N	f	\N	f	\N	\N	dee2a5f8-a6ee-4f8f-8980-7cea68c21cfe	t	2026-01-12 23:58:20.530423+00	2026-01-12 23:58:20.530423+00	facf775d-1332-488c-a1e2-5cda10cd5d1a	t
fc8f83b7-5d66-41f4-a9e3-5d8dbe8c4d43	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	3cdf4d81-8952-455f-827e-94c6c79fcdad	expense	217.15	2026-01-03	Tenda 03/01	\N	f	\N	f	\N	\N	\N	f	2026-01-12 23:59:13.447919+00	2026-01-12 23:59:13.447919+00	\N	f
51de2f38-611f-43b9-b69f-0ab926c3bb7e	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	3cdf4d81-8952-455f-827e-94c6c79fcdad	expense	682.93	2026-01-06	Tenda 06/01	\N	f	\N	f	\N	\N	\N	f	2026-01-10 02:51:31.818598+00	2026-01-12 23:59:18.681+00	\N	f
c7ebe1e8-46f6-44d7-b24d-bb02c0657f79	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	4e33ec1d-da11-4c5c-967b-18fc769797a7	expense	47.18	2026-01-02	Provedor 02/01	\N	f	\N	f	\N	\N	\N	f	2026-01-12 23:57:38.60053+00	2026-01-12 23:59:34.497+00	\N	f
dc57bc50-1149-428e-9155-11a268f9b94d	8b4c02fb-6511-473e-ae04-d3ee312f342b	facf775d-1332-488c-a1e2-5cda10cd5d1a	\N	\N	income	25	2026-01-03	Transferência p/ Nubank Marcia 03/01	\N	f	\N	f	\N	\N	81bf1f73-b370-4efc-9290-996adfe0bc56	t	2026-01-13 00:00:39.252785+00	2026-01-13 00:00:39.252785+00	\N	t
bb3565b3-320a-4122-8042-eec277b4080a	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	3b1281ae-36f6-41d4-8a98-5cfba435573e	expense	42.9	2026-01-04	ADC 04/01	\N	f	\N	f	\N	\N	\N	f	2026-01-13 00:06:44.298866+00	2026-01-13 00:06:44.298866+00	\N	f
8041d0b9-413a-4704-b0c5-d6e7ed40bb55	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	3cdf4d81-8952-455f-827e-94c6c79fcdad	expense	9.47	2026-01-04	Mercadinho 04/01	\N	f	\N	f	\N	\N	\N	f	2026-01-13 00:07:11.132809+00	2026-01-13 00:07:11.132809+00	\N	f
0ac4fe47-025a-4ce1-b679-922438a93eea	8b4c02fb-6511-473e-ae04-d3ee312f342b	facf775d-1332-488c-a1e2-5cda10cd5d1a	\N	\N	income	21	2026-01-05	Transferência p/ Nubank Marcia 05/01	\N	f	\N	f	\N	\N	4c253316-2461-487d-bb55-338fa3d7df81	t	2026-01-13 00:07:41.1029+00	2026-01-13 00:07:41.1029+00	\N	t
4c253316-2461-487d-bb55-338fa3d7df81	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	\N	expense	21	2026-01-05	Transferência p/ Nubank Marcia 05/01	\N	f	\N	f	\N	\N	0ac4fe47-025a-4ce1-b679-922438a93eea	t	2026-01-13 00:07:41.1029+00	2026-01-13 00:07:41.1029+00	facf775d-1332-488c-a1e2-5cda10cd5d1a	t
e33a31ef-cc8a-4c11-b257-55c4a0829702	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	4e33ec1d-da11-4c5c-967b-18fc769797a7	expense	72	2026-01-06	Empréstimo 06/01	\N	f	\N	f	\N	\N	\N	f	2026-01-13 00:09:17.125688+00	2026-01-13 00:09:17.125688+00	\N	f
88368469-fda1-4c8a-ab3b-514b4c87a352	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	4e33ec1d-da11-4c5c-967b-18fc769797a7	expense	135	2026-01-07	Barbeiro 07/01	\N	f	\N	f	\N	\N	\N	f	2026-01-13 00:10:38.338602+00	2026-01-13 00:10:38.338602+00	\N	f
f6010ec4-ada2-4fd2-afd3-1e99fa9640c9	8b4c02fb-6511-473e-ae04-d3ee312f342b	facf775d-1332-488c-a1e2-5cda10cd5d1a	\N	\N	income	30	2026-01-07	Transferência p/ Nubank Marcia 07/01	\N	f	\N	f	\N	\N	424235d7-0b8d-4cd6-aa7a-5a768a088963	t	2026-01-13 00:11:06.978627+00	2026-01-13 00:11:06.978627+00	\N	t
424235d7-0b8d-4cd6-aa7a-5a768a088963	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	\N	expense	30	2026-01-07	Transferência p/ Nubank Marcia 07/01	\N	f	\N	f	\N	\N	f6010ec4-ada2-4fd2-afd3-1e99fa9640c9	t	2026-01-13 00:11:06.978627+00	2026-01-13 00:11:06.978627+00	facf775d-1332-488c-a1e2-5cda10cd5d1a	t
66902441-7043-4887-be41-5ab7965a0cfa	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	2fe18e44-23d5-465d-b8af-78e19bc8e109	expense	100	2026-01-07	Mecânica 07/01	\N	f	\N	f	\N	\N	\N	f	2026-01-13 00:12:02.202726+00	2026-01-13 00:12:02.202726+00	\N	f
536bb1bc-a897-41a7-9078-24f09358a111	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	2fe18e44-23d5-465d-b8af-78e19bc8e109	expense	80	2026-01-07	Gasolina 07/01	\N	f	\N	f	\N	\N	\N	f	2026-01-13 00:12:42.898477+00	2026-01-13 00:12:42.898477+00	\N	f
bf94ae02-ee09-4788-837d-8fa4990eef19	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	4e33ec1d-da11-4c5c-967b-18fc769797a7	expense	35.8	2026-01-07	Outros Gastos 07/01	\N	f	\N	f	\N	\N	\N	f	2026-01-13 00:13:23.020474+00	2026-01-13 00:13:23.020474+00	\N	f
0569b39d-13a2-4ec0-a559-78a8d86192df	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	4e33ec1d-da11-4c5c-967b-18fc769797a7	expense	12.8	2026-01-07	Outros Gastos 07/01	\N	f	\N	f	\N	\N	\N	f	2026-01-13 00:14:05.006707+00	2026-01-13 00:14:05.006707+00	\N	f
28c0e012-2de5-456f-8ef6-ec46326346be	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	c238f56b-1bbb-4808-a451-9d87411f4248	expense	47.08	2026-01-08	Telefone Beatriz 08/01	\N	f	\N	f	\N	\N	\N	f	2026-01-09 18:27:11.059213+00	2026-01-13 14:37:07.656+00	\N	f
2d29f168-be1c-4702-97f6-29ffc1ab162b	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	c238f56b-1bbb-4808-a451-9d87411f4248	expense	30.15	2026-01-08	Telefone Geovanna 08/01	\N	f	\N	f	\N	\N	\N	f	2026-01-09 18:26:56.446736+00	2026-01-13 14:37:19.017+00	\N	f
16176f98-b8fa-4da7-bb71-79c1e7ca6db7	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	c238f56b-1bbb-4808-a451-9d87411f4248	expense	69.48	2026-01-08	Água 08/01	\N	f	\N	f	\N	\N	\N	f	2026-01-09 18:26:25.388525+00	2026-01-13 14:37:39.888+00	\N	f
d06ee903-a7cd-4da3-a3f5-4db94aba5cbd	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	ed477b98-661b-4420-ac20-889bbc1a2a42	expense	167.42	2026-01-08	Shopee 08/01	\N	f	\N	f	\N	\N	\N	f	2026-01-13 14:39:12.203538+00	2026-01-13 14:39:12.203538+00	\N	f
afd6be62-06f5-4eff-99b7-83d72b8ada32	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	ed477b98-661b-4420-ac20-889bbc1a2a42	expense	105	2026-01-08	Compra MercadoLivre 08/01	\N	f	\N	f	\N	\N	\N	f	2026-01-13 14:40:48.372131+00	2026-01-13 14:40:48.372131+00	\N	f
7daab4e5-27e9-45b5-bfff-50b21789a2f9	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	4e33ec1d-da11-4c5c-967b-18fc769797a7	expense	13	2026-01-08	Transferência p/ Beatriz 08/01	\N	f	\N	f	\N	\N	\N	f	2026-01-13 14:41:19.327755+00	2026-01-13 14:41:19.327755+00	\N	f
718ec8c8-7de3-44b0-a92d-09548648febf	8b4c02fb-6511-473e-ae04-d3ee312f342b	facf775d-1332-488c-a1e2-5cda10cd5d1a	\N	\N	income	10	2026-01-08	Transferência p/ Nubank Marcia 08/01	\N	f	\N	f	\N	\N	35d9d9fb-9296-4896-a37e-21fb3f12ddcb	t	2026-01-13 14:41:41.53136+00	2026-01-13 14:41:41.53136+00	\N	t
35d9d9fb-9296-4896-a37e-21fb3f12ddcb	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	\N	expense	10	2026-01-08	Transferência p/ Nubank Marcia 08/01	\N	f	\N	f	\N	\N	718ec8c8-7de3-44b0-a92d-09548648febf	t	2026-01-13 14:41:41.53136+00	2026-01-13 14:41:41.53136+00	facf775d-1332-488c-a1e2-5cda10cd5d1a	t
b082f329-2b50-433e-bb77-896c3395ac41	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	4e33ec1d-da11-4c5c-967b-18fc769797a7	expense	72	2026-01-08	Empréstimo 08/01	\N	f	\N	f	\N	\N	\N	f	2026-01-13 14:42:09.602981+00	2026-01-13 14:42:09.602981+00	\N	f
b45197af-5408-44aa-91a2-97629c36c732	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	4e33ec1d-da11-4c5c-967b-18fc769797a7	expense	130	2026-01-09	Transferência p/ Vera 09/01	\N	f	\N	f	\N	\N	\N	f	2026-01-13 14:43:28.661152+00	2026-01-13 14:43:38.2+00	\N	f
bb579ec7-e062-4a83-84b5-6b2387d24595	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	ed477b98-661b-4420-ac20-889bbc1a2a42	expense	322.06	2026-01-08	Assaí 08/01	\N	f	\N	f	\N	\N	\N	f	2026-01-13 14:44:30.280265+00	2026-01-13 14:44:48.671+00	\N	f
ba5f41fa-a644-43cd-8b0d-272ca865076f	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	4e33ec1d-da11-4c5c-967b-18fc769797a7	expense	39.6	2026-01-09	Shopee 09/01	\N	f	\N	f	\N	\N	\N	f	2026-01-13 14:45:31.388935+00	2026-01-13 14:45:31.388935+00	\N	f
c73b3970-1115-4774-96c9-f8d279e499ab	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	4e33ec1d-da11-4c5c-967b-18fc769797a7	expense	108	2026-01-09	Empréstimo 09/01	\N	f	\N	f	\N	\N	\N	f	2026-01-13 14:45:55.536778+00	2026-01-13 14:45:55.536778+00	\N	f
deb42f3f-6adb-471c-8bd9-344e83104199	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	ed477b98-661b-4420-ac20-889bbc1a2a42	expense	204.54	2026-01-09	Tenda 09/01	\N	f	\N	f	\N	\N	\N	f	2026-01-13 14:46:17.413334+00	2026-01-13 14:46:17.413334+00	\N	f
728a5584-2196-4c3b-a71e-9b1a2ea2133b	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	4e33ec1d-da11-4c5c-967b-18fc769797a7	expense	36	2026-01-10	Empréstimo 10/01	\N	f	\N	f	\N	\N	\N	f	2026-01-13 14:46:46.224053+00	2026-01-13 14:46:46.224053+00	\N	f
7dcda2e2-0c5f-4b5e-bc94-1b4e815538f0	8b4c02fb-6511-473e-ae04-d3ee312f342b	facf775d-1332-488c-a1e2-5cda10cd5d1a	\N	\N	income	8	2026-01-10	Transferência p/ Nubank Marcia 10/01	\N	f	\N	f	\N	\N	a65fa83c-ad38-42c9-ab06-03fac6fa520b	t	2026-01-13 14:47:05.904273+00	2026-01-13 14:47:05.904273+00	\N	t
a65fa83c-ad38-42c9-ab06-03fac6fa520b	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	\N	expense	8	2026-01-10	Transferência p/ Nubank Marcia 10/01	\N	f	\N	f	\N	\N	7dcda2e2-0c5f-4b5e-bc94-1b4e815538f0	t	2026-01-13 14:47:05.904273+00	2026-01-13 14:47:05.904273+00	facf775d-1332-488c-a1e2-5cda10cd5d1a	t
14521ebf-4f40-4a0a-a7b0-9fb09639ab61	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	3cdf4d81-8952-455f-827e-94c6c79fcdad	expense	17.86	2026-01-11	Mercadinho 11/01	\N	f	\N	f	\N	\N	\N	f	2026-01-13 14:47:39.20777+00	2026-01-13 14:47:39.20777+00	\N	f
a1b9e201-e3f3-4f1d-a948-f447e784c50e	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	2fe18e44-23d5-465d-b8af-78e19bc8e109	expense	30	2026-01-11	Gasolina 11/01	\N	f	\N	f	\N	\N	\N	f	2026-01-13 14:48:00.706355+00	2026-01-13 14:48:00.706355+00	\N	f
c043bd30-f963-48da-b3a5-1275a47ce85a	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	4e33ec1d-da11-4c5c-967b-18fc769797a7	expense	50	2026-01-11	ADC 11/01	\N	f	\N	f	\N	\N	\N	f	2026-01-13 14:48:41.712072+00	2026-01-13 14:48:41.712072+00	\N	f
ec3d7f30-7880-49dc-9d02-d5ff3c7df23b	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	3b1281ae-36f6-41d4-8a98-5cfba435573e	expense	76.47	2026-01-11	Cerveja 11/01	\N	f	\N	f	\N	\N	\N	f	2026-01-13 14:49:45.732689+00	2026-01-13 14:49:45.732689+00	\N	f
40d452a8-3c34-444d-91eb-7d29dee8de32	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	4e33ec1d-da11-4c5c-967b-18fc769797a7	expense	54	2026-01-11	Outros Gastos 11/01	\N	f	\N	f	\N	\N	\N	f	2026-01-13 14:50:11.899388+00	2026-01-13 14:50:11.899388+00	\N	f
38aab502-6894-4b00-8d1d-697e073c83ec	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	2fe18e44-23d5-465d-b8af-78e19bc8e109	expense	30	2026-01-13	Gasolina 12/01	\N	f	\N	f	\N	\N	\N	f	2026-01-13 14:50:31.440817+00	2026-01-14 04:06:22.705+00	\N	f
da0facee-cd50-4a46-b6d9-ca1e31bdbbcf	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	c238f56b-1bbb-4808-a451-9d87411f4248	expense	69.48	2026-01-09	Água	\N	f	\N	f	\N	\N	\N	t	2026-01-14 14:20:49.890333+00	2026-01-14 14:20:49.890333+00	\N	f
cfe211e2-ada2-4dbb-aae9-d789e276918f	8b4c02fb-6511-473e-ae04-d3ee312f342b	deba2a8d-6814-436c-8a6d-19b0bc8cb8c8	\N	c238f56b-1bbb-4808-a451-9d87411f4248	expense	232	2026-01-09	Telefone	\N	f	\N	f	\N	\N	\N	t	2026-01-14 14:20:49.890333+00	2026-01-14 14:20:49.890333+00	\N	f
3e63c09f-31ce-47d8-a8e6-1c8cb4b45d59	8b4c02fb-6511-473e-ae04-d3ee312f342b	cc5d48aa-21b5-4fd3-ae95-6288fe428be9	\N	038590ba-7a96-4efd-9ced-caf259a56c12	income	6380	2026-01-13	Serviços esquelyvale	\N	f	\N	f	\N	\N	\N	f	2026-01-14 14:50:46.157994+00	2026-01-14 14:51:15.856+00	\N	f
6d299e1a-f1a7-4139-b94e-6aac30a48b0f	8b4c02fb-6511-473e-ae04-d3ee312f342b	cc5d48aa-21b5-4fd3-ae95-6288fe428be9	\N	2fe18e44-23d5-465d-b8af-78e19bc8e109	expense	100	2026-01-14	COMBUSTIVEL	\N	f	\N	f	\N	\N	\N	f	2026-01-14 14:52:03.094803+00	2026-01-14 14:52:03.094803+00	\N	f
35a2777a-2103-4aee-8265-1dd46c61ee1a	8b4c02fb-6511-473e-ae04-d3ee312f342b	cc5d48aa-21b5-4fd3-ae95-6288fe428be9	\N	ed477b98-661b-4420-ac20-889bbc1a2a42	expense	800	2026-01-13	COMPRA DO SERVIDOR	\N	f	\N	f	\N	\N	\N	f	2026-01-14 14:52:44.721411+00	2026-01-14 14:52:44.721411+00	\N	f
e1e2b169-60b1-498b-a899-7c352863f816	8b4c02fb-6511-473e-ae04-d3ee312f342b	af92b43d-2e99-4cbc-9ef8-b5c8cd765427	\N	\N	income	1000	2026-01-13	Transferência entre contas	\N	f	\N	f	\N	\N	69ff2de1-69f5-462f-a826-3055ab499d3a	t	2026-01-14 15:26:58.224058+00	2026-01-14 15:26:58.224058+00	\N	t
69ff2de1-69f5-462f-a826-3055ab499d3a	8b4c02fb-6511-473e-ae04-d3ee312f342b	cc5d48aa-21b5-4fd3-ae95-6288fe428be9	\N	\N	expense	1000	2026-01-13	Transferência entre contas	\N	f	\N	f	\N	\N	e1e2b169-60b1-498b-a899-7c352863f816	t	2026-01-14 15:26:58.224058+00	2026-01-14 15:26:58.224058+00	af92b43d-2e99-4cbc-9ef8-b5c8cd765427	t
981a364d-69a7-4088-8197-bffe2c2c7e73	8b4c02fb-6511-473e-ae04-d3ee312f342b	cc5d48aa-21b5-4fd3-ae95-6288fe428be9	\N	ed477b98-661b-4420-ac20-889bbc1a2a42	expense	1558	2026-01-13	COMPRA DOS HDS	\N	f	\N	f	\N	\N	\N	f	2026-01-14 15:28:36.69063+00	2026-01-14 15:28:36.69063+00	\N	f
17781fe8-0912-4640-ae8c-30b9d2a7cfe4	8b4c02fb-6511-473e-ae04-d3ee312f342b	037f48e4-92dd-45c0-8579-903255881517	\N	\N	income	130	2026-01-13	Transferência entre contas	\N	f	\N	f	\N	\N	d7480fa9-62e7-498b-8cfa-f789a6639716	t	2026-01-14 15:29:49.77644+00	2026-01-14 15:29:49.77644+00	\N	t
d7480fa9-62e7-498b-8cfa-f789a6639716	8b4c02fb-6511-473e-ae04-d3ee312f342b	cc5d48aa-21b5-4fd3-ae95-6288fe428be9	\N	\N	expense	130	2026-01-13	Transferência entre contas	\N	f	\N	f	\N	\N	17781fe8-0912-4640-ae8c-30b9d2a7cfe4	t	2026-01-14 15:29:49.77644+00	2026-01-14 15:29:49.77644+00	037f48e4-92dd-45c0-8579-903255881517	t
86cb23ce-5c88-4b8d-9dd0-220a3c190f30	8b4c02fb-6511-473e-ae04-d3ee312f342b	cc5d48aa-21b5-4fd3-ae95-6288fe428be9	\N	ed477b98-661b-4420-ac20-889bbc1a2a42	expense	56.9	2026-01-13	COMPRA DE MEMORIA SERVIDOR	\N	f	\N	f	\N	\N	\N	f	2026-01-14 15:30:21.905026+00	2026-01-14 15:30:21.905026+00	\N	f
07296564-3ced-42cf-a15a-e43f406df52d	8b4c02fb-6511-473e-ae04-d3ee312f342b	cc5d48aa-21b5-4fd3-ae95-6288fe428be9	\N	ed477b98-661b-4420-ac20-889bbc1a2a42	expense	132.23	2026-01-13	COMPRA DO SERVIDOR ALLIEXPRESS	\N	f	\N	f	\N	\N	\N	f	2026-01-14 15:30:52.176535+00	2026-01-14 15:30:52.176535+00	\N	f
\.


--
-- Data for Name: uploaded_statements; Type: TABLE DATA; Schema: public; Owner: onlifin
--

COPY public.uploaded_statements (id, user_id, file_name, file_path, file_type, file_size, status, analysis_result, error_message, created_at, analyzed_at, imported_at) FROM stdin;
\.


--
-- Name: audit_log_id_seq; Type: SEQUENCE SET; Schema: auth; Owner: onlifin
--

SELECT pg_catalog.setval('auth.audit_log_id_seq', 1, false);


--
-- Name: login_attempts_id_seq; Type: SEQUENCE SET; Schema: auth; Owner: onlifin
--

SELECT pg_catalog.setval('auth.login_attempts_id_seq', 227, true);


--
-- Name: audit_log audit_log_pkey; Type: CONSTRAINT; Schema: auth; Owner: onlifin
--

ALTER TABLE ONLY auth.audit_log
    ADD CONSTRAINT audit_log_pkey PRIMARY KEY (id);


--
-- Name: login_attempts login_attempts_pkey; Type: CONSTRAINT; Schema: auth; Owner: onlifin
--

ALTER TABLE ONLY auth.login_attempts
    ADD CONSTRAINT login_attempts_pkey PRIMARY KEY (id);


--
-- Name: users users_email_key; Type: CONSTRAINT; Schema: auth; Owner: onlifin
--

ALTER TABLE ONLY auth.users
    ADD CONSTRAINT users_email_key UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: auth; Owner: onlifin
--

ALTER TABLE ONLY auth.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: accounts accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.accounts
    ADD CONSTRAINT accounts_pkey PRIMARY KEY (id);


--
-- Name: ai_chat_logs ai_chat_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.ai_chat_logs
    ADD CONSTRAINT ai_chat_logs_pkey PRIMARY KEY (id);


--
-- Name: ai_configurations ai_configurations_pkey; Type: CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.ai_configurations
    ADD CONSTRAINT ai_configurations_pkey PRIMARY KEY (id);


--
-- Name: bills_to_pay bills_to_pay_pkey; Type: CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.bills_to_pay
    ADD CONSTRAINT bills_to_pay_pkey PRIMARY KEY (id);


--
-- Name: bills_to_receive bills_to_receive_pkey; Type: CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.bills_to_receive
    ADD CONSTRAINT bills_to_receive_pkey PRIMARY KEY (id);


--
-- Name: cards cards_pkey; Type: CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.cards
    ADD CONSTRAINT cards_pkey PRIMARY KEY (id);


--
-- Name: categories categories_pkey; Type: CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_pkey PRIMARY KEY (id);


--
-- Name: financial_forecasts financial_forecasts_pkey; Type: CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.financial_forecasts
    ADD CONSTRAINT financial_forecasts_pkey PRIMARY KEY (id);


--
-- Name: import_history import_history_pkey; Type: CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.import_history
    ADD CONSTRAINT import_history_pkey PRIMARY KEY (id);


--
-- Name: notifications notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_pkey PRIMARY KEY (id);


--
-- Name: profiles profiles_pkey; Type: CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.profiles
    ADD CONSTRAINT profiles_pkey PRIMARY KEY (id);


--
-- Name: profiles profiles_username_key; Type: CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.profiles
    ADD CONSTRAINT profiles_username_key UNIQUE (username);


--
-- Name: transactions transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_pkey PRIMARY KEY (id);


--
-- Name: uploaded_statements uploaded_statements_pkey; Type: CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.uploaded_statements
    ADD CONSTRAINT uploaded_statements_pkey PRIMARY KEY (id);


--
-- Name: idx_audit_log_action; Type: INDEX; Schema: auth; Owner: onlifin
--

CREATE INDEX idx_audit_log_action ON auth.audit_log USING btree (action);


--
-- Name: idx_audit_log_created_at; Type: INDEX; Schema: auth; Owner: onlifin
--

CREATE INDEX idx_audit_log_created_at ON auth.audit_log USING btree (created_at DESC);


--
-- Name: idx_audit_log_user_id; Type: INDEX; Schema: auth; Owner: onlifin
--

CREATE INDEX idx_audit_log_user_id ON auth.audit_log USING btree (user_id);


--
-- Name: idx_login_attempts_email_time; Type: INDEX; Schema: auth; Owner: onlifin
--

CREATE INDEX idx_login_attempts_email_time ON auth.login_attempts USING btree (email, attempted_at DESC);


--
-- Name: idx_accounts_user_id; Type: INDEX; Schema: public; Owner: onlifin
--

CREATE INDEX idx_accounts_user_id ON public.accounts USING btree (user_id);


--
-- Name: idx_ai_chat_logs_user_id; Type: INDEX; Schema: public; Owner: onlifin
--

CREATE INDEX idx_ai_chat_logs_user_id ON public.ai_chat_logs USING btree (user_id);


--
-- Name: idx_bills_to_pay_due_date; Type: INDEX; Schema: public; Owner: onlifin
--

CREATE INDEX idx_bills_to_pay_due_date ON public.bills_to_pay USING btree (due_date);


--
-- Name: idx_bills_to_pay_status; Type: INDEX; Schema: public; Owner: onlifin
--

CREATE INDEX idx_bills_to_pay_status ON public.bills_to_pay USING btree (status);


--
-- Name: idx_bills_to_pay_user_id; Type: INDEX; Schema: public; Owner: onlifin
--

CREATE INDEX idx_bills_to_pay_user_id ON public.bills_to_pay USING btree (user_id);


--
-- Name: idx_bills_to_receive_due_date; Type: INDEX; Schema: public; Owner: onlifin
--

CREATE INDEX idx_bills_to_receive_due_date ON public.bills_to_receive USING btree (due_date);


--
-- Name: idx_bills_to_receive_status; Type: INDEX; Schema: public; Owner: onlifin
--

CREATE INDEX idx_bills_to_receive_status ON public.bills_to_receive USING btree (status);


--
-- Name: idx_bills_to_receive_user_id; Type: INDEX; Schema: public; Owner: onlifin
--

CREATE INDEX idx_bills_to_receive_user_id ON public.bills_to_receive USING btree (user_id);


--
-- Name: idx_cards_user_id; Type: INDEX; Schema: public; Owner: onlifin
--

CREATE INDEX idx_cards_user_id ON public.cards USING btree (user_id);


--
-- Name: idx_financial_forecasts_calculation_date; Type: INDEX; Schema: public; Owner: onlifin
--

CREATE INDEX idx_financial_forecasts_calculation_date ON public.financial_forecasts USING btree (calculation_date DESC);


--
-- Name: idx_financial_forecasts_user_id; Type: INDEX; Schema: public; Owner: onlifin
--

CREATE INDEX idx_financial_forecasts_user_id ON public.financial_forecasts USING btree (user_id);


--
-- Name: idx_notifications_created_at; Type: INDEX; Schema: public; Owner: onlifin
--

CREATE INDEX idx_notifications_created_at ON public.notifications USING btree (created_at DESC);


--
-- Name: idx_notifications_is_read; Type: INDEX; Schema: public; Owner: onlifin
--

CREATE INDEX idx_notifications_is_read ON public.notifications USING btree (is_read);


--
-- Name: idx_notifications_user_id; Type: INDEX; Schema: public; Owner: onlifin
--

CREATE INDEX idx_notifications_user_id ON public.notifications USING btree (user_id);


--
-- Name: idx_transactions_category_id; Type: INDEX; Schema: public; Owner: onlifin
--

CREATE INDEX idx_transactions_category_id ON public.transactions USING btree (category_id);


--
-- Name: idx_transactions_date; Type: INDEX; Schema: public; Owner: onlifin
--

CREATE INDEX idx_transactions_date ON public.transactions USING btree (date);


--
-- Name: idx_transactions_is_transfer; Type: INDEX; Schema: public; Owner: onlifin
--

CREATE INDEX idx_transactions_is_transfer ON public.transactions USING btree (is_transfer) WHERE (is_transfer = true);


--
-- Name: idx_transactions_transfer_destination; Type: INDEX; Schema: public; Owner: onlifin
--

CREATE INDEX idx_transactions_transfer_destination ON public.transactions USING btree (transfer_destination_account_id) WHERE (transfer_destination_account_id IS NOT NULL);


--
-- Name: idx_transactions_user_id; Type: INDEX; Schema: public; Owner: onlifin
--

CREATE INDEX idx_transactions_user_id ON public.transactions USING btree (user_id);


--
-- Name: idx_uploaded_statements_status; Type: INDEX; Schema: public; Owner: onlifin
--

CREATE INDEX idx_uploaded_statements_status ON public.uploaded_statements USING btree (status);


--
-- Name: idx_uploaded_statements_user_id; Type: INDEX; Schema: public; Owner: onlifin
--

CREATE INDEX idx_uploaded_statements_user_id ON public.uploaded_statements USING btree (user_id);


--
-- Name: bills_to_pay trigger_delete_bill_to_pay_transaction; Type: TRIGGER; Schema: public; Owner: onlifin
--

CREATE TRIGGER trigger_delete_bill_to_pay_transaction AFTER DELETE ON public.bills_to_pay FOR EACH ROW EXECUTE FUNCTION public.delete_associated_transaction();


--
-- Name: bills_to_receive trigger_delete_bill_to_receive_transaction; Type: TRIGGER; Schema: public; Owner: onlifin
--

CREATE TRIGGER trigger_delete_bill_to_receive_transaction AFTER DELETE ON public.bills_to_receive FOR EACH ROW EXECUTE FUNCTION public.delete_associated_transaction();


--
-- Name: bills_to_pay trigger_handle_bill_payment; Type: TRIGGER; Schema: public; Owner: onlifin
--

CREATE TRIGGER trigger_handle_bill_payment BEFORE UPDATE ON public.bills_to_pay FOR EACH ROW EXECUTE FUNCTION public.handle_bill_payment();


--
-- Name: bills_to_receive trigger_handle_bill_receipt; Type: TRIGGER; Schema: public; Owner: onlifin
--

CREATE TRIGGER trigger_handle_bill_receipt BEFORE UPDATE ON public.bills_to_receive FOR EACH ROW EXECUTE FUNCTION public.handle_bill_receipt();


--
-- Name: transactions trigger_update_account_balance; Type: TRIGGER; Schema: public; Owner: onlifin
--

CREATE TRIGGER trigger_update_account_balance AFTER INSERT OR DELETE OR UPDATE ON public.transactions FOR EACH ROW EXECUTE FUNCTION public.update_account_balance_on_transaction();


--
-- Name: accounts trigger_update_balance_on_initial_balance_change; Type: TRIGGER; Schema: public; Owner: onlifin
--

CREATE TRIGGER trigger_update_balance_on_initial_balance_change BEFORE UPDATE ON public.accounts FOR EACH ROW EXECUTE FUNCTION public.update_balance_on_initial_balance_change();


--
-- Name: audit_log audit_log_user_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: onlifin
--

ALTER TABLE ONLY auth.audit_log
    ADD CONSTRAINT audit_log_user_id_fkey FOREIGN KEY (user_id) REFERENCES auth.users(id) ON DELETE SET NULL;


--
-- Name: accounts accounts_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.accounts
    ADD CONSTRAINT accounts_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.profiles(id) ON DELETE CASCADE;


--
-- Name: ai_chat_logs ai_chat_logs_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.ai_chat_logs
    ADD CONSTRAINT ai_chat_logs_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.profiles(id) ON DELETE CASCADE;


--
-- Name: bills_to_pay bills_to_pay_account_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.bills_to_pay
    ADD CONSTRAINT bills_to_pay_account_id_fkey FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE SET NULL;


--
-- Name: bills_to_pay bills_to_pay_category_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.bills_to_pay
    ADD CONSTRAINT bills_to_pay_category_id_fkey FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE SET NULL;


--
-- Name: bills_to_pay bills_to_pay_transaction_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.bills_to_pay
    ADD CONSTRAINT bills_to_pay_transaction_id_fkey FOREIGN KEY (transaction_id) REFERENCES public.transactions(id) ON DELETE SET NULL;


--
-- Name: bills_to_pay bills_to_pay_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.bills_to_pay
    ADD CONSTRAINT bills_to_pay_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.profiles(id) ON DELETE CASCADE;


--
-- Name: bills_to_receive bills_to_receive_account_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.bills_to_receive
    ADD CONSTRAINT bills_to_receive_account_id_fkey FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE SET NULL;


--
-- Name: bills_to_receive bills_to_receive_category_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.bills_to_receive
    ADD CONSTRAINT bills_to_receive_category_id_fkey FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE SET NULL;


--
-- Name: bills_to_receive bills_to_receive_transaction_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.bills_to_receive
    ADD CONSTRAINT bills_to_receive_transaction_id_fkey FOREIGN KEY (transaction_id) REFERENCES public.transactions(id) ON DELETE SET NULL;


--
-- Name: bills_to_receive bills_to_receive_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.bills_to_receive
    ADD CONSTRAINT bills_to_receive_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.profiles(id) ON DELETE CASCADE;


--
-- Name: cards cards_account_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.cards
    ADD CONSTRAINT cards_account_id_fkey FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE SET NULL;


--
-- Name: cards cards_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.cards
    ADD CONSTRAINT cards_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.profiles(id) ON DELETE CASCADE;


--
-- Name: categories categories_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.profiles(id) ON DELETE CASCADE;


--
-- Name: financial_forecasts financial_forecasts_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.financial_forecasts
    ADD CONSTRAINT financial_forecasts_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.profiles(id) ON DELETE CASCADE;


--
-- Name: import_history import_history_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.import_history
    ADD CONSTRAINT import_history_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.profiles(id) ON DELETE CASCADE;


--
-- Name: notifications notifications_related_forecast_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_related_forecast_id_fkey FOREIGN KEY (related_forecast_id) REFERENCES public.financial_forecasts(id) ON DELETE SET NULL;


--
-- Name: notifications notifications_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.profiles(id) ON DELETE CASCADE;


--
-- Name: profiles profiles_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.profiles
    ADD CONSTRAINT profiles_id_fkey FOREIGN KEY (id) REFERENCES auth.users(id) ON DELETE CASCADE;


--
-- Name: transactions transactions_account_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_account_id_fkey FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE SET NULL;


--
-- Name: transactions transactions_card_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_card_id_fkey FOREIGN KEY (card_id) REFERENCES public.cards(id) ON DELETE SET NULL;


--
-- Name: transactions transactions_category_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_category_id_fkey FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE SET NULL;


--
-- Name: transactions transactions_parent_transaction_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_parent_transaction_id_fkey FOREIGN KEY (parent_transaction_id) REFERENCES public.transactions(id) ON DELETE CASCADE;


--
-- Name: transactions transactions_transfer_destination_account_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_transfer_destination_account_id_fkey FOREIGN KEY (transfer_destination_account_id) REFERENCES public.accounts(id) ON DELETE SET NULL;


--
-- Name: transactions transactions_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.profiles(id) ON DELETE CASCADE;


--
-- Name: uploaded_statements uploaded_statements_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: onlifin
--

ALTER TABLE ONLY public.uploaded_statements
    ADD CONSTRAINT uploaded_statements_user_id_fkey FOREIGN KEY (user_id) REFERENCES auth.users(id);


--
-- Name: profiles Admins can delete profiles; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Admins can delete profiles" ON public.profiles FOR DELETE USING (auth.is_admin());


--
-- Name: profiles Admins can insert profiles; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Admins can insert profiles" ON public.profiles FOR INSERT WITH CHECK (auth.is_admin());


--
-- Name: uploaded_statements Admins have full access; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Admins have full access" ON public.uploaded_statements USING (public.is_admin(auth.uid()));


--
-- Name: bills_to_pay Admins have full access to bills to pay; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Admins have full access to bills to pay" ON public.bills_to_pay USING (public.is_admin(auth.uid()));


--
-- Name: bills_to_receive Admins have full access to bills to receive; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Admins have full access to bills to receive" ON public.bills_to_receive USING (public.is_admin(auth.uid()));


--
-- Name: financial_forecasts Admins have full access to forecasts; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Admins have full access to forecasts" ON public.financial_forecasts USING (public.is_admin(auth.uid()));


--
-- Name: notifications Admins have full access to notifications; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Admins have full access to notifications" ON public.notifications USING (public.is_admin(auth.uid()));


--
-- Name: bills_to_pay Users can create own bills to pay; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can create own bills to pay" ON public.bills_to_pay FOR INSERT WITH CHECK ((auth.user_id() = user_id));


--
-- Name: bills_to_receive Users can create own bills to receive; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can create own bills to receive" ON public.bills_to_receive FOR INSERT WITH CHECK ((auth.user_id() = user_id));


--
-- Name: accounts Users can delete own accounts; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can delete own accounts" ON public.accounts FOR DELETE USING (((user_id = auth.user_id()) OR auth.is_admin()));


--
-- Name: bills_to_pay Users can delete own bills to pay; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can delete own bills to pay" ON public.bills_to_pay FOR DELETE USING (((auth.user_id() = user_id) OR auth.is_admin()));


--
-- Name: bills_to_receive Users can delete own bills to receive; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can delete own bills to receive" ON public.bills_to_receive FOR DELETE USING (((auth.user_id() = user_id) OR auth.is_admin()));


--
-- Name: cards Users can delete own cards; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can delete own cards" ON public.cards FOR DELETE USING (((user_id = auth.user_id()) OR auth.is_admin()));


--
-- Name: categories Users can delete own categories; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can delete own categories" ON public.categories FOR DELETE USING (((user_id = auth.user_id()) OR auth.is_admin()));


--
-- Name: ai_chat_logs Users can delete own chat logs; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can delete own chat logs" ON public.ai_chat_logs FOR DELETE USING (((user_id = auth.user_id()) OR auth.is_admin()));


--
-- Name: import_history Users can delete own import history; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can delete own import history" ON public.import_history FOR DELETE USING (((user_id = auth.user_id()) OR auth.is_admin()));


--
-- Name: transactions Users can delete own transactions; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can delete own transactions" ON public.transactions FOR DELETE USING (((user_id = auth.user_id()) OR auth.is_admin()));


--
-- Name: accounts Users can insert own accounts; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can insert own accounts" ON public.accounts FOR INSERT WITH CHECK ((user_id = auth.user_id()));


--
-- Name: cards Users can insert own cards; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can insert own cards" ON public.cards FOR INSERT WITH CHECK ((user_id = auth.user_id()));


--
-- Name: categories Users can insert own categories; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can insert own categories" ON public.categories FOR INSERT WITH CHECK ((user_id = auth.user_id()));


--
-- Name: ai_chat_logs Users can insert own chat logs; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can insert own chat logs" ON public.ai_chat_logs FOR INSERT WITH CHECK ((user_id = auth.user_id()));


--
-- Name: import_history Users can insert own import history; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can insert own import history" ON public.import_history FOR INSERT WITH CHECK ((user_id = auth.user_id()));


--
-- Name: transactions Users can insert own transactions; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can insert own transactions" ON public.transactions FOR INSERT WITH CHECK ((user_id = auth.user_id()));


--
-- Name: uploaded_statements Users can insert own uploads; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can insert own uploads" ON public.uploaded_statements FOR INSERT WITH CHECK ((auth.uid() = user_id));


--
-- Name: accounts Users can update own accounts; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can update own accounts" ON public.accounts FOR UPDATE USING ((user_id = auth.user_id())) WITH CHECK ((user_id = auth.user_id()));


--
-- Name: bills_to_pay Users can update own bills to pay; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can update own bills to pay" ON public.bills_to_pay FOR UPDATE USING (((auth.user_id() = user_id) OR auth.is_admin()));


--
-- Name: bills_to_receive Users can update own bills to receive; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can update own bills to receive" ON public.bills_to_receive FOR UPDATE USING (((auth.user_id() = user_id) OR auth.is_admin()));


--
-- Name: cards Users can update own cards; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can update own cards" ON public.cards FOR UPDATE USING ((user_id = auth.user_id())) WITH CHECK ((user_id = auth.user_id()));


--
-- Name: categories Users can update own categories; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can update own categories" ON public.categories FOR UPDATE USING ((user_id = auth.user_id())) WITH CHECK ((user_id = auth.user_id()));


--
-- Name: import_history Users can update own import history; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can update own import history" ON public.import_history FOR UPDATE USING ((user_id = auth.user_id())) WITH CHECK ((user_id = auth.user_id()));


--
-- Name: notifications Users can update own notifications; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can update own notifications" ON public.notifications FOR UPDATE USING (((auth.user_id() = user_id) OR auth.is_admin()));


--
-- Name: profiles Users can update own profile; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can update own profile" ON public.profiles FOR UPDATE USING ((id = auth.user_id())) WITH CHECK (((id = auth.user_id()) AND (role = ( SELECT profiles_1.role
   FROM public.profiles profiles_1
  WHERE (profiles_1.id = auth.user_id())))));


--
-- Name: transactions Users can update own transactions; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can update own transactions" ON public.transactions FOR UPDATE USING ((user_id = auth.user_id())) WITH CHECK ((user_id = auth.user_id()));


--
-- Name: uploaded_statements Users can update own uploads; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can update own uploads" ON public.uploaded_statements FOR UPDATE USING ((auth.uid() = user_id));


--
-- Name: accounts Users can view own accounts; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can view own accounts" ON public.accounts FOR SELECT USING (((user_id = auth.user_id()) OR auth.is_admin()));


--
-- Name: bills_to_pay Users can view own bills to pay; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can view own bills to pay" ON public.bills_to_pay FOR SELECT USING (((auth.user_id() = user_id) OR auth.is_admin()));


--
-- Name: bills_to_receive Users can view own bills to receive; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can view own bills to receive" ON public.bills_to_receive FOR SELECT USING (((auth.user_id() = user_id) OR auth.is_admin()));


--
-- Name: cards Users can view own cards; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can view own cards" ON public.cards FOR SELECT USING (((user_id = auth.user_id()) OR auth.is_admin()));


--
-- Name: categories Users can view own categories; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can view own categories" ON public.categories FOR SELECT USING (((user_id = auth.user_id()) OR auth.is_admin()));


--
-- Name: ai_chat_logs Users can view own chat logs; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can view own chat logs" ON public.ai_chat_logs FOR SELECT USING (((user_id = auth.user_id()) OR auth.is_admin()));


--
-- Name: financial_forecasts Users can view own forecasts; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can view own forecasts" ON public.financial_forecasts FOR SELECT USING (((auth.user_id() = user_id) OR auth.is_admin()));


--
-- Name: import_history Users can view own import history; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can view own import history" ON public.import_history FOR SELECT USING (((user_id = auth.user_id()) OR auth.is_admin()));


--
-- Name: notifications Users can view own notifications; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can view own notifications" ON public.notifications FOR SELECT USING (((auth.user_id() = user_id) OR auth.is_admin()));


--
-- Name: profiles Users can view own profile; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can view own profile" ON public.profiles FOR SELECT USING (((id = auth.user_id()) OR auth.is_admin()));


--
-- Name: transactions Users can view own transactions; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can view own transactions" ON public.transactions FOR SELECT USING (((user_id = auth.user_id()) OR auth.is_admin()));


--
-- Name: uploaded_statements Users can view own uploads; Type: POLICY; Schema: public; Owner: onlifin
--

CREATE POLICY "Users can view own uploads" ON public.uploaded_statements FOR SELECT USING ((auth.uid() = user_id));


--
-- Name: accounts; Type: ROW SECURITY; Schema: public; Owner: onlifin
--

ALTER TABLE public.accounts ENABLE ROW LEVEL SECURITY;

--
-- Name: ai_chat_logs; Type: ROW SECURITY; Schema: public; Owner: onlifin
--

ALTER TABLE public.ai_chat_logs ENABLE ROW LEVEL SECURITY;

--
-- Name: bills_to_pay; Type: ROW SECURITY; Schema: public; Owner: onlifin
--

ALTER TABLE public.bills_to_pay ENABLE ROW LEVEL SECURITY;

--
-- Name: bills_to_receive; Type: ROW SECURITY; Schema: public; Owner: onlifin
--

ALTER TABLE public.bills_to_receive ENABLE ROW LEVEL SECURITY;

--
-- Name: cards; Type: ROW SECURITY; Schema: public; Owner: onlifin
--

ALTER TABLE public.cards ENABLE ROW LEVEL SECURITY;

--
-- Name: categories; Type: ROW SECURITY; Schema: public; Owner: onlifin
--

ALTER TABLE public.categories ENABLE ROW LEVEL SECURITY;

--
-- Name: financial_forecasts; Type: ROW SECURITY; Schema: public; Owner: onlifin
--

ALTER TABLE public.financial_forecasts ENABLE ROW LEVEL SECURITY;

--
-- Name: import_history; Type: ROW SECURITY; Schema: public; Owner: onlifin
--

ALTER TABLE public.import_history ENABLE ROW LEVEL SECURITY;

--
-- Name: notifications; Type: ROW SECURITY; Schema: public; Owner: onlifin
--

ALTER TABLE public.notifications ENABLE ROW LEVEL SECURITY;

--
-- Name: profiles; Type: ROW SECURITY; Schema: public; Owner: onlifin
--

ALTER TABLE public.profiles ENABLE ROW LEVEL SECURITY;

--
-- Name: transactions; Type: ROW SECURITY; Schema: public; Owner: onlifin
--

ALTER TABLE public.transactions ENABLE ROW LEVEL SECURITY;

--
-- Name: uploaded_statements; Type: ROW SECURITY; Schema: public; Owner: onlifin
--

ALTER TABLE public.uploaded_statements ENABLE ROW LEVEL SECURITY;

--
-- Name: SCHEMA auth; Type: ACL; Schema: -; Owner: onlifin
--

GRANT USAGE ON SCHEMA auth TO anon;
GRANT USAGE ON SCHEMA auth TO authenticated;


--
-- Name: SCHEMA public; Type: ACL; Schema: -; Owner: pg_database_owner
--

GRANT USAGE ON SCHEMA public TO anon;
GRANT USAGE ON SCHEMA public TO authenticated;
GRANT USAGE ON SCHEMA public TO web_anon;


--
-- Name: FUNCTION base64url_encode(data bytea); Type: ACL; Schema: auth; Owner: onlifin
--

GRANT ALL ON FUNCTION auth.base64url_encode(data bytea) TO anon;
GRANT ALL ON FUNCTION auth.base64url_encode(data bytea) TO authenticated;


--
-- Name: FUNCTION check_login_rate_limit(p_email text, p_max_attempts integer, p_window_minutes integer); Type: ACL; Schema: auth; Owner: onlifin
--

GRANT ALL ON FUNCTION auth.check_login_rate_limit(p_email text, p_max_attempts integer, p_window_minutes integer) TO anon;
GRANT ALL ON FUNCTION auth.check_login_rate_limit(p_email text, p_max_attempts integer, p_window_minutes integer) TO authenticated;


--
-- Name: FUNCTION cleanup_old_login_attempts(); Type: ACL; Schema: auth; Owner: onlifin
--

GRANT ALL ON FUNCTION auth.cleanup_old_login_attempts() TO anon;
GRANT ALL ON FUNCTION auth.cleanup_old_login_attempts() TO authenticated;


--
-- Name: FUNCTION generate_jwt(p_user_id uuid, p_email text, p_app_role text, p_exp_hours integer); Type: ACL; Schema: auth; Owner: onlifin
--

GRANT ALL ON FUNCTION auth.generate_jwt(p_user_id uuid, p_email text, p_app_role text, p_exp_hours integer) TO anon;
GRANT ALL ON FUNCTION auth.generate_jwt(p_user_id uuid, p_email text, p_app_role text, p_exp_hours integer) TO authenticated;


--
-- Name: FUNCTION hash_password(password text); Type: ACL; Schema: auth; Owner: onlifin
--

GRANT ALL ON FUNCTION auth.hash_password(password text) TO anon;
GRANT ALL ON FUNCTION auth.hash_password(password text) TO authenticated;


--
-- Name: FUNCTION is_admin(); Type: ACL; Schema: auth; Owner: onlifin
--

GRANT ALL ON FUNCTION auth.is_admin() TO anon;
GRANT ALL ON FUNCTION auth.is_admin() TO authenticated;


--
-- Name: FUNCTION log_action(p_user_id uuid, p_action text, p_details jsonb); Type: ACL; Schema: auth; Owner: onlifin
--

GRANT ALL ON FUNCTION auth.log_action(p_user_id uuid, p_action text, p_details jsonb) TO anon;
GRANT ALL ON FUNCTION auth.log_action(p_user_id uuid, p_action text, p_details jsonb) TO authenticated;


--
-- Name: FUNCTION login(p_email text, p_password text); Type: ACL; Schema: auth; Owner: onlifin
--

GRANT ALL ON FUNCTION auth.login(p_email text, p_password text) TO anon;
GRANT ALL ON FUNCTION auth.login(p_email text, p_password text) TO authenticated;


--
-- Name: FUNCTION register(p_email text, p_password text); Type: ACL; Schema: auth; Owner: onlifin
--

GRANT ALL ON FUNCTION auth.register(p_email text, p_password text) TO anon;
GRANT ALL ON FUNCTION auth.register(p_email text, p_password text) TO authenticated;


--
-- Name: FUNCTION role(); Type: ACL; Schema: auth; Owner: onlifin
--

GRANT ALL ON FUNCTION auth.role() TO anon;
GRANT ALL ON FUNCTION auth.role() TO authenticated;


--
-- Name: FUNCTION uid(); Type: ACL; Schema: auth; Owner: onlifin
--

GRANT ALL ON FUNCTION auth.uid() TO anon;
GRANT ALL ON FUNCTION auth.uid() TO authenticated;


--
-- Name: FUNCTION user_id(); Type: ACL; Schema: auth; Owner: onlifin
--

GRANT ALL ON FUNCTION auth.user_id() TO anon;
GRANT ALL ON FUNCTION auth.user_id() TO authenticated;


--
-- Name: FUNCTION verify_password(password text, password_hash text); Type: ACL; Schema: auth; Owner: onlifin
--

GRANT ALL ON FUNCTION auth.verify_password(password text, password_hash text) TO anon;
GRANT ALL ON FUNCTION auth.verify_password(password text, password_hash text) TO authenticated;


--
-- Name: FUNCTION admin_create_user(p_username text, p_password text, p_full_name text, p_role text); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.admin_create_user(p_username text, p_password text, p_full_name text, p_role text) TO authenticated;
GRANT ALL ON FUNCTION public.admin_create_user(p_username text, p_password text, p_full_name text, p_role text) TO anon;


--
-- Name: FUNCTION admin_delete_user(p_user_id uuid); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.admin_delete_user(p_user_id uuid) TO authenticated;
GRANT ALL ON FUNCTION public.admin_delete_user(p_user_id uuid) TO anon;


--
-- Name: FUNCTION admin_list_users(); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.admin_list_users() TO authenticated;
GRANT ALL ON FUNCTION public.admin_list_users() TO anon;


--
-- Name: FUNCTION admin_reset_password(p_user_id uuid, p_new_password text); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.admin_reset_password(p_user_id uuid, p_new_password text) TO authenticated;
GRANT ALL ON FUNCTION public.admin_reset_password(p_user_id uuid, p_new_password text) TO anon;


--
-- Name: FUNCTION admin_update_user(p_user_id uuid, p_email text, p_full_name text, p_role text); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.admin_update_user(p_user_id uuid, p_email text, p_full_name text, p_role text) TO authenticated;
GRANT ALL ON FUNCTION public.admin_update_user(p_user_id uuid, p_email text, p_full_name text, p_role text) TO anon;


--
-- Name: FUNCTION armor(bytea); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.armor(bytea) TO web_anon;
GRANT ALL ON FUNCTION public.armor(bytea) TO anon;
GRANT ALL ON FUNCTION public.armor(bytea) TO authenticated;


--
-- Name: FUNCTION armor(bytea, text[], text[]); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.armor(bytea, text[], text[]) TO web_anon;
GRANT ALL ON FUNCTION public.armor(bytea, text[], text[]) TO anon;
GRANT ALL ON FUNCTION public.armor(bytea, text[], text[]) TO authenticated;


--
-- Name: FUNCTION create_notification(p_user_id uuid, p_title text, p_message text, p_type text, p_severity text, p_related_forecast_id uuid, p_related_bill_id uuid, p_action_url text); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.create_notification(p_user_id uuid, p_title text, p_message text, p_type text, p_severity text, p_related_forecast_id uuid, p_related_bill_id uuid, p_action_url text) TO authenticated;
GRANT ALL ON FUNCTION public.create_notification(p_user_id uuid, p_title text, p_message text, p_type text, p_severity text, p_related_forecast_id uuid, p_related_bill_id uuid, p_action_url text) TO anon;


--
-- Name: FUNCTION create_transfer(p_user_id uuid, p_source_account_id uuid, p_destination_account_id uuid, p_amount numeric, p_date date, p_description text); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.create_transfer(p_user_id uuid, p_source_account_id uuid, p_destination_account_id uuid, p_amount numeric, p_date date, p_description text) TO authenticated;
GRANT ALL ON FUNCTION public.create_transfer(p_user_id uuid, p_source_account_id uuid, p_destination_account_id uuid, p_amount numeric, p_date date, p_description text) TO anon;


--
-- Name: FUNCTION crypt(text, text); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.crypt(text, text) TO web_anon;
GRANT ALL ON FUNCTION public.crypt(text, text) TO anon;
GRANT ALL ON FUNCTION public.crypt(text, text) TO authenticated;


--
-- Name: FUNCTION dearmor(text); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.dearmor(text) TO web_anon;
GRANT ALL ON FUNCTION public.dearmor(text) TO anon;
GRANT ALL ON FUNCTION public.dearmor(text) TO authenticated;


--
-- Name: FUNCTION decrypt(bytea, bytea, text); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.decrypt(bytea, bytea, text) TO web_anon;
GRANT ALL ON FUNCTION public.decrypt(bytea, bytea, text) TO anon;
GRANT ALL ON FUNCTION public.decrypt(bytea, bytea, text) TO authenticated;


--
-- Name: FUNCTION decrypt_iv(bytea, bytea, bytea, text); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.decrypt_iv(bytea, bytea, bytea, text) TO web_anon;
GRANT ALL ON FUNCTION public.decrypt_iv(bytea, bytea, bytea, text) TO anon;
GRANT ALL ON FUNCTION public.decrypt_iv(bytea, bytea, bytea, text) TO authenticated;


--
-- Name: FUNCTION digest(bytea, text); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.digest(bytea, text) TO web_anon;
GRANT ALL ON FUNCTION public.digest(bytea, text) TO anon;
GRANT ALL ON FUNCTION public.digest(bytea, text) TO authenticated;


--
-- Name: FUNCTION digest(text, text); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.digest(text, text) TO web_anon;
GRANT ALL ON FUNCTION public.digest(text, text) TO anon;
GRANT ALL ON FUNCTION public.digest(text, text) TO authenticated;


--
-- Name: FUNCTION encrypt(bytea, bytea, text); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.encrypt(bytea, bytea, text) TO web_anon;
GRANT ALL ON FUNCTION public.encrypt(bytea, bytea, text) TO anon;
GRANT ALL ON FUNCTION public.encrypt(bytea, bytea, text) TO authenticated;


--
-- Name: FUNCTION encrypt_iv(bytea, bytea, bytea, text); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.encrypt_iv(bytea, bytea, bytea, text) TO web_anon;
GRANT ALL ON FUNCTION public.encrypt_iv(bytea, bytea, bytea, text) TO anon;
GRANT ALL ON FUNCTION public.encrypt_iv(bytea, bytea, bytea, text) TO authenticated;


--
-- Name: FUNCTION gen_random_bytes(integer); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.gen_random_bytes(integer) TO web_anon;
GRANT ALL ON FUNCTION public.gen_random_bytes(integer) TO anon;
GRANT ALL ON FUNCTION public.gen_random_bytes(integer) TO authenticated;


--
-- Name: FUNCTION gen_random_uuid(); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.gen_random_uuid() TO web_anon;
GRANT ALL ON FUNCTION public.gen_random_uuid() TO anon;
GRANT ALL ON FUNCTION public.gen_random_uuid() TO authenticated;


--
-- Name: FUNCTION gen_salt(text); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.gen_salt(text) TO web_anon;
GRANT ALL ON FUNCTION public.gen_salt(text) TO anon;
GRANT ALL ON FUNCTION public.gen_salt(text) TO authenticated;


--
-- Name: FUNCTION gen_salt(text, integer); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.gen_salt(text, integer) TO web_anon;
GRANT ALL ON FUNCTION public.gen_salt(text, integer) TO anon;
GRANT ALL ON FUNCTION public.gen_salt(text, integer) TO authenticated;


--
-- Name: FUNCTION get_transfer_pair(p_transaction_id uuid); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.get_transfer_pair(p_transaction_id uuid) TO authenticated;
GRANT ALL ON FUNCTION public.get_transfer_pair(p_transaction_id uuid) TO anon;


--
-- Name: FUNCTION get_user_total_balance(p_user_id uuid); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.get_user_total_balance(p_user_id uuid) TO authenticated;
GRANT ALL ON FUNCTION public.get_user_total_balance(p_user_id uuid) TO anon;


--
-- Name: FUNCTION hmac(bytea, bytea, text); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.hmac(bytea, bytea, text) TO web_anon;
GRANT ALL ON FUNCTION public.hmac(bytea, bytea, text) TO anon;
GRANT ALL ON FUNCTION public.hmac(bytea, bytea, text) TO authenticated;


--
-- Name: FUNCTION hmac(text, text, text); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.hmac(text, text, text) TO web_anon;
GRANT ALL ON FUNCTION public.hmac(text, text, text) TO anon;
GRANT ALL ON FUNCTION public.hmac(text, text, text) TO authenticated;


--
-- Name: FUNCTION is_admin(uid uuid); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.is_admin(uid uuid) TO authenticated;
GRANT ALL ON FUNCTION public.is_admin(uid uuid) TO anon;


--
-- Name: FUNCTION login(p_email text, p_password text); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.login(p_email text, p_password text) TO anon;
GRANT ALL ON FUNCTION public.login(p_email text, p_password text) TO authenticated;
GRANT ALL ON FUNCTION public.login(p_email text, p_password text) TO web_anon;


--
-- Name: FUNCTION onlifin_auth_token(); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.onlifin_auth_token() TO anon;
GRANT ALL ON FUNCTION public.onlifin_auth_token() TO authenticated;


--
-- Name: FUNCTION onlifin_auth_user(); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.onlifin_auth_user() TO authenticated;
GRANT ALL ON FUNCTION public.onlifin_auth_user() TO anon;


--
-- Name: FUNCTION pgp_armor_headers(text, OUT key text, OUT value text); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.pgp_armor_headers(text, OUT key text, OUT value text) TO web_anon;
GRANT ALL ON FUNCTION public.pgp_armor_headers(text, OUT key text, OUT value text) TO anon;
GRANT ALL ON FUNCTION public.pgp_armor_headers(text, OUT key text, OUT value text) TO authenticated;


--
-- Name: FUNCTION pgp_key_id(bytea); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.pgp_key_id(bytea) TO web_anon;
GRANT ALL ON FUNCTION public.pgp_key_id(bytea) TO anon;
GRANT ALL ON FUNCTION public.pgp_key_id(bytea) TO authenticated;


--
-- Name: FUNCTION pgp_pub_decrypt(bytea, bytea); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.pgp_pub_decrypt(bytea, bytea) TO web_anon;
GRANT ALL ON FUNCTION public.pgp_pub_decrypt(bytea, bytea) TO anon;
GRANT ALL ON FUNCTION public.pgp_pub_decrypt(bytea, bytea) TO authenticated;


--
-- Name: FUNCTION pgp_pub_decrypt(bytea, bytea, text); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.pgp_pub_decrypt(bytea, bytea, text) TO web_anon;
GRANT ALL ON FUNCTION public.pgp_pub_decrypt(bytea, bytea, text) TO anon;
GRANT ALL ON FUNCTION public.pgp_pub_decrypt(bytea, bytea, text) TO authenticated;


--
-- Name: FUNCTION pgp_pub_decrypt(bytea, bytea, text, text); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.pgp_pub_decrypt(bytea, bytea, text, text) TO web_anon;
GRANT ALL ON FUNCTION public.pgp_pub_decrypt(bytea, bytea, text, text) TO anon;
GRANT ALL ON FUNCTION public.pgp_pub_decrypt(bytea, bytea, text, text) TO authenticated;


--
-- Name: FUNCTION pgp_pub_decrypt_bytea(bytea, bytea); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.pgp_pub_decrypt_bytea(bytea, bytea) TO web_anon;
GRANT ALL ON FUNCTION public.pgp_pub_decrypt_bytea(bytea, bytea) TO anon;
GRANT ALL ON FUNCTION public.pgp_pub_decrypt_bytea(bytea, bytea) TO authenticated;


--
-- Name: FUNCTION pgp_pub_decrypt_bytea(bytea, bytea, text); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.pgp_pub_decrypt_bytea(bytea, bytea, text) TO web_anon;
GRANT ALL ON FUNCTION public.pgp_pub_decrypt_bytea(bytea, bytea, text) TO anon;
GRANT ALL ON FUNCTION public.pgp_pub_decrypt_bytea(bytea, bytea, text) TO authenticated;


--
-- Name: FUNCTION pgp_pub_decrypt_bytea(bytea, bytea, text, text); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.pgp_pub_decrypt_bytea(bytea, bytea, text, text) TO web_anon;
GRANT ALL ON FUNCTION public.pgp_pub_decrypt_bytea(bytea, bytea, text, text) TO anon;
GRANT ALL ON FUNCTION public.pgp_pub_decrypt_bytea(bytea, bytea, text, text) TO authenticated;


--
-- Name: FUNCTION pgp_pub_encrypt(text, bytea); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.pgp_pub_encrypt(text, bytea) TO web_anon;
GRANT ALL ON FUNCTION public.pgp_pub_encrypt(text, bytea) TO anon;
GRANT ALL ON FUNCTION public.pgp_pub_encrypt(text, bytea) TO authenticated;


--
-- Name: FUNCTION pgp_pub_encrypt(text, bytea, text); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.pgp_pub_encrypt(text, bytea, text) TO web_anon;
GRANT ALL ON FUNCTION public.pgp_pub_encrypt(text, bytea, text) TO anon;
GRANT ALL ON FUNCTION public.pgp_pub_encrypt(text, bytea, text) TO authenticated;


--
-- Name: FUNCTION pgp_pub_encrypt_bytea(bytea, bytea); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.pgp_pub_encrypt_bytea(bytea, bytea) TO web_anon;
GRANT ALL ON FUNCTION public.pgp_pub_encrypt_bytea(bytea, bytea) TO anon;
GRANT ALL ON FUNCTION public.pgp_pub_encrypt_bytea(bytea, bytea) TO authenticated;


--
-- Name: FUNCTION pgp_pub_encrypt_bytea(bytea, bytea, text); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.pgp_pub_encrypt_bytea(bytea, bytea, text) TO web_anon;
GRANT ALL ON FUNCTION public.pgp_pub_encrypt_bytea(bytea, bytea, text) TO anon;
GRANT ALL ON FUNCTION public.pgp_pub_encrypt_bytea(bytea, bytea, text) TO authenticated;


--
-- Name: FUNCTION pgp_sym_decrypt(bytea, text); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.pgp_sym_decrypt(bytea, text) TO web_anon;
GRANT ALL ON FUNCTION public.pgp_sym_decrypt(bytea, text) TO anon;
GRANT ALL ON FUNCTION public.pgp_sym_decrypt(bytea, text) TO authenticated;


--
-- Name: FUNCTION pgp_sym_decrypt(bytea, text, text); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.pgp_sym_decrypt(bytea, text, text) TO web_anon;
GRANT ALL ON FUNCTION public.pgp_sym_decrypt(bytea, text, text) TO anon;
GRANT ALL ON FUNCTION public.pgp_sym_decrypt(bytea, text, text) TO authenticated;


--
-- Name: FUNCTION pgp_sym_decrypt_bytea(bytea, text); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.pgp_sym_decrypt_bytea(bytea, text) TO web_anon;
GRANT ALL ON FUNCTION public.pgp_sym_decrypt_bytea(bytea, text) TO anon;
GRANT ALL ON FUNCTION public.pgp_sym_decrypt_bytea(bytea, text) TO authenticated;


--
-- Name: FUNCTION pgp_sym_decrypt_bytea(bytea, text, text); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.pgp_sym_decrypt_bytea(bytea, text, text) TO web_anon;
GRANT ALL ON FUNCTION public.pgp_sym_decrypt_bytea(bytea, text, text) TO anon;
GRANT ALL ON FUNCTION public.pgp_sym_decrypt_bytea(bytea, text, text) TO authenticated;


--
-- Name: FUNCTION pgp_sym_encrypt(text, text); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.pgp_sym_encrypt(text, text) TO web_anon;
GRANT ALL ON FUNCTION public.pgp_sym_encrypt(text, text) TO anon;
GRANT ALL ON FUNCTION public.pgp_sym_encrypt(text, text) TO authenticated;


--
-- Name: FUNCTION pgp_sym_encrypt(text, text, text); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.pgp_sym_encrypt(text, text, text) TO web_anon;
GRANT ALL ON FUNCTION public.pgp_sym_encrypt(text, text, text) TO anon;
GRANT ALL ON FUNCTION public.pgp_sym_encrypt(text, text, text) TO authenticated;


--
-- Name: FUNCTION pgp_sym_encrypt_bytea(bytea, text); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.pgp_sym_encrypt_bytea(bytea, text) TO web_anon;
GRANT ALL ON FUNCTION public.pgp_sym_encrypt_bytea(bytea, text) TO anon;
GRANT ALL ON FUNCTION public.pgp_sym_encrypt_bytea(bytea, text) TO authenticated;


--
-- Name: FUNCTION pgp_sym_encrypt_bytea(bytea, text, text); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.pgp_sym_encrypt_bytea(bytea, text, text) TO web_anon;
GRANT ALL ON FUNCTION public.pgp_sym_encrypt_bytea(bytea, text, text) TO anon;
GRANT ALL ON FUNCTION public.pgp_sym_encrypt_bytea(bytea, text, text) TO authenticated;


--
-- Name: FUNCTION register(p_email text, p_password text); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.register(p_email text, p_password text) TO anon;
GRANT ALL ON FUNCTION public.register(p_email text, p_password text) TO web_anon;
GRANT ALL ON FUNCTION public.register(p_email text, p_password text) TO authenticated;


--
-- Name: FUNCTION sign(payload json, secret text); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.sign(payload json, secret text) TO web_anon;
GRANT ALL ON FUNCTION public.sign(payload json, secret text) TO anon;
GRANT ALL ON FUNCTION public.sign(payload json, secret text) TO authenticated;


--
-- Name: FUNCTION supabase_auth_token(p_email text, p_password text, p_grant_type text); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.supabase_auth_token(p_email text, p_password text, p_grant_type text) TO anon;
GRANT ALL ON FUNCTION public.supabase_auth_token(p_email text, p_password text, p_grant_type text) TO authenticated;


--
-- Name: FUNCTION update_bills_status(); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.update_bills_status() TO authenticated;
GRANT ALL ON FUNCTION public.update_bills_status() TO anon;


--
-- Name: FUNCTION url_encode(data bytea); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.url_encode(data bytea) TO web_anon;
GRANT ALL ON FUNCTION public.url_encode(data bytea) TO anon;
GRANT ALL ON FUNCTION public.url_encode(data bytea) TO authenticated;


--
-- Name: FUNCTION url_encode_nopad(data bytea); Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON FUNCTION public.url_encode_nopad(data bytea) TO web_anon;
GRANT ALL ON FUNCTION public.url_encode_nopad(data bytea) TO anon;
GRANT ALL ON FUNCTION public.url_encode_nopad(data bytea) TO authenticated;


--
-- Name: TABLE audit_log; Type: ACL; Schema: auth; Owner: onlifin
--

GRANT ALL ON TABLE auth.audit_log TO anon;


--
-- Name: SEQUENCE audit_log_id_seq; Type: ACL; Schema: auth; Owner: onlifin
--

GRANT ALL ON SEQUENCE auth.audit_log_id_seq TO anon;


--
-- Name: TABLE login_attempts; Type: ACL; Schema: auth; Owner: onlifin
--

GRANT SELECT ON TABLE auth.login_attempts TO authenticated;
GRANT ALL ON TABLE auth.login_attempts TO anon;


--
-- Name: SEQUENCE login_attempts_id_seq; Type: ACL; Schema: auth; Owner: onlifin
--

GRANT ALL ON SEQUENCE auth.login_attempts_id_seq TO anon;


--
-- Name: TABLE users; Type: ACL; Schema: auth; Owner: onlifin
--

GRANT SELECT ON TABLE auth.users TO authenticated;
GRANT ALL ON TABLE auth.users TO anon;


--
-- Name: TABLE accounts; Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON TABLE public.accounts TO authenticated;
GRANT ALL ON TABLE public.accounts TO anon;
GRANT ALL ON TABLE public.accounts TO web_anon;


--
-- Name: TABLE ai_chat_logs; Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON TABLE public.ai_chat_logs TO authenticated;
GRANT ALL ON TABLE public.ai_chat_logs TO anon;
GRANT ALL ON TABLE public.ai_chat_logs TO web_anon;


--
-- Name: TABLE ai_configurations; Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON TABLE public.ai_configurations TO authenticated;
GRANT ALL ON TABLE public.ai_configurations TO anon;
GRANT ALL ON TABLE public.ai_configurations TO web_anon;


--
-- Name: TABLE bills_to_pay; Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON TABLE public.bills_to_pay TO authenticated;
GRANT ALL ON TABLE public.bills_to_pay TO anon;


--
-- Name: TABLE bills_to_receive; Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON TABLE public.bills_to_receive TO authenticated;
GRANT ALL ON TABLE public.bills_to_receive TO anon;


--
-- Name: TABLE cards; Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON TABLE public.cards TO authenticated;
GRANT ALL ON TABLE public.cards TO anon;
GRANT ALL ON TABLE public.cards TO web_anon;


--
-- Name: TABLE categories; Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON TABLE public.categories TO authenticated;
GRANT ALL ON TABLE public.categories TO anon;
GRANT ALL ON TABLE public.categories TO web_anon;


--
-- Name: TABLE financial_forecasts; Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON TABLE public.financial_forecasts TO authenticated;
GRANT ALL ON TABLE public.financial_forecasts TO anon;


--
-- Name: TABLE import_history; Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON TABLE public.import_history TO authenticated;
GRANT ALL ON TABLE public.import_history TO anon;
GRANT ALL ON TABLE public.import_history TO web_anon;


--
-- Name: TABLE notifications; Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON TABLE public.notifications TO authenticated;
GRANT ALL ON TABLE public.notifications TO anon;


--
-- Name: TABLE profiles; Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON TABLE public.profiles TO authenticated;
GRANT ALL ON TABLE public.profiles TO anon;
GRANT ALL ON TABLE public.profiles TO web_anon;


--
-- Name: TABLE transactions; Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON TABLE public.transactions TO authenticated;
GRANT ALL ON TABLE public.transactions TO anon;
GRANT ALL ON TABLE public.transactions TO web_anon;


--
-- Name: TABLE uploaded_statements; Type: ACL; Schema: public; Owner: onlifin
--

GRANT ALL ON TABLE public.uploaded_statements TO authenticated;
GRANT ALL ON TABLE public.uploaded_statements TO anon;


--
-- PostgreSQL database dump complete
--

\unrestrict AZwFacS2dAZGoJw3zF49jcqfTesrnVyBEucvaXP3pQaykiPZUbVePM0KJ1LBhbR

