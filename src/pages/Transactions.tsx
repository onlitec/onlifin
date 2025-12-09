import { useEffect, useState, useMemo } from 'react';
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
import { Plus, TrendingUp, TrendingDown, Pencil, Trash2, Search, Filter, X, ArrowUpDown, ArrowRightLeft, CheckCircle2, Save } from 'lucide-react';
import { ActiveFiltersBar } from '@/components/common/FilterBadge';
import type { Transaction, Account, Card as CardType, Category } from '@/types/types';

export default function Transactions() {
  const [transactions, setTransactions] = useState<Transaction[]>([]);
  const [accounts, setAccounts] = useState<Account[]>([]);
  const [cards, setCards] = useState<CardType[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingTransaction, setEditingTransaction] = useState<Transaction | null>(null);
  
  // Filtros e busca
  const [searchTerm, setSearchTerm] = useState('');
  const [filterAccount, setFilterAccount] = useState<string>('all');
  const [filterCategory, setFilterCategory] = useState<string>('all');
  const [filterType, setFilterType] = useState<string>('all'); // all, income, expense
  const [filterDateFrom, setFilterDateFrom] = useState<string>('');
  const [filterDateTo, setFilterDateTo] = useState<string>('');
  const [sortBy, setSortBy] = useState<string>('date-desc'); // date-desc, date-asc, category, amount-desc, amount-asc
  const [showFilters, setShowFilters] = useState(false);
  
  // Category selection state
  const [categorySelections, setCategorySelections] = useState<Record<string, string>>({});
  const [isSavingCategories, setIsSavingCategories] = useState(false);
  
  const [formData, setFormData] = useState({
    type: 'expense' as 'income' | 'expense' | 'transfer',
    amount: '',
    date: new Date().toISOString().split('T')[0],
    description: '',
    category_id: '',
    account_id: '',
    card_id: '',
    destination_account_id: '', // Para transferências
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
      
      // Initialize category selections with existing categories
      const initialSelections: Record<string, string> = {};
      txs.forEach(t => {
        if (t.category_id) {
          initialSelections[t.id] = t.category_id;
        }
      });
      setCategorySelections(initialSelections);
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

      // Validação para transferências
      if (formData.type === 'transfer') {
        if (!formData.account_id) {
          toast({
            title: 'Erro',
            description: 'Selecione a conta de origem',
            variant: 'destructive'
          });
          return;
        }
        if (!formData.destination_account_id) {
          toast({
            title: 'Erro',
            description: 'Selecione a conta de destino',
            variant: 'destructive'
          });
          return;
        }
        if (formData.account_id === formData.destination_account_id) {
          toast({
            title: 'Erro',
            description: 'As contas de origem e destino devem ser diferentes',
            variant: 'destructive'
          });
          return;
        }
      }

      if (editingTransaction) {
        // Não permitir editar transferências (devem ser excluídas e recriadas)
        if (editingTransaction.is_transfer) {
          toast({
            title: 'Erro',
            description: 'Transferências não podem ser editadas. Exclua e crie uma nova.',
            variant: 'destructive'
          });
          return;
        }
        
        // Update existing transaction
        await transactionsApi.updateTransaction(editingTransaction.id, {
          type: formData.type,
          amount: Number(formData.amount),
          date: formData.date,
          description: formData.description,
          category_id: formData.category_id || null,
          account_id: formData.account_id || null,
          card_id: formData.card_id || null,
          is_recurring: formData.is_recurring,
          recurrence_pattern: formData.is_recurring ? formData.recurrence_pattern : null
        });
        toast({ title: 'Sucesso', description: 'Transação atualizada com sucesso' });
      } else {
        // Criar transferência
        if (formData.type === 'transfer') {
          await transactionsApi.createTransfer({
            userId: user.id,
            sourceAccountId: formData.account_id,
            destinationAccountId: formData.destination_account_id,
            amount: Number(formData.amount),
            date: formData.date,
            description: formData.description || 'Transferência entre contas'
          });
          toast({ 
            title: 'Sucesso', 
            description: 'Transferência realizada com sucesso' 
          });
        } else {
          // Create new transaction (income or expense)
          const baseTransaction = {
            ...formData,
            user_id: user.id,
            amount: Number(formData.amount),
            category_id: formData.category_id || null,
            account_id: formData.account_id || null,
            card_id: formData.card_id || null,
            is_recurring: formData.is_recurring,
            is_reconciled: false,
            is_transfer: false,
            transfer_destination_account_id: null,
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
        }
      }

      setIsDialogOpen(false);
      setEditingTransaction(null);
      resetForm();
      loadData();
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || `Erro ao ${editingTransaction ? 'atualizar' : 'criar'} transação`,
        variant: 'destructive'
      });
    }
  };

  const handleEdit = (transaction: Transaction) => {
    setEditingTransaction(transaction);
    setFormData({
      type: transaction.type,
      amount: transaction.amount.toString(),
      date: transaction.date,
      description: transaction.description || '',
      category_id: transaction.category_id || '',
      account_id: transaction.account_id || '',
      card_id: transaction.card_id || '',
      destination_account_id: transaction.transfer_destination_account_id || '',
      is_recurring: transaction.is_recurring || false,
      recurrence_pattern: (transaction.recurrence_pattern || 'monthly') as 'daily' | 'weekly' | 'monthly' | 'yearly',
      is_installment: false,
      total_installments: '1'
    });
    setIsDialogOpen(true);
  };

  const handleDelete = async (id: string) => {
    if (!confirm('Tem certeza que deseja excluir esta transação?')) return;
    
    try {
      await transactionsApi.deleteTransaction(id);
      toast({ title: 'Sucesso', description: 'Transação excluída com sucesso' });
      loadData();
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao excluir transação',
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
        const transaction = transactions.find(t => t.id === transactionId);
        if (transaction) {
          return transactionsApi.updateTransaction(transactionId, {
            ...transaction,
            category_id: categoryId
          });
        }
        return Promise.resolve();
      });

      await Promise.all(updatePromises);

      toast({
        title: 'Sucesso',
        description: `${transactionsToUpdate.length} transação(ões) atualizada(s) com sucesso`
      });

      // Reload transactions to reflect changes
      await loadData();
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

  const handleDialogOpenChange = (open: boolean) => {
    setIsDialogOpen(open);
    if (!open) {
      setEditingTransaction(null);
      resetForm();
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
      destination_account_id: '',
      is_recurring: false,
      recurrence_pattern: 'monthly',
      is_installment: false,
      total_installments: '1'
    });
  };

  // Filtrar e ordenar transações
  const filteredAndSortedTransactions = useMemo(() => {
    let filtered = [...transactions];

    // Filtro de busca (descrição)
    if (searchTerm) {
      filtered = filtered.filter(tx => 
        tx.description?.toLowerCase().includes(searchTerm.toLowerCase())
      );
    }

    // Filtro por conta
    if (filterAccount && filterAccount !== 'all') {
      filtered = filtered.filter(tx => tx.account_id === filterAccount);
    }

    // Filtro por categoria
    if (filterCategory && filterCategory !== 'all') {
      filtered = filtered.filter(tx => tx.category_id === filterCategory);
    }

    // Filtro por tipo (receita/despesa/transferência)
    if (filterType && filterType !== 'all') {
      if (filterType === 'transfer') {
        filtered = filtered.filter(tx => tx.is_transfer);
      } else {
        filtered = filtered.filter(tx => tx.type === filterType && !tx.is_transfer);
      }
    }

    // Filtro por data (de)
    if (filterDateFrom) {
      filtered = filtered.filter(tx => tx.date >= filterDateFrom);
    }

    // Filtro por data (até)
    if (filterDateTo) {
      filtered = filtered.filter(tx => tx.date <= filterDateTo);
    }

    // Ordenação
    filtered.sort((a, b) => {
      switch (sortBy) {
        case 'date-desc':
          return new Date(b.date).getTime() - new Date(a.date).getTime();
        case 'date-asc':
          return new Date(a.date).getTime() - new Date(b.date).getTime();
        case 'category': {
          const catA = categories.find(c => c.id === a.category_id)?.name || '';
          const catB = categories.find(c => c.id === b.category_id)?.name || '';
          return catA.localeCompare(catB);
        }
        case 'amount-desc':
          return b.amount - a.amount;
        case 'amount-asc':
          return a.amount - b.amount;
        default:
          return 0;
      }
    });

    return filtered;
  }, [transactions, searchTerm, filterAccount, filterCategory, filterType, filterDateFrom, filterDateTo, sortBy, categories]);

  // Limpar todos os filtros
  const clearFilters = () => {
    setSearchTerm('');
    setFilterAccount('all');
    setFilterCategory('all');
    setFilterType('all');
    setFilterDateFrom('');
    setFilterDateTo('');
    setSortBy('date-desc');
  };

  // Verificar se há filtros ativos
  const hasActiveFilters = searchTerm || filterAccount !== 'all' || filterCategory !== 'all' || 
    filterType !== 'all' || filterDateFrom || filterDateTo || sortBy !== 'date-desc';

  // Construir lista de filtros ativos para exibição
  const activeFilters = useMemo(() => {
    const filters: Array<{
      key: string;
      label: string;
      value: string;
      onRemove: () => void;
    }> = [];

    if (searchTerm) {
      filters.push({
        key: 'search',
        label: 'Busca',
        value: searchTerm,
        onRemove: () => setSearchTerm('')
      });
    }

    if (filterAccount !== 'all') {
      const account = accounts.find(a => a.id === filterAccount);
      filters.push({
        key: 'account',
        label: 'Conta',
        value: account?.name || 'Desconhecida',
        onRemove: () => setFilterAccount('all')
      });
    }

    if (filterCategory !== 'all') {
      const category = categories.find(c => c.id === filterCategory);
      filters.push({
        key: 'category',
        label: 'Categoria',
        value: category?.name || 'Desconhecida',
        onRemove: () => setFilterCategory('all')
      });
    }

    if (filterType !== 'all') {
      const typeLabels = {
        income: 'Receitas',
        expense: 'Despesas',
        transfer: 'Transferências'
      };
      filters.push({
        key: 'type',
        label: 'Tipo',
        value: typeLabels[filterType as keyof typeof typeLabels] || filterType,
        onRemove: () => setFilterType('all')
      });
    }

    if (filterDateFrom) {
      filters.push({
        key: 'dateFrom',
        label: 'Data inicial',
        value: formatDate(filterDateFrom),
        onRemove: () => setFilterDateFrom('')
      });
    }

    if (filterDateTo) {
      filters.push({
        key: 'dateTo',
        label: 'Data final',
        value: formatDate(filterDateTo),
        onRemove: () => setFilterDateTo('')
      });
    }

    if (sortBy !== 'date-desc') {
      const sortLabels = {
        'date-asc': 'Data (crescente)',
        'date-desc': 'Data (decrescente)',
        'category': 'Categoria',
        'amount-desc': 'Valor (maior)',
        'amount-asc': 'Valor (menor)'
      };
      filters.push({
        key: 'sort',
        label: 'Ordenação',
        value: sortLabels[sortBy as keyof typeof sortLabels] || sortBy,
        onRemove: () => setSortBy('date-desc')
      });
    }

    return filters;
  }, [searchTerm, filterAccount, filterCategory, filterType, filterDateFrom, filterDateTo, sortBy, accounts, categories]);

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
    <div className="container mx-auto p-4 xl:p-8 space-y-6">
      {/* Header Section */}
      <div className="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-4 pb-2">
        <div>
          <h1 className="text-3xl xl:text-4xl font-bold tracking-tight">Transações</h1>
          <p className="text-muted-foreground mt-1">Gerencie suas receitas, despesas e transferências</p>
        </div>
        <div className="flex gap-2 w-full xl:w-auto">
          <Button 
            onClick={handleSaveCategories}
            disabled={isSavingCategories || Object.keys(categorySelections).length === 0}
            variant="outline"
            size="lg"
            className="flex-1 xl:flex-initial"
          >
            <Save className="mr-2 h-5 w-5" />
            Salvar Categorias
          </Button>
          <Dialog open={isDialogOpen} onOpenChange={handleDialogOpenChange}>
            <DialogTrigger asChild>
              <Button size="lg" className="flex-1 xl:flex-initial">
                <Plus className="mr-2 h-5 w-5" />
                Nova Transação
              </Button>
            </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>{editingTransaction ? 'Editar Transação' : 'Nova Transação'}</DialogTitle>
              <DialogDescription>
                {editingTransaction ? 'Atualize os dados da transação' : 'Registre uma nova receita ou despesa'}
              </DialogDescription>
            </DialogHeader>
            <form onSubmit={handleSubmit}>
              <div className="space-y-4 py-4">
                <div className="space-y-2">
                  <Label htmlFor="type">Tipo *</Label>
                  <Select
                    value={formData.type}
                    onValueChange={(value: 'income' | 'expense' | 'transfer') => setFormData({ ...formData, type: value })}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="income">Receita</SelectItem>
                      <SelectItem value="expense">Despesa</SelectItem>
                      <SelectItem value="transfer">Transferência</SelectItem>
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
                
                {formData.type !== 'transfer' && (
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
                )}
                
                <div className="space-y-2">
                  <Label htmlFor="account">{formData.type === 'transfer' ? 'Conta de Origem *' : 'Conta'}</Label>
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
                
                {formData.type === 'transfer' && (
                  <div className="space-y-2">
                    <Label htmlFor="destination_account">Conta de Destino *</Label>
                    <Select
                      value={formData.destination_account_id}
                      onValueChange={(value) => setFormData({ ...formData, destination_account_id: value })}
                    >
                      <SelectTrigger>
                        <SelectValue placeholder="Selecione a conta de destino" />
                      </SelectTrigger>
                      <SelectContent>
                        {accounts
                          .filter(acc => acc.id !== formData.account_id)
                          .map(acc => (
                            <SelectItem key={acc.id} value={acc.id}>
                              {acc.name}
                            </SelectItem>
                          ))}
                      </SelectContent>
                    </Select>
                  </div>
                )}
                
                <div className="space-y-2">
                  <Label htmlFor="description">Descrição</Label>
                  <Input
                    id="description"
                    value={formData.description}
                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                  />
                </div>
                
                {formData.type !== 'transfer' && (
                  <>
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

                    {!editingTransaction && (
                      <>
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
                      </>
                    )}
                  </>
                )}
              </div>
              <DialogFooter>
                <Button type="submit">{editingTransaction ? 'Atualizar' : 'Criar'}</Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
        </div>
      </div>

      {/* Barra de Busca e Filtros */}
      <Card className="shadow-sm">
        <CardContent className="p-6 space-y-4">
          {/* Busca e botões de ação */}
          <div className="flex flex-col xl:flex-row gap-3">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Buscar transações por descrição..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="pl-10 h-11"
              />
            </div>
            <div className="flex gap-2">
              <Button
                variant={showFilters ? "default" : "outline"}
                onClick={() => setShowFilters(!showFilters)}
                className="flex-1 xl:flex-none"
              >
                <Filter className="mr-2 h-4 w-4" />
                Filtros
              </Button>
              {hasActiveFilters && (
                <Button
                  variant="ghost"
                  onClick={clearFilters}
                  title="Limpar filtros"
                  className="flex-1 xl:flex-none"
                >
                  <X className="mr-2 h-4 w-4" />
                  Limpar
                </Button>
              )}
            </div>
          </div>

          {/* Painel de Filtros */}
          {showFilters && (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 pt-4 border-t">
              {/* Filtro por Tipo */}
              <div className="space-y-2">
                <Label>Tipo</Label>
                <Select value={filterType} onValueChange={setFilterType}>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">Todos</SelectItem>
                    <SelectItem value="income">Receitas</SelectItem>
                    <SelectItem value="expense">Despesas</SelectItem>
                    <SelectItem value="transfer">Transferências</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              {/* Filtro por Conta */}
              <div className="space-y-2">
                <Label>Conta Bancária</Label>
                <Select value={filterAccount} onValueChange={setFilterAccount}>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">Todas as contas</SelectItem>
                    {accounts.map(acc => (
                      <SelectItem key={acc.id} value={acc.id}>
                        {acc.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              {/* Filtro por Categoria */}
              <div className="space-y-2">
                <Label>Categoria</Label>
                <Select value={filterCategory} onValueChange={setFilterCategory}>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">Todas as categorias</SelectItem>
                    {categories.map(cat => (
                      <SelectItem key={cat.id} value={cat.id}>
                        {cat.icon} {cat.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              {/* Filtro por Data (De) */}
              <div className="space-y-2">
                <Label>Data Inicial</Label>
                <Input
                  type="date"
                  value={filterDateFrom}
                  onChange={(e) => setFilterDateFrom(e.target.value)}
                />
              </div>

              {/* Filtro por Data (Até) */}
              <div className="space-y-2">
                <Label>Data Final</Label>
                <Input
                  type="date"
                  value={filterDateTo}
                  onChange={(e) => setFilterDateTo(e.target.value)}
                />
              </div>

              {/* Ordenação */}
              <div className="space-y-2">
                <Label>Ordenar Por</Label>
                <Select value={sortBy} onValueChange={setSortBy}>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="date-desc">Data (mais recente)</SelectItem>
                    <SelectItem value="date-asc">Data (mais antiga)</SelectItem>
                    <SelectItem value="category">Categoria</SelectItem>
                    <SelectItem value="amount-desc">Valor (maior para menor)</SelectItem>
                    <SelectItem value="amount-asc">Valor (menor para maior)</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>
          )}

          {/* Barra de Filtros Ativos */}
          {activeFilters.length > 0 && (
            <div className="pt-4">
              <ActiveFiltersBar 
                filters={activeFilters}
                onClearAll={clearFilters}
              />
            </div>
          )}

          {/* Contador de resultados */}
          <div className="flex items-center justify-between text-sm text-muted-foreground pt-2 border-t">
            <span>
              {filteredAndSortedTransactions.length} {filteredAndSortedTransactions.length === 1 ? 'transação encontrada' : 'transações encontradas'}
            </span>
            {hasActiveFilters && (
              <span className="flex items-center gap-1">
                <Filter className="h-3 w-3" />
                Filtros ativos
              </span>
            )}
          </div>
        </CardContent>
      </Card>

      {/* Lista de Transações */}
      <div className="space-y-3">
        {filteredAndSortedTransactions.length === 0 ? (
          <Card className="shadow-sm">
            <CardContent className="flex flex-col items-center justify-center py-12">
              <div className="rounded-full bg-muted p-4 mb-4">
                <Search className="h-8 w-8 text-muted-foreground" />
              </div>
              <p className="text-lg font-medium text-muted-foreground">Nenhuma transação encontrada</p>
              <p className="text-sm text-muted-foreground mt-1">
                {hasActiveFilters ? 'Tente ajustar os filtros' : 'Comece criando uma nova transação'}
              </p>
            </CardContent>
          </Card>
        ) : (
          filteredAndSortedTransactions.map((tx) => {
            const category = categories.find(c => c.id === tx.category_id);
            const account = accounts.find(a => a.id === tx.account_id);
            const destinationAccount = tx.is_transfer && tx.transfer_destination_account_id 
              ? accounts.find(a => a.id === tx.transfer_destination_account_id)
              : null;
            
            return (
              <Card key={tx.id} className="shadow-sm hover:shadow-md transition-shadow">
                <CardContent className="flex flex-col xl:flex-row items-start xl:items-center justify-between p-5 gap-4">
                  <div className="flex items-start xl:items-center gap-4 flex-1 min-w-0">
                    <div className={`p-3 rounded-full shrink-0 ${
                      tx.is_transfer 
                        ? 'bg-primary/10' 
                        : tx.type === 'income' 
                          ? 'bg-income/10' 
                          : 'bg-expense/10'
                    }`}>
                      {tx.is_transfer ? (
                        <ArrowRightLeft className="h-5 w-5 text-primary" />
                      ) : tx.type === 'income' ? (
                        <TrendingUp className="h-5 w-5 text-income" />
                      ) : (
                        <TrendingDown className="h-5 w-5 text-expense" />
                      )}
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="font-semibold text-base truncate">{tx.description || 'Sem descrição'}</p>
                      <p className="text-sm text-muted-foreground mt-1">
                        {tx.is_transfer ? (
                          <>
                            Transferência: {account?.name || 'Conta origem'} → {destinationAccount?.name || 'Conta destino'} • {formatDate(tx.date)}
                          </>
                        ) : (
                          <>
                            {category?.icon} {category?.name || 'Sem categoria'} • {account?.name || 'Sem conta'} • {formatDate(tx.date)}
                          </>
                        )}
                      </p>
                    </div>
                  </div>
                  <div className="flex items-center gap-3 xl:gap-4 w-full xl:w-auto justify-between xl:justify-end flex-wrap">
                    <div className={`text-xl font-bold ${
                      tx.is_transfer 
                        ? 'text-primary' 
                        : tx.type === 'income' 
                          ? 'text-income' 
                          : 'text-expense'
                    }`}>
                    {tx.is_transfer ? '' : tx.type === 'income' ? '+' : '-'} {formatCurrency(tx.amount)}
                  </div>
                  <div className="flex items-center gap-2">
                    {!tx.is_transfer && (
                      <Select
                        value={categorySelections[tx.id] || 'none'}
                        onValueChange={(value) => {
                          setCategorySelections(prev => ({
                            ...prev,
                            [tx.id]: value
                          }));
                        }}
                      >
                        <SelectTrigger className="w-[180px]">
                          <SelectValue placeholder="Categoria..." />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="none">Sem categoria</SelectItem>
                          {categories
                            .filter(cat => cat.type === tx.type)
                            .map(cat => (
                              <SelectItem key={cat.id} value={cat.id}>
                                {cat.icon} {cat.name}
                              </SelectItem>
                            ))}
                        </SelectContent>
                      </Select>
                    )}
                    {!tx.is_transfer && (
                      <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => handleEdit(tx)}
                        title="Editar transação"
                      >
                        <Pencil className="h-4 w-4" />
                      </Button>
                    )}
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => handleDelete(tx.id)}
                      title="Excluir transação"
                    >
                      <Trash2 className="h-4 w-4 text-destructive" />
                    </Button>
                  </div>
                </div>
              </CardContent>
            </Card>
          );
        })
        )}
      </div>
    </div>
  );
}
