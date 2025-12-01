import { useState, useEffect } from 'react';
import { supabase } from '@/db/supabase';
import { useToast } from '@/hooks/use-toast';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Checkbox } from '@/components/ui/checkbox';
import { Upload, FileText, CheckCircle, XCircle, Loader2 } from 'lucide-react';
import { accountsApi, transactionsApi, categoriesApi } from '@/db/api';
import { Account, Category } from '@/types/types';

interface ParsedTransaction {
  date: string;
  description: string;
  amount: number;
  type: 'income' | 'expense';
  selected: boolean;
}

export default function Import() {
  const [accounts, setAccounts] = useState<Account[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [selectedAccount, setSelectedAccount] = useState<string>('');
  const [file, setFile] = useState<File | null>(null);
  const [parsedData, setParsedData] = useState<ParsedTransaction[]>([]);
  const [isProcessing, setIsProcessing] = useState(false);
  const [isImporting, setIsImporting] = useState(false);
  const { toast } = useToast();

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      const { data: { user } } = await supabase.auth.getUser();
      if (!user) return;

      const [accountsData, categoriesData] = await Promise.all([
        accountsApi.getAccounts(user.id),
        categoriesApi.getCategories()
      ]);
      setAccounts(accountsData);
      setCategories(categoriesData);
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao carregar dados',
        variant: 'destructive'
      });
    }
  };

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const selectedFile = e.target.files?.[0];
    if (selectedFile) {
      const fileExtension = selectedFile.name.split('.').pop()?.toLowerCase();
      if (!['csv', 'ofx', 'qif'].includes(fileExtension || '')) {
        toast({
          title: 'Erro',
          description: 'Formato de arquivo não suportado. Use CSV, OFX ou QIF.',
          variant: 'destructive'
        });
        return;
      }
      setFile(selectedFile);
      setParsedData([]);
    }
  };

  const parseCSV = (content: string): ParsedTransaction[] => {
    const lines = content.split('\n').filter(line => line.trim());
    const transactions: ParsedTransaction[] = [];

    // Skip header if exists
    const startIndex = lines[0].toLowerCase().includes('data') || 
                       lines[0].toLowerCase().includes('date') ? 1 : 0;

    for (let i = startIndex; i < lines.length; i++) {
      const line = lines[i];
      const parts = line.split(/[,;]/);
      
      if (parts.length >= 3) {
        const date = parts[0].trim();
        const description = parts[1].trim();
        const amountStr = parts[2].trim().replace(/[^\d.,-]/g, '').replace(',', '.');
        const amount = parseFloat(amountStr);

        if (!isNaN(amount) && date) {
          transactions.push({
            date: formatDate(date),
            description: description || 'Transação importada',
            amount: Math.abs(amount),
            type: amount < 0 ? 'expense' : 'income',
            selected: true
          });
        }
      }
    }

    return transactions;
  };

  const parseOFX = (content: string): ParsedTransaction[] => {
    const transactions: ParsedTransaction[] = [];
    const stmtTrnRegex = /<STMTTRN>([\s\S]*?)<\/STMTTRN>/g;
    let match;

    while ((match = stmtTrnRegex.exec(content)) !== null) {
      const trn = match[1];
      const dateMatch = trn.match(/<DTPOSTED>(\d{8})/);
      const amountMatch = trn.match(/<TRNAMT>([-\d.]+)/);
      const memoMatch = trn.match(/<MEMO>(.*?)(?:<|$)/);

      if (dateMatch && amountMatch) {
        const date = dateMatch[1];
        const amount = parseFloat(amountMatch[1]);
        const description = memoMatch ? memoMatch[1].trim() : 'Transação importada';

        transactions.push({
          date: `${date.substring(0, 4)}-${date.substring(4, 6)}-${date.substring(6, 8)}`,
          description,
          amount: Math.abs(amount),
          type: amount < 0 ? 'expense' : 'income',
          selected: true
        });
      }
    }

    return transactions;
  };

  const parseQIF = (content: string): ParsedTransaction[] => {
    const transactions: ParsedTransaction[] = [];
    const lines = content.split('\n');
    let currentTransaction: Partial<ParsedTransaction> = {};

    for (const line of lines) {
      const trimmed = line.trim();
      if (!trimmed) continue;

      if (trimmed === '^') {
        if (currentTransaction.date && currentTransaction.amount !== undefined) {
          transactions.push({
            date: currentTransaction.date,
            description: currentTransaction.description || 'Transação importada',
            amount: Math.abs(currentTransaction.amount),
            type: currentTransaction.amount < 0 ? 'expense' : 'income',
            selected: true
          });
        }
        currentTransaction = {};
      } else if (trimmed.startsWith('D')) {
        currentTransaction.date = formatDate(trimmed.substring(1));
      } else if (trimmed.startsWith('T')) {
        currentTransaction.amount = parseFloat(trimmed.substring(1).replace(',', '.'));
      } else if (trimmed.startsWith('P') || trimmed.startsWith('M')) {
        currentTransaction.description = trimmed.substring(1);
      }
    }

    return transactions;
  };

  const formatDate = (dateStr: string): string => {
    // Try to parse various date formats
    const formats = [
      /(\d{4})-(\d{2})-(\d{2})/, // YYYY-MM-DD
      /(\d{2})\/(\d{2})\/(\d{4})/, // DD/MM/YYYY
      /(\d{2})-(\d{2})-(\d{4})/, // DD-MM-YYYY
    ];

    for (const format of formats) {
      const match = dateStr.match(format);
      if (match) {
        if (format === formats[0]) {
          return `${match[1]}-${match[2]}-${match[3]}`;
        } else {
          return `${match[3]}-${match[2]}-${match[1]}`;
        }
      }
    }

    return new Date().toISOString().split('T')[0];
  };

  const handleProcess = async () => {
    if (!file) {
      toast({
        title: 'Erro',
        description: 'Selecione um arquivo para processar',
        variant: 'destructive'
      });
      return;
    }

    setIsProcessing(true);
    try {
      const content = await file.text();
      const fileExtension = file.name.split('.').pop()?.toLowerCase();
      let parsed: ParsedTransaction[] = [];

      switch (fileExtension) {
        case 'csv':
          parsed = parseCSV(content);
          break;
        case 'ofx':
          parsed = parseOFX(content);
          break;
        case 'qif':
          parsed = parseQIF(content);
          break;
        default:
          throw new Error('Formato não suportado');
      }

      if (parsed.length === 0) {
        toast({
          title: 'Aviso',
          description: 'Nenhuma transação encontrada no arquivo',
          variant: 'destructive'
        });
      } else {
        setParsedData(parsed);
        toast({
          title: 'Sucesso',
          description: `${parsed.length} transações encontradas`
        });
      }
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao processar arquivo',
        variant: 'destructive'
      });
    } finally {
      setIsProcessing(false);
    }
  };

  const handleImport = async () => {
    if (!selectedAccount) {
      toast({
        title: 'Erro',
        description: 'Selecione uma conta para importar',
        variant: 'destructive'
      });
      return;
    }

    const selectedTransactions = parsedData.filter(t => t.selected);
    if (selectedTransactions.length === 0) {
      toast({
        title: 'Erro',
        description: 'Selecione pelo menos uma transação',
        variant: 'destructive'
      });
      return;
    }

    setIsImporting(true);
    try {
      const { data: { user } } = await supabase.auth.getUser();
      if (!user) return;

      // Get default category for each type
      const expenseCategory = categories.find(c => c.name === 'Outros' && c.type === 'expense');
      const incomeCategory = categories.find(c => c.name === 'Outros' && c.type === 'income');

      for (const transaction of selectedTransactions) {
        const categoryId = transaction.type === 'expense' 
          ? expenseCategory?.id 
          : incomeCategory?.id;

        await transactionsApi.createTransaction({
          user_id: user.id,
          account_id: selectedAccount,
          card_id: null,
          category_id: categoryId || null,
          type: transaction.type,
          amount: transaction.amount,
          description: transaction.description,
          date: transaction.date,
          is_recurring: false,
          recurrence_pattern: null,
          installment_number: null,
          total_installments: null,
          parent_transaction_id: null,
          is_reconciled: false,
          tags: ['importado']
        });
      }

      toast({
        title: 'Sucesso',
        description: `${selectedTransactions.length} transações importadas com sucesso`
      });

      // Reset
      setFile(null);
      setParsedData([]);
      setSelectedAccount('');
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao importar transações',
        variant: 'destructive'
      });
    } finally {
      setIsImporting(false);
    }
  };

  const toggleTransaction = (index: number) => {
    setParsedData(prev => prev.map((t, i) => 
      i === index ? { ...t, selected: !t.selected } : t
    ));
  };

  const toggleAll = () => {
    const allSelected = parsedData.every(t => t.selected);
    setParsedData(prev => prev.map(t => ({ ...t, selected: !allSelected })));
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Importar Extratos</h1>
        <p className="text-muted-foreground">
          Importe transações de arquivos CSV, OFX ou QIF
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Selecionar Arquivo</CardTitle>
          <CardDescription>
            Escolha um arquivo de extrato bancário para importar
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="account">Conta de Destino</Label>
            <Select value={selectedAccount} onValueChange={setSelectedAccount}>
              <SelectTrigger>
                <SelectValue placeholder="Selecione uma conta" />
              </SelectTrigger>
              <SelectContent>
                {accounts.map(account => (
                  <SelectItem key={account.id} value={account.id}>
                    {account.name} - {account.bank}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div className="space-y-2">
            <Label htmlFor="file">Arquivo</Label>
            <div className="flex gap-2">
              <input
                id="file"
                type="file"
                accept=".csv,.ofx,.qif"
                onChange={handleFileChange}
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm file:border-0 file:bg-transparent file:text-sm file:font-medium"
              />
              <Button
                onClick={handleProcess}
                disabled={!file || isProcessing}
              >
                {isProcessing ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    Processando...
                  </>
                ) : (
                  <>
                    <Upload className="mr-2 h-4 w-4" />
                    Processar
                  </>
                )}
              </Button>
            </div>
            <p className="text-xs text-muted-foreground">
              Formatos suportados: CSV, OFX, QIF (máximo 5MB)
            </p>
          </div>

          {file && (
            <div className="flex items-center gap-2 p-3 bg-muted rounded-md">
              <FileText className="h-5 w-5 text-muted-foreground" />
              <div className="flex-1">
                <p className="text-sm font-medium">{file.name}</p>
                <p className="text-xs text-muted-foreground">
                  {(file.size / 1024).toFixed(2)} KB
                </p>
              </div>
            </div>
          )}
        </CardContent>
      </Card>

      {parsedData.length > 0 && (
        <Card>
          <CardHeader>
            <div className="flex items-center justify-between">
              <div>
                <CardTitle>Transações Encontradas</CardTitle>
                <CardDescription>
                  {parsedData.filter(t => t.selected).length} de {parsedData.length} selecionadas
                </CardDescription>
              </div>
              <Button
                onClick={handleImport}
                disabled={!selectedAccount || isImporting || parsedData.filter(t => t.selected).length === 0}
              >
                {isImporting ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    Importando...
                  </>
                ) : (
                  <>
                    <CheckCircle className="mr-2 h-4 w-4" />
                    Importar Selecionadas
                  </>
                )}
              </Button>
            </div>
          </CardHeader>
          <CardContent>
            <div className="rounded-md border">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead className="w-12">
                      <Checkbox
                        checked={parsedData.every(t => t.selected)}
                        onCheckedChange={toggleAll}
                      />
                    </TableHead>
                    <TableHead>Data</TableHead>
                    <TableHead>Descrição</TableHead>
                    <TableHead>Tipo</TableHead>
                    <TableHead className="text-right">Valor</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {parsedData.map((transaction, index) => (
                    <TableRow key={index}>
                      <TableCell>
                        <Checkbox
                          checked={transaction.selected}
                          onCheckedChange={() => toggleTransaction(index)}
                        />
                      </TableCell>
                      <TableCell>{new Date(transaction.date).toLocaleDateString('pt-BR')}</TableCell>
                      <TableCell>{transaction.description}</TableCell>
                      <TableCell>
                        <span className={`inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs ${
                          transaction.type === 'income' 
                            ? 'bg-green-100 text-green-700' 
                            : 'bg-red-100 text-red-700'
                        }`}>
                          {transaction.type === 'income' ? (
                            <>
                              <CheckCircle className="h-3 w-3" />
                              Receita
                            </>
                          ) : (
                            <>
                              <XCircle className="h-3 w-3" />
                              Despesa
                            </>
                          )}
                        </span>
                      </TableCell>
                      <TableCell className="text-right font-medium">
                        R$ {transaction.amount.toFixed(2)}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
