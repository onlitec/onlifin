import * as React from 'react';
import { supabase } from '@/db/client';
import { accountsApi } from '@/db/api';
import { Button } from '@/components/ui/button';
// Card components removed - using list layout now
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { useToast } from '@/hooks/use-toast';
import { Plus, Pencil, Trash2, Building2, RefreshCw, TrendingUp, TrendingDown, Info } from 'lucide-react';
import { BankIconSelector } from '@/components/ui/bank-icon-selector';
import { getBankById, getDefaultBankIcon } from '@/config/banks';
import { useFinanceScope } from '@/hooks/useFinanceScope';
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
    balance: '0',
    icon: '' as string | null
  });
  const { toast } = useToast();

  const { companyId, isPJ, personId } = useFinanceScope();

  React.useEffect(() => {
    loadAccounts();
  }, [companyId, personId]);

  const loadAccounts = async () => {
    setIsLoading(true);
    try {
      const { data: { user } } = await supabase.auth.getUser();
      if (!user) return;

      // Carregar contas - filtrando pelo ID da URL (null para PF) e personId
      const data = await accountsApi.getAccounts(user.id, companyId, personId);
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

      const accountData = {
        name: formData.name,
        bank: formData.bank || null,
        agency: formData.agency || null,
        account_number: formData.account_number || null,
        currency: formData.currency,
        initial_balance: Number(formData.balance),
        icon: formData.icon || null
      };

      if (editingAccount) {
        await accountsApi.updateAccount(editingAccount.id, accountData);
        toast({ title: 'Sucesso', description: 'Conta atualizada com sucesso' });
      } else {
        await accountsApi.createAccount({
          ...accountData,
          balance: Number(formData.balance), // Also set current balance to initial on create
          user_id: user.id,
          company_id: companyId, // Associar ao ID da URL (null para PF)
          person_id: personId // Associar à pessoa selecionada (PF)
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
      balance: account.initial_balance.toString(),
      icon: account.icon || null
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
      balance: '0',
      icon: null
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
    <div className="w-full max-w-[1600px] mx-auto p-4 xl:p-8 space-y-6">
      {/* Header Section */}
      <div className="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-4 pb-2">
        <div>
          <h1 className="text-3xl xl:text-4xl font-bold tracking-tight">
            Contas {isPJ ? 'PJ' : 'PF'}
          </h1>
          <p className="text-muted-foreground mt-1">
            Gerencie suas contas {isPJ ? 'empresariais' : 'pessoais'} e acompanhe seus saldos
          </p>
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
                  <BankIconSelector
                    value={formData.icon}
                    onChange={(icon) => setFormData({ ...formData, icon })}
                    label="Selecione o Banco"
                  />
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

      {/* Lista de Contas */}
      <div className="rounded-lg border-2 border-white/40 bg-card overflow-hidden">
        {accounts.length === 0 && !isLoading ? (
          <div className="flex flex-col items-center justify-center py-10">
            <Building2 className="h-10 w-10 text-muted-foreground mb-3" />
            <p className="text-base font-medium mb-1">Nenhuma conta cadastrada</p>
            <p className="text-sm text-muted-foreground">
              Comece adicionando sua primeira conta bancária
            </p>
          </div>
        ) : (
          accounts.map((account) => (
            <div key={account.id} className="flex items-center justify-between px-4 py-3 hover:bg-muted/50 transition-colors border-b border-white/20 last:border-b-0">
              <div className="flex items-center gap-4 flex-1 min-w-0">
                {/* Ícone do Banco */}
                <div className="p-1 rounded-full bg-muted shrink-0">
                  <img
                    src={account.icon ? getBankById(account.icon)?.icon || getDefaultBankIcon() : getDefaultBankIcon()}
                    alt={account.bank || 'Banco'}
                    className="w-8 h-8 object-contain"
                  />
                </div>

                {/* Informações da Conta */}
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2">
                    <p className="font-semibold text-sm truncate">{account.name}</p>
                    {account.bank && (
                      <span className="text-xs text-muted-foreground">• {account.bank}</span>
                    )}
                  </div>
                  {account.agency && account.account_number && (
                    <p className="text-xs text-muted-foreground mt-0.5">
                      Ag: {account.agency} / Conta: {account.account_number}
                    </p>
                  )}
                </div>

                {/* Saldo */}
                <div className="text-right shrink-0">
                  <p className={`text-lg font-bold flex items-center gap-1 justify-end ${account.balance >= 0 ? 'text-green-500' : 'text-red-500'}`}>
                    {account.balance >= 0 ? <TrendingUp className="h-4 w-4" /> : <TrendingDown className="h-4 w-4" />}
                    {formatCurrency(account.balance)}
                  </p>
                </div>
              </div>

              {/* Botões de Ação */}
              <div className="flex gap-1 ml-4">
                <Button
                  variant="ghost"
                  size="icon"
                  className="h-8 w-8"
                  onClick={() => openEditDialog(account)}
                  title="Editar"
                >
                  <Pencil className="h-4 w-4" />
                </Button>
                <Button
                  variant="ghost"
                  size="icon"
                  className="h-8 w-8"
                  onClick={() => handleDelete(account.id)}
                  title="Excluir"
                >
                  <Trash2 className="h-4 w-4 text-destructive" />
                </Button>
              </div>
            </div>
          ))
        )}
      </div>
    </div>
  );
}
