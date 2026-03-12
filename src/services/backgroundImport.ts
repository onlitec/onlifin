import { supabase } from '@/db/client';

export interface BackgroundImportJob {
  id: string;
  userId: string;
  accountId: string;
  companyId: string | null;
  personId: string | null;
  fileName: string;
  fileSize: number;
  fileType: 'csv' | 'ofx' | 'qif' | 'xlsx';
  status: 'pending' | 'processing' | 'completed' | 'failed';
  progress: number;
  totalTransactions: number;
  importedTransactions: number;
  duplicatesSkipped: number;
  errors: number;
  errorDetails?: Array<{ line: number; error: string }>;
  createdAt: string;
  startedAt?: string;
  completedAt?: string;
}

/**
 * Cria um job de importação em background
 */
export async function createBackgroundImportJob(
  userId: string,
  accountId: string,
  companyId: string | null,
  personId: string | null,
  file: File
): Promise<BackgroundImportJob> {
  const { data, error } = await supabase
    .from('background_import_jobs')
    .insert({
      user_id: userId,
      account_id: accountId,
      company_id: companyId,
      person_id: personId,
      file_name: file.name,
      file_size: file.size,
      file_type: file.name.split('.').pop()?.toLowerCase() as any,
      status: 'pending',
      progress: 0,
      total_transactions: 0,
      imported_transactions: 0,
      duplicates_skipped: 0,
      errors: 0
    })
    .select()
    .single();

  if (error) throw error;
  return data as BackgroundImportJob;
}

/**
 * Atualiza o progresso de um job de importação
 */
export async function updateImportJobProgress(
  jobId: string,
  updates: Partial<Pick<BackgroundImportJob,
    'status' | 'progress' | 'totalTransactions' | 'importedTransactions' |
    'duplicatesSkipped' | 'errors' | 'errorDetails' | 'startedAt' | 'completedAt'
  >>
): Promise<void> {
  const { error } = await supabase
    .from('background_import_jobs')
    .update(updates)
    .eq('id', jobId);

  if (error) throw error;
}

/**
 * Busca o status de um job de importação
 */
export async function getImportJobStatus(jobId: string): Promise<BackgroundImportJob | null> {
  const { data, error } = await supabase
    .from('background_import_jobs')
    .select('*')
    .eq('id', jobId)
    .single();

  if (error) return null;
  return data as BackgroundImportJob;
}

/**
 * Lista jobs de importação do usuário
 */
export async function listImportJobs(userId: string): Promise<BackgroundImportJob[]> {
  const { data, error } = await supabase
    .from('background_import_jobs')
    .select('*')
    .eq('user_id', userId)
    .order('created_at', { ascending: false })
    .limit(20);

  if (error) throw error;
  return (data || []) as BackgroundImportJob[];
}

/**
 * Cancela um job de importação
 */
export async function cancelImportJob(jobId: string): Promise<void> {
  const { error } = await supabase
    .from('background_import_jobs')
    .update({ status: 'failed', completedAt: new Date().toISOString() })
    .eq('id', jobId)
    .in('status', ['pending', 'processing']);

  if (error) throw error;
}

/**
 * Processa um job de importação em background
 * Esta função deve ser chamada por um worker/processo separado
 */
export async function processBackgroundImportJob(jobId: string): Promise<void> {
  try {
    // Atualizar status para processing
    await updateImportJobProgress(jobId, {
      status: 'processing',
      startedAt: new Date().toISOString(),
      progress: 0
    });

    // Buscar detalhes do job
    const job = await getImportJobStatus(jobId);
    if (!job) throw new Error('Job não encontrado');

    // Buscar o arquivo do storage
    // TODO: Implementar upload e download do arquivo do storage
    // const fileContent = await downloadFile(job.id);

    // Processar o arquivo
    // TODO: Implementar parsing e importação
    // const transactions = parseFile(fileContent, job.fileType);
    
    // Importar transações em lotes
    // for (const batch of chunkArray(transactions, 100)) {
    //   await importBatch(batch, job);
    //   await updateImportJobProgress(jobId, { progress: ... });
    // }

    // Marcar como completado
    await updateImportJobProgress(jobId, {
      status: 'completed',
      progress: 100,
      completedAt: new Date().toISOString()
    });
  } catch (error) {
    // Marcar como falha
    await updateImportJobProgress(jobId, {
      status: 'failed',
      completedAt: new Date().toISOString()
    });
    throw error;
  }
}

/**
 * Hook para monitorar jobs de importação em tempo real
 */
export function useImportJobs(userId: string) {
  const [jobs, setJobs] = React.useState<BackgroundImportJob[]>([]);
  const [loading, setLoading] = React.useState(false);

  const refresh = React.useCallback(async () => {
    setLoading(true);
    try {
      const data = await listImportJobs(userId);
      setJobs(data);
    } catch (error) {
      console.error('Erro ao carregar jobs:', error);
    } finally {
      setLoading(false);
    }
  }, [userId]);

  React.useEffect(() => {
    refresh();
    
    // Polling para atualizar jobs em andamento
    const interval = setInterval(() => {
      const activeJobs = jobs.filter(j => j.status === 'processing' || j.status === 'pending');
      if (activeJobs.length > 0) {
        refresh();
      }
    }, 2000);

    return () => clearInterval(interval);
  }, [refresh, jobs]);

  return { jobs, loading, refresh };
}

// Import React para o hook
import * as React from 'react';
import { chunkArray } from '@/utils/array';
