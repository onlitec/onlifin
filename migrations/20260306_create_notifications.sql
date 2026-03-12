-- Migration: Criar sistema de notificações e preferências de alerta
-- Descrição: Implementa tabelas para sistema de alertas de contas a pagar e receber

-- Tabela de notificações
CREATE TABLE IF NOT EXISTS notifications (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
  title TEXT NOT NULL,
  message TEXT NOT NULL,
  type TEXT NOT NULL CHECK (type IN ('alert', 'info', 'warning', 'success')),
  severity TEXT CHECK (severity IN ('low', 'medium', 'high')) DEFAULT 'medium',
  is_read BOOLEAN DEFAULT false,
  related_bill_id UUID REFERENCES bills_to_pay(id) ON DELETE SET NULL,
  related_transaction_id UUID REFERENCES transactions(id) ON DELETE SET NULL,
  metadata JSONB DEFAULT '{}',
  created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
);

-- Adicionar coluna para bills_to_receive se não existir
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'notifications' 
        AND column_name = 'related_bill_to_receive_id'
    ) THEN
        ALTER TABLE notifications ADD COLUMN related_bill_to_receive_id UUID REFERENCES bills_to_receive(id) ON DELETE SET NULL;
    END IF;
END $$;

-- Tabela de preferências de alerta do usuário
CREATE TABLE IF NOT EXISTS alert_preferences (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
  
  -- Configurações de tempo
  days_before_due INTEGER DEFAULT 3,
  days_before_overdue INTEGER DEFAULT 1,
  
  -- Tipos de alerta
  alert_due_soon BOOLEAN DEFAULT true, -- Contas vencendo em breve
  alert_overdue BOOLEAN DEFAULT true, -- Contas vencidas
  alert_paid BOOLEAN DEFAULT true, -- Contas pagas
  alert_received BOOLEAN DEFAULT true, -- Contas recebidas
  
  -- Canais de notificação
  toast_notifications BOOLEAN DEFAULT true,
  database_notifications BOOLEAN DEFAULT true,
  email_notifications BOOLEAN DEFAULT false,
  push_notifications BOOLEAN DEFAULT false,
  
  -- Configurações avançadas
  quiet_hours_start TIME DEFAULT '22:00:00',
  quiet_hours_end TIME DEFAULT '08:00:00',
  weekend_notifications BOOLEAN DEFAULT true,
  
  created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
  
  UNIQUE(user_id)
);

-- Índices para performance
CREATE INDEX IF NOT EXISTS idx_notifications_user_id ON notifications(user_id);
CREATE INDEX IF NOT EXISTS idx_notifications_is_read ON notifications(is_read);
CREATE INDEX IF NOT EXISTS idx_notifications_created_at ON notifications(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_notifications_type ON notifications(type);
CREATE INDEX IF NOT EXISTS idx_notifications_severity ON notifications(severity);
CREATE INDEX IF NOT EXISTS idx_notifications_related_bill_id ON notifications(related_bill_id);
CREATE INDEX IF NOT EXISTS idx_notifications_related_bill_to_receive_id ON notifications(related_bill_to_receive_id);

-- Trigger para atualizar updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = now();
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_notifications_updated_at BEFORE UPDATE ON notifications
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_alert_preferences_updated_at BEFORE UPDATE ON alert_preferences
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Inserir preferências padrão para usuários existentes
INSERT INTO alert_preferences (user_id)
SELECT id FROM auth.users
WHERE id NOT IN (SELECT user_id FROM alert_preferences);

-- Comentários
COMMENT ON TABLE notifications IS 'Tabela de notificações do sistema';
COMMENT ON TABLE alert_preferences IS 'Preferências de alerta dos usuários';
COMMENT ON COLUMN notifications.type IS 'Tipo da notificação: alert, info, warning, success';
COMMENT ON COLUMN notifications.severity IS 'Severidade: low, medium, high';
COMMENT ON COLUMN alert_preferences.days_before_due IS 'Dias antes do vencimento para alertar';
COMMENT ON COLUMN alert_preferences.quiet_hours_start IS 'Início do horário de silêncio';
COMMENT ON COLUMN alert_preferences.quiet_hours_end IS 'Fim do horário de silêncio';
