-- ===========================================
-- 06 - Geração de JWT no PostgreSQL
-- ===========================================
-- Funções para gerar tokens JWT HS256 válidos
-- usando pgcrypto

CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- 1. Função para URL Encode (Base64Url)
CREATE OR REPLACE FUNCTION url_encode(data bytea) RETURNS text LANGUAGE sql AS $$
    SELECT translate(encode(data, 'base64'), '+/', '-_');
$$;

-- 2. Função para codificar URL Base64 sem padding
CREATE OR REPLACE FUNCTION url_encode_nopad(data bytea) RETURNS text LANGUAGE sql AS $$
    SELECT rtrim(translate(encode(data, 'base64'), '+/', '-_'), '=');
$$;

-- 3. Função para assinar JWT
CREATE OR REPLACE FUNCTION sign(payload json, secret text) RETURNS text LANGUAGE plpgsql AS $$
DECLARE
    header text;
    payload_encoded text;
    signature text;
BEGIN
    -- Header padrão HS256
    header := url_encode_nopad(convert_to('{"alg":"HS256","typ":"JWT"}', 'utf8'));
    
    -- Payload
    payload_encoded := url_encode_nopad(convert_to(payload::text, 'utf8'));
    
    -- Assinatura HMAC SHA256
    signature := url_encode_nopad(hmac(
        header || '.' || payload_encoded,
        secret,
        'sha256'
    ));
    
    RETURN header || '.' || payload_encoded || '.' || signature;
END;
$$;
