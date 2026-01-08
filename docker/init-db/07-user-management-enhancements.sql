-- ===========================================
-- 07 - Melhorias no Gerenciamento de Usuários
-- ===========================================
-- Adiciona campos de contato e informações estendidas aos perfis

-- Adicionar novos campos à tabela profiles
DO $$
BEGIN
    -- Email
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name='profiles' AND column_name='email') THEN
        ALTER TABLE profiles ADD COLUMN email text UNIQUE;
    END IF;

    -- Telefone
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name='profiles' AND column_name='phone') THEN
        ALTER TABLE profiles ADD COLUMN phone text;
    END IF;

    -- WhatsApp
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name='profiles' AND column_name='whatsapp') THEN
        ALTER TABLE profiles ADD COLUMN whatsapp text;
    END IF;

    -- CPF/CNPJ
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name='profiles' AND column_name='document') THEN
        ALTER TABLE profiles ADD COLUMN document text UNIQUE;
    END IF;

    -- Data de nascimento
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name='profiles' AND column_name='birth_date') THEN
        ALTER TABLE profiles ADD COLUMN birth_date date;
    END IF;

    -- Endereço
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name='profiles' AND column_name='address') THEN
        ALTER TABLE profiles ADD COLUMN address text;
    END IF;

    -- Cidade
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name='profiles' AND column_name='city') THEN
        ALTER TABLE profiles ADD COLUMN city text;
    END IF;

    -- Estado
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name='profiles' AND column_name='state') THEN
        ALTER TABLE profiles ADD COLUMN state text;
    END IF;

    -- CEP
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name='profiles' AND column_name='postal_code') THEN
        ALTER TABLE profiles ADD COLUMN postal_code text;
    END IF;

    -- Avatar URL
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name='profiles' AND column_name='avatar_url') THEN
        ALTER TABLE profiles ADD COLUMN avatar_url text;
    END IF;

    -- Status do usuário (ativo, suspenso, inativo)
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name='profiles' AND column_name='status') THEN
        ALTER TABLE profiles ADD COLUMN status text DEFAULT 'active' CHECK (status IN ('active', 'suspended', 'inactive'));
    END IF;

    -- Último login
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name='profiles' AND column_name='last_login_at') THEN
        ALTER TABLE profiles ADD COLUMN last_login_at timestamptz;
    END IF;

    -- Notas/Observações do admin
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name='profiles' AND column_name='admin_notes') THEN
        ALTER TABLE profiles ADD COLUMN admin_notes text;
    END IF;

    -- Data de atualização
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name='profiles' AND column_name='updated_at') THEN
        ALTER TABLE profiles ADD COLUMN updated_at timestamptz DEFAULT now();
    END IF;
END;
$$;

-- Criar índices para melhorar performance
CREATE INDEX IF NOT EXISTS idx_profiles_email ON profiles(email) WHERE email IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_profiles_phone ON profiles(phone) WHERE phone IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_profiles_document ON profiles(document) WHERE document IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_profiles_status ON profiles(status);
CREATE INDEX IF NOT EXISTS idx_profiles_role ON profiles(role);

-- Função para atualizar updated_at automaticamente
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = now();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger para atualizar updated_at em profiles
DROP TRIGGER IF EXISTS update_profiles_updated_at ON profiles;
CREATE TRIGGER update_profiles_updated_at
    BEFORE UPDATE ON profiles
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Comentários para documentação
COMMENT ON COLUMN profiles.email IS 'Email principal do usuário';
COMMENT ON COLUMN profiles.phone IS 'Telefone de contato (formato: +55 11 98765-4321)';
COMMENT ON COLUMN profiles.whatsapp IS 'WhatsApp para contato (pode ser diferente do telefone)';
COMMENT ON COLUMN profiles.document IS 'CPF ou CNPJ do usuário';
COMMENT ON COLUMN profiles.birth_date IS 'Data de nascimento';
COMMENT ON COLUMN profiles.address IS 'Endereço completo (rua, número, complemento)';
COMMENT ON COLUMN profiles.city IS 'Cidade';
COMMENT ON COLUMN profiles.state IS 'Estado (UF)';
COMMENT ON COLUMN profiles.postal_code IS 'CEP';
COMMENT ON COLUMN profiles.avatar_url IS 'URL da imagem de perfil';
COMMENT ON COLUMN profiles.status IS 'Status do usuário: active (ativo), suspended (suspenso), inactive (inativo)';
COMMENT ON COLUMN profiles.last_login_at IS 'Data e hora do último login';
COMMENT ON COLUMN profiles.admin_notes IS 'Notas administrativas sobre o usuário';

-- Atualizar email dos usuários existentes baseado no auth.users
UPDATE profiles p
SET email = au.email
FROM auth.users au
WHERE p.id = au.id AND p.email IS NULL;
