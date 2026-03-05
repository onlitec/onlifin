import * as React from 'react';
import { supabase } from '@/db/client';
import { accountsApi } from '@/db/api';
import { Button } from '@/components/ui/button';
// Card components removed - using list layout now
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { useToast } from '@/hooks/use-toast';
import { Plus, Pencil, Trash2, Building2, RefreshCw, TrendingUp, TrendingDown, Info, DollarSign } from 'lucide-react';
import { cn } from '@/lib/utils';
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
          person_id: personId ?? null // Associar à pessoa selecionada (PF)
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
    <div className="w-full max-w-[1600px] mx-auto p-4 xl:p-8 space-y-8 animate-in fade-in duration-700">
      {/* Header Section */}
      <div className="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-6 pb-2">
        <div className="space-y-1">
          <h1 className="text-3xl xl:text-5xl font-black tracking-tighter uppercase">
            Contas <span className="text-primary/50">{isPJ ? 'Corporativas' : 'Pessoais'}</span>
          </h1>
          <p className="text-muted-foreground font-medium uppercase text-xs tracking-[0.2em] opacity-70">
            Gestão estratégica de ativos e controle de liquidez
          </p>
        </div>
        <div className="flex flex-col sm:flex-row gap-3 w-full xl:w-auto">
          <Button
            variant="ghost"
            onClick={handleRecalculateBalances}
            disabled={isRecalculating}
            className="glass border-white/5 text-[10px] uppercase font-black tracking-widest px-6 h-12 rounded-xl group transition-all hover:bg-white/5"
          >
            {isRecalculating ? (
              <>
                <RefreshCw className="mr-2 h-4 w-4 animate-spin text-primary" />
                Recalculando...
              </>
            ) : (
              <>
                <RefreshCw className="mr-2 h-4 w-4 text-primary opacity-50 group-hover:opacity-100 transition-opacity" />
                Recalcular Ativos
              </>
            )}
          </Button>
          <Dialog open={isDialogOpen} onOpenChange={(open) => {
            setIsDialogOpen(open);
            if (!open) resetForm();
          }}>
            <DialogTrigger asChild>
              <Button variant="outline" size="lg" className="glass border-primary/20 hover:bg-primary/20 text-primary font-black uppercase tracking-widest px-8 h-14 rounded-2xl shadow-xl shadow-primary/10 transition-all hover:scale-105 active:scale-95">
                <Plus className="mr-2 h-5 w-5" />
                Inicializar Conta
              </Button>
            </DialogTrigger>
            <DialogContent className="glass-card premium-card border-white/10 backdrop-blur-3xl rounded-3xl p-0 overflow-hidden">
              <div className="p-8 space-y-6">
                <DialogHeader>
                  <DialogTitle className="text-2xl font-black tracking-tighter uppercase leading-tight">
                    {editingAccount ? 'Modificar Conta' : 'Nova Fonte de Ativos'}
                  </DialogTitle>
                  <DialogDescription className="text-[10px] uppercase tracking-widest font-bold opacity-60">
                    Estabelecendo um portal financeiro estruturado
                  </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleSubmit} className="space-y-6">
                  <div className="space-y-4">
                    <div className="space-y-2">
                      <Label htmlFor="name" className="text-[10px] uppercase tracking-widest font-black ml-1 opacity-50">Apelido da Conta *</Label>
                      <Input
                        id="name"
                        className="glass-card border-white/5 h-12 rounded-xl px-4 font-bold"
                        placeholder="Ex: Reserva Operacional Primária"
                        value={formData.name}
                        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                        required
                      />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="bank" className="text-[10px] uppercase tracking-widest font-black ml-1 opacity-50">Instituição Financeira</Label>
                      <Input
                        id="bank"
                        className="glass-card border-white/5 h-12 rounded-xl px-4 font-medium"
                        placeholder="Ex: Banco do Brasil"
                        value={formData.bank}
                        onChange={(e) => setFormData({ ...formData, bank: e.target.value })}
                      />
                    </div>
                    <div className="grid grid-cols-2 gap-6">
                      <div className="space-y-2">
                        <Label htmlFor="agency" className="text-[10px] uppercase tracking-widest font-black ml-1 opacity-50">Agência</Label>
                        <Input
                          id="agency"
                          className="glass-card border-white/5 h-12 rounded-xl px-4 font-bold"
                          value={formData.agency}
                          onChange={(e) => setFormData({ ...formData, agency: e.target.value })}
                        />
                      </div>
                      <div className="space-y-2">
                        <Label htmlFor="account_number" className="text-[10px] uppercase tracking-widest font-black ml-1 opacity-50">Número da Conta</Label>
                        <Input
                          id="account_number"
                          className="glass-card border-white/5 h-12 rounded-xl px-4 font-bold"
                          value={formData.account_number}
                          onChange={(e) => setFormData({ ...formData, account_number: e.target.value })}
                        />
                      </div>
                    </div>
                    <div className="space-y-2">
                      <Label className="text-[10px] uppercase tracking-widest font-black ml-1 opacity-50">Identidade Visual</Label>
                      <BankIconSelector
                        value={formData.icon}
                        onChange={(icon) => setFormData({ ...formData, icon })}
                        label="Selecionar Ícone da Entidade"
                      />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="balance" className="text-[10px] uppercase tracking-widest font-black ml-1 opacity-50">Valor de Capital Inicial</Label>
                      <div className="relative">
                        <DollarSign className="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-primary opacity-50" />
                        <Input
                          id="balance"
                          type="number"
                          step="0.01"
                          className="glass-card border-white/5 h-14 rounded-xl pl-10 pr-4 font-black text-xl"
                          value={formData.balance}
                          onChange={(e) => setFormData({ ...formData, balance: e.target.value })}
                          required
                        />
                      </div>
                      <p className="text-[10px] text-muted-foreground font-medium uppercase tracking-widest opacity-40 ml-1">
                        A soberania começa com um rastreamento preciso
                      </p>
                    </div>
                  </div>
                  <div className="flex justify-end gap-3 pt-4">
                    <Button type="button" variant="ghost" className="rounded-xl px-6 font-bold uppercase text-[10px] tracking-widest" onClick={() => setIsDialogOpen(false)}>
                      Cancelar
                    </Button>
                    <Button variant="outline" type="submit" className="glass border-primary/20 text-primary font-black uppercase tracking-widest px-8 h-12 rounded-xl">
                      {editingAccount ? 'Salvar Alterações' : 'Inicializar Ativo'}
                    </Button>
                  </div>
                </form>
              </div>
            </DialogContent>
          </Dialog>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div className="md:col-span-2 lg:col-span-2">
          <Alert className="glass-card premium-card border-slate-300 bg-white rounded-3xl p-6">
            <div className="flex items-start gap-4">
              <div className="p-3 bg-primary/10 rounded-2xl">
                <Info className="h-6 w-6 text-primary" />
              </div>
              <AlertDescription className="space-y-3">
                <p className="text-[10px] font-black uppercase tracking-[0.2em] text-primary/70">Dinâmica de Liquidez</p>
                <div className="flex flex-wrap items-center gap-6">
                  <div className="flex items-center gap-2">
                    <div className="p-1.5 bg-green-500/10 rounded-lg">
                      <TrendingUp className="h-4 w-4 text-green-500" />
                    </div>
                    <span className="text-xs uppercase font-black tracking-widest opacity-80 decoration-green-500 underline decoration-2 underline-offset-4">Entrada de Ativos</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <div className="p-1.5 bg-red-500/10 rounded-lg">
                      <TrendingDown className="h-4 w-4 text-red-500" />
                    </div>
                    <span className="text-xs uppercase font-black tracking-widest opacity-80 decoration-red-500 underline decoration-2 underline-offset-4">Saída de Capital</span>
                  </div>
                </div>
              </AlertDescription>
            </div>
          </Alert>
        </div>
      </div>

      {/* Sumário de Ativos */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div className="glass-card premium-card border-slate-300 bg-white rounded-3xl p-8 relative overflow-hidden group">
          <div className="absolute top-0 right-0 p-8 opacity-10 group-hover:opacity-20 transition-opacity">
            <DollarSign className="h-24 w-24 text-primary" />
          </div>
          <p className="text-[10px] font-black uppercase tracking-[0.2em] text-primary/70 mb-2">Patrimônio Líquido Total</p>
          <h2 className="text-4xl font-black tracking-tighter text-slate-900">
            {formatCurrency(accounts.reduce((acc, curr) => acc + curr.balance, 0))}
          </h2>
          <div className="mt-4 flex items-center gap-2">
            <div className="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse" />
            <span className="text-[10px] font-bold text-emerald-600 uppercase tracking-widest">Saldos Consolidados</span>
          </div>
        </div>

        <div className="glass-card premium-card border-slate-300 bg-white rounded-3xl p-8 relative overflow-hidden group">
          <div className="absolute top-0 right-0 p-8 opacity-10 group-hover:opacity-20 transition-opacity">
            <TrendingUp className="h-24 w-24 text-emerald-500" />
          </div>
          <p className="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-600/70 mb-2">Ativos Positivos</p>
          <h2 className="text-4xl font-black tracking-tighter text-emerald-600">
            {formatCurrency(accounts.filter(a => a.balance > 0).reduce((acc, curr) => acc + curr.balance, 0))}
          </h2>
          <p className="text-[10px] font-bold text-slate-400 mt-4 uppercase tracking-widest">
            {accounts.filter(a => a.balance > 0).length} Contas no azul
          </p>
        </div>

        <div className="glass-card premium-card border-slate-300 bg-white rounded-3xl p-8 relative overflow-hidden group">
          <div className="absolute top-0 right-0 p-8 opacity-10 group-hover:opacity-20 transition-opacity">
            <TrendingDown className="h-24 w-24 text-red-500" />
          </div>
          <p className="text-[10px] font-black uppercase tracking-[0.2em] text-red-600/70 mb-2">Passivos / Descobertos</p>
          <h2 className="text-4xl font-black tracking-tighter text-red-600">
            {formatCurrency(accounts.filter(a => a.balance < 0).reduce((acc, curr) => acc + curr.balance, 0))}
          </h2>
          <p className="text-[10px] font-bold text-slate-400 mt-4 uppercase tracking-widest">
            {accounts.filter(a => a.balance < 0).length} Contas em débito
          </p>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-8">
        {accounts.length === 0 && !isLoading ? (
          <div className="col-span-full glass-card premium-card border-slate-300 rounded-3xl overflow-hidden shadow-2xl">
            <div className="flex flex-col items-center justify-center py-24 px-4 bg-white/[0.02]">
              <div className="relative group mb-6">
                <div className="absolute inset-0 bg-primary/20 blur-3xl rounded-full transition-all group-hover:bg-primary/30" />
                <Building2 className="h-16 w-16 text-primary relative z-10 opacity-40 group-hover:opacity-60 transition-all group-hover:scale-110" />
              </div>
              <p className="text-xl font-black uppercase tracking-tighter mb-2 text-slate-900">Sem Contas Registradas</p>
              <p className="text-sm text-muted-foreground font-medium uppercase tracking-widest opacity-50 max-w-xs text-center">
                Inicie sua jornada financeira consolidando seus primeiros ativos.
              </p>
            </div>
          </div>
        ) : (
          accounts.map((account) => {
            const bankColor = account.icon ? getBankById(account.icon)?.color || '#1e293b' : '#1e293b';

            return (
              <div
                key={account.id}
                className="group relative h-72 glass-card premium-card border-slate-200 bg-white rounded-[2.5rem] p-8 shadow-xl hover:shadow-2xl transition-all duration-500 hover:-translate-y-2 overflow-hidden"
              >
                {/* Bank Context Gradient */}
                <div
                  className="absolute -top-24 -right-24 w-64 h-64 rounded-full opacity-[0.03] group-hover:opacity-[0.08] transition-opacity duration-500 blur-3xl"
                  style={{ backgroundColor: bankColor }}
                />

                <div className="relative h-full flex flex-col justify-between">
                  <div className="flex justify-between items-start">
                    <div className="flex items-center gap-4">
                      <div
                        className="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 shadow-inner group-hover:scale-110 transition-transform duration-500"
                        style={{ boxShadow: `0 10px 20px -10px ${bankColor}33` }}
                      >
                        <img
                          src={account.icon ? getBankById(account.icon)?.icon || getDefaultBankIcon() : getDefaultBankIcon()}
                          alt={account.bank || 'Bank'}
                          className="w-8 h-8 object-contain opacity-80 group-hover:opacity-100 transition-opacity"
                        />
                      </div>
                      <div className="space-y-0.5">
                        <p className="font-black text-lg tracking-tight uppercase leading-none text-slate-900 group-hover:text-primary transition-colors">
                          {account.name}
                        </p>
                        <p className="text-[10px] font-black uppercase tracking-widest text-slate-400">
                          {account.bank || 'Instituição não informada'}
                        </p>
                      </div>
                    </div>

                    <div className="flex gap-1.5 opacity-0 translate-y-2 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300">
                      <Button
                        variant="ghost"
                        size="icon"
                        className="h-9 w-9 rounded-xl bg-slate-50 text-slate-400 hover:bg-primary/10 hover:text-primary"
                        onClick={() => openEditDialog(account)}
                      >
                        <Pencil className="h-4 w-4" />
                      </Button>
                      <Button
                        variant="ghost"
                        size="icon"
                        className="h-9 w-9 rounded-xl bg-red-50 text-red-300 hover:bg-red-500 hover:text-white"
                        onClick={() => handleDelete(account.id)}
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    </div>
                  </div>

                  <div className="space-y-1">
                    <span className="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 block ml-1">Saldo em Conta</span>
                    <div className="flex items-baseline gap-1">
                      <p className={cn(
                        "text-4xl font-black tracking-tighter",
                        account.balance >= 0 ? "text-emerald-600" : "text-red-500"
                      )}>
                        {formatCurrency(account.balance)}
                      </p>
                    </div>
                  </div>

                  <div className="flex items-center justify-between pt-4 border-t border-slate-100">
                    <div className="flex flex-col">
                      {account.agency && account.account_number ? (
                        <p className="text-[9px] font-bold text-slate-400 uppercase tracking-widest">
                          Ag {account.agency} • Cc {account.account_number}
                        </p>
                      ) : (
                        <p className="text-[9px] font-bold text-slate-300 uppercase tracking-widest italic">
                          Dados Bancários Omissos
                        </p>
                      )}
                    </div>

                    <Button
                      variant="ghost"
                      className="px-4 h-9 rounded-xl bg-primary/5 hover:bg-primary hover:text-white text-primary text-[10px] font-black uppercase tracking-widest group/btn transition-all"
                      onClick={() => window.location.href = `/transactions?account_id=${account.id}`}
                    >
                      Ver Extrato
                      <TrendingUp className="ml-2 h-3.5 w-3.5 group-hover/btn:translate-x-1 transition-transform" />
                    </Button>
                  </div>
                </div>
              </div>
            );
          })
        )}
      </div>
    </div>
  );
}
