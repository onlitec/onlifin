import * as React from 'react';
import { supabase } from '@/db/client';
import { cardsApi, accountsApi } from '@/db/api';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useToast } from '@/hooks/use-toast';
import { Plus, Pencil, Trash2, CreditCard } from 'lucide-react';
import { CardBrandSelector } from '@/components/ui/card-brand-selector';
import { getCardBrandById, getDefaultCardIcon } from '@/config/banks';
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

  React.useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      const { data: { user } } = await supabase.auth.getUser();
      if (!user) return;

      const [cardsData, accountsData] = await Promise.all([
        cardsApi.getCards(user.id),
        accountsApi.getAccounts(user.id)
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
        ...formData,
        card_limit: Number(formData.card_limit),
        closing_day: formData.closing_day ? Number(formData.closing_day) : null,
        due_day: formData.due_day ? Number(formData.due_day) : null,
        account_id: formData.account_id || null,
        brand: formData.icon || null
      };

      if (editingCard) {
        await cardsApi.updateCard(editingCard.id, cardData);
        toast({ title: 'Sucesso', description: 'Cartão atualizado com sucesso' });
      } else {
        await cardsApi.createCard({
          ...cardData,
          user_id: user.id
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

  const getLinkedAccount = (accountId: string | null) => {
    if (!accountId) return null;
    return accounts.find(acc => acc.id === accountId);
  };

  return (
    <div className="w-full max-w-[1600px] mx-auto p-6 space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold">Cartões de Crédito</h1>
        <Dialog open={isDialogOpen} onOpenChange={(open) => {
          setIsDialogOpen(open);
          if (!open) resetForm();
        }}>
          <DialogTrigger asChild>
            <Button>
              <Plus className="mr-2 h-4 w-4" />
              Novo Cartão
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>{editingCard ? 'Editar Cartão' : 'Novo Cartão'}</DialogTitle>
              <DialogDescription>
                Preencha os dados do cartão de crédito
              </DialogDescription>
            </DialogHeader>
            <form onSubmit={handleSubmit}>
              <div className="space-y-4 py-4">
                <div className="space-y-2">
                  <Label htmlFor="name">Nome do Cartão *</Label>
                  <Input
                    id="name"
                    placeholder="Ex: Cartão Nubank"
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="card_limit">Limite *</Label>
                  <Input
                    id="card_limit"
                    type="number"
                    step="0.01"
                    placeholder="0.00"
                    value={formData.card_limit}
                    onChange={(e) => setFormData({ ...formData, card_limit: e.target.value })}
                    required
                  />
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label htmlFor="closing_day">Dia de Fechamento</Label>
                    <Input
                      id="closing_day"
                      type="number"
                      min="1"
                      max="31"
                      placeholder="Ex: 10"
                      value={formData.closing_day}
                      onChange={(e) => setFormData({ ...formData, closing_day: e.target.value })}
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="due_day">Dia de Vencimento</Label>
                    <Input
                      id="due_day"
                      type="number"
                      min="1"
                      max="31"
                      placeholder="Ex: 15"
                      value={formData.due_day}
                      onChange={(e) => setFormData({ ...formData, due_day: e.target.value })}
                    />
                  </div>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="account_id">Conta Vinculada</Label>
                  <Select
                    value={formData.account_id || 'none'}
                    onValueChange={(value) => setFormData({ ...formData, account_id: value === 'none' ? '' : value })}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Selecione uma conta" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="none">Nenhuma</SelectItem>
                      {accounts.map(acc => (
                        <SelectItem key={acc.id} value={acc.id}>
                          {acc.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <CardBrandSelector
                  value={formData.icon}
                  onChange={(icon) => setFormData({ ...formData, icon })}
                  label="Bandeira do Cartão"
                />
              </div>
              <DialogFooter>
                <Button type="submit">{editingCard ? 'Atualizar' : 'Criar'}</Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        {cards.map((card) => {
          const linkedAccount = getLinkedAccount(card.account_id);

          return (
            <Card key={card.id}>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-lg font-medium">{card.name}</CardTitle>
                <img
                  src={card.icon ? getCardBrandById(card.icon)?.icon || getDefaultCardIcon() : getDefaultCardIcon()}
                  alt={card.brand || 'Cartão'}
                  className="h-8 w-12 object-contain"
                />
              </CardHeader>
              <CardContent>
                <div className="space-y-2">
                  <div>
                    <p className="text-sm text-muted-foreground">Limite</p>
                    <p className="text-2xl font-bold">{formatCurrency(card.card_limit)}</p>
                  </div>
                  {(card.closing_day || card.due_day) && (
                    <div className="text-sm text-muted-foreground">
                      {card.closing_day && <p>Fechamento: dia {card.closing_day}</p>}
                      {card.due_day && <p>Vencimento: dia {card.due_day}</p>}
                    </div>
                  )}
                  {linkedAccount && (
                    <p className="text-sm text-muted-foreground">
                      Conta: {linkedAccount.name}
                    </p>
                  )}
                  <div className="flex gap-2 pt-2">
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => openEditDialog(card)}
                    >
                      <Pencil className="h-4 w-4" />
                    </Button>
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => handleDelete(card.id)}
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              </CardContent>
            </Card>
          );
        })}
      </div>

      {cards.length === 0 && !isLoading && (
        <Card>
          <CardContent className="flex flex-col items-center justify-center py-12">
            <CreditCard className="h-12 w-12 text-muted-foreground mb-4" />
            <p className="text-lg font-medium mb-2">Nenhum cartão cadastrado</p>
            <p className="text-sm text-muted-foreground mb-4">
              Comece adicionando seu primeiro cartão de crédito
            </p>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
