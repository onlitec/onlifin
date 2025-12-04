import { useState } from 'react';
import { supabase } from '@/db/supabase';
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
import { Upload, FileText, Loader2, CheckCircle2, AlertCircle, Sparkles } from 'lucide-react';
import { parseOFX, isValidOFX } from '@/utils/ofxParser';
import type { Category } from '@/types/types';

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
}

interface NewCategorySuggestion {
  name: string;
  type: 'income' | 'expense';
  selected: boolean;
}

export default function ImportStatements() {
  const [fileContent, setFileContent] = useState('');
  const [textContent, setTextContent] = useState('');
  const [isAnalyzing, setIsAnalyzing] = useState(false);
  const [isImporting, setIsImporting] = useState(false);
  const [parsedTransactions, setParsedTransactions] = useState<ParsedTransaction[]>([]);
  const [categorizedTransactions, setCategorizedTransactions] = useState<CategorizedTransaction[]>([]);
  const [newCategorySuggestions, setNewCategorySuggestions] = useState<NewCategorySuggestion[]>([]);
  const [existingCategories, setExistingCategories] = useState<Category[]>([]);
  const [step, setStep] = useState<'upload' | 'review' | 'complete'>('upload');
  const [ofxError, setOfxError] = useState<string>('');
  const { toast } = useToast();

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

    // Skip header if exists
    const startIndex = lines[0].toLowerCase().includes('data') ? 1 : 0;

    for (let i = startIndex; i < lines.length; i++) {
      const line = lines[i].trim();
      if (!line) continue;

      // Try to parse CSV line (handle quoted fields)
      const fields: string[] = [];
      let currentField = '';
      let inQuotes = false;

      for (let j = 0; j < line.length; j++) {
        const char = line[j];
        
        if (char === '"') {
          inQuotes = !inQuotes;
        } else if (char === ',' && !inQuotes) {
          fields.push(currentField.trim());
          currentField = '';
        } else {
          currentField += char;
        }
      }
      fields.push(currentField.trim());

      if (fields.length >= 3) {
        const [dateStr, description, amountStr] = fields;
        const amount = Math.abs(parseFloat(amountStr.replace(/[^\d.,-]/g, '').replace(',', '.')));
        
        if (!isNaN(amount) && amount > 0) {
          // Determine if it's income or expense based on amount sign or description
          const isNegative = amountStr.includes('-');
          const type: 'income' | 'expense' = isNegative ? 'expense' : 'income';

          transactions.push({
            date: dateStr,
            description: description.replace(/^"|"$/g, ''),
            amount,
            type,
            merchant: description.replace(/^"|"$/g, '').split(' ')[0],
          });
        }
      }
    }

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

      setParsedTransactions(parsed);

      // Get existing categories
      const { data: { user } } = await supabase.auth.getUser();
      if (!user) throw new Error('Usuário não autenticado');

      const { data: categories, error: catError } = await supabase
        .from('categories')
        .select('*')
        .eq('user_id', user.id);

      if (catError) throw catError;
      setExistingCategories(categories || []);

      console.log('Enviando para IA:', {
        transactionCount: parsed.length,
        categoryCount: categories?.length || 0
      });

      // Send to AI for categorization
      const { data, error } = await supabase.functions.invoke('ai-assistant', {
        body: {
          action: 'categorize_transactions',
          transactions: parsed,
          existingCategories: categories || [],
        },
      });

      console.log('Resposta da IA:', { data, error });

      if (error) {
        console.error('Erro da Edge Function:', error);
        const errorMsg = await error?.context?.text();
        console.error('Mensagem de erro:', errorMsg);
        throw new Error(errorMsg || 'Erro ao analisar transações');
      }

      if (!data) {
        throw new Error('Resposta vazia da IA');
      }

      const result = data;
      
      if (!result.categorizedTransactions || result.categorizedTransactions.length === 0) {
        throw new Error('IA não retornou transações categorizadas');
      }

      setCategorizedTransactions(result.categorizedTransactions || []);
      setNewCategorySuggestions(result.newCategories || []);
      setStep('review');

      toast({
        title: 'Análise concluída',
        description: `${parsed.length} transações analisadas e categorizadas`,
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

      // Get user's accounts
      const { data: accounts, error: accError } = await supabase
        .from('accounts')
        .select('*')
        .eq('user_id', user.id)
        .limit(1);

      if (accError) throw accError;
      if (!accounts || accounts.length === 0) {
        throw new Error('Você precisa ter pelo menos uma conta cadastrada para importar transações');
      }

      const defaultAccountId = accounts[0].id;

      // Create new categories
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

      // Prepare transactions for insert
      const transactionsToInsert = categorizedTransactions.map(t => {
        let categoryId = t.selectedCategoryId || t.suggestedCategoryId;
        
        // If it's a new category, get the created ID
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
          account_id: defaultAccountId,
        };
      });

      // Insert transactions
      const { error: insertError } = await supabase
        .from('transactions')
        .insert(transactionsToInsert);

      if (insertError) throw insertError;

      setStep('complete');
      toast({
        title: 'Importação concluída',
        description: `${transactionsToInsert.length} transações importadas com sucesso`,
      });
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
    setParsedTransactions([]);
    setCategorizedTransactions([]);
    setNewCategorySuggestions([]);
    setStep('upload');
  };

  if (step === 'complete') {
    return (
      <div className="container mx-auto p-6 space-y-6">
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

  if (step === 'review') {
    return (
      <div className="container mx-auto p-6 space-y-6">
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
                  <TableHead>Data</TableHead>
                  <TableHead>Descrição</TableHead>
                  <TableHead>Tipo</TableHead>
                  <TableHead>Valor</TableHead>
                  <TableHead>Categoria</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {categorizedTransactions.map((transaction, index) => (
                  <TableRow key={index}>
                    <TableCell>{transaction.date}</TableCell>
                    <TableCell>{transaction.description}</TableCell>
                    <TableCell>
                      <span className={transaction.type === 'income' ? 'text-green-600' : 'text-red-600'}>
                        {transaction.type === 'income' ? 'Receita' : 'Despesa'}
                      </span>
                    </TableCell>
                    <TableCell>R$ {transaction.amount.toFixed(2)}</TableCell>
                    <TableCell>
                      <Select
                        value={transaction.selectedCategoryId || transaction.suggestedCategoryId || ''}
                        onValueChange={(value) => handleCategoryChange(index, value)}
                      >
                        <SelectTrigger className="w-[200px]">
                          <SelectValue placeholder="Selecione categoria" />
                        </SelectTrigger>
                        <SelectContent>
                          {existingCategories
                            .filter(c => c.type === transaction.type)
                            .map(cat => (
                              <SelectItem key={cat.id} value={cat.id}>
                                {cat.name}
                              </SelectItem>
                            ))}
                          {transaction.isNewCategory && (
                            <SelectItem value={transaction.suggestedCategoryId || ''}>
                              {transaction.suggestedCategory} (Nova)
                            </SelectItem>
                          )}
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
    <div className="container mx-auto p-6 space-y-6">
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
              <div className="space-y-2">
                <Label htmlFor="file">Arquivo de Extrato</Label>
                <Input
                  id="file"
                  type="file"
                  accept=".csv,.txt,.ofx"
                  onChange={handleFileUpload}
                />
                <p className="text-sm text-muted-foreground">
                  Formatos aceitos: CSV, TXT ou OFX
                </p>
              </div>
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
