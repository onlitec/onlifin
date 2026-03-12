import * as React from 'react';
import { supabase } from '@/db/client';
import { transactionsApi, accountsApi, categoriesApi } from '@/db/api';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent } from '@/components/ui/dialog';
import { useToast } from '@/hooks/use-toast';
import { useAuth } from 'miaoda-auth-react';
import {
  TrendingUp,
  TrendingDown,
  ArrowRightLeft,
  Pencil,
  Trash2,
  Plus,
  Search,
  History,
  Camera,
  DollarSign
} from 'lucide-react';
import type { Transaction, Account, Category } from '@/types/types';
import { cn } from '@/lib/utils';
import { useFinanceScope } from '@/hooks/useFinanceScope';
import ReceiptScanner from '@/components/transactions/ReceiptScanner';
import { useSearchParams } from 'react-router-dom';

export default function Transactions() {
  const { toast } = useToast();
  const { companyId, personId } = useFinanceScope();
  const { user } = useAuth();
  const [searchParams] = useSearchParams();
  const [transactions, setTransactions] = React.useState<Transaction[]>([]);
  const [accounts, setAccounts] = React.useState<Account[]>([]);
  const [categories, setCategories] = React.useState<Category[]>([]);
  const [isLoading, setIsLoading] = React.useState(true);
  const [isFormOpen, setIsFormOpen] = React.useState(false);
  const [editingId, setEditingId] = React.useState<string | null>(null);
  const [showReceiptScanner, setShowReceiptScanner] = React.useState(false);

  // Filtros
  const [filterType, setFilterType] = React.useState<string>('all');
  const [filterAccountId, setFilterAccountId] = React.useState<string>(searchParams.get('account_id') || 'all');
  const [searchTerm, setSearchTerm] = React.useState('');
  const [filterDateStart, setFilterDateStart] = React.useState('');
  const [filterDateEnd, setFilterDateEnd] = React.useState('');

  // Form State
  const [formData, setFormData] = React.useState({
    description: '',
    amount: '',
    type: 'expense' as 'income' | 'expense' | 'transfer',
    date: new Date().toISOString().split('T')[0],
    account_id: '',
    destination_account_id: '',
    category_id: '',
    is_recurring: false,
    recurrence_period: 'monthly' as 'daily' | 'weekly' | 'monthly' | 'yearly',
    is_installment: false,
    total_installments: 1,
  });

  React.useEffect(() => {
    loadData();
  }, [filterType, filterAccountId, filterDateStart, filterDateEnd, companyId, personId, user]);

  const loadData = async () => {
    try {
      setIsLoading(true);
      if (!user) return;

      const [txList, accList, catList] = await Promise.all([
        transactionsApi.getTransactions(user.id, {
          type: filterType === 'all' ? undefined : (filterType as any),
          startDate: filterDateStart || undefined,
          endDate: filterDateEnd || undefined,
          companyId,
          personId,
          accountId: filterAccountId === 'all' ? undefined : filterAccountId
        }),
        accountsApi.getAccounts(user.id, companyId, personId),
        categoriesApi.getCategories(companyId)
      ]);

      setTransactions(txList);
      setAccounts(accList);
      setCategories(catList);
    } catch (err) {
      console.error(err);
      toast({
        title: 'Erro',
        description: 'Falha ao carregar dados',
        variant: 'destructive',
      });
    } finally {
      setIsLoading(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const { data: { user } } = await supabase.auth.getUser();
      if (!user) return;

      const payload: Omit<Transaction, 'id' | 'created_at' | 'updated_at'> = {
        description: formData.description,
        amount: parseFloat(formData.amount),
        type: formData.type,
        date: formData.date,
        account_id: formData.account_id || null,
        category_id: formData.category_id || null,
        user_id: user.id,
        company_id: companyId ?? null,
        person_id: personId || null,
        card_id: null,
        tags: null,
        is_recurring: formData.is_recurring,
        recurrence_pattern: formData.recurrence_period,
        is_installment: formData.is_installment,
        installment_number: 1,
        total_installments: formData.total_installments,
        parent_transaction_id: null,
        is_reconciled: false,
        is_transfer: formData.type === 'transfer',
        transfer_destination_account_id: formData.destination_account_id || null,
      };

      if (editingId) {
        await transactionsApi.updateTransaction(editingId, payload);
        toast({ title: 'Sucesso', description: 'Transação atualizada' });
      } else {
        await transactionsApi.createTransaction(payload);
        toast({ title: 'Sucesso', description: 'Transação criada' });
      }

      setIsFormOpen(false);
      setEditingId(null);
      resetForm();
      loadData();
    } catch (err) {
      console.error(err);
      toast({ title: 'Erro', description: 'Falha ao salvar transação', variant: 'destructive' });
    }
  };

  const handleEdit = (tx: Transaction) => {
    setEditingId(tx.id);
    setFormData({
      description: tx.description || '',
      amount: tx.amount.toString(),
      type: tx.type,
      date: tx.date,
      account_id: tx.account_id || '',
      destination_account_id: tx.transfer_destination_account_id || '',
      category_id: tx.category_id || '',
      is_recurring: tx.is_recurring || false,
      recurrence_period: (tx.recurrence_pattern as any) || 'monthly',
      is_installment: tx.is_installment || false,
      total_installments: tx.total_installments || 1,
    });
    setIsFormOpen(true);
  };

  const handleDelete = async (id: string) => {
    if (!confirm('Deseja excluir esta transação?')) return;
    try {
      await transactionsApi.deleteTransaction(id);
      toast({ title: 'Sucesso', description: 'Transação excluída' });
      loadData();
    } catch (err) {
      toast({ title: 'Erro', description: 'Falha ao excluir', variant: 'destructive' });
    }
  };

  const resetForm = () => {
    setFormData({
      description: '',
      amount: '',
      type: 'expense',
      date: new Date().toISOString().split('T')[0],
      account_id: '',
      destination_account_id: '',
      category_id: '',
      is_recurring: false,
      recurrence_period: 'monthly',
      is_installment: false,
      total_installments: 1,
    });
  };

  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
  };

  const formatDate = (dateStr: string) => {
    const [year, month, day] = dateStr.split('T')[0].split('-').map(Number);
    return new Date(year, month - 1, day).toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', year: 'numeric' });
  };

  const filteredTransactions = transactions.filter(tx =>
    (tx.description || '').toLowerCase().includes(searchTerm.toLowerCase())
  );

  return (
    <div className="w-full max-w-[1600px] mx-auto p-4 lg:p-6 space-y-6 animate-slide-up bg-slate-50/30 min-h-screen">
      {/* Page Header */}
      <header className="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
        <div>
          <h1 className="text-xl font-black tracking-[0.05em] text-slate-900 uppercase">
            Transações
          </h1>
          <p className="text-[11px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">
            Histórico financeiro completo e gestão de fluxo
          </p>
        </div>
        <div className="flex gap-2 w-full lg:w-auto">
          <Button
            variant="outline"
            className="bg-white border-slate-200 text-slate-600 font-bold text-[10px] uppercase tracking-widest h-9 px-4 rounded-lg shadow-sm transition-all"
            onClick={() => setShowReceiptScanner(true)}
          >
            <Camera className="mr-2 h-4 w-4" />
            Escanear
          </Button>
          <Button
            className="bg-blue-600 hover:bg-blue-700 text-white font-black text-[10px] uppercase tracking-widest h-10 px-6 rounded-lg shadow-sm transition-all hover:scale-105 active:scale-95"
            onClick={() => { resetForm(); setEditingId(null); setIsFormOpen(true); }}
          >
            <Plus className="mr-2 h-4 w-4" />
            Nova Transação
          </Button>
        </div>
      </header>

      {/* Filters Section */}
      <section className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
        <div className="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-6 gap-6">
          <div className="md:col-span-2 lg:col-span-2 space-y-2">
            <Label className="text-[10px] font-bold text-slate-400 uppercase tracking-wider ml-1">Descrição</Label>
            <div className="relative">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
              <Input
                placeholder="Buscar..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="pl-10 h-11 rounded-xl border-slate-200 bg-slate-50 focus-visible:ring-1 focus-visible:ring-blue-500"
              />
            </div>
          </div>

          <div className="space-y-2">
            <Label className="text-[10px] font-bold text-slate-400 uppercase tracking-wider ml-1">Tipo</Label>
            <Select value={filterType} onValueChange={setFilterType}>
              <SelectTrigger className="h-11 rounded-xl bg-slate-50 border-slate-200">
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

          <div className="space-y-2">
            <Label className="text-[10px] font-bold text-slate-400 uppercase tracking-wider ml-1">Conta</Label>
            <Select value={filterAccountId} onValueChange={setFilterAccountId}>
              <SelectTrigger className="h-11 rounded-xl bg-slate-50 border-slate-200 font-bold">
                <SelectValue placeholder="Todas Contas" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Todas Contas</SelectItem>
                {accounts.map(acc => (
                  <SelectItem key={acc.id} value={acc.id}>{acc.name}</SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div className="space-y-2">
            <Label className="text-[10px] font-bold text-slate-400 uppercase tracking-wider ml-1">Início</Label>
            <Input
              type="date"
              value={filterDateStart}
              onChange={(e) => setFilterDateStart(e.target.value)}
              className="h-11 rounded-xl bg-slate-50 border-slate-200"
            />
          </div>

          <div className="space-y-2">
            <Label className="text-[10px] font-bold text-slate-400 uppercase tracking-wider ml-1">Fim</Label>
            <Input
              type="date"
              value={filterDateEnd}
              onChange={(e) => setFilterDateEnd(e.target.value)}
              className="h-11 rounded-xl bg-slate-50 border-slate-200"
            />
          </div>

          <div className="flex items-end">
            <Button
              variant="ghost"
              className="text-slate-400 hover:text-slate-900 w-full rounded-xl font-bold h-11"
              onClick={() => {
                setFilterType('all');
                setFilterAccountId('all');
                setSearchTerm('');
                setFilterDateStart('');
                setFilterDateEnd('');
              }}
            >
              <History className="mr-2 h-4 w-4" />
              Limpar
            </Button>
          </div>
        </div>
      </section>

      {/* Transactions List */}
      <section className="bg-white border-2 border-slate-300 rounded-3xl shadow-lg overflow-hidden">
        {isLoading ? (
          <div className="p-12 text-center space-y-4">
            <div className="h-12 w-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mx-auto" />
            <p className="font-bold text-slate-400 animate-pulse">Sincronizando dados...</p>
          </div>
        ) : filteredTransactions.length === 0 ? (
          <div className="p-24 text-center space-y-6">
            <div className="bg-slate-50 h-24 w-24 rounded-full flex items-center justify-center mx-auto text-slate-300">
              <History className="h-12 w-12" />
            </div>
            <div className="space-y-2">
              <h3 className="text-xl font-bold text-slate-900 tracking-tight">Vazio por aqui</h3>
              <p className="text-slate-400 font-medium">Nenhuma transação encontrada para este período.</p>
            </div>
          </div>
        ) : (
          <div className="space-y-4">
            {filteredTransactions.map((tx) => {
              const account = accounts.find(a => a.id === tx.account_id);
              const category = categories.find(c => c.id === tx.category_id);

              return (
                <div
                  key={tx.id}
                  className={cn(
                    "group flex items-center justify-between p-6 bg-white hover:bg-slate-50/50 transition-all animate-in fade-in duration-500 rounded-3xl border-2 shadow-sm relative overflow-hidden",
                    tx.type === 'income' ? "border-emerald-100 hover:border-emerald-200" :
                      tx.type === 'expense' ? "border-red-100 hover:border-red-200" :
                        "border-blue-100 hover:border-blue-200"
                  )}
                >
                  {/* Accent Bar */}
                  <div className={cn(
                    "absolute left-0 top-0 bottom-0 w-1.5",
                    tx.type === 'income' ? "bg-emerald-500" :
                      tx.type === 'expense' ? "bg-red-500" : "bg-blue-500"
                  )} />

                  <div className="flex items-start gap-6 flex-1 min-w-0 pl-2">
                    <div className={cn(
                      "p-3.5 rounded-2xl shrink-0 transition-transform group-hover:scale-105 mt-1",
                      tx.type === 'income' ? "bg-emerald-50 text-emerald-600 shadow-[0_0_15px_-5px_#10b98133]" :
                        tx.type === 'expense' ? "bg-red-50 text-red-600 shadow-[0_0_15px_-5px_#ef444433]" :
                          "bg-blue-50 text-blue-600 shadow-[0_0_15px_-5px_#3b82f633]"
                    )}>
                      {tx.type === 'income' ? <TrendingUp className="h-6 w-6" /> :
                        tx.type === 'expense' ? <TrendingDown className="h-6 w-6" /> :
                          <ArrowRightLeft className="h-6 w-6" />}
                    </div>

                    <div className="flex-1 min-w-0">
                      <div className="flex flex-wrap items-center gap-x-3 gap-y-2 mb-2">
                        <p className="font-bold text-slate-900 text-lg tracking-tight leading-snug break-words">
                          {tx.description}
                        </p>
                        <div className="flex gap-2 shrink-0">
                          {tx.is_recurring && (
                            <span className="text-[10px] font-bold uppercase text-blue-600 bg-blue-50 px-2 py-0.5 rounded-md">Recorrente</span>
                          )}
                          {tx.is_installment && (
                            <span className="text-[10px] font-bold uppercase text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md">
                              {tx.installment_number}/{tx.total_installments} Parc
                            </span>
                          )}
                        </div>
                      </div>
                      <div className="flex flex-wrap items-center gap-x-4 gap-y-1">
                        <span className="text-[11px] font-semibold text-slate-400">{formatDate(tx.date)}</span>
                        <span className="h-3 w-[1px] bg-slate-200 hidden sm:block" />
                        <span className="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">{account?.name || 'Carteira'}</span>
                        {category && (
                          <>
                            <span className="h-3 w-[1px] bg-slate-200 hidden sm:block" />
                            <span className="text-[11px] font-semibold text-slate-400 capitalize">{category.icon} {category.name}</span>
                          </>
                        )}
                      </div>
                    </div>

                    <div className="text-right px-4 sm:px-8 min-w-[140px] mt-1">
                      <p className={cn(
                        "text-xl sm:text-2xl font-bold tracking-tight",
                        tx.type === 'income' ? 'text-emerald-600' : tx.type === 'expense' ? 'text-red-600' : 'text-slate-600'
                      )}>
                        {tx.type === 'expense' ? '-' : tx.type === 'income' ? '+' : ''}
                        {formatCurrency(tx.amount)}
                      </p>
                    </div>
                  </div>

                  <div className="flex gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                    <Button
                      variant="ghost"
                      size="icon"
                      className="h-10 w-10 rounded-xl text-slate-400 hover:text-blue-600 hover:bg-blue-50"
                      onClick={() => handleEdit(tx)}
                    >
                      <Pencil className="h-4 w-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      className="h-10 w-10 rounded-xl text-slate-300 hover:text-red-500 hover:bg-red-50"
                      onClick={() => handleDelete(tx.id)}
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </section>

      {/* Add/Edit Transaction Dialog */}
      <Dialog open={isFormOpen} onOpenChange={(open) => {
        setIsFormOpen(open);
        if (!open) {
          setEditingId(null);
          resetForm();
        }
      }}>
        <DialogContent className="max-w-2xl glass-card premium-card border-slate-300 backdrop-blur-3xl overflow-hidden rounded-3xl p-0 shadow-2xl">
          <div className="p-10 space-y-8">
            <div className="space-y-2">
              <h2 className="text-2xl font-black tracking-tighter uppercase text-slate-900">
                {editingId ? 'Editar Transação' : 'Nova Movimentação'}
              </h2>
              <p className="text-xs uppercase tracking-widest font-bold text-slate-500">
                Gestão analítica de fluxo de capital
              </p>
            </div>

            <form onSubmit={handleSubmit} className="space-y-6">
              <div className="space-y-4">
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label className="text-[10px] uppercase tracking-widest font-black text-slate-500 ml-1">Tipo de Fluxo</Label>
                    <Select
                      value={formData.type}
                      onValueChange={(val: any) => setFormData({ ...formData, type: val })}
                    >
                      <SelectTrigger className="h-12 rounded-xl bg-slate-50 border-slate-200 font-bold">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent className="rounded-xl border-slate-200">
                        <SelectItem value="income" className="text-emerald-600 font-bold">Receita</SelectItem>
                        <SelectItem value="expense" className="text-red-600 font-bold">Despesa</SelectItem>
                        <SelectItem value="transfer" className="text-blue-600 font-bold">Transferência</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  <div className="space-y-2">
                    <Label className="text-[10px] uppercase tracking-widest font-black text-slate-500 ml-1">Data</Label>
                    <Input
                      type="date"
                      value={formData.date}
                      onChange={(e) => setFormData({ ...formData, date: e.target.value })}
                      className="h-12 rounded-xl bg-slate-50 border-slate-200 font-bold"
                      required
                    />
                  </div>
                </div>

                <div className="space-y-2">
                  <Label className="text-[10px] uppercase tracking-widest font-black text-slate-500 ml-1">Descrição</Label>
                  <Input
                    placeholder="Ex: Assinatura Mensal, Venda de Produto..."
                    value={formData.description}
                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                    className="h-12 rounded-xl bg-slate-50 border-slate-200 font-medium"
                    required
                  />
                </div>

                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label className="text-[10px] uppercase tracking-widest font-black text-slate-500 ml-1">Valor (BRL)</Label>
                    <div className="relative">
                      <DollarSign className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                      <Input
                        type="number"
                        step="0.01"
                        placeholder="0,00"
                        value={formData.amount}
                        onChange={(e) => setFormData({ ...formData, amount: e.target.value })}
                        className="pl-10 h-12 rounded-xl bg-slate-50 border-slate-200 font-black text-lg"
                        required
                      />
                    </div>
                  </div>

                  <div className="space-y-2">
                    <Label className="text-[10px] uppercase tracking-widest font-black text-slate-500 ml-1">Conta de Origem</Label>
                    <Select
                      value={formData.account_id}
                      onValueChange={(val) => setFormData({ ...formData, account_id: val })}
                    >
                      <SelectTrigger className="h-12 rounded-xl bg-slate-50 border-slate-200 font-bold">
                        <SelectValue placeholder="Selecione..." />
                      </SelectTrigger>
                      <SelectContent className="rounded-xl border-slate-200">
                        {accounts.map(acc => (
                          <SelectItem key={acc.id} value={acc.id}>{acc.name}</SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>
                </div>

                {formData.type === 'transfer' ? (
                  <div className="space-y-2">
                    <Label className="text-[10px] uppercase tracking-widest font-black text-slate-500 ml-1">Conta de Destino</Label>
                    <Select
                      value={formData.destination_account_id}
                      onValueChange={(val) => setFormData({ ...formData, destination_account_id: val })}
                    >
                      <SelectTrigger className="h-12 rounded-xl bg-blue-50 border-blue-200 font-bold text-blue-700">
                        <SelectValue placeholder="Selecione destino..." />
                      </SelectTrigger>
                      <SelectContent className="rounded-xl border-slate-200">
                        {accounts.map(acc => (
                          <SelectItem key={acc.id} value={acc.id}>{acc.name}</SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>
                ) : (
                  <div className="space-y-2">
                    <Label className="text-[10px] uppercase tracking-widest font-black text-slate-500 ml-1">Categoria</Label>
                    <Select
                      value={formData.category_id}
                      onValueChange={(val) => setFormData({ ...formData, category_id: val })}
                    >
                      <SelectTrigger className="h-12 rounded-xl bg-slate-50 border-slate-200 font-bold">
                        <SelectValue placeholder="Selecione categoria..." />
                      </SelectTrigger>
                      <SelectContent className="rounded-xl border-slate-200">
                        {categories.map(cat => (
                          <SelectItem key={cat.id} value={cat.id}>{cat.icon} {cat.name}</SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>
                )}
              </div>

              <div className="flex justify-end gap-3 pt-6">
                <Button
                  type="button"
                  variant="ghost"
                  className="rounded-xl px-6 font-bold text-slate-500 hover:text-slate-900"
                  onClick={() => setIsFormOpen(false)}
                >
                  Cancelar
                </Button>
                <Button
                  type="submit"
                  className="bg-blue-600 hover:bg-blue-700 text-white font-bold px-8 h-12 rounded-xl shadow-lg transition-all active:scale-95"
                >
                  {editingId ? 'Salvar Alterações' : 'Confirmar Registro'}
                </Button>
              </div>
            </form>
          </div>
        </DialogContent>
      </Dialog>

      <Dialog open={showReceiptScanner} onOpenChange={setShowReceiptScanner}>
        <DialogContent className="max-w-2xl glass-card premium-card border-slate-300 rounded-3xl p-0 overflow-hidden shadow-2xl">
          <ReceiptScanner
            onDataExtracted={(data) => {
              setFormData(prev => ({
                ...prev,
                description: data.storeName || prev.description,
                amount: data.totalAmount?.toString() || prev.amount,
                date: data.date || prev.date,
              }));
              setShowReceiptScanner(false);
              setIsFormOpen(true);
            }}
            onClose={() => setShowReceiptScanner(false)}
          />
        </DialogContent>
      </Dialog>
    </div>
  );
}
