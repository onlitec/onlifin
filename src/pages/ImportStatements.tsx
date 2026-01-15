import * as React from 'react';
import { supabase } from '@/db/client';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { useToast } from '@/hooks/use-toast';
import { Upload, FileText, Loader2, CheckCircle2, AlertCircle, Sparkles, History, X, Pencil } from 'lucide-react';
import { parseOFX, isValidOFX } from '@/utils/ofxParser';
import type { Category, Account, Transaction } from '@/types/types';
import { accountsApi } from '@/db/api';

interface ParsedTransaction {
  date: string;
  description: string;
  amount: number;
  type: 'income' | 'expense';
  merchant?: string;
}

interface CategorizedTransaction extends ParsedTransaction {
  suggestedCategory: string;
  suggestedCategoryId?: string;
  isNewCategory: boolean;
  confidence: number;
  selectedCategoryId?: string;
  // Intelligent Matching Fields
  action: 'new' | 'reconcile' | 'ignore' | 'edit';
  matchId?: string;
  isDuplicate?: boolean;
}

interface NewCategorySuggestion {
  name: string;
  type: 'income' | 'expense';
  selected: boolean;
}

export default function ImportStatements() {
  const [fileContent, setFileContent] = React.useState('');
  const [textContent, setTextContent] = React.useState('');
  const [isAnalyzing, setIsAnalyzing] = React.useState(false);
  const [isImporting, setIsImporting] = React.useState(false);
  const [categorizedTransactions, setCategorizedTransactions] = React.useState<CategorizedTransaction[]>([]);
  const [newCategorySuggestions, setNewCategorySuggestions] = React.useState<NewCategorySuggestion[]>([]);
  const [existingCategories, setExistingCategories] = React.useState<Category[]>([]);
  const [accounts, setAccounts] = React.useState<Account[]>([]);
  const [selectedAccountId, setSelectedAccountId] = React.useState<string>('');
  const [existingTransactions, setExistingTransactions] = React.useState<Transaction[]>([]);
  const [step, setStep] = React.useState<'upload' | 'review' | 'edit_phase' | 'complete'>('upload');
  const [ofxError, setOfxError] = React.useState<string>('');
  const [transactionsToEdit, setTransactionsToEdit] = React.useState<CategorizedTransaction[]>([]);
  const { toast } = useToast();

  React.useEffect(() => {
    loadAccounts();
  }, []);

  const loadAccounts = async () => {
    try {
      const { data: { user } } = await supabase.auth.getUser();
      if (!user) return;
      const data = await accountsApi.getAccounts(user.id);
      setAccounts(data);
      if (data.length > 0 && !selectedAccountId) {
        setSelectedAccountId(data[0].id);
      }
    } catch (error: any) {
      console.error('Erro ao carregar contas:', error);
    }
  };

  const handleFileUpload = async (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file) return;

    try {
      const text = await file.text();
      setFileContent(text);
      toast({
        title: 'Arquivo carregado',
        description: 'Arquivo lido com sucesso. Clique em "Analisar" para continuar.',
      });
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao ler arquivo',
        variant: 'destructive',
      });
    }
  };

  const parseCSV = (content: string): ParsedTransaction[] => {
    const lines = content.trim().split('\n');
    const transactions: ParsedTransaction[] = [];

    if (lines.length === 0) {
      console.log('CSV vazio');
      return transactions;
    }

    // Detecta o separador (vírgula ou ponto-e-vírgula)
    const firstLine = lines[0];
    const semicolonCount = (firstLine.match(/;/g) || []).length;
    const commaCount = (firstLine.match(/,/g) || []).length;
    const separator = semicolonCount > commaCount ? ';' : ',';

    console.log(`Separador detectado: "${separator}" (vírgulas: ${commaCount}, ponto-e-vírgula: ${semicolonCount})`);

    // Função para fazer split respeitando aspas
    const splitCSVLine = (line: string, sep: string): string[] => {
      const fields: string[] = [];
      let currentField = '';
      let inQuotes = false;

      for (let j = 0; j < line.length; j++) {
        const char = line[j];

        if (char === '"') {
          inQuotes = !inQuotes;
        } else if (char === sep && !inQuotes) {
          fields.push(currentField.trim());
          currentField = '';
        } else {
          currentField += char;
        }
      }
      fields.push(currentField.trim());
      return fields;
    };

    // Detecta se tem header
    const firstFields = splitCSVLine(lines[0], separator);
    const hasHeader = firstFields.some(f =>
      f.toLowerCase().includes('data') ||
      f.toLowerCase().includes('date') ||
      f.toLowerCase().includes('descri') ||
      f.toLowerCase().includes('valor') ||
      f.toLowerCase().includes('amount') ||
      f.toLowerCase().includes('identifica')
    );

    const startIndex = hasHeader ? 1 : 0;
    console.log(`Header detectado: ${hasHeader}, iniciando na linha ${startIndex}`);
    console.log(`Colunas do header: ${firstFields.join(' | ')}`);
    console.log(`Total de colunas: ${firstFields.length}`);

    // Tenta identificar índices das colunas
    let dateIdx = 0;
    let descIdx = 1;
    let amountIdx = 2;
    let typeIdx = -1; // Coluna de tipo (DEBIT/CREDIT)

    if (hasHeader) {
      for (let i = 0; i < firstFields.length; i++) {
        const col = firstFields[i].toLowerCase();
        if (col.includes('data') || col.includes('date')) {
          dateIdx = i;
        } else if (col.includes('identifica') || col.includes('descri') || col.includes('histor') || col.includes('memo') || col.includes('nome')) {
          descIdx = i;
        } else if (col.includes('valor') || col.includes('amount') || col.includes('quantia') || col === 'value') {
          amountIdx = i;
        } else if (col.includes('tipo') || col.includes('type')) {
          typeIdx = i;
        }
      }
    }

    console.log(`Índices: data=${dateIdx}, descrição=${descIdx}, valor=${amountIdx}, tipo=${typeIdx}`);

    for (let i = startIndex; i < lines.length; i++) {
      const line = lines[i].trim();
      if (!line) continue;

      const fields = splitCSVLine(line, separator);

      // Precisa ter pelo menos data, descrição e valor
      const minFields = Math.max(dateIdx, descIdx, amountIdx) + 1;
      if (fields.length >= minFields) {
        const dateStr = fields[dateIdx] || '';
        const description = (fields[descIdx] || '').replace(/^"|"$/g, '');
        const amountStr = fields[amountIdx] || '';
        const typeStr = typeIdx >= 0 ? (fields[typeIdx] || '').toUpperCase() : '';

        // Parse do valor (suporta formato brasileiro: 1.234,56 ou americano: 1,234.56)
        let amount = 0;
        let cleanAmount = amountStr.replace(/[^\d.,-]/g, '');

        // Se tem vírgula E ponto, determina qual é separador decimal
        if (cleanAmount.includes(',') && cleanAmount.includes('.')) {
          // Se vírgula vem depois do ponto, é formato BR (1.234,56)
          if (cleanAmount.lastIndexOf(',') > cleanAmount.lastIndexOf('.')) {
            cleanAmount = cleanAmount.replace(/\./g, '').replace(',', '.');
          } else {
            // Formato US (1,234.56)
            cleanAmount = cleanAmount.replace(/,/g, '');
          }
        } else if (cleanAmount.includes(',')) {
          // Só tem vírgula, assume decimal BR
          cleanAmount = cleanAmount.replace(',', '.');
        }

        amount = Math.abs(parseFloat(cleanAmount));

        if (!isNaN(amount) && amount > 0 && description) {
          // Determina se é receita ou despesa
          // Prioridade: coluna de tipo > sinal do valor
          let type: 'income' | 'expense' = 'expense';

          if (typeStr.includes('CREDIT') || typeStr.includes('CRÉDITO') || typeStr.includes('CREDITO')) {
            type = 'income';
          } else if (typeStr.includes('DEBIT') || typeStr.includes('DÉBITO') || typeStr.includes('DEBITO')) {
            type = 'expense';
          } else {
            // Fallback: usa sinal do valor
            const isNegative = amountStr.includes('-') || amountStr.startsWith('(');
            type = isNegative ? 'expense' : 'income';
          }

          transactions.push({
            date: dateStr,
            description,
            amount,
            type,
            merchant: description.split(/[\s-]/)[0],
          });

          console.log(`Transação ${transactions.length}: ${dateStr} | ${description.substring(0, 30)} | ${type} | R$ ${amount.toFixed(2)}`);
        }
      }
    }

    console.log(`Total de transações parseadas: ${transactions.length}`);
    return transactions;
  };

  const parseTextContent = (content: string): ParsedTransaction[] => {
    // Simple text parser - can be enhanced
    const lines = content.trim().split('\n');
    const transactions: ParsedTransaction[] = [];

    for (const line of lines) {
      // Try to extract date, description, and amount
      const dateMatch = line.match(/\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}/);
      const amountMatch = line.match(/R?\$?\s*[\d.,]+/);

      if (dateMatch && amountMatch) {
        const date = dateMatch[0];
        const amountStr = amountMatch[0];
        const amount = Math.abs(parseFloat(amountStr.replace(/[^\d.,-]/g, '').replace(',', '.')));

        // Extract description (text between date and amount)
        const dateIndex = line.indexOf(dateMatch[0]);
        const amountIndex = line.indexOf(amountMatch[0]);
        const description = line.substring(dateIndex + dateMatch[0].length, amountIndex).trim();

        if (!isNaN(amount) && amount > 0 && description) {
          const isNegative = line.toLowerCase().includes('débito') || line.toLowerCase().includes('despesa');
          const type: 'income' | 'expense' = isNegative ? 'expense' : 'income';

          transactions.push({
            date,
            description,
            amount,
            type,
            merchant: description.split(' ')[0],
          });
        }
      }
    }

    return transactions;
  };

  const analyzeTransactions = async () => {
    const content = fileContent || textContent;
    if (!content.trim()) {
      toast({
        title: 'Erro',
        description: 'Por favor, carregue um arquivo ou cole o conteúdo do extrato',
        variant: 'destructive',
      });
      return;
    }

    setIsAnalyzing(true);

    try {
      // Detecta e faz parse do conteúdo baseado no formato
      let parsed: ParsedTransaction[] = [];
      const content = fileContent || textContent;

      // Verifica se é OFX
      if (isValidOFX(content)) {
        console.log('Arquivo OFX detectado, fazendo parse...');
        try {
          parsed = parseOFX(content);
          setOfxError(''); // Limpa erro anterior se houver
        } catch (ofxErr: any) {
          // Captura erro específico do OFX e mostra ajuda
          setOfxError(ofxErr.message || 'Erro ao processar arquivo OFX');
          throw ofxErr;
        }
      }
      // Caso contrário, tenta CSV ou texto
      else {
        setOfxError(''); // Limpa erro OFX se não for OFX
        parsed = fileContent ? parseCSV(fileContent) : parseTextContent(textContent);
      }

      if (parsed.length === 0) {
        toast({
          title: 'Erro',
          description: 'Nenhuma transação encontrada no extrato. Verifique o formato.',
          variant: 'destructive',
        });
        setIsAnalyzing(false);
        return;
      }

      // setCategorizedTransactions will be set after AI analysis

      // Get existing categories and accounts
      const { data: { user } } = await supabase.auth.getUser();
      if (!user) throw new Error('Usuário não autenticado');

      if (!selectedAccountId) {
        throw new Error('Selecione uma conta bancária antes de analisar');
      }

      // Determine date range for pre-fetching existing transactions
      const transactionDates = parsed.map(t => {
        // Handle various date formats (DD/MM/YYYY or YYYY-MM-DD)
        const parts = t.date.split(/[\/\-]/);
        if (parts[0].length === 4) return new Date(t.date); // YYYY-MM-DD
        return new Date(Number(parts[2]), Number(parts[1]) - 1, Number(parts[0])); // DD/MM/YYYY
      }).filter(d => !isNaN(d.getTime()));

      const minDate = new Date(Math.min(...transactionDates.map(d => d.getTime()))).toISOString().split('T')[0];
      const maxDate = new Date(Math.max(...transactionDates.map(d => d.getTime()))).toISOString().split('T')[0];

      // Fetch existing transactions for duplicate matching
      const { data: existingTx, error: txError } = await supabase
        .from('transactions')
        .select('*')
        .eq('user_id', user.id)
        .eq('account_id', selectedAccountId)
        .gte('date', minDate)
        .lte('date', maxDate);

      if (txError) throw txError;
      setExistingTransactions(existingTx || []);

      const { data: categories, error: catError } = await supabase
        .from('categories')
        .select('*')
        .eq('user_id', user.id);

      if (catError) throw catError;
      setExistingCategories(categories || []);

      // Send to AI for categorization
      const { data, error } = await supabase.functions.invoke('ai-assistant', {
        body: {
          action: 'categorize_transactions',
          transactions: parsed,
          existingCategories: categories || [],
        },
      });

      if (error) {
        console.error('Erro da IA:', error);
        throw new Error('Erro ao categorizar transações com IA');
      }

      const result = data;
      if (!result) {
        throw new Error('Resposta inválida da IA');
      }

      // Perform intelligent matching
      const processed: CategorizedTransaction[] = (result.categorizedTransactions || []).map((t: any) => {
        // Helper to normalize date for comparison
        const parseTDate = (d: string) => {
          const parts = d.split(/[\/\-]/);
          if (parts[0].length === 4) return d; // YYYY-MM-DD
          return `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
        };

        const targetDate = parseTDate(t.date);

        // Find exact match (same date and amount)
        const match = (existingTx || []).find(ex =>
          ex.date === targetDate &&
          Math.abs(Number(ex.amount)) === Math.abs(t.amount)
        );

        return {
          ...t,
          action: match ? 'reconcile' : 'new',
          matchId: match?.id,
          isDuplicate: !!match
        };
      });

      setCategorizedTransactions(processed);
      setNewCategorySuggestions(result.newCategories || []);
      setStep('review');

      toast({
        title: 'Análise concluída',
        description: `${parsed.length} transações analisadas. ${processed.filter(p => p.isDuplicate).length} duplicatas potenciais encontradas.`,
      });
    } catch (error: any) {
      console.error('Erro completo:', error);
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao analisar transações',
        variant: 'destructive',
      });
    } finally {
      setIsAnalyzing(false);
    }
  };

  const handleCategoryChange = (index: number, categoryId: string) => {
    const updated = [...categorizedTransactions];
    updated[index].selectedCategoryId = categoryId;
    setCategorizedTransactions(updated);
  };

  const toggleNewCategory = (index: number) => {
    const updated = [...newCategorySuggestions];
    updated[index].selected = !updated[index].selected;
    setNewCategorySuggestions(updated);
  };

  const importTransactions = async () => {
    setIsImporting(true);

    try {
      const { data: { user } } = await supabase.auth.getUser();
      if (!user) throw new Error('Usuário não autenticado');

      // 1. Process new categories
      const selectedNewCategories = newCategorySuggestions.filter(c => c.selected);
      const createdCategoryMap = new Map<string, string>();

      for (const newCat of selectedNewCategories) {
        const { data: created, error: createError } = await supabase
          .from('categories')
          .insert({
            user_id: user.id,
            name: newCat.name,
            type: newCat.type,
            icon: 'tag',
            color: '#6366f1',
          })
          .select()
          .single();

        if (createError) throw createError;
        createdCategoryMap.set(newCat.name, created.id);
      }

      // 2. Identify transactions by action
      const toInsert = categorizedTransactions.filter(t => t.action === 'new');
      const toReconcile = categorizedTransactions.filter(t => t.action === 'reconcile');
      const toEditLater = categorizedTransactions.filter(t => t.action === 'edit');

      // 3. Perform Reconciliations (Update existing transactions)
      for (const t of toReconcile) {
        if (!t.matchId) continue;
        const { error: recError } = await supabase
          .from('transactions')
          .update({ is_reconciled: true, updated_at: new Date().toISOString() })
          .eq('id', t.matchId);

        if (recError) console.error(`Erro ao conciliar ${t.matchId}:`, recError);
      }

      // 4. Perform New Insertions
      if (toInsert.length > 0) {
        const transactionsToInsert = toInsert.map(t => {
          let categoryId = t.selectedCategoryId || t.suggestedCategoryId;
          if (t.isNewCategory && createdCategoryMap.has(t.suggestedCategory)) {
            categoryId = createdCategoryMap.get(t.suggestedCategory);
          }

          return {
            user_id: user.id,
            type: t.type,
            amount: t.amount,
            date: t.date,
            description: t.description,
            category_id: categoryId,
            account_id: selectedAccountId,
            is_reconciled: true
          };
        });

        const { error: insertError } = await supabase
          .from('transactions')
          .insert(transactionsToInsert);

        if (insertError) throw insertError;
      }

      // 5. Handle Phase 2 (Editing)
      if (toEditLater.length > 0) {
        setTransactionsToEdit(toEditLater);
        setStep('edit_phase');
        toast({
          title: 'Fase 1 concluída',
          description: `${toInsert.length + toReconcile.length} transações processadas. Agora revise as transações marcadas para edição.`,
        });
      } else {
        setStep('complete');
        toast({
          title: 'Importação concluída',
          description: `${toInsert.length + toReconcile.length} transações processadas com sucesso.`,
        });
      }
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao importar transações',
        variant: 'destructive',
      });
    } finally {
      setIsImporting(false);
    }
  };

  const resetImport = () => {
    setFileContent('');
    setTextContent('');
    // Clear state
    setCategorizedTransactions([]);
    setNewCategorySuggestions([]);
    setStep('upload');
  };

  if (step === 'complete') {
    return (
      <div className="w-full max-w-[1600px] mx-auto p-6 space-y-6">
        <Card>
          <CardContent className="flex flex-col items-center justify-center py-12">
            <CheckCircle2 className="h-16 w-16 text-green-600 mb-4" />
            <h2 className="text-2xl font-bold mb-2">Importação Concluída!</h2>
            <p className="text-muted-foreground mb-6 text-center">
              Suas transações foram importadas e categorizadas com sucesso.
            </p>
            <div className="flex gap-4">
              <Button onClick={resetImport} variant="outline">
                Importar Mais Transações
              </Button>
              <Button onClick={() => window.location.href = '/transactions'}>
                Ver Transações
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
    );
  }

  const finishEditing = async () => {
    setIsImporting(true);
    try {
      const { data: { user } } = await supabase.auth.getUser();
      if (!user) throw new Error('Usuário não autenticado');

      const transactionsToInsert = transactionsToEdit.map(t => ({
        user_id: user.id,
        type: t.type,
        amount: t.amount,
        date: t.date,
        description: t.description,
        category_id: t.selectedCategoryId || t.suggestedCategoryId,
        account_id: selectedAccountId,
        is_reconciled: true
      }));

      const { error } = await supabase
        .from('transactions')
        .insert(transactionsToInsert);

      if (error) throw error;

      setStep('complete');
      toast({
        title: 'Importação concluída',
        description: `${transactionsToInsert.length} transações editadas e importadas com sucesso.`,
      });
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao salvar transações editadas',
        variant: 'destructive',
      });
    } finally {
      setIsImporting(false);
    }
  };

  if (step === 'edit_phase') {
    return (
      <div className="w-full max-w-[1600px] mx-auto p-6 space-y-6">
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-3xl font-bold flex items-center gap-2">
              <Pencil className="h-8 w-8 text-blue-500" />
              Fase 2: Edição Manual
            </h1>
            <p className="text-muted-foreground">
              Ajuste as transações que você marcou para edição antes do cadastro final
            </p>
          </div>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Transações para Ajuste</CardTitle>
            <CardDescription>
              Você pode alterar a descrição, data e valor destas transações
            </CardDescription>
          </CardHeader>
          <CardContent>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="w-[150px]">Data</TableHead>
                  <TableHead>Descrição</TableHead>
                  <TableHead className="w-[150px]">Valor (R$)</TableHead>
                  <TableHead>Categoria</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {transactionsToEdit.map((transaction, index) => (
                  <TableRow key={index}>
                    <TableCell>
                      <Input
                        type="date"
                        value={transaction.date.split('/').length === 3 ? transaction.date.split('/').reverse().join('-') : transaction.date}
                        className="h-8 text-xs"
                        onChange={(e) => {
                          const updated = [...transactionsToEdit];
                          updated[index].date = e.target.value;
                          setTransactionsToEdit(updated);
                        }}
                      />
                    </TableCell>
                    <TableCell>
                      <Input
                        value={transaction.description}
                        className="h-8 text-xs"
                        onChange={(e) => {
                          const updated = [...transactionsToEdit];
                          updated[index].description = e.target.value;
                          setTransactionsToEdit(updated);
                        }}
                      />
                    </TableCell>
                    <TableCell>
                      <Input
                        type="number"
                        step="0.01"
                        value={transaction.amount}
                        className={`h-8 text-xs font-medium ${transaction.type === 'income' ? 'text-green-600' : 'text-red-600'}`}
                        onChange={(e) => {
                          const updated = [...transactionsToEdit];
                          updated[index].amount = Number(e.target.value);
                          setTransactionsToEdit(updated);
                        }}
                      />
                    </TableCell>
                    <TableCell>
                      <Select
                        value={transaction.selectedCategoryId || transaction.suggestedCategoryId || ''}
                        onValueChange={(value) => {
                          const updated = [...transactionsToEdit];
                          updated[index].selectedCategoryId = value;
                          setTransactionsToEdit(updated);
                        }}
                      >
                        <SelectTrigger className="w-[180px] h-8 text-xs">
                          <SelectValue placeholder="Categoria" />
                        </SelectTrigger>
                        <SelectContent>
                          {existingCategories
                            .filter(c => c.type === transaction.type)
                            .map(cat => (
                              <SelectItem key={cat.id} value={cat.id}>
                                {cat.icon} {cat.name}
                              </SelectItem>
                            ))}
                        </SelectContent>
                      </Select>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </CardContent>
        </Card>

        <div className="flex justify-end gap-4">
          <Button onClick={finishEditing} disabled={isImporting}>
            {isImporting ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Finalizando...
              </>
            ) : (
              'Concluir Importação'
            )}
          </Button>
        </div>
      </div>
    );
  }

  if (step === 'review') {
    return (
      <div className="w-full max-w-[1600px] mx-auto p-6 space-y-6">
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-3xl font-bold">Revisar Transações</h1>
            <p className="text-muted-foreground">
              Revise as categorias sugeridas antes de importar
            </p>
          </div>
          <Button onClick={resetImport} variant="outline">
            Cancelar
          </Button>
        </div>

        {newCategorySuggestions.length > 0 && (
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Sparkles className="h-5 w-5 text-yellow-500" />
                Novas Categorias Sugeridas
              </CardTitle>
              <CardDescription>
                Selecione as categorias que deseja criar
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-2">
                {newCategorySuggestions.map((cat, index) => (
                  <div key={index} className="flex items-center space-x-2">
                    <Checkbox
                      id={`new-cat-${index}`}
                      checked={cat.selected}
                      onCheckedChange={() => toggleNewCategory(index)}
                    />
                    <Label htmlFor={`new-cat-${index}`} className="flex-1 cursor-pointer">
                      {cat.name} ({cat.type === 'income' ? 'Receita' : 'Despesa'})
                    </Label>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        )}

        <Card>
          <CardHeader>
            <CardTitle>Transações Categorizadas</CardTitle>
            <CardDescription>
              {categorizedTransactions.length} transações encontradas
            </CardDescription>
          </CardHeader>
          <CardContent>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="w-[100px]">Data</TableHead>
                  <TableHead>Descrição</TableHead>
                  <TableHead>Valor</TableHead>
                  <TableHead>Categoria</TableHead>
                  <TableHead>Status/Ação</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {categorizedTransactions.map((transaction, index) => {
                  const hasMatch = transaction.isDuplicate && transaction.matchId;
                  const matchingTx = existingTransactions.find(et => et.id === transaction.matchId);

                  return (
                    <TableRow key={index} className={transaction.action === 'ignore' ? 'opacity-50' : ''}>
                      <TableCell>{transaction.date}</TableCell>
                      <TableCell>
                        <div className="flex flex-col">
                          <span className="font-medium">{transaction.description}</span>
                          {hasMatch && (
                            <span className="text-[10px] text-yellow-600 flex items-center gap-1 mt-1 bg-yellow-50 w-fit px-1 rounded border border-yellow-200">
                              <History className="h-3 w-3" />
                              Já existe: {matchingTx?.description}
                            </span>
                          )}
                        </div>
                      </TableCell>
                      <TableCell>
                        <span className={transaction.type === 'income' ? 'text-green-600 font-medium' : 'text-red-600 font-medium'}>
                          {transaction.type === 'income' ? '+' : '-'} R$ {transaction.amount.toFixed(2)}
                        </span>
                      </TableCell>
                      <TableCell>
                        <Select
                          value={transaction.selectedCategoryId || transaction.suggestedCategoryId || ''}
                          onValueChange={(value) => handleCategoryChange(index, value)}
                          disabled={transaction.action === 'ignore' || transaction.action === 'reconcile'}
                        >
                          <SelectTrigger className="w-[180px] h-8 text-xs">
                            <SelectValue placeholder="Categoria" />
                          </SelectTrigger>
                          <SelectContent>
                            {existingCategories
                              .filter(c => c.type === transaction.type)
                              .map(cat => (
                                <SelectItem key={cat.id} value={cat.id}>
                                  {cat.icon} {cat.name}
                                </SelectItem>
                              ))}
                            {transaction.isNewCategory && transaction.suggestedCategory && (
                              <SelectItem value={transaction.suggestedCategoryId || `new_${transaction.suggestedCategory}`}>
                                {transaction.suggestedCategory} (Nova)
                              </SelectItem>
                            )}
                          </SelectContent>
                        </Select>
                      </TableCell>
                      <TableCell>
                        <Select
                          value={transaction.action}
                          onValueChange={(value: any) => {
                            const updated = [...categorizedTransactions];
                            updated[index].action = value;
                            setCategorizedTransactions(updated);
                          }}
                        >
                          <SelectTrigger className={`w-[140px] h-8 text-xs ${transaction.action === 'reconcile' ? 'bg-yellow-50 border-yellow-200 text-yellow-700' :
                            transaction.action === 'ignore' ? 'bg-gray-50 text-gray-500' :
                              transaction.action === 'edit' ? 'bg-blue-50 border-blue-200 text-blue-700' :
                                'bg-green-50 border-green-200 text-green-700'
                            }`}>
                            <div className="flex items-center gap-2">
                              {transaction.action === 'reconcile' && <History className="h-3 w-3" />}
                              {transaction.action === 'new' && <CheckCircle2 className="h-3 w-3" />}
                              {transaction.action === 'ignore' && <X className="h-3 w-3" />}
                              {transaction.action === 'edit' && <Pencil className="h-3 w-3" />}
                              <SelectValue />
                            </div>
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="new" className="text-green-600 font-medium">Novo Cadastro</SelectItem>
                            {hasMatch && <SelectItem value="reconcile" className="text-yellow-600 font-medium">Conciliar</SelectItem>}
                            <SelectItem value="edit" className="text-blue-600 font-medium">Editar</SelectItem>
                            <SelectItem value="ignore" className="text-gray-500 font-medium">Não Importar</SelectItem>
                          </SelectContent>
                        </Select>
                      </TableCell>
                    </TableRow>
                  );
                })}
              </TableBody>
            </Table>
          </CardContent>
        </Card>

        <div className="flex justify-end gap-4">
          <Button onClick={resetImport} variant="outline">
            Voltar
          </Button>
          <Button onClick={importTransactions} disabled={isImporting}>
            {isImporting ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Importando...
              </>
            ) : (
              'Cadastrar Transações'
            )}
          </Button>
        </div>
      </div>
    );
  }

  return (
    <div className="w-full max-w-[1600px] mx-auto p-6 space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Importar Extrato Bancário</h1>
        <p className="text-muted-foreground">
          Importe seu extrato e deixe a IA categorizar automaticamente suas transações
        </p>
      </div>

      <Alert>
        <Sparkles className="h-4 w-4" />
        <AlertDescription>
          A IA analisará cada transação e sugerirá a categoria mais apropriada. Você poderá revisar e ajustar antes de importar.
        </AlertDescription>
      </Alert>

      <Card>
        <CardHeader>
          <CardTitle>Carregar Extrato</CardTitle>
          <CardDescription>
            Escolha como deseja fornecer o extrato bancário
          </CardDescription>
        </CardHeader>
        <CardContent>
          <Tabs defaultValue="file">
            <TabsList className="grid w-full grid-cols-2">
              <TabsTrigger value="file">
                <Upload className="mr-2 h-4 w-4" />
                Arquivo CSV
              </TabsTrigger>
              <TabsTrigger value="text">
                <FileText className="mr-2 h-4 w-4" />
                Colar Texto
              </TabsTrigger>
            </TabsList>
            <TabsContent value="file" className="space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="account">Conta para Importação *</Label>
                  <Select value={selectedAccountId} onValueChange={setSelectedAccountId}>
                    <SelectTrigger id="account">
                      <SelectValue placeholder="Selecione a conta..." />
                    </SelectTrigger>
                    <SelectContent>
                      {accounts.map(acc => (
                        <SelectItem key={acc.id} value={acc.id}>
                          {acc.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="file">Arquivo de Extrato *</Label>
                  <Input
                    id="file"
                    type="file"
                    accept=".csv,.txt,.ofx"
                    onChange={handleFileUpload}
                  />
                </div>
              </div>
              <p className="text-sm text-muted-foreground">
                Formatos aceitos: CSV, TXT ou OFX. Selecione a conta onde essas transações ocorreram.
              </p>
              {fileContent && (
                <Alert>
                  <CheckCircle2 className="h-4 w-4" />
                  <AlertDescription>
                    Arquivo carregado com sucesso. Clique em "Analisar com IA" para continuar.
                  </AlertDescription>
                </Alert>
              )}
              {ofxError && (
                <Alert variant="destructive">
                  <AlertCircle className="h-4 w-4" />
                  <AlertDescription className="space-y-2">
                    <p className="font-semibold">Erro ao processar arquivo OFX</p>
                    <p className="text-sm">{ofxError}</p>
                    <div className="mt-3 space-y-1 text-sm">
                      <p className="font-medium">Soluções alternativas:</p>
                      <ul className="list-disc list-inside space-y-1 ml-2">
                        <li>Exporte o arquivo novamente do banco</li>
                        <li>Tente um período menor (ex: 1 mês)</li>
                        <li>Use o formato CSV como alternativa</li>
                        <li>Consulte o console do navegador (F12) para mais detalhes</li>
                      </ul>
                    </div>
                  </AlertDescription>
                </Alert>
              )}
            </TabsContent>
            <TabsContent value="text" className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="text">Cole o conteúdo do extrato</Label>
                <Textarea
                  id="text"
                  placeholder="Cole aqui o conteúdo do seu extrato bancário..."
                  value={textContent}
                  onChange={(e) => setTextContent(e.target.value)}
                  rows={10}
                />
                <p className="text-sm text-muted-foreground">
                  Cole o texto do seu extrato. A IA tentará identificar as transações automaticamente.
                </p>
              </div>
            </TabsContent>
          </Tabs>
        </CardContent>
      </Card>

      <div className="flex justify-end">
        <Button
          onClick={analyzeTransactions}
          disabled={isAnalyzing || (!fileContent && !textContent)}
          size="lg"
        >
          {isAnalyzing ? (
            <>
              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              Analisando...
            </>
          ) : (
            <>
              <Sparkles className="mr-2 h-4 w-4" />
              Analisar com IA
            </>
          )}
        </Button>
      </div>
    </div>
  );
}
