-- Migration: Adicionar configurações de notificação às contas
-- Descrição: Adiciona campos para modo e frequência de notificação individual por conta

-- Adicionar campos à tabela bills_to_pay
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'bills_to_pay' 
        AND column_name = 'notification_mode'
    ) THEN
        ALTER TABLE bills_to_pay ADD COLUMN notification_mode TEXT DEFAULT 'default' CHECK (notification_mode IN ('default', 'custom', 'disabled'));
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'bills_to_pay' 
        AND column_name = 'notification_frequency'
    ) THEN
        ALTER TABLE bills_to_pay ADD COLUMN notification_frequency TEXT DEFAULT 'standard' CHECK (notification_frequency IN ('once', 'daily', 'weekly', 'standard'));
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'bills_to_pay' 
        AND column_name = 'custom_days_before'
    ) THEN
        ALTER TABLE bills_to_pay ADD COLUMN custom_days_before INTEGER DEFAULT 3;
    END IF;
END $$;

-- Adicionar campos à tabela bills_to_receive
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'bills_to_receive' 
        AND column_name = 'notification_mode'
    ) THEN
        ALTER TABLE bills_to_receive ADD COLUMN notification_mode TEXT DEFAULT 'default' CHECK (notification_mode IN ('default', 'custom', 'disabled'));
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'bills_to_receive' 
        AND column_name = 'notification_frequency'
    ) THEN
        ALTER TABLE bills_to_receive ADD COLUMN notification_frequency TEXT DEFAULT 'standard' CHECK (notification_frequency IN ('once', 'daily', 'weekly', 'standard'));
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'bills_to_receive' 
        AND column_name = 'custom_days_before'
    ) THEN
        ALTER TABLE bills_to_receive ADD COLUMN custom_days_before INTEGER DEFAULT 3;
    END IF;
END $$;

-- Comentários
COMMENT ON COLUMN bills_to_pay.notification_mode IS 'Modo de notificação: default (usa preferências globais), custom (configurações personalizadas), disabled (sem notificações)';
COMMENT ON COLUMN bills_to_pay.notification_frequency IS 'Frequência: once (uma vez), daily (diário), weekly (semanal), standard (padrão)';
COMMENT ON COLUMN bills_to_pay.custom_days_before IS 'Dias personalizados antes do vencimento para modo custom';

COMMENT ON COLUMN bills_to_receive.notification_mode IS 'Modo de notificação: default (usa preferências globais), custom (configurações personalizadas), disabled (sem notificações)';
COMMENT ON COLUMN bills_to_receive.notification_frequency IS 'Frequência: once (uma vez), daily (diário), weekly (semanal), standard (padrão)';
COMMENT ON COLUMN bills_to_receive.custom_days_before IS 'Dias personalizados antes do vencimento para modo custom';
