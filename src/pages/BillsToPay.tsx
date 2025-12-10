import * as React from 'react';
import { supabase } from '@/db/supabase';
import { billsToPayApi, accountsApi, categoriesApi } from '@/db/api';
import type { BillToPay, Account, Category } from '@/types/types';
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
import { Plus, Calendar, DollarSign, CheckCircle, AlertCircle, Pencil, Trash2 } from 'lucide-react';

export default function BillsToPay() {
  const { toast } = useToast();
  const [userId, setUserId] = React.useState<string | null>(null);
  const [bills, setBills] = React.useState<BillToPay[]>([]);
  const [accounts, setAccounts] = React.useState<Account[]>([]);
  const [categories, setCategories] = React.useState<Category[]>([]);
  const [loading, setLoading] = React.useState(true);
  const [isDialogOpen, setIsDialogOpen] = React.useState(false);
  const [editingBill, setEditingBill] = React.useState<BillToPay | null>(null);

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

  React.useEffect(() => {
    if (userId) {
      loadData();
    }
  }, [userId]);

  const loadData = async () => {
    try {
      setLoading(true);
      const [billsData, accountsData, categoriesData] = await Promise.all([
        billsToPayApi.getAll(userId!),
        accountsApi.getAccounts(userId!),
        categoriesApi.getCategories()
      ]);
      setBills(billsData);
      setAccounts(accountsData);
      setCategories(categoriesData.filter(c => c.type === 'expense'));
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

  const openEditDialog = (bill: BillToPay) => {
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

    try {
      const billData = {
        user_id: userId!,
        description: formData.description,
        amount: Number.parseFloat(formData.amount),
        due_date: formData.due_date,
        category_id: formData.category_id || null,
        account_id: formData.account_id || null,
        is_recurring: formData.is_recurring,
        recurrence_pattern: formData.recurrence_pattern || null,
        notes: formData.notes || null,
        status: 'pending' as const,
        paid_date: null
      };

      if (editingBill) {
        await billsToPayApi.update(editingBill.id, billData);
        toast({
          title: 'Sucesso',
          description: 'Conta atualizada com sucesso!'
        });
      } else {
        await billsToPayApi.create(billData);
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

  const handleMarkAsPaid = async (bill: BillToPay) => {
    try {
      await billsToPayApi.markAsPaid(bill.id, new Date().toISOString().split('T')[0]);
      toast({
        title: 'Sucesso',
        description: 'Conta marcada como paga!'
      });
      loadData();
    } catch (error) {
      console.error('Erro ao marcar como paga:', error);
      toast({
        title: 'Erro',
        description: 'Não foi possível marcar a conta como paga',
        variant: 'destructive'
      });
    }
  };

  const handleDelete = async (bill: BillToPay) => {
    if (!confirm('Tem certeza que deseja excluir esta conta?')) return;

    try {
      await billsToPayApi.delete(bill.id);
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
        return <Badge className="bg-income">Paga</Badge>;
      case 'overdue':
        return <Badge variant="destructive">Atrasada</Badge>;
      default:
        return <Badge variant="secondary">Pendente</Badge>;
    }
  };

  const pendingBills = bills.filter(b => b.status === 'pending');
  const overdueBills = bills.filter(b => b.status === 'overdue');
  const paidBills = bills.filter(b => b.status === 'paid');

  return (
    <div className="container mx-auto p-4 xl:p-8 space-y-6">
      {/* Header */}
      <div className="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-4 pb-2">
        <div>
          <h1 className="text-3xl xl:text-4xl font-bold tracking-tight">Contas a Pagar</h1>
          <p className="text-muted-foreground mt-1">Gerencie suas despesas e compromissos financeiros</p>
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
              <DialogTitle>{editingBill ? 'Editar Conta' : 'Nova Conta a Pagar'}</DialogTitle>
              <DialogDescription>
                Registre suas despesas e compromissos financeiros
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

      {/* Summary Cards */}
      <div className="grid gap-4 grid-cols-1 md:grid-cols-3">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Pendentes</CardTitle>
            <AlertCircle className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{pendingBills.length}</div>
            <p className="text-xs text-muted-foreground">
              R$ {pendingBills.reduce((sum, b) => sum + b.amount, 0).toFixed(2)}
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Atrasadas</CardTitle>
            <AlertCircle className="h-4 w-4 text-destructive" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-destructive">{overdueBills.length}</div>
            <p className="text-xs text-muted-foreground">
              R$ {overdueBills.reduce((sum, b) => sum + b.amount, 0).toFixed(2)}
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Pagas</CardTitle>
            <CheckCircle className="h-4 w-4 text-income" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-income">{paidBills.length}</div>
            <p className="text-xs text-muted-foreground">
              R$ {paidBills.reduce((sum, b) => sum + b.amount, 0).toFixed(2)}
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Bills List */}
      <div className="space-y-3">
        {bills.length === 0 ? (
          <Card>
            <CardContent className="flex flex-col items-center justify-center py-12">
              <DollarSign className="h-12 w-12 text-muted-foreground mb-4" />
              <p className="text-lg font-medium mb-2">Nenhuma conta cadastrada</p>
              <p className="text-sm text-muted-foreground">
                Comece adicionando suas contas a pagar
              </p>
            </CardContent>
          </Card>
        ) : (
          bills.map((bill) => (
            <Card key={bill.id} className="shadow-sm hover:shadow-md transition-shadow">
              <CardContent className="flex items-center justify-between p-5">
                <div className="flex items-center gap-4 flex-1 min-w-0">
                  <div className="p-3 rounded-full bg-expense/10">
                    <DollarSign className="h-6 w-6 text-expense" />
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 mb-1">
                      <p className="font-semibold text-base truncate">{bill.description}</p>
                      {getStatusBadge(bill.status)}
                    </div>
                    <div className="flex items-center gap-4 text-sm text-muted-foreground">
                      <span className="flex items-center gap-1">
                        <Calendar className="h-3 w-3" />
                        {new Date(bill.due_date).toLocaleDateString('pt-BR')}
                      </span>
                      <span className="font-medium text-expense">
                        R$ {bill.amount.toFixed(2)}
                      </span>
                    </div>
                  </div>
                </div>
                <div className="flex gap-2">
                  {bill.status === 'pending' && (
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => handleMarkAsPaid(bill)}
                    >
                      <CheckCircle className="h-4 w-4" />
                    </Button>
                  )}
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => openEditDialog(bill)}
                  >
                    <Pencil className="h-4 w-4" />
                  </Button>
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => handleDelete(bill)}
                  >
                    <Trash2 className="h-4 w-4" />
                  </Button>
                </div>
              </CardContent>
            </Card>
          ))
        )}
      </div>
    </div>
  );
}
