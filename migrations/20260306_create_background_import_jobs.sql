-- Tabela para jobs de importação em background
-- Permite processamento assíncrono de arquivos grandes

CREATE TABLE IF NOT EXISTS background_import_jobs (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
  account_id UUID NOT NULL REFERENCES accounts(id) ON DELETE CASCADE,
  company_id TEXT,
  person_id TEXT,
  
  -- Informações do arquivo
  file_name TEXT NOT NULL,
  file_size BIGINT NOT NULL,
  file_type TEXT NOT NULL CHECK (file_type IN ('csv', 'ofx', 'qif', 'xlsx')),
  
  -- Status e progresso
  status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'processing', 'completed', 'failed')),
  progress INTEGER NOT NULL DEFAULT 0 CHECK (progress >= 0 AND progress <= 100),
  
  -- Estatísticas
  total_transactions INTEGER NOT NULL DEFAULT 0,
  imported_transactions INTEGER NOT NULL DEFAULT 0,
  duplicates_skipped INTEGER NOT NULL DEFAULT 0,
  errors INTEGER NOT NULL DEFAULT 0,
  error_details JSONB,
  
  -- Timestamps
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  started_at TIMESTAMP WITH TIME ZONE,
  completed_at TIMESTAMP WITH TIME ZONE,
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Índices para performance
CREATE INDEX IF NOT EXISTS idx_background_import_jobs_user_id ON background_import_jobs(user_id);
CREATE INDEX IF NOT EXISTS idx_background_import_jobs_account_id ON background_import_jobs(account_id);
CREATE INDEX IF NOT EXISTS idx_background_import_jobs_status ON background_import_jobs(status);
CREATE INDEX IF NOT EXISTS idx_background_import_jobs_created_at ON background_import_jobs(created_at DESC);

-- Trigger para atualizar updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_background_import_jobs_updated_at BEFORE UPDATE ON background_import_jobs
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Comentários
COMMENT ON TABLE background_import_jobs IS 'Jobs de importação em background para arquivos grandes';
COMMENT ON COLUMN background_import_jobs.file_size IS 'Tamanho do arquivo em bytes';
COMMENT ON COLUMN background_import_jobs.progress IS 'Progresso da importação (0-100)';
COMMENT ON COLUMN background_import_jobs.error_details IS 'Detalhes dos erros em formato JSON';
