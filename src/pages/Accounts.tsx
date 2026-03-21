import * as React from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { requireCurrentUser } from '@/db/client';
import { accountsApi } from '@/db/api';
import { Button } from '@/components/ui/button';
// Card components removed - using list layout now
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

import { useToast } from '@/hooks/use-toast';
import { Plus, Pencil, Trash2, Building2, RefreshCw, TrendingUp, TrendingDown, DollarSign } from 'lucide-react';
import { cn } from '@/lib/utils';
import { BankCombobox } from '@/components/ui/bank-combobox';
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "@/components/ui/tooltip";
import { getBankById, getDefaultBankIcon } from '@/config/banks';
import { useFinanceScope } from '@/hooks/useFinanceScope';
import type { Account } from '@/types/types';

export default function Accounts() {
  const navigate = useNavigate();
  const [searchParams, setSearchParams] = useSearchParams();
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
  const onboardingMode = searchParams.get('onboarding');
  const isOnboarding = onboardingMode === '1' || onboardingMode === 'account';
  const prefix = isPJ && companyId ? `/pj/${companyId}` : '/pf';

  React.useEffect(() => {
    loadAccounts();
  }, [companyId, personId]);

  React.useEffect(() => {
    if (!isOnboarding || isLoading || accounts.length > 0 || isDialogOpen) {
      return;
    }

    setIsDialogOpen(true);
    toast({
      title: isPJ ? 'Configure a primeira conta PJ' : 'Configure a primeira conta PF',
      description: 'Cadastre a conta inicial para começar a movimentar o ambiente.',
    });
  }, [accounts.length, isDialogOpen, isLoading, isOnboarding, isPJ, toast]);

  const loadAccounts = async () => {
    setIsLoading(true);
    try {
      const user = await requireCurrentUser();

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
      const user = await requireCurrentUser();

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
        const createdAccount = await accountsApi.createAccount({
          ...accountData,
          balance: Number(formData.balance), // Also set current balance to initial on create
          user_id: user.id,
          company_id: companyId ?? null, // Associar ao ID da URL (null para PF)
          person_id: personId ?? null // Associar à pessoa selecionada (PF)
        });

        if (isOnboarding && createdAccount?.id) {
          toast({
            title: 'Conta inicial configurada',
            description: 'Agora registre a primeira transação para ativar o histórico financeiro.',
          });
          setIsDialogOpen(false);
          setSearchParams({}, { replace: true });
          resetForm();
          await loadAccounts();
          navigate(`${prefix}/transactions?onboarding=transaction&account_id=${createdAccount.id}`);
          return;
        }

        toast({ title: 'Sucesso', description: 'Conta criada com sucesso' });
      }

      setIsDialogOpen(false);
      if (isOnboarding) {
        setSearchParams({}, { replace: true });
      }
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
      const user = await requireCurrentUser();

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

  const handleBankSelection = ({
    bankName,
    iconId,
  }: {
    bankName: string;
    iconId: string | null;
  }) => {
    setFormData((current) => ({
      ...current,
      bank: bankName,
      icon: iconId,
      name: !editingAccount && iconId && iconId !== 'default' && (!current.name || current.name === current.bank || current.name.startsWith('Conta '))
        ? `Conta ${bankName.split(' ')[0]}`
        : current.name,
    }));
  };

  return (
    <TooltipProvider delayDuration={0}>
      <div className="w-full max-w-[1600px] mx-auto p-4 lg:p-6 space-y-6 animate-slide-up bg-slate-50/30 min-h-screen">
      {/* Header Section */}
      <div className="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
        <div>
          <h1 className="text-xl font-black tracking-[0.05em] text-slate-900 uppercase">
            Contas <span className="text-primary/70">{isPJ ? 'Corporativas' : 'Pessoais'}</span>
          </h1>
          <p className="text-[11px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">
            Gestão estratégica de ativos e controle de liquidez
          </p>
        </div>
        <div className="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
          <Button
            variant="outline"
            onClick={handleRecalculateBalances}
            disabled={isRecalculating}
            className="bg-white border-slate-200 text-slate-600 font-bold text-[10px] uppercase tracking-widest h-9 px-4 rounded-lg shadow-sm transition-all"
          >
            {isRecalculating ? (
              <>
                <RefreshCw className="mr-2 h-3 w-3 animate-spin" />
                Recalculando...
              </>
            ) : (
              <>
                <RefreshCw className="mr-2 h-3 w-3" />
                Recalcular Ativos
              </>
            )}
          </Button>
          <Dialog open={isDialogOpen} onOpenChange={(open) => {
            setIsDialogOpen(open);
            if (!open) {
              if (isOnboarding) {
                setSearchParams({}, { replace: true });
              }
              resetForm();
            }
          }}>
            <DialogTrigger asChild>
              <Button className="bg-blue-600 hover:bg-blue-700 text-white font-black text-[10px] uppercase tracking-widest h-10 px-6 rounded-lg shadow-sm transition-all hover:scale-105 active:scale-95">
                <Plus className="mr-2 h-4 w-4" />
                Inicializar Conta
              </Button>
            </DialogTrigger>
            <DialogContent className="w-[min(96vw,1400px)] max-w-[1400px] overflow-hidden rounded-3xl border-white/10 p-0 backdrop-blur-3xl">
              <form onSubmit={handleSubmit} className="flex max-h-[85vh] flex-col bg-background/95">
                <div className="border-b border-border/60 px-6 py-5 lg:px-8">
                  <DialogHeader className="space-y-2 text-left">
                    <DialogTitle className="text-2xl font-black uppercase tracking-tight leading-tight">
                      {editingAccount ? 'Modificar Conta' : 'Nova Fonte de Ativos'}
                    </DialogTitle>
                    <DialogDescription className="text-[11px] font-bold uppercase tracking-widest opacity-60">
                      Formulário mais largo, menos alto e com ação sempre visível no rodapé.
                    </DialogDescription>
                  </DialogHeader>
                </div>

                <div className="flex-1 overflow-y-auto px-6 py-6 lg:px-8 lg:py-8">
                  <div className="grid gap-6 md:grid-cols-2">
                    <div className="space-y-6">
                      <div className="space-y-2">
                        <Label htmlFor="name" className="ml-1 text-[10px] font-black uppercase tracking-widest opacity-50">Apelido da Conta *</Label>
                        <Input
                          id="name"
                          className="glass-card h-12 rounded-xl border-slate-300 px-4 font-bold"
                          placeholder="Ex: Reserva Operacional Primária"
                          value={formData.name}
                          onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                          required
                        />
                      </div>
                      <BankCombobox
                        bankName={formData.bank}
                        iconId={formData.icon}
                        onChange={handleBankSelection}
                        label="Instituição Financeira"
                        placeholder="Selecionar ou digitar banco"
                      />
                      <div className="grid items-start gap-4 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]">
                        <div className="flex h-full flex-col gap-2">
                          <Label htmlFor="agency" className="ml-1 text-[10px] font-black uppercase tracking-widest opacity-50">Agência</Label>
                          <Input
                            id="agency"
                            className="glass-card h-12 rounded-xl border-slate-300 px-4 font-bold tracking-[0.12em]"
                            placeholder="0001"
                            value={formData.agency}
                            onChange={(e) => setFormData({ ...formData, agency: e.target.value })}
                          />
                        </div>
                        <div className="flex h-full flex-col gap-2">
                          <Label htmlFor="account_number" className="ml-1 text-[10px] font-black uppercase tracking-widest opacity-50">Número da Conta</Label>
                          <Input
                            id="account_number"
                            className="glass-card h-12 rounded-xl border-slate-300 px-4 font-bold tracking-[0.12em]"
                            placeholder="12345-6"
                            value={formData.account_number}
                            onChange={(e) => setFormData({ ...formData, account_number: e.target.value })}
                          />
                        </div>
                      </div>
                    </div>

                    <div className="space-y-6">
                      <div className="space-y-2 rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                        <Label className="ml-1 text-[10px] font-black uppercase tracking-widest opacity-50">Identidade Visual do Banco</Label>
                        <div className="flex items-center gap-4 rounded-xl border border-slate-200 bg-white px-4 py-4">
                          <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-50 shadow-sm">
                            <img
                              src={formData.icon ? getBankById(formData.icon)?.icon || getDefaultBankIcon() : getDefaultBankIcon()}
                              alt={formData.bank || 'Banco selecionado'}
                              className="h-10 w-10 object-contain"
                            />
                          </div>
                          <div className="min-w-0">
                            <p className="text-sm font-black uppercase tracking-wide text-slate-900">
                              {formData.bank || 'Nenhum banco selecionado'}
                            </p>
                            <p className="text-[11px] font-medium uppercase tracking-widest text-slate-500">
                              {formData.icon && formData.icon !== 'default'
                                ? 'Ícone aplicado automaticamente'
                                : 'Ícone padrão para banco manual'}
                            </p>
                          </div>
                        </div>
                      </div>
                      <div className="space-y-2">
                        <Label htmlFor="balance" className="ml-1 text-[10px] font-black uppercase tracking-widest opacity-50">Valor de Capital Inicial</Label>
                        <div className="relative">
                          <DollarSign className="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-primary opacity-50" />
                          <Input
                            id="balance"
                            type="number"
                            step="0.01"
                            className="glass-card h-14 rounded-xl border-slate-300 pl-10 pr-4 text-xl font-black"
                            value={formData.balance}
                            onChange={(e) => setFormData({ ...formData, balance: e.target.value })}
                            required
                          />
                        </div>
                        <p className="ml-1 text-[10px] font-medium uppercase tracking-widest text-muted-foreground opacity-40">
                          A soberania começa com um rastreamento preciso
                        </p>
                      </div>
                    </div>
                  </div>
                </div>

                <div className="flex flex-col-reverse gap-3 border-t border-border/60 bg-background/95 px-6 py-4 sm:flex-row sm:justify-end lg:px-8">
                  <Button type="button" variant="ghost" className="rounded-xl px-6 text-[10px] font-bold uppercase tracking-widest" onClick={() => setIsDialogOpen(false)}>
                    Cancelar
                  </Button>
                  <Button variant="outline" type="submit" className="glass h-12 rounded-xl border-primary/20 px-8 font-black uppercase tracking-widest text-primary">
                    {editingAccount ? 'Salvar Alterações' : 'Inicializar Ativo'}
                  </Button>
                </div>
              </form>
            </DialogContent>
          </Dialog>
        </div>
      </div>



      {/* Sumário de Ativos */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div className="glass-card premium-card border-slate-300 bg-white rounded-2xl p-6 relative overflow-hidden group">
          <div className="absolute top-0 right-0 p-6 opacity-10 group-hover:opacity-20 transition-opacity">
            <DollarSign className="h-16 w-16 text-primary" />
          </div>
          <p className="text-[10px] font-black uppercase tracking-[0.2em] text-primary/70 mb-2">Patrimônio Líquido Total</p>
          <h2 className="text-2xl font-black tracking-tighter text-slate-900">
            {formatCurrency(accounts.reduce((acc, curr) => acc + curr.balance, 0))}
          </h2>
          <div className="mt-4 flex items-center gap-2">
            <div className="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse" />
            <span className="text-[10px] font-bold text-emerald-600 uppercase tracking-widest">Saldos Consolidados</span>
          </div>
        </div>

        <div className="glass-card premium-card border-slate-300 bg-white rounded-2xl p-6 relative overflow-hidden group">
          <div className="absolute top-0 right-0 p-6 opacity-10 group-hover:opacity-20 transition-opacity">
            <Tooltip>
              <TooltipTrigger asChild>
                <TrendingUp className="h-16 w-16 text-emerald-500 cursor-help" />
              </TooltipTrigger>
              <TooltipContent className="bg-emerald-600 text-white border-0 font-bold">
                <p>Entrada de Ativos: Receitas e depósitos que aumentam o saldo.</p>
              </TooltipContent>
            </Tooltip>
          </div>
          <p className="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-600/70 mb-2">Ativos Positivos</p>
          <h2 className="text-2xl font-black tracking-tighter text-emerald-600">
            {formatCurrency(accounts.filter(a => a.balance > 0).reduce((acc, curr) => acc + curr.balance, 0))}
          </h2>
          <p className="text-[10px] font-bold text-slate-400 mt-4 uppercase tracking-widest">
            {accounts.filter(a => a.balance > 0).length} Contas no azul
          </p>
        </div>

        <div className="glass-card premium-card border-slate-300 bg-white rounded-2xl p-6 relative overflow-hidden group">
          <div className="absolute top-0 right-0 p-6 opacity-10 group-hover:opacity-20 transition-opacity">
            <Tooltip>
              <TooltipTrigger asChild>
                <TrendingDown className="h-16 w-16 text-red-500 cursor-help" />
              </TooltipTrigger>
              <TooltipContent className="bg-red-600 text-white border-0 font-bold">
                <p>Saída de Capital: Despesas e pagamentos que reduzem o saldo.</p>
              </TooltipContent>
            </Tooltip>
          </div>
          <p className="text-[10px] font-black uppercase tracking-[0.2em] text-red-600/70 mb-2">Passivos / Descobertos</p>
          <h2 className="text-2xl font-black tracking-tighter text-red-600">
            {formatCurrency(accounts.filter(a => a.balance < 0).reduce((acc, curr) => acc + curr.balance, 0))}
          </h2>
          <p className="text-[10px] font-bold text-slate-400 mt-4 uppercase tracking-widest">
            {accounts.filter(a => a.balance < 0).length} Contas em débito
          </p>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-6">
        {accounts.length === 0 && !isLoading ? (
          <div className="col-span-full glass-card premium-card border-slate-300 rounded-2xl overflow-hidden shadow-lg">
            <div className="flex flex-col items-center justify-center py-16 px-4 bg-white/[0.02]">
              <div className="relative group mb-4">
                <div className="absolute inset-0 bg-primary/20 blur-2xl rounded-full transition-all group-hover:bg-primary/30" />
                <Building2 className="h-12 w-12 text-primary relative z-10 opacity-40 group-hover:opacity-60 transition-all group-hover:scale-110" />
              </div>
              <p className="text-lg font-black uppercase tracking-tighter mb-2 text-slate-900">Sem Contas Registradas</p>
              <p className="text-xs text-muted-foreground font-medium uppercase tracking-widest opacity-50 max-w-xs text-center">
                {isPJ
                  ? 'Cadastre a primeira conta bancária da empresa para iniciar o controle corporativo.'
                  : 'Cadastre sua primeira conta pessoal para iniciar o controle financeiro.'}
              </p>
              <div className="mt-5 flex flex-col gap-2 sm:flex-row">
                <Button
                  className="bg-blue-600 hover:bg-blue-700 text-white font-black text-[10px] uppercase tracking-widest h-10 px-6 rounded-lg"
                  onClick={() => setIsDialogOpen(true)}
                >
                  <Plus className="mr-2 h-4 w-4" />
                  Cadastrar Primeira Conta
                </Button>
                <Button
                  variant="outline"
                  className="font-black text-[10px] uppercase tracking-widest h-10 px-6 rounded-lg"
                  onClick={() => navigate(isPJ && companyId ? `/pj/${companyId}` : '/pf')}
                >
                  Voltar ao Painel
                </Button>
              </div>
            </div>
          </div>
        ) : (
          accounts.map((account) => {
            const bankColor = account.icon ? getBankById(account.icon)?.color || '#1e293b' : '#1e293b';

            return (
              <div
                key={account.id}
                className="group relative h-48 glass-card premium-card border-slate-200 bg-white rounded-2xl p-6 shadow-md hover:shadow-lg transition-all duration-300 hover:-translate-y-1 overflow-hidden flex flex-col justify-between"
              >
                {/* Bank Context Gradient */}
                <div
                  className="absolute -top-16 -right-16 w-48 h-48 rounded-full opacity-[0.03] group-hover:opacity-[0.06] transition-opacity duration-500 blur-2xl"
                  style={{ backgroundColor: bankColor }}
                />

                <div className="relative h-full flex flex-col justify-between">
                  <div className="flex justify-between items-start">
                    <div className="flex items-center gap-3">
                      <div
                        className="p-2.5 rounded-xl bg-slate-50 border border-slate-100 shadow-sm group-hover:scale-105 transition-transform duration-300"
                        style={{ boxShadow: `0 4px 10px -5px ${bankColor}33` }}
                      >
                        <img
                          src={account.icon ? getBankById(account.icon)?.icon || getDefaultBankIcon() : getDefaultBankIcon()}
                          alt={account.bank || 'Bank'}
                          className="w-6 h-6 object-contain opacity-80 group-hover:opacity-100 transition-opacity"
                        />
                      </div>
                      <div className="space-y-0.5">
                        <p className="font-black text-base tracking-tight uppercase leading-none text-slate-900 group-hover:text-primary transition-colors">
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
                  <div className="mt-4 pt-4 border-t border-slate-100/50 flex flex-col items-center justify-between sm:flex-row">
                    <div>
                      <p className="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-1">
                        Saldo Atual
                      </p>
                      <h3 className={cn(
                        "text-2xl font-black tracking-tighter",
                        account.balance >= 0 ? "text-emerald-600" : "text-red-500"
                      )}>
                        {formatCurrency(account.balance)}
                      </h3>
                    </div>

                    <Button
                      variant="ghost"
                      className="mt-2 sm:mt-0 px-4 h-9 rounded-xl bg-primary/5 hover:bg-primary hover:text-white text-primary text-[10px] font-black uppercase tracking-widest group/btn transition-all"
                      onClick={() => {
                        navigate(`${prefix}/transactions?account_id=${account.id}`);
                      }}
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
    </TooltipProvider>
  );
}
