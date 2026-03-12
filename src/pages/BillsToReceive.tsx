import * as React from 'react';
import { supabase } from '@/db/client';

import { billsToReceiveApi, accountsApi, categoriesApi } from '@/db/api';
import { BillTransactionService } from '@/services/billTransactionService';
import type { BillToReceive, Account, Category } from '@/types/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@/components/ui/select';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger
} from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { useToast } from '@/hooks/use-toast';
import { Plus, Calendar, DollarSign, CheckCircle, AlertCircle, Pencil, Trash2, Landmark } from 'lucide-react';
import { useFinanceScope } from '@/hooks/useFinanceScope';
import { cn } from '@/lib/utils';

export default function BillsToReceive() {
  const { toast } = useToast();
  const [userId, setUserId] = React.useState<string | null>(null);
  const [bills, setBills] = React.useState<BillToReceive[]>([]);
  const [accounts, setAccounts] = React.useState<Account[]>([]);
  const [categories, setCategories] = React.useState<Category[]>([]);
  const [loading, setLoading] = React.useState(true);
  const [isDialogOpen, setIsDialogOpen] = React.useState(false);
  const [editingBill, setEditingBill] = React.useState<BillToReceive | null>(null);

  const [formData, setFormData] = React.useState({
    description: '',
    amount: '',
    due_date: '',
    category_id: '',
    account_id: '',
    is_recurring: false,
    recurrence_pattern: '',
    notes: '',
    launch_type: 'single', // 'single', 'fixed', 'installments'
    installments_count: '1',
    frequency: 'monthly' // 'weekly', 'monthly', 'yearly'
  });

  React.useEffect(() => {
    const initUser = async () => {
      const { data: { user } } = await supabase.auth.getUser();
      if (user) {
        setUserId(user.id);
      }
    };
    initUser();
  }, []);

  const { companyId, personId, isPJ } = useFinanceScope();

  React.useEffect(() => {
    if (userId) {
      loadData();
    }
  }, [userId, companyId]);

  const loadData = async () => {
    try {
      setLoading(true);
      const [billsData, accountsData, categoriesData] = await Promise.all([
        billsToReceiveApi.getAll(userId!, companyId),
        accountsApi.getAccounts(userId!, companyId),
        categoriesApi.getCategories(companyId)
      ]);
      setBills(billsData);
      setAccounts(accountsData);
      setCategories(categoriesData.filter(c => c.type === 'income'));
    } catch (error) {
      console.error('Erro ao carregar dados:', error);
      toast({
        title: 'Erro',
        description: 'Não foi possível carregar os dados',
        variant: 'destructive'
      });
    } finally {
      setLoading(false);
    }
  };

  const resetForm = () => {
    setFormData({
      description: '',
      amount: '',
      due_date: '',
      category_id: '',
      account_id: '',
      is_recurring: false,
      recurrence_pattern: '',
      notes: '',
      launch_type: 'single',
      installments_count: '1',
      frequency: 'monthly'
    });
    setEditingBill(null);
  };

  const openEditDialog = (bill: BillToReceive) => {
    setEditingBill(bill);
    setFormData({
      description: bill.description,
      amount: bill.amount.toString(),
      due_date: bill.due_date,
      category_id: bill.category_id || '',
      account_id: bill.account_id || '',
      is_recurring: bill.is_recurring,
      recurrence_pattern: bill.recurrence_pattern || '',
      notes: bill.notes || '',
      launch_type: bill.is_installment ? 'installments' : (bill.is_recurring ? 'fixed' : 'single'),
      installments_count: bill.total_installments?.toString() || '1',
      frequency: bill.recurrence_pattern || 'monthly'
    });
    setIsDialogOpen(true);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!formData.description || !formData.amount || !formData.due_date) {
      toast({
        title: 'Erro',
        description: 'Preencha todos os campos obrigatórios',
        variant: 'destructive'
      });
      return;
    }

    if (!userId) {
      toast({
        title: 'Erro',
        description: 'Dados do usuário ausentes. Tente novamente.',
        variant: 'destructive'
      });
      return;
    }

    try {
      if (editingBill) {
        const billData = {
          user_id: userId!,
          company_id: (isPJ ? companyId : null) ?? null,
          person_id: !isPJ ? (personId || null) : null,
          description: formData.description,
          amount: Number.parseFloat(formData.amount),
          due_date: formData.due_date,
          category_id: (formData.category_id && formData.category_id !== 'none') ? formData.category_id : null,
          account_id: (formData.account_id && formData.account_id !== 'none') ? formData.account_id : null,
          is_recurring: formData.launch_type === 'fixed',
          recurrence_pattern: formData.launch_type === 'fixed' ? formData.frequency : null,
          notes: formData.notes || null,
          status: 'pending' as const
        };
        await billsToReceiveApi.update(editingBill.id, billData);
        toast({
          title: 'Sucesso',
          description: 'Receita atualizada com sucesso!'
        });
      } else if (formData.launch_type === 'installments') {
        const count = Math.max(1, parseInt(formData.installments_count) || 1);
        const bills = [];
        const baseDate = new Date(formData.due_date + 'T12:00:00');

        for (let i = 0; i < count; i++) {
          const dueDate = new Date(baseDate);
          if (formData.frequency === 'monthly') {
            dueDate.setMonth(dueDate.getMonth() + i);
          } else if (formData.frequency === 'weekly') {
            dueDate.setDate(dueDate.getDate() + (i * 7));
          } else if (formData.frequency === 'yearly') {
            dueDate.setFullYear(dueDate.getFullYear() + i);
          }

          bills.push({
            user_id: userId!,
            company_id: (isPJ ? companyId : null) ?? null,
            person_id: !isPJ ? (personId || null) : null,
            description: `${formData.description} (${i + 1}/${count})`,
            amount: Number.parseFloat(formData.amount),
            due_date: dueDate.toISOString().split('T')[0],
            category_id: (formData.category_id && formData.category_id !== 'none') ? formData.category_id : null,
            account_id: (formData.account_id && formData.account_id !== 'none') ? formData.account_id : null,
            is_recurring: false,
            recurrence_pattern: null,
            notes: formData.notes || null,
            status: 'pending' as const,
            is_installment: true,
            installment_number: i + 1,
            total_installments: count,
            received_date: null,
            transaction_id: null
          });
        }
        await billsToReceiveApi.createMany(bills);
        toast({
          title: 'Sucesso',
          description: `${count} parcelas de receita criadas com sucesso!`
        });
      } else {
        const billData = {
          user_id: userId!,
          company_id: (isPJ ? companyId : null) ?? null,
          person_id: !isPJ ? (personId || null) : null,
          description: formData.description,
          amount: Number.parseFloat(formData.amount),
          due_date: formData.due_date,
          category_id: (formData.category_id && formData.category_id !== 'none') ? formData.category_id : null,
          account_id: (formData.account_id && formData.account_id !== 'none') ? formData.account_id : null,
          is_recurring: formData.launch_type === 'fixed',
          recurrence_pattern: formData.launch_type === 'fixed' ? formData.frequency : null,
          notes: formData.notes || null,
          status: 'pending' as const,
          received_date: null,
          transaction_id: null
        };
        await billsToReceiveApi.create(billData);
        toast({
          title: 'Sucesso',
          description: 'Receita registrada com sucesso!'
        });
      }

      setIsDialogOpen(false);
      resetForm();
      loadData();
    } catch (error) {
      console.error('Erro ao salvar conta:', error);
      toast({
        title: 'Erro',
        description: 'Não foi possível salvar a conta',
        variant: 'destructive'
      });
    }
  };

  const handleMarkAsReceived = async (bill: BillToReceive) => {
    try {
      await BillTransactionService.createTransactionFromBillToReceive({
        billId: bill.id,
        userId: userId!,
      });
      toast({
        title: 'Sucesso',
        description: 'Conta marcada como recebida!'
      });
      loadData();
    } catch (error) {
      console.error('Erro ao marcar como recebida:', error);
      toast({
        title: 'Erro',
        description: 'Não foi possível marcar a conta como recebida',
        variant: 'destructive'
      });
    }
  };

  const handleDelete = async (bill: BillToReceive) => {
    if (!confirm('Tem certeza que deseja excluir esta conta?')) return;

    try {
      await billsToReceiveApi.delete(bill.id);
      toast({
        title: 'Sucesso',
        description: 'Conta excluída com sucesso!'
      });
      loadData();
    } catch (error) {
      console.error('Erro ao excluir conta:', error);
      toast({
        title: 'Erro',
        description: 'Não foi possível excluir a conta',
        variant: 'destructive'
      });
    }
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'received':
        return <Badge className="bg-income uppercase font-black text-[8px] tracking-widest border-0">Recebido</Badge>;
      case 'overdue':
        return <Badge variant="destructive" className="uppercase font-black text-[8px] tracking-widest border-0">Atrasado</Badge>;
      default:
        return <Badge variant="secondary" className="uppercase font-black text-[8px] tracking-widest border-0 opacity-50">Pendente</Badge>;
    }
  };

  const pendingBills = bills.filter(b => b.status === 'pending');
  const overdueBills = bills.filter(b => b.status === 'overdue');
  const paidBills = bills.filter(b => b.status === 'paid');

  const formatDate = (dateStr: string) => {
    if (!dateStr) return '';
    try {
      const [year, month, day] = dateStr.split('T')[0].split('-').map(Number);
      return new Date(year, month - 1, day).toLocaleDateString('pt-BR');
    } catch (e) {
      return dateStr;
    }
  };

  return (
    <div className="w-full max-w-[1600px] mx-auto p-4 lg:p-6 space-y-6 animate-slide-up bg-slate-50/30 min-h-screen">
      {/* Header */}
      <div className="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
        <div>
          <h1 className="text-xl font-black tracking-[0.05em] text-slate-900 uppercase">
            Capital de Entrada <span className="text-blue-500/70">{isPJ ? 'Empresarial' : 'Pessoal'}</span>
          </h1>
          <p className="text-[11px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">
            Monitore seus fluxos financeiros de entrada e receitas esperadas
          </p>
        </div>
        <Dialog open={isDialogOpen} onOpenChange={(open) => {
          setIsDialogOpen(open);
          if (!open) resetForm();
        }}>
          <DialogTrigger asChild>
            <Button className="bg-blue-600 hover:bg-blue-700 text-white font-black text-[10px] uppercase tracking-widest h-10 px-6 rounded-lg shadow-sm transition-all hover:scale-105 active:scale-95">
              <Plus className="mr-2 h-4 w-4" />
              Registrar Receita
            </Button>
          </DialogTrigger>
          <DialogContent className="max-w-2xl glass-card premium-card border-white/10 backdrop-blur-3xl overflow-hidden rounded-3xl p-0">
            <div className="p-8 space-y-6">
              <DialogHeader>
                <DialogTitle className="text-2xl font-black tracking-tighter uppercase">
                  {editingBill ? 'Modificar Entrada' : 'Previsão de Entrada de Ativos'}
                </DialogTitle>
                <DialogDescription className="text-xs uppercase tracking-widest font-bold opacity-60">
                  O capital esperado define a trajetória de crescimento
                </DialogDescription>
              </DialogHeader>
              <form onSubmit={handleSubmit} className="space-y-6">
                <div className="space-y-2">
                  <Label htmlFor="description" className="text-[10px] uppercase tracking-widest font-black ml-1 opacity-50">Descrição da Receita</Label>
                  <Input
                    id="description"
                    className="glass-card border-white/5 h-12 rounded-xl px-4 font-medium"
                    placeholder="Ex: Pagamento de Licença de Software..."
                    value={formData.description}
                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                    required
                  />
                </div>

                <div className="grid grid-cols-2 gap-6">
                  <div className="space-y-2">
                    <Label htmlFor="amount" className="text-[10px] uppercase tracking-widest font-black ml-1 opacity-50">Valor Esperado</Label>
                    <div className="relative">
                      <DollarSign className="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-blue-500 opacity-50" />
                      <Input
                        id="amount"
                        type="number"
                        step="0.01"
                        className="glass-card border-white/5 h-12 rounded-xl pl-10 pr-4 font-black text-lg"
                        placeholder="0.00"
                        value={formData.amount}
                        onChange={(e) => setFormData({ ...formData, amount: e.target.value })}
                        required
                      />
                    </div>
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="due_date" className="text-[10px] uppercase tracking-widest font-black ml-1 opacity-50">Data de Expectativa</Label>
                    <Input
                      id="due_date"
                      type="date"
                      className="glass-card border-white/5 h-12 rounded-xl px-4 font-bold"
                      value={formData.due_date}
                      onChange={(e) => setFormData({ ...formData, due_date: e.target.value })}
                      required
                    />
                  </div>
                </div>

                <div className="grid grid-cols-2 gap-6">
                  <div className="space-y-2">
                    <Label htmlFor="category" className="text-[10px] uppercase tracking-widest font-black ml-1 opacity-50">Fluxo de Receita</Label>
                    <Select
                      value={formData.category_id}
                      onValueChange={(value) => setFormData({ ...formData, category_id: value })}
                    >
                      <SelectTrigger className="glass-card border-white/5 h-12 rounded-xl px-4 font-bold">
                        <SelectValue placeholder="Selecionar Fluxo..." />
                      </SelectTrigger>
                      <SelectContent className="glass-card premium-card border-white/10">
                        <SelectItem value="none">Indefinido</SelectItem>
                        {categories.map((cat) => (
                          <SelectItem key={cat.id} value={cat.id}>
                            {cat.icon} {cat.name}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="account" className="text-[10px] uppercase tracking-widest font-black ml-1 opacity-50">Repositório de Destino</Label>
                    <Select
                      value={formData.account_id}
                      onValueChange={(value) => setFormData({ ...formData, account_id: value })}
                    >
                      <SelectTrigger className="glass-card border-white/5 h-12 rounded-xl px-4 font-bold">
                        <SelectValue placeholder="Selecionar Destino..." />
                      </SelectTrigger>
                      <SelectContent className="glass-card premium-card border-white/10">
                        <SelectItem value="none">Destino Indefinido</SelectItem>
                        {accounts.map((acc) => (
                          <SelectItem key={acc.id} value={acc.id}>
                            {acc.name}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                  <div className="space-y-2">
                    <Label className="text-[10px] uppercase tracking-widest font-black ml-1 opacity-50">Tipo de Lançamento</Label>
                    <Select
                      value={formData.launch_type}
                      onValueChange={(value) => setFormData({ ...formData, launch_type: value })}
                    >
                      <SelectTrigger className="glass-card border-white/5 h-12 rounded-xl px-4 font-bold">
                        <SelectValue placeholder="Tipo..." />
                      </SelectTrigger>
                      <SelectContent className="glass-card premium-card border-white/10">
                        <SelectItem value="single">Único</SelectItem>
                        <SelectItem value="fixed">Fixo (Recorrente)</SelectItem>
                        <SelectItem value="installments">Parcelado</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  {(formData.launch_type === 'fixed' || formData.launch_type === 'installments') && (
                    <div className="space-y-2 animate-in fade-in slide-in-from-left-2 transition-all">
                      <Label className="text-[10px] uppercase tracking-widest font-black ml-1 opacity-50">Frequência</Label>
                      <Select
                        value={formData.frequency}
                        onValueChange={(value) => setFormData({ ...formData, frequency: value })}
                      >
                        <SelectTrigger className="glass-card border-white/5 h-12 rounded-xl px-4 font-bold">
                          <SelectValue placeholder="Frequência..." />
                        </SelectTrigger>
                        <SelectContent className="glass-card premium-card border-white/10">
                          <SelectItem value="weekly">Semanal</SelectItem>
                          <SelectItem value="monthly">Mensal</SelectItem>
                          <SelectItem value="yearly">Anual</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                  )}

                  {formData.launch_type === 'installments' && (
                    <div className="space-y-2 animate-in fade-in slide-in-from-left-2 transition-all">
                      <Label className="text-[10px] uppercase tracking-widest font-black ml-1 opacity-50">Nº de Parcelas</Label>
                      <Input
                        type="number"
                        min="1"
                        className="glass-card border-white/5 h-12 rounded-xl px-4 font-bold"
                        value={formData.installments_count}
                        onChange={(e) => setFormData({ ...formData, installments_count: e.target.value })}
                        required
                      />
                    </div>
                  )}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="notes" className="text-[10px] uppercase tracking-widest font-black ml-1 opacity-50">Business Intelligence/Notas</Label>
                  <Textarea
                    id="notes"
                    className="glass-card border-white/5 rounded-xl px-4 py-3 font-medium min-h-[80px]"
                    placeholder="Detalhes adicionais sobre esta entrada..."
                    value={formData.notes}
                    onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                    rows={2}
                  />
                </div>

                <div className="flex justify-end gap-3 pt-4">
                  <Button type="button" variant="ghost" className="rounded-xl px-6 font-bold uppercase text-[10px] tracking-widest" onClick={() => setIsDialogOpen(false)}>
                    Cancelar
                  </Button>
                  <Button variant="outline" type="submit" className="glass border-blue-500/20 text-blue-500 font-black uppercase tracking-widest px-8 h-12 rounded-xl">
                    {editingBill ? 'Atualizar Previsão' : 'Confirmar Entrada'}
                  </Button>
                </div>
              </form>
            </div>
          </DialogContent>
        </Dialog>
      </div>
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div className="bg-white border border-slate-200 p-4 rounded-2xl relative overflow-hidden group shadow-sm">
          <div className="absolute top-0 right-0 p-3 opacity-5 group-hover:opacity-10 transition-opacity">
            <AlertCircle className="h-16 w-16 text-yellow-500 -mr-4 -mt-4" />
          </div>
          <span className="text-[9px] font-black uppercase tracking-widest text-slate-400 block mb-1">Expectativa: Pendente</span>
          <p className="text-xl font-black tracking-tight text-slate-900">
            R$ {pendingBills.reduce((sum, b) => sum + b.amount, 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
          </p>
          <div className="mt-2 text-[9px] font-bold text-slate-400 uppercase tracking-widest bg-slate-50 inline-block px-2 py-0.5 rounded">
            {pendingBills.length} Lançamentos
          </div>
        </div>

        <div className="bg-white border border-slate-200 p-4 rounded-2xl relative overflow-hidden group shadow-sm">
          <div className="absolute top-0 right-0 p-3 opacity-5 group-hover:opacity-10 transition-opacity">
            <AlertCircle className="h-16 w-16 text-red-500 -mr-4 -mt-4" />
          </div>
          <span className="text-[9px] font-black uppercase tracking-widest text-red-500 block mb-1">Risco: Em Atraso</span>
          <p className="text-xl font-black tracking-tight text-red-500">
            R$ {overdueBills.reduce((sum, b) => sum + b.amount, 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
          </p>
          <div className="mt-2 text-[9px] font-bold text-red-400 uppercase tracking-widest bg-red-50 inline-block px-2 py-0.5 rounded">
            {overdueBills.length} Pendências
          </div>
        </div>

        <div className="bg-white border border-slate-200 p-4 rounded-2xl relative overflow-hidden group shadow-sm">
          <div className="absolute top-0 right-0 p-3 opacity-5 group-hover:opacity-10 transition-opacity">
            <CheckCircle className="h-16 w-16 text-emerald-500 -mr-4 -mt-4" />
          </div>
          <span className="text-[9px] font-black uppercase tracking-widest text-emerald-600 block mb-1">Realizado: Confirmado</span>
          <p className="text-xl font-black tracking-tight text-emerald-600">
            R$ {paidBills.reduce((sum, b) => sum + b.amount, 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
          </p>
          <div className="mt-2 text-[9px] font-bold text-emerald-500 uppercase tracking-widest bg-emerald-50 inline-block px-2 py-0.5 rounded">
            {paidBills.length} Efetuados
          </div>
        </div>
      </div>

      {/* Bills List List */}
      <div className="glass-card premium-card border-slate-300 rounded-3xl overflow-hidden shadow-2xl">
        {loading ? (
          <div className="p-12 space-y-6">
            {[1, 2, 3].map((i) => (
              <div key={i} className="flex items-center gap-6 animate-pulse">
                <div className="w-14 h-14 bg-white/5 rounded-2xl" />
                <div className="flex-1 space-y-2">
                  <div className="h-4 bg-white/10 rounded w-1/3" />
                  <div className="h-2 bg-white/5 rounded w-1/4" />
                </div>
              </div>
            ))}
          </div>
        ) : bills.length === 0 ? (
          <div className="flex flex-col items-center justify-center py-24 px-4 bg-white/[0.02]">
            <div className="relative group mb-6">
              <div className="absolute inset-0 bg-blue-500/20 blur-3xl rounded-full transition-all group-hover:bg-blue-500/30" />
              <Landmark className="h-16 w-16 text-blue-500 relative z-10 opacity-40 group-hover:opacity-60 transition-all group-hover:scale-110" />
            </div>
            <p className="text-xl font-black uppercase tracking-tighter mb-2">Sem Previsão de Entrada</p>
            <p className="text-sm text-muted-foreground font-medium uppercase tracking-widest opacity-50 max-w-xs text-center">
              Registre os recebimentos futuros para gerenciar sua previsão de liquidez.
            </p>
          </div>
        ) : (
          <div className="divide-y divide-white/5">
            {bills.map((bill) => (
              <div key={bill.id} className="flex items-center justify-between p-6 hover:bg-white/[0.03] transition-all duration-300 group">
                <div className="flex items-center gap-6 flex-1 min-w-0">
                  <div className={cn(
                    "p-4 rounded-2xl transition-all shadow-lg text-blue-500 bg-blue-500/10"
                  )}>
                    <DollarSign className="h-6 w-6" />
                  </div>
                  <div className="flex-1 min-w-0 space-y-1.5">
                    <div className="flex items-center gap-3">
                      <p className="font-black text-lg tracking-tighter uppercase leading-none">{bill.description}</p>
                      {getStatusBadge(bill.status)}
                      {bill.is_recurring && !bill.is_installment && (
                        <Badge variant="outline" className="bg-purple-500/10 text-purple-500 border-purple-500/20 text-[9px] font-black uppercase tracking-tighter">Fixo</Badge>
                      )}
                      {bill.is_installment && (
                        <Badge variant="outline" className="bg-orange-500/10 text-orange-500 border-orange-500/20 text-[9px] font-black uppercase tracking-tighter">
                          Parcela {bill.installment_number}/{bill.total_installments}
                        </Badge>
                      )}
                    </div>
                    <div className="flex items-center gap-6 flex-wrap">
                      <div className="flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-muted-foreground/60">
                        <Calendar className="h-3 w-3" />
                        Esperado <span>{formatDate(bill.due_date)}</span>
                      </div>
                      {bill.account_id && (
                        <div className="flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-muted-foreground/60">
                          <Landmark className="h-3 w-3" />
                          {accounts.find(a => a.id === bill.account_id)?.name || 'Repositório'}
                        </div>
                      )}
                      <div className="flex items-center gap-2">
                        <span className="text-[10px] font-black uppercase tracking-widest opacity-30 text-muted-foreground">Entrada</span>
                        <span className="font-black text-lg tracking-tight text-income">
                          R$ {bill.amount.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
                <div className="flex gap-2 items-center opacity-0 group-hover:opacity-100 transition-all translate-x-4 group-hover:translate-x-0">
                  {bill.status === 'pending' && (
                    <Button
                      variant="ghost"
                      size="icon"
                      className="h-12 w-12 rounded-xl bg-income/10 text-income hover:bg-income/20 hover:scale-110 transition-all"
                      onClick={() => handleMarkAsReceived(bill)}
                      title="Confirmar Entrada"
                    >
                      <CheckCircle className="h-5 w-5" />
                    </Button>
                  )}
                  <Button
                    variant="ghost"
                    size="icon"
                    className="h-12 w-12 rounded-xl bg-white/5 text-muted-foreground hover:bg-white/10 hover:text-foreground hover:scale-110 transition-all"
                    onClick={() => openEditDialog(bill)}
                    title="Editar Entrada"
                  >
                    <Pencil className="h-5 w-5" />
                  </Button>
                  <Button
                    variant="ghost"
                    size="icon"
                    className="h-12 w-12 rounded-xl bg-red-500/5 text-red-500/40 hover:bg-red-500/20 hover:text-red-500 hover:scale-110 transition-all"
                    onClick={() => handleDelete(bill)}
                    title="Excluir Entrada"
                  >
                    <Trash2 className="h-5 w-5" />
                  </Button>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
