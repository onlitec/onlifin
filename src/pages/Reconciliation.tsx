import * as React from 'react';
import { supabase } from '@/db/client';
import { useToast } from '@/hooks/use-toast';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { CheckCircle, XCircle, AlertCircle, Loader2, Save } from 'lucide-react';
import { accountsApi, transactionsApi, categoriesApi } from '@/db/api';
import { Account, Transaction, Category } from '@/types/types';

export default function Reconciliation() {
  const [accounts, setAccounts] = React.useState<Account[]>([]);
  const [categories, setCategories] = React.useState<Category[]>([]);
  const [selectedAccount, setSelectedAccount] = React.useState<string>('');
  const [transactions, setTransactions] = React.useState<Transaction[]>([]);
  const [bankBalance, setBankBalance] = React.useState<string>('');
  const [isLoading, setIsLoading] = React.useState(false);
  const [isSavingCategories, setIsSavingCategories] = React.useState(false);
  const [reconciledIds, setReconciledIds] = React.useState<Set<string>>(new Set());
  const [categorySelections, setCategorySelections] = React.useState<Record<string, string>>({});
  const { toast } = useToast();

  React.useEffect(() => {
    loadAccounts();
    loadCategories();
  }, []);

  React.useEffect(() => {
    if (selectedAccount) {
      loadTransactions();
    }
  }, [selectedAccount]);

  const loadAccounts = async () => {
    try {
      const { data: { user } } = await supabase.auth.getUser();
      if (!user) return;

      const data = await accountsApi.getAccounts(user.id);
      setAccounts(data);
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao carregar contas',
        variant: 'destructive'
      });
    }
  };

  const loadCategories = async () => {
    try {
      const data = await categoriesApi.getCategories();
      setCategories(data);
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao carregar categorias',
        variant: 'destructive'
      });
    }
  };

  const loadTransactions = async () => {
    setIsLoading(true);
    try {
      const { data: { user } } = await supabase.auth.getUser();
      if (!user) return;

      const data = await transactionsApi.getTransactions(user.id);
      const filtered = data.filter(t => t.account_id === selectedAccount);
      setTransactions(filtered);
      
      // Initialize category selections with existing categories
      const initialSelections: Record<string, string> = {};
      filtered.forEach(t => {
        if (t.category_id) {
          initialSelections[t.id] = t.category_id;
        }
      });
      setCategorySelections(initialSelections);
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao carregar transações',
        variant: 'destructive'
      });
    } finally {
      setIsLoading(false);
    }
  };

  const toggleReconciled = (id: string) => {
    setReconciledIds(prev => {
      const newSet = new Set(prev);
      if (newSet.has(id)) {
        newSet.delete(id);
      } else {
        newSet.add(id);
      }
      return newSet;
    });
  };

  const calculateSystemBalance = () => {
    return transactions.reduce((sum, t) => {
      if (t.type === 'income') {
        return sum + t.amount;
      } else {
        return sum - t.amount;
      }
    }, 0);
  };

  const calculateReconciledBalance = () => {
    return transactions
      .filter(t => reconciledIds.has(t.id))
      .reduce((sum, t) => {
        if (t.type === 'income') {
          return sum + t.amount;
        } else {
          return sum - t.amount;
        }
      }, 0);
  };

  const getDifference = () => {
    const bank = parseFloat(bankBalance) || 0;
    const reconciled = calculateReconciledBalance();
    return bank - reconciled;
  };

  const handleFinishReconciliation = async () => {
    const difference = getDifference();
    
    if (Math.abs(difference) > 0.01) {
      toast({
        title: 'Aviso',
        description: `Há uma diferença de R$ ${Math.abs(difference).toFixed(2)}. Verifique as transações.`,
        variant: 'destructive'
      });
      return;
    }

    try {
      // Update account balance
      const account = accounts.find(a => a.id === selectedAccount);
      if (account) {
        await accountsApi.updateAccount(selectedAccount, {
          ...account,
          balance: parseFloat(bankBalance) || 0
        });
      }

      toast({
        title: 'Sucesso',
        description: 'Conciliação finalizada com sucesso'
      });

      // Reset
      setReconciledIds(new Set());
      setBankBalance('');
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao finalizar conciliação',
        variant: 'destructive'
      });
    }
  };

  const handleSaveCategories = async () => {
    setIsSavingCategories(true);
    try {
      const { data: { user } } = await supabase.auth.getUser();
      if (!user) return;

      // Filter transactions that have a category selected (excluding 'none')
      const transactionsToUpdate = Object.entries(categorySelections).filter(
        ([_, categoryId]) => categoryId && categoryId !== '' && categoryId !== 'none'
      );

      if (transactionsToUpdate.length === 0) {
        toast({
          title: 'Aviso',
          description: 'Nenhuma categoria selecionada para salvar',
          variant: 'destructive'
        });
        return;
      }

      // Update each transaction with its selected category
      const updatePromises = transactionsToUpdate.map(([transactionId, categoryId]) => {
        return transactionsApi.updateTransaction(transactionId, {
          category_id: categoryId
        });
      });

      await Promise.all(updatePromises);

      toast({
        title: 'Sucesso',
        description: `${transactionsToUpdate.length} transação(ões) atualizada(s) com sucesso`
      });

      // Reload transactions to reflect changes
      await loadTransactions();
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao salvar categorias',
        variant: 'destructive'
      });
    } finally {
      setIsSavingCategories(false);
    }
  };

  const systemBalance = calculateSystemBalance();
  const reconciledBalance = calculateReconciledBalance();
  const difference = getDifference();
  const isBalanced = Math.abs(difference) < 0.01;

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Conciliação Bancária</h1>
        <p className="text-muted-foreground">
          Compare e reconcilie suas transações com o extrato bancário
        </p>
      </div>

      <div className="grid gap-6 md:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Configuração</CardTitle>
            <CardDescription>
              Selecione a conta e informe o saldo bancário
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="account">Conta</Label>
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
              <Label htmlFor="bank_balance">Saldo no Banco</Label>
              <Input
                id="bank_balance"
                type="number"
                step="0.01"
                value={bankBalance}
                onChange={(e) => setBankBalance(e.target.value)}
                placeholder="0.00"
              />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Resumo da Conciliação</CardTitle>
            <CardDescription>
              Comparação entre sistema e banco
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="flex justify-between items-center">
              <span className="text-sm text-muted-foreground">Saldo no Sistema:</span>
              <span className="font-medium">R$ {systemBalance.toFixed(2)}</span>
            </div>
            <div className="flex justify-between items-center">
              <span className="text-sm text-muted-foreground">Saldo Conciliado:</span>
              <span className="font-medium">R$ {reconciledBalance.toFixed(2)}</span>
            </div>
            <div className="flex justify-between items-center">
              <span className="text-sm text-muted-foreground">Saldo no Banco:</span>
              <span className="font-medium">R$ {(parseFloat(bankBalance) || 0).toFixed(2)}</span>
            </div>
            <div className="pt-4 border-t">
              <div className="flex justify-between items-center">
                <span className="text-sm font-medium">Diferença:</span>
                <div className="flex items-center gap-2">
                  {isBalanced ? (
                    <CheckCircle className="h-5 w-5 text-green-600" />
                  ) : (
                    <AlertCircle className="h-5 w-5 text-yellow-600" />
                  )}
                  <span className={`font-bold ${isBalanced ? 'text-green-600' : 'text-yellow-600'}`}>
                    R$ {Math.abs(difference).toFixed(2)}
                  </span>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {selectedAccount && (
        <Card>
          <CardHeader>
            <div className="flex items-center justify-between">
              <div>
                <CardTitle>Transações</CardTitle>
                <CardDescription>
                  {reconciledIds.size} de {transactions.length} transações conciliadas
                </CardDescription>
              </div>
              <div className="flex gap-2">
                <Button
                  onClick={handleSaveCategories}
                  disabled={isSavingCategories || Object.keys(categorySelections).length === 0}
                  variant="outline"
                >
                  {isSavingCategories ? (
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  ) : (
                    <Save className="mr-2 h-4 w-4" />
                  )}
                  Salvar Categorias
                </Button>
                <Button
                  onClick={handleFinishReconciliation}
                  disabled={!bankBalance || !isBalanced}
                >
                  <CheckCircle className="mr-2 h-4 w-4" />
                  Finalizar Conciliação
                </Button>
              </div>
            </div>
          </CardHeader>
          <CardContent>
            {isLoading ? (
              <div className="flex items-center justify-center py-8">
                <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
              </div>
            ) : transactions.length === 0 ? (
              <div className="text-center py-8 text-muted-foreground">
                Nenhuma transação encontrada para esta conta
              </div>
            ) : (
              <div className="rounded-md border">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead className="w-12">Status</TableHead>
                      <TableHead>Data</TableHead>
                      <TableHead>Descrição</TableHead>
                      <TableHead>Tipo</TableHead>
                      <TableHead>Categoria</TableHead>
                      <TableHead className="text-right">Valor</TableHead>
                      <TableHead className="w-32">Ação</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {transactions.map((transaction) => {
                      const isReconciled = reconciledIds.has(transaction.id);
                      return (
                        <TableRow key={transaction.id}>
                          <TableCell>
                            {isReconciled ? (
                              <CheckCircle className="h-5 w-5 text-green-600" />
                            ) : (
                              <XCircle className="h-5 w-5 text-muted-foreground" />
                            )}
                          </TableCell>
                          <TableCell>
                            {new Date(transaction.date).toLocaleDateString('pt-BR')}
                          </TableCell>
                          <TableCell>{transaction.description}</TableCell>
                          <TableCell>
                            <Badge variant={transaction.type === 'income' ? 'default' : 'destructive'}>
                              {transaction.type === 'income' ? 'Receita' : 'Despesa'}
                            </Badge>
                          </TableCell>
                          <TableCell>
                            <Select
                              value={categorySelections[transaction.id] || 'none'}
                              onValueChange={(value) => {
                                setCategorySelections(prev => ({
                                  ...prev,
                                  [transaction.id]: value
                                }));
                              }}
                            >
                              <SelectTrigger className="w-[180px]">
                                <SelectValue placeholder="Selecione..." />
                              </SelectTrigger>
                              <SelectContent>
                                <SelectItem value="none">Sem categoria</SelectItem>
                                {categories
                                  .filter(cat => cat.type === transaction.type)
                                  .map(cat => (
                                    <SelectItem key={cat.id} value={cat.id}>
                                      {cat.icon} {cat.name}
                                    </SelectItem>
                                  ))}
                              </SelectContent>
                            </Select>
                          </TableCell>
                          <TableCell className="text-right font-medium">
                            {transaction.type === 'income' ? '+' : '-'} R$ {transaction.amount.toFixed(2)}
                          </TableCell>
                          <TableCell>
                            <Button
                              variant={isReconciled ? 'outline' : 'default'}
                              size="sm"
                              onClick={() => toggleReconciled(transaction.id)}
                            >
                              {isReconciled ? 'Desmarcar' : 'Conciliar'}
                            </Button>
                          </TableCell>
                        </TableRow>
                      );
                    })}
                  </TableBody>
                </Table>
              </div>
            )}
          </CardContent>
        </Card>
      )}
    </div>
  );
}
