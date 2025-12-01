import { useEffect, useState } from 'react';
import { supabase } from '@/db/supabase';
import { transactionsApi, accountsApi, cardsApi, categoriesApi } from '@/db/api';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { useToast } from '@/hooks/use-toast';
import { Plus, TrendingUp, TrendingDown } from 'lucide-react';
import type { Transaction, Account, Card as CardType, Category } from '@/types/types';

export default function Transactions() {
  const [transactions, setTransactions] = useState<Transaction[]>([]);
  const [accounts, setAccounts] = useState<Account[]>([]);
  const [cards, setCards] = useState<CardType[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [formData, setFormData] = useState({
    type: 'expense' as 'income' | 'expense',
    amount: '',
    date: new Date().toISOString().split('T')[0],
    description: '',
    category_id: '',
    account_id: '',
    card_id: '',
    is_recurring: false,
    recurrence_pattern: 'monthly' as 'daily' | 'weekly' | 'monthly' | 'yearly',
    is_installment: false,
    total_installments: '1'
  });
  const { toast } = useToast();

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      const { data: { user } } = await supabase.auth.getUser();
      if (!user) return;

      const [txs, accs, crds, cats] = await Promise.all([
        transactionsApi.getTransactions(user.id),
        accountsApi.getAccounts(user.id),
        cardsApi.getCards(user.id),
        categoriesApi.getCategories()
      ]);

      setTransactions(txs);
      setAccounts(accs);
      setCards(crds);
      setCategories(cats);
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao carregar dados',
        variant: 'destructive'
      });
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const { data: { user } } = await supabase.auth.getUser();
      if (!user) return;

      const baseTransaction = {
        ...formData,
        user_id: user.id,
        amount: Number(formData.amount),
        category_id: formData.category_id || null,
        account_id: formData.account_id || null,
        card_id: formData.card_id || null,
        is_recurring: formData.is_recurring,
        is_reconciled: false,
        recurrence_pattern: formData.is_recurring ? formData.recurrence_pattern : null,
        tags: null
      };

      if (formData.is_installment && Number(formData.total_installments) > 1) {
        // Create installments
        const totalInstallments = Number(formData.total_installments);
        const installmentAmount = Number(formData.amount) / totalInstallments;
        
        for (let i = 1; i <= totalInstallments; i++) {
          const installmentDate = new Date(formData.date);
          installmentDate.setMonth(installmentDate.getMonth() + (i - 1));
          
          await transactionsApi.createTransaction({
            ...baseTransaction,
            amount: installmentAmount,
            date: installmentDate.toISOString().split('T')[0],
            description: `${formData.description} (${i}/${totalInstallments})`,
            installment_number: i,
            total_installments: totalInstallments,
            parent_transaction_id: null
          });
        }
        toast({ 
          title: 'Sucesso', 
          description: `${totalInstallments} parcelas criadas com sucesso` 
        });
      } else {
        // Create single transaction
        await transactionsApi.createTransaction({
          ...baseTransaction,
          date: formData.date,
          installment_number: null,
          total_installments: null,
          parent_transaction_id: null
        });
        toast({ title: 'Sucesso', description: 'Transação criada com sucesso' });
      }

      setIsDialogOpen(false);
      resetForm();
      loadData();
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao criar transação',
        variant: 'destructive'
      });
    }
  };

  const resetForm = () => {
    setFormData({
      type: 'expense',
      amount: '',
      date: new Date().toISOString().split('T')[0],
      description: '',
      category_id: '',
      account_id: '',
      card_id: '',
      is_recurring: false,
      recurrence_pattern: 'monthly',
      is_installment: false,
      total_installments: '1'
    });
  };

  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL'
    }).format(value);
  };

  const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('pt-BR');
  };

  return (
    <div className="container mx-auto p-6 space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold">Transações</h1>
        <Dialog open={isDialogOpen} onOpenChange={(open) => {
          setIsDialogOpen(open);
          if (!open) resetForm();
        }}>
          <DialogTrigger asChild>
            <Button>
              <Plus className="mr-2 h-4 w-4" />
              Nova Transação
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Nova Transação</DialogTitle>
              <DialogDescription>
                Registre uma nova receita ou despesa
              </DialogDescription>
            </DialogHeader>
            <form onSubmit={handleSubmit}>
              <div className="space-y-4 py-4">
                <div className="space-y-2">
                  <Label htmlFor="type">Tipo *</Label>
                  <Select
                    value={formData.type}
                    onValueChange={(value: 'income' | 'expense') => setFormData({ ...formData, type: value })}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="income">Receita</SelectItem>
                      <SelectItem value="expense">Despesa</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="amount">Valor *</Label>
                  <Input
                    id="amount"
                    type="number"
                    step="0.01"
                    value={formData.amount}
                    onChange={(e) => setFormData({ ...formData, amount: e.target.value })}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="date">Data *</Label>
                  <Input
                    id="date"
                    type="date"
                    value={formData.date}
                    onChange={(e) => setFormData({ ...formData, date: e.target.value })}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="category">Categoria</Label>
                  <Select
                    value={formData.category_id}
                    onValueChange={(value) => setFormData({ ...formData, category_id: value })}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Selecione uma categoria" />
                    </SelectTrigger>
                    <SelectContent>
                      {categories
                        .filter(c => c.type === formData.type)
                        .map(cat => (
                          <SelectItem key={cat.id} value={cat.id}>
                            {cat.icon} {cat.name}
                          </SelectItem>
                        ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="account">Conta</Label>
                  <Select
                    value={formData.account_id}
                    onValueChange={(value) => setFormData({ ...formData, account_id: value })}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Selecione uma conta" />
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
                  <Label htmlFor="description">Descrição</Label>
                  <Input
                    id="description"
                    value={formData.description}
                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                  />
                </div>
                
                <div className="flex items-center space-x-2 pt-2">
                  <Checkbox
                    id="is_recurring"
                    checked={formData.is_recurring}
                    onCheckedChange={(checked) => 
                      setFormData({ ...formData, is_recurring: checked as boolean })
                    }
                  />
                  <Label htmlFor="is_recurring" className="cursor-pointer">
                    Transação recorrente
                  </Label>
                </div>

                {formData.is_recurring && (
                  <div className="space-y-2">
                    <Label htmlFor="recurrence">Frequência</Label>
                    <Select
                      value={formData.recurrence_pattern}
                      onValueChange={(value: 'daily' | 'weekly' | 'monthly' | 'yearly') => 
                        setFormData({ ...formData, recurrence_pattern: value })
                      }
                    >
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="daily">Diária</SelectItem>
                        <SelectItem value="weekly">Semanal</SelectItem>
                        <SelectItem value="monthly">Mensal</SelectItem>
                        <SelectItem value="yearly">Anual</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                )}

                <div className="flex items-center space-x-2 pt-2">
                  <Checkbox
                    id="is_installment"
                    checked={formData.is_installment}
                    onCheckedChange={(checked) => 
                      setFormData({ ...formData, is_installment: checked as boolean })
                    }
                  />
                  <Label htmlFor="is_installment" className="cursor-pointer">
                    Parcelar transação
                  </Label>
                </div>

                {formData.is_installment && (
                  <div className="space-y-2">
                    <Label htmlFor="installments">Número de Parcelas</Label>
                    <Input
                      id="installments"
                      type="number"
                      min="2"
                      max="48"
                      value={formData.total_installments}
                      onChange={(e) => setFormData({ ...formData, total_installments: e.target.value })}
                    />
                    <p className="text-xs text-muted-foreground">
                      Valor por parcela: R$ {(Number(formData.amount) / Number(formData.total_installments) || 0).toFixed(2)}
                    </p>
                  </div>
                )}
              </div>
              <DialogFooter>
                <Button type="submit">Criar</Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <div className="space-y-2">
        {transactions.map((tx) => {
          const category = categories.find(c => c.id === tx.category_id);
          const account = accounts.find(a => a.id === tx.account_id);
          
          return (
            <Card key={tx.id}>
              <CardContent className="flex items-center justify-between p-4">
                <div className="flex items-center gap-4">
                  <div className={`p-2 rounded-full ${tx.type === 'income' ? 'bg-income/10' : 'bg-expense/10'}`}>
                    {tx.type === 'income' ? (
                      <TrendingUp className="h-5 w-5 text-income" />
                    ) : (
                      <TrendingDown className="h-5 w-5 text-expense" />
                    )}
                  </div>
                  <div>
                    <p className="font-medium">{tx.description || 'Sem descrição'}</p>
                    <p className="text-sm text-muted-foreground">
                      {category?.icon} {category?.name || 'Sem categoria'} • {account?.name || 'Sem conta'} • {formatDate(tx.date)}
                    </p>
                  </div>
                </div>
                <div className={`text-lg font-bold ${tx.type === 'income' ? 'text-income' : 'text-expense'}`}>
                  {tx.type === 'income' ? '+' : '-'} {formatCurrency(tx.amount)}
                </div>
              </CardContent>
            </Card>
          );
        })}
      </div>

      {transactions.length === 0 && (
        <Card>
          <CardContent className="flex flex-col items-center justify-center py-12">
            <p className="text-lg font-medium mb-2">Nenhuma transação registrada</p>
            <p className="text-sm text-muted-foreground">
              Comece adicionando suas receitas e despesas
            </p>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
