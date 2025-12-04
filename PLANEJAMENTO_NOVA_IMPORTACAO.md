# 📋 Planejamento Detalhado - Nova Importação de Extrato

## 🎯 Objetivo

Reestruturar o fluxo de importação de extrato bancário no chatbot para separar o upload do arquivo da análise por IA, permitindo revisão e ajuste de categorias antes do cadastro final.

---

## 🔄 Fluxo Atual vs. Novo Fluxo

### Fluxo Atual
```
1. Usuário faz upload do arquivo
2. Sistema processa imediatamente com IA
3. Transações são cadastradas automaticamente
```

### Novo Fluxo
```
1. Usuário faz upload do arquivo no chatbot
2. Arquivo é salvo no Supabase Storage
3. Botão "Analisar com IA" aparece
4. Usuário clica para iniciar análise
5. IA analisa o arquivo salvo
6. Popup mostra resultados:
   - Lista de transações ordenadas por data
   - Categoria sugerida pela IA (editável)
   - Botão "Cadastrar Transações"
7. Usuário revisa e ajusta categorias
8. Usuário clica "Cadastrar Transações"
9. Todas as transações são salvas no banco
```

---

## 🗄️ Mudanças no Banco de Dados

### Nova Tabela: `uploaded_statements`

```sql
CREATE TABLE uploaded_statements (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid REFERENCES auth.users(id) NOT NULL,
  file_name text NOT NULL,
  file_path text NOT NULL,
  file_type text NOT NULL, -- 'csv', 'ofx', 'pdf'
  file_size integer NOT NULL,
  status text NOT NULL DEFAULT 'uploaded', -- 'uploaded', 'analyzing', 'analyzed', 'imported', 'error'
  analysis_result jsonb, -- Resultado da análise da IA
  error_message text,
  created_at timestamptz DEFAULT now(),
  analyzed_at timestamptz,
  imported_at timestamptz
);

CREATE INDEX idx_uploaded_statements_user_id ON uploaded_statements(user_id);
CREATE INDEX idx_uploaded_statements_status ON uploaded_statements(status);
```

### Estrutura do `analysis_result` (JSONB)

```json
{
  "transactions": [
    {
      "date": "2024-01-15",
      "description": "Supermercado ABC",
      "amount": -150.50,
      "type": "expense",
      "suggested_category": "Alimentação",
      "confidence": 0.95
    }
  ],
  "summary": {
    "total_transactions": 25,
    "total_income": 5000.00,
    "total_expenses": -3200.50,
    "period_start": "2024-01-01",
    "period_end": "2024-01-31"
  }
}
```

---

## 📁 Estrutura de Arquivos

### Novos Componentes

```
src/
├── components/
│   ├── chat/
│   │   ├── ChatBot.tsx (MODIFICAR)
│   │   ├── FileUploadArea.tsx (NOVO)
│   │   └── AnalysisResultPopup.tsx (NOVO)
│   └── transactions/
│       └── TransactionReviewList.tsx (NOVO)
├── db/
│   └── api.ts (ADICIONAR funções)
├── types/
│   └── types.ts (ADICIONAR tipos)
└── utils/
    └── fileUpload.ts (NOVO)
```

---

## 🔧 Implementação Detalhada

### Fase 1: Preparação do Banco de Dados

#### 1.1 Criar Migration
**Arquivo:** `supabase/migrations/YYYYMMDDHHMMSS_create_uploaded_statements.sql`

```sql
/*
# Create uploaded_statements table

1. New Tables
- `uploaded_statements`
  - `id` (uuid, primary key)
  - `user_id` (uuid, references auth.users)
  - `file_name` (text)
  - `file_path` (text)
  - `file_type` (text)
  - `file_size` (integer)
  - `status` (text)
  - `analysis_result` (jsonb)
  - `error_message` (text)
  - `created_at` (timestamptz)
  - `analyzed_at` (timestamptz)
  - `imported_at` (timestamptz)

2. Security
- Enable RLS
- Users can only access their own uploads
- Admins can access all uploads

3. Indexes
- user_id for fast user queries
- status for filtering
*/

CREATE TABLE IF NOT EXISTS uploaded_statements (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid REFERENCES auth.users(id) NOT NULL,
  file_name text NOT NULL,
  file_path text NOT NULL,
  file_type text NOT NULL,
  file_size integer NOT NULL,
  status text NOT NULL DEFAULT 'uploaded',
  analysis_result jsonb,
  error_message text,
  created_at timestamptz DEFAULT now(),
  analyzed_at timestamptz,
  imported_at timestamptz
);

CREATE INDEX idx_uploaded_statements_user_id ON uploaded_statements(user_id);
CREATE INDEX idx_uploaded_statements_status ON uploaded_statements(status);

ALTER TABLE uploaded_statements ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Users can view own uploads" ON uploaded_statements
  FOR SELECT USING (auth.uid() = user_id);

CREATE POLICY "Users can insert own uploads" ON uploaded_statements
  FOR INSERT WITH CHECK (auth.uid() = user_id);

CREATE POLICY "Users can update own uploads" ON uploaded_statements
  FOR UPDATE USING (auth.uid() = user_id);

CREATE POLICY "Admins have full access" ON uploaded_statements
  FOR ALL USING (is_admin(auth.uid()));
```

#### 1.2 Criar Bucket no Supabase Storage
**Arquivo:** `supabase/migrations/YYYYMMDDHHMMSS_create_statements_bucket.sql`

```sql
/*
# Create storage bucket for bank statements

1. Bucket Configuration
- Name: app-7xkeeoe4bsap_statements
- Public: false (private files)
- File size limit: 5MB
- Allowed MIME types: text/csv, application/vnd.ms-excel, application/ofx, application/pdf

2. Security
- Users can upload their own files
- Users can read their own files
- Users can delete their own files
*/

INSERT INTO storage.buckets (id, name, public, file_size_limit, allowed_mime_types)
VALUES (
  'app-7xkeeoe4bsap_statements',
  'app-7xkeeoe4bsap_statements',
  false,
  5242880, -- 5MB
  ARRAY['text/csv', 'application/vnd.ms-excel', 'application/x-ofx', 'application/pdf']
);

CREATE POLICY "Users can upload own statements"
ON storage.objects FOR INSERT
TO authenticated
WITH CHECK (
  bucket_id = 'app-7xkeeoe4bsap_statements' AND
  (storage.foldername(name))[1] = auth.uid()::text
);

CREATE POLICY "Users can read own statements"
ON storage.objects FOR SELECT
TO authenticated
USING (
  bucket_id = 'app-7xkeeoe4bsap_statements' AND
  (storage.foldername(name))[1] = auth.uid()::text
);

CREATE POLICY "Users can delete own statements"
ON storage.objects FOR DELETE
TO authenticated
USING (
  bucket_id = 'app-7xkeeoe4bsap_statements' AND
  (storage.foldername(name))[1] = auth.uid()::text
);
```

---

### Fase 2: Tipos TypeScript

#### 2.1 Adicionar Tipos
**Arquivo:** `src/types/types.ts`

```typescript
// Adicionar ao arquivo existente

export interface UploadedStatement {
  id: string;
  user_id: string;
  file_name: string;
  file_path: string;
  file_type: 'csv' | 'ofx' | 'pdf';
  file_size: number;
  status: 'uploaded' | 'analyzing' | 'analyzed' | 'imported' | 'error';
  analysis_result?: AnalysisResult;
  error_message?: string;
  created_at: string;
  analyzed_at?: string;
  imported_at?: string;
}

export interface AnalysisResult {
  transactions: AnalyzedTransaction[];
  summary: {
    total_transactions: number;
    total_income: number;
    total_expenses: number;
    period_start: string;
    period_end: string;
  };
}

export interface AnalyzedTransaction {
  date: string;
  description: string;
  amount: number;
  type: 'income' | 'expense';
  suggested_category: string;
  confidence: number;
  selected_category?: string; // Categoria selecionada pelo usuário
}
```

---

### Fase 3: Funções de API

#### 3.1 Adicionar Funções ao api.ts
**Arquivo:** `src/db/api.ts`

```typescript
// Adicionar ao arquivo existente

// ============================================
// UPLOADED STATEMENTS
// ============================================

export const uploadedStatementsApi = {
  /**
   * Cria registro de upload de extrato
   */
  async create(data: {
    file_name: string;
    file_path: string;
    file_type: string;
    file_size: number;
  }): Promise<UploadedStatement> {
    const { data: result, error } = await supabase
      .from('uploaded_statements')
      .insert({
        file_name: data.file_name,
        file_path: data.file_path,
        file_type: data.file_type,
        file_size: data.file_size,
        status: 'uploaded',
      })
      .select()
      .single();

    if (error) throw error;
    return result;
  },

  /**
   * Atualiza status do upload
   */
  async updateStatus(
    id: string,
    status: UploadedStatement['status'],
    error_message?: string
  ): Promise<void> {
    const { error } = await supabase
      .from('uploaded_statements')
      .update({ status, error_message })
      .eq('id', id);

    if (error) throw error;
  },

  /**
   * Salva resultado da análise
   */
  async saveAnalysisResult(
    id: string,
    analysis_result: AnalysisResult
  ): Promise<void> {
    const { error } = await supabase
      .from('uploaded_statements')
      .update({
        status: 'analyzed',
        analysis_result,
        analyzed_at: new Date().toISOString(),
      })
      .eq('id', id);

    if (error) throw error;
  },

  /**
   * Marca como importado
   */
  async markAsImported(id: string): Promise<void> {
    const { error } = await supabase
      .from('uploaded_statements')
      .update({
        status: 'imported',
        imported_at: new Date().toISOString(),
      })
      .eq('id', id);

    if (error) throw error;
  },

  /**
   * Busca upload por ID
   */
  async getById(id: string): Promise<UploadedStatement | null> {
    const { data, error } = await supabase
      .from('uploaded_statements')
      .select('*')
      .eq('id', id)
      .maybeSingle();

    if (error) throw error;
    return data;
  },

  /**
   * Lista uploads do usuário
   */
  async listByUser(limit = 10): Promise<UploadedStatement[]> {
    const { data, error } = await supabase
      .from('uploaded_statements')
      .select('*')
      .order('created_at', { ascending: false })
      .limit(limit);

    if (error) throw error;
    return Array.isArray(data) ? data : [];
  },
};
```

---

### Fase 4: Utilitário de Upload

#### 4.1 Criar Função de Upload
**Arquivo:** `src/utils/fileUpload.ts`

```typescript
import { supabase } from '@/db/supabase';

/**
 * Faz upload de arquivo para o Supabase Storage
 */
export async function uploadStatementFile(
  file: File,
  userId: string
): Promise<{ path: string; error?: string }> {
  try {
    // Validar tamanho (5MB)
    if (file.size > 5 * 1024 * 1024) {
      return { path: '', error: 'Arquivo muito grande. Máximo: 5MB' };
    }

    // Validar tipo
    const allowedTypes = ['text/csv', 'application/vnd.ms-excel', 'application/x-ofx', 'application/pdf'];
    if (!allowedTypes.includes(file.type) && !file.name.match(/\.(csv|ofx|pdf)$/i)) {
      return { path: '', error: 'Tipo de arquivo não suportado. Use CSV, OFX ou PDF' };
    }

    // Gerar nome único
    const timestamp = Date.now();
    const sanitizedName = file.name.replace(/[^a-zA-Z0-9.-]/g, '_');
    const fileName = `${timestamp}_${sanitizedName}`;
    const filePath = `${userId}/${fileName}`;

    // Upload
    const { error: uploadError } = await supabase.storage
      .from('app-7xkeeoe4bsap_statements')
      .upload(filePath, file, {
        cacheControl: '3600',
        upsert: false,
      });

    if (uploadError) {
      console.error('Erro no upload:', uploadError);
      return { path: '', error: 'Erro ao fazer upload do arquivo' };
    }

    return { path: filePath };
  } catch (error) {
    console.error('Erro no upload:', error);
    return { path: '', error: 'Erro inesperado ao fazer upload' };
  }
}

/**
 * Baixa arquivo do Supabase Storage
 */
export async function downloadStatementFile(filePath: string): Promise<Blob | null> {
  try {
    const { data, error } = await supabase.storage
      .from('app-7xkeeoe4bsap_statements')
      .download(filePath);

    if (error) {
      console.error('Erro ao baixar arquivo:', error);
      return null;
    }

    return data;
  } catch (error) {
    console.error('Erro ao baixar arquivo:', error);
    return null;
  }
}

/**
 * Deleta arquivo do Supabase Storage
 */
export async function deleteStatementFile(filePath: string): Promise<boolean> {
  try {
    const { error } = await supabase.storage
      .from('app-7xkeeoe4bsap_statements')
      .remove([filePath]);

    if (error) {
      console.error('Erro ao deletar arquivo:', error);
      return false;
    }

    return true;
  } catch (error) {
    console.error('Erro ao deletar arquivo:', error);
    return false;
  }
}
```

---

### Fase 5: Componentes React

#### 5.1 Área de Upload de Arquivo
**Arquivo:** `src/components/chat/FileUploadArea.tsx`

```typescript
import { useState, useRef } from 'react';
import { Button } from '@/components/ui/button';
import { Upload, FileText, X } from 'lucide-react';
import { useToast } from '@/hooks/use-toast';

interface FileUploadAreaProps {
  onFileSelected: (file: File) => void;
  onFileRemoved: () => void;
  isUploading: boolean;
  selectedFile: File | null;
}

export default function FileUploadArea({
  onFileSelected,
  onFileRemoved,
  isUploading,
  selectedFile,
}: FileUploadAreaProps) {
  const [isDragging, setIsDragging] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const { toast } = useToast();

  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(true);
  };

  const handleDragLeave = () => {
    setIsDragging(false);
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(false);

    const files = e.dataTransfer.files;
    if (files.length > 0) {
      handleFileSelection(files[0]);
    }
  };

  const handleFileSelection = (file: File) => {
    // Validar tipo
    const validExtensions = ['csv', 'ofx', 'pdf'];
    const extension = file.name.split('.').pop()?.toLowerCase();

    if (!extension || !validExtensions.includes(extension)) {
      toast({
        title: 'Arquivo inválido',
        description: 'Por favor, selecione um arquivo CSV, OFX ou PDF',
        variant: 'destructive',
      });
      return;
    }

    // Validar tamanho (5MB)
    if (file.size > 5 * 1024 * 1024) {
      toast({
        title: 'Arquivo muito grande',
        description: 'O arquivo deve ter no máximo 5MB',
        variant: 'destructive',
      });
      return;
    }

    onFileSelected(file);
  };

  const handleFileInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const files = e.target.files;
    if (files && files.length > 0) {
      handleFileSelection(files[0]);
    }
  };

  const handleRemoveFile = () => {
    onFileRemoved();
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  if (selectedFile) {
    return (
      <div className="flex items-center gap-2 p-3 bg-muted rounded-lg">
        <FileText className="w-5 h-5 text-primary" />
        <div className="flex-1 min-w-0">
          <p className="text-sm font-medium truncate">{selectedFile.name}</p>
          <p className="text-xs text-muted-foreground">
            {(selectedFile.size / 1024).toFixed(1)} KB
          </p>
        </div>
        {!isUploading && (
          <Button
            variant="ghost"
            size="sm"
            onClick={handleRemoveFile}
            className="h-8 w-8 p-0"
          >
            <X className="w-4 h-4" />
          </Button>
        )}
      </div>
    );
  }

  return (
    <div
      className={`border-2 border-dashed rounded-lg p-6 text-center transition-colors ${
        isDragging
          ? 'border-primary bg-primary/5'
          : 'border-border hover:border-primary/50'
      }`}
      onDragOver={handleDragOver}
      onDragLeave={handleDragLeave}
      onDrop={handleDrop}
    >
      <input
        ref={fileInputRef}
        type="file"
        accept=".csv,.ofx,.pdf"
        onChange={handleFileInputChange}
        className="hidden"
      />

      <Upload className="w-10 h-10 mx-auto mb-3 text-muted-foreground" />

      <p className="text-sm font-medium mb-1">
        Arraste seu extrato aqui ou clique para selecionar
      </p>
      <p className="text-xs text-muted-foreground mb-4">
        Formatos aceitos: CSV, OFX, PDF (máx. 5MB)
      </p>

      <Button
        variant="outline"
        size="sm"
        onClick={() => fileInputRef.current?.click()}
        disabled={isUploading}
      >
        <Upload className="w-4 h-4 mr-2" />
        Selecionar Arquivo
      </Button>
    </div>
  );
}
```

#### 5.2 Lista de Revisão de Transações
**Arquivo:** `src/components/transactions/TransactionReviewList.tsx`

```typescript
import { useState } from 'react';
import { AnalyzedTransaction } from '@/types/types';
import { Category } from '@/types/types';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { TrendingUp, TrendingDown } from 'lucide-react';

interface TransactionReviewListProps {
  transactions: AnalyzedTransaction[];
  categories: Category[];
  onCategoryChange: (index: number, categoryId: string) => void;
}

export default function TransactionReviewList({
  transactions,
  categories,
  onCategoryChange,
}: TransactionReviewListProps) {
  return (
    <div className="space-y-2 max-h-[400px] overflow-y-auto">
      {transactions.map((transaction, index) => {
        const isIncome = transaction.type === 'income';
        const selectedCategory =
          transaction.selected_category || transaction.suggested_category;

        return (
          <div
            key={index}
            className="flex items-center gap-3 p-3 bg-muted/50 rounded-lg hover:bg-muted transition-colors"
          >
            {/* Ícone de tipo */}
            <div
              className={`flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center ${
                isIncome ? 'bg-green-100' : 'bg-red-100'
              }`}
            >
              {isIncome ? (
                <TrendingUp className="w-5 h-5 text-green-600" />
              ) : (
                <TrendingDown className="w-5 h-5 text-red-600" />
              )}
            </div>

            {/* Informações da transação */}
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium truncate">
                {transaction.description}
              </p>
              <p className="text-xs text-muted-foreground">
                {new Date(transaction.date).toLocaleDateString('pt-BR')}
              </p>
            </div>

            {/* Valor */}
            <div className="text-right">
              <p
                className={`text-sm font-semibold ${
                  isIncome ? 'text-green-600' : 'text-red-600'
                }`}
              >
                {isIncome ? '+' : ''}
                {transaction.amount.toLocaleString('pt-BR', {
                  style: 'currency',
                  currency: 'BRL',
                })}
              </p>
              {transaction.confidence && (
                <Badge variant="outline" className="text-xs mt-1">
                  {Math.round(transaction.confidence * 100)}% confiança
                </Badge>
              )}
            </div>

            {/* Seletor de categoria */}
            <div className="w-48">
              <Select
                value={selectedCategory}
                onValueChange={(value) => onCategoryChange(index, value)}
              >
                <SelectTrigger className="h-9">
                  <SelectValue placeholder="Selecione categoria" />
                </SelectTrigger>
                <SelectContent>
                  {categories.map((category) => (
                    <SelectItem key={category.id} value={category.name}>
                      {category.icon} {category.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>
        );
      })}
    </div>
  );
}
```

#### 5.3 Popup de Resultado da Análise
**Arquivo:** `src/components/chat/AnalysisResultPopup.tsx`

```typescript
import { useState, useEffect } from 'react';
import { AnalysisResult, AnalyzedTransaction } from '@/types/types';
import { Category } from '@/types/types';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Loader2, CheckCircle, TrendingUp, TrendingDown } from 'lucide-react';
import TransactionReviewList from '@/components/transactions/TransactionReviewList';
import { categoriesApi } from '@/db/api';
import { useToast } from '@/hooks/use-toast';

interface AnalysisResultPopupProps {
  open: boolean;
  onClose: () => void;
  analysisResult: AnalysisResult | null;
  onConfirm: (transactions: AnalyzedTransaction[]) => Promise<void>;
}

export default function AnalysisResultPopup({
  open,
  onClose,
  analysisResult,
  onConfirm,
}: AnalysisResultPopupProps) {
  const [categories, setCategories] = useState<Category[]>([]);
  const [transactions, setTransactions] = useState<AnalyzedTransaction[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const { toast } = useToast();

  useEffect(() => {
    if (open && analysisResult) {
      setTransactions(analysisResult.transactions);
      loadCategories();
    }
  }, [open, analysisResult]);

  const loadCategories = async () => {
    try {
      const data = await categoriesApi.list();
      setCategories(data);
    } catch (error) {
      console.error('Erro ao carregar categorias:', error);
      toast({
        title: 'Erro',
        description: 'Não foi possível carregar as categorias',
        variant: 'destructive',
      });
    }
  };

  const handleCategoryChange = (index: number, categoryName: string) => {
    setTransactions((prev) =>
      prev.map((t, i) =>
        i === index ? { ...t, selected_category: categoryName } : t
      )
    );
  };

  const handleConfirm = async () => {
    setIsLoading(true);
    try {
      await onConfirm(transactions);
      toast({
        title: 'Sucesso!',
        description: `${transactions.length} transações cadastradas com sucesso`,
      });
      onClose();
    } catch (error) {
      console.error('Erro ao cadastrar transações:', error);
      toast({
        title: 'Erro',
        description: 'Não foi possível cadastrar as transações',
        variant: 'destructive',
      });
    } finally {
      setIsLoading(false);
    }
  };

  if (!analysisResult) return null;

  const { summary } = analysisResult;

  return (
    <Dialog open={open} onOpenChange={onClose}>
      <DialogContent className="max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
        <DialogHeader>
          <DialogTitle>Resultado da Análise</DialogTitle>
          <DialogDescription>
            Revise as transações identificadas e ajuste as categorias se necessário
          </DialogDescription>
        </DialogHeader>

        {/* Resumo */}
        <div className="grid grid-cols-3 gap-4 py-4">
          <div className="bg-muted p-4 rounded-lg">
            <p className="text-sm text-muted-foreground mb-1">Total de Transações</p>
            <p className="text-2xl font-bold">{summary.total_transactions}</p>
          </div>
          <div className="bg-green-50 p-4 rounded-lg">
            <p className="text-sm text-green-700 mb-1 flex items-center gap-1">
              <TrendingUp className="w-4 h-4" />
              Receitas
            </p>
            <p className="text-2xl font-bold text-green-600">
              {summary.total_income.toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL',
              })}
            </p>
          </div>
          <div className="bg-red-50 p-4 rounded-lg">
            <p className="text-sm text-red-700 mb-1 flex items-center gap-1">
              <TrendingDown className="w-4 h-4" />
              Despesas
            </p>
            <p className="text-2xl font-bold text-red-600">
              {summary.total_expenses.toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL',
              })}
            </p>
          </div>
        </div>

        {/* Período */}
        <Alert>
          <AlertDescription>
            Período: {new Date(summary.period_start).toLocaleDateString('pt-BR')} até{' '}
            {new Date(summary.period_end).toLocaleDateString('pt-BR')}
          </AlertDescription>
        </Alert>

        {/* Lista de transações */}
        <div className="flex-1 overflow-hidden">
          <TransactionReviewList
            transactions={transactions}
            categories={categories}
            onCategoryChange={handleCategoryChange}
          />
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={onClose} disabled={isLoading}>
            Cancelar
          </Button>
          <Button onClick={handleConfirm} disabled={isLoading}>
            {isLoading ? (
              <>
                <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                Cadastrando...
              </>
            ) : (
              <>
                <CheckCircle className="w-4 h-4 mr-2" />
                Cadastrar {transactions.length} Transações
              </>
            )}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
```

#### 5.4 Modificar ChatBot
**Arquivo:** `src/components/chat/ChatBot.tsx` (MODIFICAÇÕES)

```typescript
// Adicionar imports
import FileUploadArea from './FileUploadArea';
import AnalysisResultPopup from './AnalysisResultPopup';
import { uploadStatementFile, downloadStatementFile } from '@/utils/fileUpload';
import { uploadedStatementsApi, transactionsApi, accountsApi } from '@/db/api';
import { UploadedStatement, AnalyzedTransaction } from '@/types/types';

// Adicionar estados
const [selectedFile, setSelectedFile] = useState<File | null>(null);
const [isUploading, setIsUploading] = useState(false);
const [uploadedStatement, setUploadedStatement] = useState<UploadedStatement | null>(null);
const [isAnalyzing, setIsAnalyzing] = useState(false);
const [showAnalysisPopup, setShowAnalysisPopup] = useState(false);

// Função de upload
const handleFileUpload = async () => {
  if (!selectedFile || !user) return;

  setIsUploading(true);
  try {
    // Upload do arquivo
    const { path, error } = await uploadStatementFile(selectedFile, user.id);
    if (error) {
      toast({
        title: 'Erro no upload',
        description: error,
        variant: 'destructive',
      });
      return;
    }

    // Criar registro no banco
    const fileType = selectedFile.name.split('.').pop()?.toLowerCase() || 'csv';
    const statement = await uploadedStatementsApi.create({
      file_name: selectedFile.name,
      file_path: path,
      file_type: fileType,
      file_size: selectedFile.size,
    });

    setUploadedStatement(statement);
    toast({
      title: 'Upload concluído!',
      description: 'Arquivo salvo. Clique em "Analisar com IA" para continuar.',
    });
  } catch (error) {
    console.error('Erro no upload:', error);
    toast({
      title: 'Erro',
      description: 'Não foi possível fazer upload do arquivo',
      variant: 'destructive',
    });
  } finally {
    setIsUploading(false);
  }
};

// Função de análise
const handleAnalyze = async () => {
  if (!uploadedStatement) return;

  setIsAnalyzing(true);
  try {
    // Atualizar status
    await uploadedStatementsApi.updateStatus(uploadedStatement.id, 'analyzing');

    // Baixar arquivo
    const fileBlob = await downloadStatementFile(uploadedStatement.file_path);
    if (!fileBlob) {
      throw new Error('Não foi possível baixar o arquivo');
    }

    // Converter para texto
    const fileText = await fileBlob.text();

    // Processar com IA (usar a função existente de análise)
    const analysisResult = await analyzeStatementWithAI(fileText, uploadedStatement.file_type);

    // Salvar resultado
    await uploadedStatementsApi.saveAnalysisResult(uploadedStatement.id, analysisResult);

    // Atualizar estado local
    setUploadedStatement((prev) =>
      prev ? { ...prev, analysis_result: analysisResult } : null
    );

    // Mostrar popup
    setShowAnalysisPopup(true);
  } catch (error) {
    console.error('Erro na análise:', error);
    await uploadedStatementsApi.updateStatus(
      uploadedStatement.id,
      'error',
      error instanceof Error ? error.message : 'Erro desconhecido'
    );
    toast({
      title: 'Erro na análise',
      description: 'Não foi possível analisar o arquivo',
      variant: 'destructive',
    });
  } finally {
    setIsAnalyzing(false);
  }
};

// Função de confirmação
const handleConfirmTransactions = async (transactions: AnalyzedTransaction[]) => {
  if (!uploadedStatement) return;

  // Buscar conta padrão do usuário
  const accounts = await accountsApi.list();
  const defaultAccount = accounts[0];

  if (!defaultAccount) {
    throw new Error('Nenhuma conta encontrada. Crie uma conta primeiro.');
  }

  // Cadastrar todas as transações
  for (const transaction of transactions) {
    const categoryName = transaction.selected_category || transaction.suggested_category;

    await transactionsApi.create({
      account_id: defaultAccount.id,
      date: transaction.date,
      description: transaction.description,
      amount: transaction.amount,
      type: transaction.type,
      category: categoryName,
    });
  }

  // Marcar como importado
  await uploadedStatementsApi.markAsImported(uploadedStatement.id);

  // Limpar estados
  setSelectedFile(null);
  setUploadedStatement(null);
  setShowAnalysisPopup(false);
};

// No JSX, adicionar a área de upload e botões
{!uploadedStatement && (
  <FileUploadArea
    onFileSelected={setSelectedFile}
    onFileRemoved={() => setSelectedFile(null)}
    isUploading={isUploading}
    selectedFile={selectedFile}
  />
)}

{selectedFile && !uploadedStatement && (
  <Button onClick={handleFileUpload} disabled={isUploading} className="w-full">
    {isUploading ? (
      <>
        <Loader2 className="w-4 h-4 mr-2 animate-spin" />
        Fazendo upload...
      </>
    ) : (
      <>
        <Upload className="w-4 h-4 mr-2" />
        Fazer Upload
      </>
    )}
  </Button>
)}

{uploadedStatement && uploadedStatement.status === 'uploaded' && (
  <Button onClick={handleAnalyze} disabled={isAnalyzing} className="w-full">
    {isAnalyzing ? (
      <>
        <Loader2 className="w-4 h-4 mr-2 animate-spin" />
        Analisando...
      </>
    ) : (
      <>
        <Sparkles className="w-4 h-4 mr-2" />
        Analisar com IA
      </>
    )}
  </Button>
)}

{/* Popup de resultado */}
<AnalysisResultPopup
  open={showAnalysisPopup}
  onClose={() => setShowAnalysisPopup(false)}
  analysisResult={uploadedStatement?.analysis_result || null}
  onConfirm={handleConfirmTransactions}
/>
```

---

## 📝 Ordem de Implementação

### Dia 1: Infraestrutura
1. ✅ Criar migration para tabela `uploaded_statements`
2. ✅ Criar bucket no Supabase Storage
3. ✅ Aplicar migrations
4. ✅ Adicionar tipos TypeScript
5. ✅ Adicionar funções de API

### Dia 2: Utilitários e Componentes Base
6. ✅ Criar `fileUpload.ts`
7. ✅ Criar `FileUploadArea.tsx`
8. ✅ Criar `TransactionReviewList.tsx`
9. ✅ Testar upload e download de arquivos

### Dia 3: Componente Principal e Integração
10. ✅ Criar `AnalysisResultPopup.tsx`
11. ✅ Modificar `ChatBot.tsx`
12. ✅ Integrar todos os componentes
13. ✅ Testar fluxo completo

### Dia 4: Testes e Refinamentos
14. ✅ Testar com arquivos CSV
15. ✅ Testar com arquivos OFX
16. ✅ Testar com arquivos PDF
17. ✅ Ajustar UI/UX
18. ✅ Adicionar tratamento de erros
19. ✅ Otimizar performance

---

## 🧪 Casos de Teste

### Teste 1: Upload de Arquivo
- [ ] Upload de CSV válido
- [ ] Upload de OFX válido
- [ ] Upload de PDF válido
- [ ] Rejeitar arquivo muito grande (>5MB)
- [ ] Rejeitar tipo de arquivo inválido
- [ ] Drag and drop funciona
- [ ] Remover arquivo selecionado

### Teste 2: Análise com IA
- [ ] Análise de CSV com múltiplas transações
- [ ] Análise de OFX com múltiplas transações
- [ ] Categorização automática funciona
- [ ] Confiança da IA é exibida
- [ ] Tratamento de erro na análise

### Teste 3: Revisão de Transações
- [ ] Lista ordenada por data
- [ ] Seletor de categoria funciona
- [ ] Categoria sugerida é pré-selecionada
- [ ] Alterar categoria funciona
- [ ] Scroll funciona com muitas transações

### Teste 4: Cadastro de Transações
- [ ] Cadastrar todas as transações
- [ ] Transações aparecem na lista principal
- [ ] Categorias corretas são aplicadas
- [ ] Saldo da conta é atualizado
- [ ] Upload é marcado como importado

### Teste 5: Casos de Erro
- [ ] Erro no upload mostra mensagem clara
- [ ] Erro na análise mostra mensagem clara
- [ ] Erro no cadastro mostra mensagem clara
- [ ] Usuário sem conta mostra mensagem apropriada
- [ ] Arquivo corrompido é tratado

---

## 🎨 Melhorias Futuras

### Fase 2 (Opcional)
- [ ] Permitir editar transações antes de cadastrar
- [ ] Permitir excluir transações da lista
- [ ] Histórico de uploads anteriores
- [ ] Re-análise de arquivo já enviado
- [ ] Exportar resultado da análise

### Fase 3 (Opcional)
- [ ] Suporte a mais formatos (Excel, etc.)
- [ ] OCR para extratos em imagem
- [ ] Detecção automática de duplicatas
- [ ] Sugestão de merge com transações existentes
- [ ] Estatísticas de uploads

---

## 📊 Métricas de Sucesso

- ✅ Taxa de sucesso de upload > 95%
- ✅ Taxa de sucesso de análise > 90%
- ✅ Taxa de sucesso de cadastro > 99%
- ✅ Tempo médio de upload < 3 segundos
- ✅ Tempo médio de análise < 10 segundos
- ✅ Satisfação do usuário > 4.5/5

---

## 🔒 Considerações de Segurança

1. **Arquivos são privados**: Apenas o dono pode acessar
2. **RLS habilitado**: Políticas de segurança no banco
3. **Validação de tamanho**: Máximo 5MB
4. **Validação de tipo**: Apenas formatos permitidos
5. **Limpeza de dados**: Remover dados sensíveis dos logs
6. **Timeout**: Análise tem limite de tempo
7. **Rate limiting**: Limitar uploads por usuário

---

## 📚 Documentação para o Usuário

### Como Importar Extrato

1. **Clique no chatbot** no canto inferior direito
2. **Selecione ou arraste** seu arquivo de extrato (CSV, OFX ou PDF)
3. **Clique em "Fazer Upload"** e aguarde o envio
4. **Clique em "Analisar com IA"** para processar o arquivo
5. **Revise as transações** identificadas pela IA
6. **Ajuste as categorias** se necessário
7. **Clique em "Cadastrar Transações"** para finalizar

### Dicas

- ✅ Use extratos do último mês para melhor precisão
- ✅ Verifique se as categorias sugeridas estão corretas
- ✅ Você pode alterar qualquer categoria antes de cadastrar
- ✅ Arquivos maiores que 5MB devem ser divididos

---

**Status:** Planejamento completo ✅  
**Próximo passo:** Iniciar implementação Fase 1
