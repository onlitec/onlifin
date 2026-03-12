-- Tabela para rastrear correções de categorização
-- Permite medir a acurácia da IA e aprender com as correções do usuário

CREATE TABLE IF NOT EXISTS category_corrections (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
  transaction_id UUID NOT NULL REFERENCES transactions(id) ON DELETE CASCADE,
  original_category_id UUID REFERENCES categories(id) ON DELETE SET NULL,
  corrected_category_id UUID NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
  original_category TEXT, -- Nome da categoria original
  corrected_category TEXT, -- Nome da categoria corrigida
  description TEXT, -- Descrição da transação
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Índices para performance
CREATE INDEX IF NOT EXISTS idx_category_corrections_user_id ON category_corrections(user_id);
CREATE INDEX IF NOT EXISTS idx_category_corrections_transaction_id ON category_corrections(transaction_id);
CREATE INDEX IF NOT EXISTS idx_category_corrections_created_at ON category_corrections(created_at DESC);

-- Trigger para atualizar updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_category_corrections_updated_at BEFORE UPDATE ON category_corrections
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Comentários
COMMENT ON TABLE category_corrections IS 'Rastreia correções de categorização feitas pelo usuário';
COMMENT ON COLUMN category_corrections.original_category_id IS 'ID da categoria sugerida pela IA';
COMMENT ON COLUMN category_corrections.corrected_category_id IS 'ID da categoria corrigida pelo usuário';
