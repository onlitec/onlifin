import * as React from 'react';
import { supabase } from '@/db/client';
import { cardsApi, accountsApi } from '@/db/api';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useToast } from '@/hooks/use-toast';
import { Plus, Pencil, Trash2, CreditCard, Landmark, Calendar, DollarSign, Wallet } from 'lucide-react';
import { CardBrandSelector } from '@/components/ui/card-brand-selector';
import { getCardBrandById, getDefaultCardIcon } from '@/config/banks';
import { useFinanceScope } from '@/hooks/useFinanceScope';
import type { Card as CardType, Account } from '@/types/types';

export default function Cards() {
  const [cards, setCards] = React.useState<CardType[]>([]);
  const [accounts, setAccounts] = React.useState<Account[]>([]);
  const [isLoading, setIsLoading] = React.useState(true);
  const [isDialogOpen, setIsDialogOpen] = React.useState(false);
  const [editingCard, setEditingCard] = React.useState<CardType | null>(null);
  const [formData, setFormData] = React.useState({
    name: '',
    card_limit: '',
    closing_day: '',
    due_day: '',
    account_id: '',
    icon: '' as string | null
  });
  const { toast } = useToast();

  const { companyId, isPJ, personId } = useFinanceScope();

  React.useEffect(() => {
    loadData();
  }, [companyId, personId]);

  const loadData = async () => {
    setIsLoading(true);
    try {
      const { data: { user } } = await supabase.auth.getUser();
      if (!user) return;

      const [cardsData, accountsData] = await Promise.all([
        cardsApi.getCards(user.id, companyId, personId),
        accountsApi.getAccounts(user.id, companyId, personId)
      ]);

      setCards(cardsData);
      setAccounts(accountsData);
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao carregar dados',
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

      const cardData = {
        name: formData.name,
        card_limit: Number(formData.card_limit),
        closing_day: formData.closing_day ? Number(formData.closing_day) : null,
        due_day: formData.due_day ? Number(formData.due_day) : null,
        account_id: formData.account_id || null,
        brand: formData.icon || null
      };

      if (editingCard) {
        await cardsApi.updateCard(editingCard.id, {
          ...cardData,
          icon: formData.icon || null
        });
        toast({ title: 'Sucesso', description: 'Cartão atualizado com sucesso' });
      } else {
        await cardsApi.createCard({
          ...cardData,
          icon: formData.icon || null,
          user_id: user.id,
          company_id: companyId ?? null,
          person_id: personId ?? null
        });
        toast({ title: 'Sucesso', description: 'Cartão criado com sucesso' });
      }

      setIsDialogOpen(false);
      resetForm();
      loadData();
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao salvar cartão',
        variant: 'destructive'
      });
    }
  };

  const handleDelete = async (id: string) => {
    if (!confirm('Tem certeza que deseja excluir este cartão?')) return;

    try {
      await cardsApi.deleteCard(id);
      toast({ title: 'Sucesso', description: 'Cartão excluído com sucesso' });
      loadData();
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao excluir cartão',
        variant: 'destructive'
      });
    }
  };

  const openEditDialog = (card: CardType) => {
    setEditingCard(card);
    setFormData({
      name: card.name,
      card_limit: card.card_limit.toString(),
      closing_day: card.closing_day?.toString() || '',
      due_day: card.due_day?.toString() || '',
      account_id: card.account_id || '',
      icon: card.icon || null
    });
    setIsDialogOpen(true);
  };

  const resetForm = () => {
    setEditingCard(null);
    setFormData({
      name: '',
      card_limit: '',
      closing_day: '',
      due_day: '',
      account_id: '',
      icon: null
    });
  };

  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL'
    }).format(value);
  };

  return (
    <div className="w-full max-w-[1600px] mx-auto p-4 lg:p-6 space-y-6 animate-slide-up bg-slate-50/30 min-h-screen">
      {/* Header Section */}
      <div className="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
        <div>
          <h1 className="text-xl font-black tracking-[0.05em] text-slate-900 uppercase">
            Cartões <span className="text-primary/70">{isPJ ? 'Corporativos' : 'Pessoais'}</span>
          </h1>
          <p className="text-[11px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">
            Gestão de crédito e ciclos de faturamento
          </p>
        </div>
        <Dialog open={isDialogOpen} onOpenChange={(open) => {
          setIsDialogOpen(open);
          if (!open) resetForm();
        }}>
          <DialogTrigger asChild>
            <Button className="bg-blue-600 hover:bg-blue-700 text-white font-black text-[10px] uppercase tracking-widest h-10 px-6 rounded-lg shadow-sm transition-all hover:scale-105 active:scale-95">
              <Plus className="mr-2 h-4 w-4" />
              Novo Cartão
            </Button>
          </DialogTrigger>
          <DialogContent className="glass-card premium-card border-white/10 backdrop-blur-3xl rounded-3xl p-0 overflow-hidden">
            <div className="p-8 space-y-6">
              <DialogHeader>
                <DialogTitle className="text-2xl font-black tracking-tighter uppercase">
                  {editingCard ? 'Modificar Parâmetros' : 'Novo Instrumento de Crédito'}
                </DialogTitle>
                <DialogDescription className="text-[10px] uppercase tracking-widest font-bold opacity-60">
                  Configuração técnica de limite e vencimento
                </DialogDescription>
              </DialogHeader>
              <form onSubmit={handleSubmit} className="space-y-6">
                <div className="space-y-4">
                  <div className="space-y-2">
                    <Label htmlFor="name" className="text-[10px] uppercase tracking-widest font-black ml-1 opacity-50">Identificação do Cartão *</Label>
                    <Input
                      id="name"
                      className="glass-card border-white/5 h-12 rounded-xl px-4 font-bold"
                      placeholder="Ex: Visa Infinite Platinum"
                      value={formData.name}
                      onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                      required
                    />
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="card_limit" className="text-[10px] uppercase tracking-widest font-black ml-1 opacity-50">Limite de Crédito Disponível *</Label>
                    <div className="relative">
                      <DollarSign className="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-primary opacity-50" />
                      <Input
                        id="card_limit"
                        type="number"
                        step="0.01"
                        className="glass-card border-white/5 h-14 rounded-xl pl-10 pr-4 font-black text-xl"
                        placeholder="0.00"
                        value={formData.card_limit}
                        onChange={(e) => setFormData({ ...formData, card_limit: e.target.value })}
                        required
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-2 gap-6">
                    <div className="space-y-2">
                      <Label htmlFor="closing_day" className="text-[10px] uppercase tracking-widest font-black ml-1 opacity-50">Dia de Fechamento</Label>
                      <Input
                        id="closing_day"
                        type="number"
                        min="1"
                        max="31"
                        className="glass-card border-white/5 h-12 rounded-xl px-4 font-bold"
                        placeholder="1 a 31"
                        value={formData.closing_day}
                        onChange={(e) => setFormData({ ...formData, closing_day: e.target.value })}
                      />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="due_day" className="text-[10px] uppercase tracking-widest font-black ml-1 opacity-50">Dia de Vencimento</Label>
                      <Input
                        id="due_day"
                        type="number"
                        min="1"
                        max="31"
                        className="glass-card border-white/5 h-12 rounded-xl px-4 font-bold"
                        placeholder="1 a 31"
                        value={formData.due_day}
                        onChange={(e) => setFormData({ ...formData, due_day: e.target.value })}
                      />
                    </div>
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="account_id" className="text-[10px] uppercase tracking-widest font-black ml-1 opacity-50">Débito em Conta (Opcional)</Label>
                    <Select
                      value={formData.account_id || 'none'}
                      onValueChange={(value) => setFormData({ ...formData, account_id: value === 'none' ? '' : value })}
                    >
                      <SelectTrigger className="glass-card border-white/5 h-12 rounded-xl px-4 font-bold">
                        <SelectValue placeholder="Selecione uma conta" />
                      </SelectTrigger>
                      <SelectContent className="glass-card premium-card border-white/10">
                        <SelectItem value="none">Nenhuma</SelectItem>
                        {accounts.map(acc => (
                          <SelectItem key={acc.id} value={acc.id}>
                            {acc.name}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>

                  <div className="space-y-2">
                    <Label className="text-[10px] uppercase tracking-widest font-black ml-1 opacity-50">Bandeira / Identidade</Label>
                    <CardBrandSelector
                      value={formData.icon}
                      onChange={(icon) => setFormData({ ...formData, icon })}
                      label="Selecionar Bandeira"
                    />
                  </div>
                </div>

                <div className="flex justify-end gap-3 pt-4">
                  <Button type="button" variant="ghost" className="rounded-xl px-6 font-bold uppercase text-[10px] tracking-widest" onClick={() => setIsDialogOpen(false)}>
                    Cancelar
                  </Button>
                  <Button variant="outline" type="submit" className="glass border-primary/20 text-primary font-black uppercase tracking-widest px-8 h-12 rounded-xl">
                    {editingCard ? 'Salvar Alterações' : 'Criar Cartão'}
                  </Button>
                </div>
              </form>
            </div>
          </DialogContent>
        </Dialog>
      </div>

      {/* Lista de Cartões */}
      <div className="grid gap-4">
        {cards.length === 0 && !isLoading ? (
          <div className="bg-white border border-slate-200 rounded-2xl p-12 flex flex-col items-center justify-center shadow-sm">
            <div className="p-4 bg-slate-50 rounded-2xl border border-slate-100 mb-4">
              <CreditCard className="h-8 w-8 text-slate-400 opacity-50" />
            </div>
            <p className="text-sm font-black uppercase tracking-widest text-slate-800 mb-1">Sem Cartões Registrados</p>
            <p className="text-[11px] text-slate-400 font-bold uppercase tracking-widest text-center max-w-xs">
              Adicione seus cartões de crédito para monitorar limites e faturas.
            </p>
          </div>
        ) : (
          <div className="space-y-4">
            {cards.map((card) => (
              <div key={card.id} className="bg-white border border-slate-200 rounded-2xl p-4 flex flex-col sm:flex-row items-center justify-between shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
                <div className="flex items-center gap-4 flex-1 min-w-0 w-full sm:w-auto">
                  {/* Ícone da Bandeira */}
                  <div className="w-16 h-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center shrink-0 shadow-sm group-hover:scale-105 transition-transform">
                    <img
                      src={card.icon ? getCardBrandById(card.icon)?.icon || getDefaultCardIcon() : getDefaultCardIcon()}
                      alt={card.brand || 'Bandeira'}
                      className="h-6 w-10 object-contain"
                    />
                  </div>

                  {/* Informações do Cartão */}
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 mb-1">
                      <p className="font-black text-sm tracking-tight text-slate-900 uppercase truncate">{card.name}</p>
                      {card.account_id && (
                        <span className="text-[9px] font-black uppercase tracking-widest text-emerald-600 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded-md flex items-center gap-1">
                          <Wallet className="h-2 w-2" />
                          {accounts.find(a => a.id === card.account_id)?.name}
                        </span>
                      )}
                    </div>
                    <div className="flex items-center gap-4">
                      {card.closing_day && (
                        <div className="flex items-center gap-1 text-[9px] font-bold uppercase tracking-widest text-slate-400">
                          <Calendar className="h-3 w-3" />
                          Fechamento: Dia {card.closing_day}
                        </div>
                      )}
                      {card.due_day && (
                        <div className="flex items-center gap-1 text-[9px] font-bold uppercase tracking-widest text-slate-400 pl-4 border-l border-slate-100">
                          <Landmark className="h-3 w-3" />
                          Vencimento: Dia {card.due_day}
                        </div>
                      )}
                    </div>
                  </div>

                  {/* Limite */}
                  <div className="text-right shrink-0 px-6 border-l border-slate-100 hidden md:block">
                    <span className="text-[9px] font-black uppercase tracking-widest text-slate-400 block mb-0.5">Limite Total</span>
                    <p className="text-lg font-black tracking-tight text-slate-900">
                      {formatCurrency(card.card_limit)}
                    </p>
                  </div>
                </div>

                {/* Mobile View Limite */}
                <div className="flex items-center justify-between w-full mt-4 pt-4 border-t border-slate-50 md:hidden">
                    <div className="text-left">
                        <span className="text-[9px] font-black uppercase tracking-widest text-slate-400 block mb-0.5">Limite Total</span>
                        <p className="text-base font-black tracking-tight text-slate-900">
                            {formatCurrency(card.card_limit)}
                        </p>
                    </div>
                </div>

                {/* Botões de Ação */}
                <div className="flex gap-2 items-center mt-4 sm:mt-0 sm:ml-4">
                  <Button
                    variant="ghost"
                    size="icon"
                    className="h-9 w-9 rounded-lg bg-slate-50 text-slate-400 hover:bg-slate-100 hover:text-slate-900 transition-all"
                    onClick={() => openEditDialog(card)}
                  >
                    <Pencil className="h-4 w-4" />
                  </Button>
                  <Button
                    variant="ghost"
                    size="icon"
                    className="h-9 w-9 rounded-lg bg-red-50 text-red-400 hover:bg-red-100 hover:text-red-600 transition-all"
                    onClick={() => handleDelete(card.id)}
                  >
                    <Trash2 className="h-4 w-4" />
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
