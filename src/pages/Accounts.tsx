import * as React from 'react';
import { supabase } from '@/db/client';
import { accountsApi } from '@/db/api';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { useToast } from '@/hooks/use-toast';
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip';
import { Plus, Pencil, Trash2, Building2, RefreshCw, Info, TrendingUp, TrendingDown } from 'lucide-react';
import type { Account } from '@/types/types';

export default function Accounts() {
  const [accounts, setAccounts] = React.useState<Account[]>([]);
  const [isLoading, setIsLoading] = React.useState(true);
  const [isRecalculating, setIsRecalculating] = React.useState(false);
  const [isDialogOpen, setIsDialogOpen] = React.useState(false);
  const [editingAccount, setEditingAccount] = React.useState<Account | null>(null);
  const [formData, setFormData] = React.useState({
    name: '',
    bank: '',
    agency: '',
    account_number: '',
    currency: 'BRL',
    balance: '0'
  });
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
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao carregar contas',
        variant: 'destructive'
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

      if (editingAccount) {
        await accountsApi.updateAccount(editingAccount.id, {
          ...formData,
          balance: Number(formData.balance)
        });
        toast({ title: 'Sucesso', description: 'Conta atualizada com sucesso' });
      } else {
        await accountsApi.createAccount({
          ...formData,
          user_id: user.id,
          balance: Number(formData.balance)
        });
        toast({ title: 'Sucesso', description: 'Conta criada com sucesso' });
      }

      setIsDialogOpen(false);
      resetForm();
      loadAccounts();
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao salvar conta',
        variant: 'destructive'
      });
    }
  };

  const handleDelete = async (id: string) => {
    if (!confirm('Tem certeza que deseja excluir esta conta?')) return;

    try {
      await accountsApi.deleteAccount(id);
      toast({ title: 'Sucesso', description: 'Conta excluída com sucesso' });
      loadAccounts();
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao excluir conta',
        variant: 'destructive'
      });
    }
  };

  const openEditDialog = (account: Account) => {
    setEditingAccount(account);
    setFormData({
      name: account.name,
      bank: account.bank || '',
      agency: account.agency || '',
      account_number: account.account_number || '',
      currency: account.currency,
      balance: account.balance.toString()
    });
    setIsDialogOpen(true);
  };

  const resetForm = () => {
    setEditingAccount(null);
    setFormData({
      name: '',
      bank: '',
      agency: '',
      account_number: '',
      currency: 'BRL',
      balance: '0'
    });
  };

  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL'
    }).format(value);
  };

  const handleRecalculateBalances = async () => {
    setIsRecalculating(true);
    try {
      const { data: { user } } = await supabase.auth.getUser();
      if (!user) return;

      const results = await accountsApi.recalculateAllAccountBalances(user.id);
      
      toast({
        title: 'Saldos Recalculados',
        description: `${results.length} conta(s) atualizada(s) com sucesso`,
      });

      // Reload accounts to show updated balances
      await loadAccounts();
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao recalcular saldos',
        variant: 'destructive'
      });
    } finally {
      setIsRecalculating(false);
    }
  };

  return (
    <div className="container mx-auto p-4 xl:p-8 space-y-6">
      {/* Header Section */}
      <div className="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-4 pb-2">
        <div>
          <h1 className="text-3xl xl:text-4xl font-bold tracking-tight">Contas Bancárias</h1>
          <p className="text-muted-foreground mt-1">Gerencie suas contas e acompanhe seus saldos</p>
        </div>
        <div className="flex gap-2 w-full xl:w-auto">
          <Button
            variant="outline"
            onClick={handleRecalculateBalances}
            disabled={isRecalculating}
            className="flex-1 xl:flex-none"
          >
            {isRecalculating ? (
              <>
                <RefreshCw className="mr-2 h-4 w-4 animate-spin" />
                Recalculando...
              </>
            ) : (
              <>
                <RefreshCw className="mr-2 h-4 w-4" />
                Recalcular Saldos
              </>
            )}
          </Button>
          <Dialog open={isDialogOpen} onOpenChange={(open) => {
            setIsDialogOpen(open);
            if (!open) resetForm();
          }}>
            <DialogTrigger asChild>
              <Button size="lg" className="flex-1 xl:flex-none">
                <Plus className="mr-2 h-5 w-5" />
                Nova Conta
              </Button>
            </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>{editingAccount ? 'Editar Conta' : 'Nova Conta'}</DialogTitle>
              <DialogDescription>
                Preencha os dados da conta bancária
              </DialogDescription>
            </DialogHeader>
            <form onSubmit={handleSubmit}>
              <div className="space-y-4 py-4">
                <div className="space-y-2">
                  <Label htmlFor="name">Nome da Conta *</Label>
                  <Input
                    id="name"
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="bank">Banco</Label>
                  <Input
                    id="bank"
                    value={formData.bank}
                    onChange={(e) => setFormData({ ...formData, bank: e.target.value })}
                  />
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label htmlFor="agency">Agência</Label>
                    <Input
                      id="agency"
                      value={formData.agency}
                      onChange={(e) => setFormData({ ...formData, agency: e.target.value })}
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="account_number">Conta</Label>
                    <Input
                      id="account_number"
                      value={formData.account_number}
                      onChange={(e) => setFormData({ ...formData, account_number: e.target.value })}
                    />
                  </div>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="balance">Saldo Inicial</Label>
                  <Input
                    id="balance"
                    type="number"
                    step="0.01"
                    value={formData.balance}
                    onChange={(e) => setFormData({ ...formData, balance: e.target.value })}
                    required
                  />
                  <p className="text-xs text-muted-foreground">
                    O saldo será atualizado automaticamente conforme você registra receitas e despesas
                  </p>
                </div>
              </div>
              <DialogFooter>
                <Button type="submit">{editingAccount ? 'Atualizar' : 'Criar'}</Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
        </div>
      </div>

      <Alert>
        <Info className="h-4 w-4" />
        <AlertDescription className="flex items-center gap-2">
          <span>
            Os saldos das contas são atualizados automaticamente:
          </span>
          <span className="inline-flex items-center gap-1 text-green-600 font-medium">
            <TrendingUp className="h-3 w-3" />
            Receitas aumentam
          </span>
          <span>•</span>
          <span className="inline-flex items-center gap-1 text-red-600 font-medium">
            <TrendingDown className="h-3 w-3" />
            Despesas diminuem
          </span>
        </AlertDescription>
      </Alert>

      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        {accounts.map((account) => (
          <Card key={account.id} className="shadow-sm hover:shadow-md transition-shadow">
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-3">
              <CardTitle className="text-lg font-semibold">{account.name}</CardTitle>
              <div className={`p-2 rounded-full ${account.balance >= 0 ? 'bg-income/10' : 'bg-expense/10'}`}>
                <Building2 className={`h-5 w-5 ${account.balance >= 0 ? 'text-income' : 'text-expense'}`} />
              </div>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                {account.bank && (
                  <p className="text-sm text-muted-foreground font-medium">{account.bank}</p>
                )}
                {account.agency && account.account_number && (
                  <p className="text-sm text-muted-foreground">
                    Ag: {account.agency} / Conta: {account.account_number}
                  </p>
                )}
                <div className="flex items-center gap-2 pt-2">
                  <div className="flex-1">
                    <p className="text-xs text-muted-foreground mb-1">Saldo Atual</p>
                    <p className={`text-2xl font-bold flex items-center gap-1 ${account.balance >= 0 ? 'text-income' : 'text-expense'}`}>
                      {account.balance >= 0 ? <TrendingUp className="h-5 w-5" /> : <TrendingDown className="h-5 w-5" />}
                      {formatCurrency(account.balance)}
                    </p>
                  </div>
                  <TooltipProvider>
                    <Tooltip>
                      <TooltipTrigger asChild>
                        <Info className="h-4 w-4 text-muted-foreground cursor-help" />
                      </TooltipTrigger>
                      <TooltipContent className="max-w-xs">
                        <p className="text-sm">
                          O saldo é atualizado automaticamente com suas transações:
                          <br />• Receitas aumentam o saldo
                          <br />• Despesas diminuem o saldo
                        </p>
                      </TooltipContent>
                    </Tooltip>
                  </TooltipProvider>
                </div>
                <div className="flex gap-2 pt-3 border-t">
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => openEditDialog(account)}
                    className="flex-1"
                  >
                    <Pencil className="h-4 w-4 mr-2" />
                    Editar
                  </Button>
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => handleDelete(account.id)}
                    className="flex-1"
                  >
                    <Trash2 className="h-4 w-4 mr-2" />
                    Excluir
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {accounts.length === 0 && !isLoading && (
        <Card>
          <CardContent className="flex flex-col items-center justify-center py-12">
            <Building2 className="h-12 w-12 text-muted-foreground mb-4" />
            <p className="text-lg font-medium mb-2">Nenhuma conta cadastrada</p>
            <p className="text-sm text-muted-foreground mb-4">
              Comece adicionando sua primeira conta bancária
            </p>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
