import * as React from 'react';
import { supabase } from '@/db/client';

import { billsToReceiveApi, accountsApi, categoriesApi } from '@/db/api';
import type { BillToReceive, Account, Category } from '@/types/types';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
    notes: ''
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

  const { companyId, isPJ } = useFinanceScope();

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
      notes: ''
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
      notes: bill.notes || ''
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

    if (!userId || !companyId) { // Ensure userId and companyId are available
      toast({
        title: 'Erro',
        description: 'Dados do usuário ou empresa ausentes. Tente novamente.',
        variant: 'destructive'
      });
      return;
    }

    try {
      const billData = {
        user_id: userId!,
        company_id: companyId, // Associar ao ID da URL
        description: formData.description,
        amount: Number.parseFloat(formData.amount),
        due_date: formData.due_date,
        category_id: (formData.category_id && formData.category_id !== 'none') ? formData.category_id : null,
        account_id: (formData.account_id && formData.account_id !== 'none') ? formData.account_id : null,
        is_recurring: formData.is_recurring,
        recurrence_pattern: formData.recurrence_pattern || null,
        notes: formData.notes || null,
        status: 'pending' as const,
        received_date: null,
        transaction_id: null
      };

      if (editingBill) {
        await billsToReceiveApi.update(editingBill.id, billData);
        toast({
          title: 'Sucesso',
          description: 'Conta atualizada com sucesso!'
        });
      } else {
        await billsToReceiveApi.create(billData);
        toast({
          title: 'Sucesso',
          description: 'Conta criada com sucesso!'
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

  const handleMarkAsPaid = async (bill: BillToReceive) => {
    try {
      await billsToReceiveApi.markAsReceived(bill.id, new Date().toISOString().split('T')[0]);
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
      case 'paid':
        return <Badge className="bg-income">Recebida</Badge>;
      case 'overdue':
        return <Badge variant="destructive">Atrasada</Badge>;
      default:
        return <Badge variant="secondary">Pendente</Badge>;
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
    <div className="w-full space-y-6">
      {/* Header - Full Width */}
      <div className="w-full bg-card border-b px-4 xl:px-8 py-4">
        <div className="max-w-[1600px] mx-auto flex flex-col xl:flex-row justify-between items-start xl:items-center gap-4">
          <div>
            <h1 className="text-3xl xl:text-4xl font-bold tracking-tight">
              Contas a Receber {isPJ ? 'PJ' : 'PF'}
            </h1>
            <p className="text-muted-foreground mt-1">Gerencie suas receitas {isPJ ? 'empresariais' : 'pessoais'} e entradas financeiras</p>
          </div>
          <Dialog open={isDialogOpen} onOpenChange={(open) => {
            setIsDialogOpen(open);
            if (!open) resetForm();
          }}>
            <DialogTrigger asChild>
              <Button size="lg" className="w-full xl:w-auto">
                <Plus className="mr-2 h-5 w-5" />
                Nova Conta
              </Button>
            </DialogTrigger>
            <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
              <DialogHeader>
                <DialogTitle>{editingBill ? 'Editar Conta' : 'Nova Conta a Receber'}</DialogTitle>
                <DialogDescription>
                  Registre suas receitas e valores a receber financeiros
                </DialogDescription>
              </DialogHeader>
              <form onSubmit={handleSubmit} className="space-y-4">
                <div className="space-y-2">
                  <Label htmlFor="description">Descrição *</Label>
                  <Input
                    id="description"
                    placeholder="Ex: Aluguel, Conta de luz..."
                    value={formData.description}
                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                    required
                  />
                </div>

                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label htmlFor="amount">Valor *</Label>
                    <Input
                      id="amount"
                      type="number"
                      step="0.01"
                      placeholder="0.00"
                      value={formData.amount}
                      onChange={(e) => setFormData({ ...formData, amount: e.target.value })}
                      required
                    />
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="due_date">Data de Vencimento *</Label>
                    <Input
                      id="due_date"
                      type="date"
                      value={formData.due_date}
                      onChange={(e) => setFormData({ ...formData, due_date: e.target.value })}
                      required
                    />
                  </div>
                </div>

                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label htmlFor="category">Categoria</Label>
                    <Select
                      value={formData.category_id}
                      onValueChange={(value) => setFormData({ ...formData, category_id: value })}
                    >
                      <SelectTrigger>
                        <SelectValue placeholder="Selecione..." />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="none">Sem categoria</SelectItem>
                        {categories.map((cat) => (
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
                        <SelectValue placeholder="Selecione..." />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="none">Sem conta</SelectItem>
                        {accounts.map((acc) => (
                          <SelectItem key={acc.id} value={acc.id}>
                            {acc.name}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="notes">Observações</Label>
                  <Textarea
                    id="notes"
                    placeholder="Informações adicionais..."
                    value={formData.notes}
                    onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                    rows={3}
                  />
                </div>

                <div className="flex justify-end gap-3 pt-4">
                  <Button type="button" variant="outline" onClick={() => setIsDialogOpen(false)}>
                    Cancelar
                  </Button>
                  <Button type="submit">
                    {editingBill ? 'Atualizar' : 'Criar'} Conta
                  </Button>
                </div>
              </form>
            </DialogContent>
          </Dialog>
        </div>
      </div>

      {/* Content Area */}
      <div className="max-w-[1600px] mx-auto px-4 xl:px-8 space-y-6">

        {/* Summary Cards */}
        <div className="grid gap-4 grid-cols-1 md:grid-cols-3">
          <Card className="border-2 border-yellow-500">
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium">Pendentes</CardTitle>
              <AlertCircle className="h-4 w-4 text-yellow-500" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-yellow-500">{pendingBills.length}</div>
              <p className="text-xs text-muted-foreground">
                R$ {pendingBills.reduce((sum, b) => sum + b.amount, 0).toFixed(2)}
              </p>
            </CardContent>
          </Card>

          <Card className="border-2 border-red-500">
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium">Atrasadas</CardTitle>
              <AlertCircle className="h-4 w-4 text-red-500" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-red-500">{overdueBills.length}</div>
              <p className="text-xs text-muted-foreground">
                R$ {overdueBills.reduce((sum, b) => sum + b.amount, 0).toFixed(2)}
              </p>
            </CardContent>
          </Card>

          <Card className="border-2 border-green-500">
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium">Recebidas</CardTitle>
              <CheckCircle className="h-4 w-4 text-green-500" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-green-500">{paidBills.length}</div>
              <p className="text-xs text-muted-foreground">
                R$ {paidBills.reduce((sum, b) => sum + b.amount, 0).toFixed(2)}
              </p>
            </CardContent>
          </Card>
        </div>

        {/* Bills List */}
        <div className="rounded-lg border-2 border-white/40 bg-card overflow-hidden">
          {bills.length === 0 ? (
            <div className="flex flex-col items-center justify-center py-10">
              <DollarSign className="h-10 w-10 text-muted-foreground mb-3" />
              <p className="text-base font-medium mb-1">Nenhuma conta cadastrada</p>
              <p className="text-sm text-muted-foreground">
                Comece adicionando suas contas a receber
              </p>
            </div>
          ) : (
            bills.map((bill) => (
              <div key={bill.id} className="flex items-center justify-between px-3 py-2.5 hover:bg-muted/50 transition-colors border-b border-white/20 last:border-b-0">
                <div className="flex items-center gap-3 flex-1 min-w-0">
                  <div className="p-1.5 rounded-full bg-income/10">
                    <DollarSign className="h-4 w-4 text-income" />
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2">
                      <p className="font-medium text-sm truncate">{bill.description}</p>
                      {getStatusBadge(bill.status)}
                    </div>
                    <div className="flex items-center gap-3 text-xs text-muted-foreground mt-0.5">
                      <span className="flex items-center gap-1">
                        <Calendar className="h-3 w-3" />
                        {formatDate(bill.due_date)}
                      </span>
                      {bill.account_id && (
                        <span className="flex items-center gap-1">
                          <Landmark className="h-3 w-3" />
                          {accounts.find(a => a.id === bill.account_id)?.name || 'Conta'}
                        </span>
                      )}
                      <span className="font-medium text-income">
                        R$ {bill.amount.toFixed(2)}
                      </span>
                    </div>
                  </div>
                </div>
                <div className="flex gap-1">
                  {bill.status === 'pending' && (
                    <Button
                      variant="ghost"
                      size="icon"
                      className="h-7 w-7"
                      onClick={() => handleMarkAsPaid(bill)}
                      title="Marcar como recebida"
                    >
                      <CheckCircle className="h-3.5 w-3.5" />
                    </Button>
                  )}
                  <Button
                    variant="ghost"
                    size="icon"
                    className="h-7 w-7"
                    onClick={() => openEditDialog(bill)}
                    title="Editar"
                  >
                    <Pencil className="h-3.5 w-3.5" />
                  </Button>
                  <Button
                    variant="ghost"
                    size="icon"
                    className="h-7 w-7"
                    onClick={() => handleDelete(bill)}
                    title="Excluir"
                  >
                    <Trash2 className="h-3.5 w-3.5 text-destructive" />
                  </Button>
                </div>
              </div>
            ))
          )}
        </div>
      </div>
    </div>
  );
}
