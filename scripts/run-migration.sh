#!/bin/bash
# ===========================================
# Script para executar migrações no banco de produção
# ===========================================
# Este script deve ser executado no VPS onde o Onlifin está rodando
# Uso: ./run-migration.sh

set -e

echo "🔄 Executando migração para adicionar colunas icon e initial_balance..."

# Executar a migração no container do banco de dados
docker exec onlifin-database psql -U onlifin -d onlifin << 'EOF'
-- Add icon and initial_balance columns to accounts table
DO $$
BEGIN
    -- Add icon column to accounts if not exists
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'accounts' AND column_name = 'icon'
    ) THEN
        ALTER TABLE accounts ADD COLUMN icon TEXT;
        COMMENT ON COLUMN accounts.icon IS 'Bank icon identifier (e.g., bb, itau, nubank)';
        RAISE NOTICE 'Coluna icon adicionada à tabela accounts';
    ELSE
        RAISE NOTICE 'Coluna icon já existe na tabela accounts';
    END IF;

    -- Add initial_balance column to accounts if not exists  
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'accounts' AND column_name = 'initial_balance'
    ) THEN
        ALTER TABLE accounts ADD COLUMN initial_balance NUMERIC DEFAULT 0 NOT NULL;
        COMMENT ON COLUMN accounts.initial_balance IS 'Initial balance when account was created';
        RAISE NOTICE 'Coluna initial_balance adicionada à tabela accounts';
    ELSE
        RAISE NOTICE 'Coluna initial_balance já existe na tabela accounts';
    END IF;
END;
$$;

-- Add icon and brand columns to cards table
DO $$
BEGIN
    -- Add icon column to cards if not exists
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'cards' AND column_name = 'icon'
    ) THEN
        ALTER TABLE cards ADD COLUMN icon TEXT;
        COMMENT ON COLUMN cards.icon IS 'Card brand icon identifier (e.g., visa, mastercard, elo)';
        RAISE NOTICE 'Coluna icon adicionada à tabela cards';
    ELSE
        RAISE NOTICE 'Coluna icon já existe na tabela cards';
    END IF;

    -- Add brand column to cards if not exists
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'cards' AND column_name = 'brand'
    ) THEN
        ALTER TABLE cards ADD COLUMN brand TEXT;
        COMMENT ON COLUMN cards.brand IS 'Card brand name';
        RAISE NOTICE 'Coluna brand adicionada à tabela cards';
    ELSE
        RAISE NOTICE 'Coluna brand já existe na tabela cards';
    END IF;
END;
$$;

-- Verify columns exist
SELECT 'accounts' as table_name, column_name, data_type 
FROM information_schema.columns 
WHERE table_name = 'accounts' AND column_name IN ('icon', 'initial_balance')
UNION ALL
SELECT 'cards' as table_name, column_name, data_type 
FROM information_schema.columns 
WHERE table_name = 'cards' AND column_name IN ('icon', 'brand')
ORDER BY table_name, column_name;
EOF

echo "✅ Migração concluída com sucesso!"
