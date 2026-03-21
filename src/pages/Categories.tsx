import * as React from 'react';
import { requireCurrentUser } from '@/db/client';
import { categoriesApi } from '@/db/api';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useToast } from '@/hooks/use-toast';
import { useFinanceScope } from '@/hooks/useFinanceScope';
import {
  Plus, Pencil, Trash2, Tag,
  Briefcase, Code, TrendingUp, ShoppingCart, DollarSign,
  Utensils, Car, Home, Heart, BookOpen, Gamepad2,
  Smartphone, Plane, Shirt, Gift, Coffee, ShoppingBag,
  Wifi, Zap, Droplet, Tv, Music, Film, Camera,
  Dumbbell, Pill, Stethoscope, Bus, Train, Bike,
  CreditCard, Wallet, PiggyBank, Receipt, FileText,
  Building2, Landmark, GraduationCap, Baby, Dog, Cat,
  Scissors, Wrench, Hammer, PaintBucket, Flower2
} from 'lucide-react';
import type { Category } from '@/types/types';

// Mapping Lucide icon names to components
const LUCIDE_ICONS: Record<string, React.ElementType> = {
  Briefcase, Code, TrendingUp, ShoppingCart, DollarSign,
  Utensils, Car, Home, Heart, BookOpen, Gamepad2,
  Smartphone, Plane, Shirt, Gift, Coffee, ShoppingBag,
  Wifi, Zap, Droplet, Tv, Music, Film, Camera,
  Dumbbell, Pill, Stethoscope, Bus, Train, Bike,
  CreditCard, Wallet, PiggyBank, Receipt, FileText,
  Building2, Landmark, GraduationCap, Baby, Dog, Cat,
  Scissors, Wrench, Hammer, PaintBucket, Flower2, Tag
};

const EMOJI_OPTIONS = [
  '💰', '💵', '💸', '💳', '🏦', '📈', '📊', '💼', '🎯', '🎁',
  '🍔', '🍕', '🍜', '☕', '🛒', '🏠', '🚗', '⛽', '🚌', '✈️',
  '🏥', '💊', '🏋️', '📚', '🎓', '📱', '💻', '🎮', '🎬', '🎵',
  '👕', '👗', '👟', '💄', '🎨', '🔧', '⚡', '💡', '📄', '🎉'
];

const COLOR_OPTIONS = [
  { name: 'Verde', value: '#27AE60' },
  { name: 'Azul', value: '#2C3E50' },
  { name: 'Vermelho', value: '#E74C3C' },
  { name: 'Laranja', value: '#E67E22' },
  { name: 'Roxo', value: '#9B59B6' },
  { name: 'Amarelo', value: '#F39C12' },
  { name: 'Rosa', value: '#E91E63' },
  { name: 'Ciano', value: '#00BCD4' }
];

const STARTER_CATEGORIES: Array<{
  name: string;
  type: 'income' | 'expense';
  icon: string;
  color: string;
}> = [
  { name: 'Salário', type: 'income', icon: 'Wallet', color: '#27AE60' },
  { name: 'Pró-Labore', type: 'income', icon: 'Briefcase', color: '#2C3E50' },
  { name: 'Vendas', type: 'income', icon: 'TrendingUp', color: '#00BCD4' },
  { name: 'Moradia', type: 'expense', icon: 'Home', color: '#2C3E50' },
  { name: 'Alimentação', type: 'expense', icon: 'Utensils', color: '#E67E22' },
  { name: 'Transporte', type: 'expense', icon: 'Car', color: '#00BCD4' },
  { name: 'Saúde', type: 'expense', icon: 'Heart', color: '#E74C3C' },
  { name: 'Serviços', type: 'expense', icon: 'Receipt', color: '#9B59B6' },
];

export default function Categories() {
  const { companyId } = useFinanceScope();
  const [categories, setCategories] = React.useState<Category[]>([]);
  const [isDialogOpen, setIsDialogOpen] = React.useState(false);
  const [editingCategory, setEditingCategory] = React.useState<Category | null>(null);
  const [isSeedingStarterCategories, setIsSeedingStarterCategories] = React.useState(false);
  const [formData, setFormData] = React.useState({
    name: '',
    type: 'expense' as 'income' | 'expense',
    icon: '💰',
    color: '#27AE60'
  });
  const { toast } = useToast();

  React.useEffect(() => {
    loadCategories();
  }, [companyId]);

  const loadCategories = async () => {
    try {
      const data = await categoriesApi.getCategories(companyId);
      setCategories(data);
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao carregar categorias',
        variant: 'destructive'
      });
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const user = await requireCurrentUser();

      if (editingCategory) {
        await categoriesApi.updateCategory(editingCategory.id, formData);
        toast({ title: 'Sucesso', description: 'Categoria atualizada com sucesso' });
      } else {
        await categoriesApi.createCategory({
          ...formData,
          user_id: user.id,
          company_id: companyId ?? null
        });
        toast({ title: 'Sucesso', description: 'Categoria criada com sucesso' });
      }

      setIsDialogOpen(false);
      resetForm();
      loadCategories();
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao salvar categoria',
        variant: 'destructive'
      });
    }
  };

  const handleDelete = async (category: Category) => {
    if (!confirm('Tem certeza que deseja excluir esta categoria?')) return;

    try {
      await categoriesApi.deleteCategory(category.id);
      toast({ title: 'Sucesso', description: 'Categoria excluída com sucesso' });
      loadCategories();
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao excluir categoria',
        variant: 'destructive'
      });
    }
  };

  const openEditDialog = (category: Category) => {
    setEditingCategory(category);
    setFormData({
      name: category.name,
      type: category.type,
      icon: category.icon || '💰',
      color: category.color || '#27AE60'
    });
    setIsDialogOpen(true);
  };

  const resetForm = () => {
    setEditingCategory(null);
    setFormData({
      name: '',
      type: 'expense',
      icon: '💰',
      color: '#27AE60'
    });
  };

  const incomeCategories = categories.filter(c => c.type === 'income');
  const expenseCategories = categories.filter(c => c.type === 'expense');
  const userCategories = categories.filter(c => c.user_id !== null);

  const handleCreateStarterCategories = async () => {
    setIsSeedingStarterCategories(true);
    try {
      const user = await requireCurrentUser();

      const existingKeys = new Set(
        categories.map((category) => `${category.type}:${category.name.trim().toLowerCase()}`)
      );

      const missingCategories = STARTER_CATEGORIES.filter(
        (category) => !existingKeys.has(`${category.type}:${category.name.trim().toLowerCase()}`)
      );

      if (missingCategories.length === 0) {
        toast({
          title: 'Sugestões já disponíveis',
          description: 'As categorias recomendadas já existem neste ambiente.',
        });
        return;
      }

      await Promise.all(
        missingCategories.map((category) =>
          categoriesApi.createCategory({
            ...category,
            user_id: user.id,
            company_id: companyId ?? null,
          })
        )
      );

      toast({
        title: 'Categorias sugeridas criadas',
        description: `${missingCategories.length} categoria(s) adicionada(s) ao seu ambiente.`,
      });
      await loadCategories();
    } catch (error: any) {
      toast({
        title: 'Erro ao criar sugestões',
        description: error.message || 'Não foi possível criar as categorias sugeridas.',
        variant: 'destructive'
      });
    } finally {
      setIsSeedingStarterCategories(false);
    }
  };

  // Helper to render category icon (Lucide or emoji)
  const renderCategoryIcon = (iconName: string | null, color: string | null) => {
    if (!iconName) return <Tag className="w-5 h-5 text-foreground/70" />;

    // Check if it's a Lucide icon name
    const LucideIcon = LUCIDE_ICONS[iconName];
    if (LucideIcon) {
      return <LucideIcon className="w-5 h-5" style={{ color: color || undefined }} />;
    }

    // Otherwise render as emoji
    return <span className="text-xl">{iconName}</span>;
  };

  const CategoryCard = ({ category }: { category: Category }) => (
    <div className="flex items-center justify-between p-5 hover:bg-white/[0.03] transition-all duration-300 group">
      <div className="flex items-center gap-5 flex-1 min-w-0">
        <div
          className="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 border border-white/5 shadow-lg group-hover:scale-110 transition-transform bg-white/5"
          style={{ boxShadow: `0 0 20px ${category.color}10` }}
        >
          {renderCategoryIcon(category.icon, category.color)}
        </div>
        <div className="flex-1 min-w-0 space-y-1">
          <div className="flex items-center gap-3">
            <p className="font-black text-lg tracking-tighter uppercase leading-none">{category.name}</p>
            <span className={`text-[8px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full ${category.type === 'income' ? 'bg-income/10 text-income' : 'bg-expense/10 text-expense'}`}>
              {category.type === 'income' ? 'Entrada' : 'Saída'}
            </span>
          </div>
          <p className="text-[10px] font-bold text-muted-foreground/40 uppercase tracking-widest">
            {category.user_id === null ? 'Definição Padrão' : 'Lógica Personalizada'}
          </p>
        </div>
      </div>
      <div className="flex gap-2 opacity-0 group-hover:opacity-100 transition-all translate-x-4 group-hover:translate-x-0">
        <Button
          variant="ghost"
          size="icon"
          className="h-10 w-10 rounded-xl bg-white/5 text-muted-foreground hover:bg-white/10 hover:text-foreground transition-all"
          onClick={() => openEditDialog(category)}
        >
          <Pencil className="h-4 w-4" />
        </Button>
        <Button
          variant="ghost"
          size="icon"
          className="h-10 w-10 rounded-xl bg-red-500/5 text-red-500/40 hover:bg-red-500/20 hover:text-red-500 transition-all"
          onClick={() => handleDelete(category)}
        >
          <Trash2 className="h-4 w-4" />
        </Button>
      </div>
    </div>
  );

  return (
    <div className="w-full max-w-[1600px] mx-auto p-4 xl:p-8 space-y-8 animate-in fade-in duration-700">
      {/* Header Section */}
      <div className="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-6 pb-2">
        <div className="space-y-1">
          <h1 className="text-3xl xl:text-5xl font-black tracking-tighter uppercase">
            Motor de <span className="text-primary/50">Categorias</span>
          </h1>
          <p className="text-muted-foreground font-medium uppercase text-xs tracking-[0.2em] opacity-70">
            Mapeamento estrutural de fluxos de capital
          </p>
        </div>
        <Dialog open={isDialogOpen} onOpenChange={(open) => {
          setIsDialogOpen(open);
          if (!open) resetForm();
        }}>
          <div className="flex flex-col gap-3 sm:flex-row">
            <Button
              variant="outline"
              size="lg"
              disabled={isSeedingStarterCategories}
              className="glass border-slate-200 hover:bg-slate-100 text-slate-700 font-black uppercase tracking-widest px-8 h-14 rounded-2xl shadow-xl transition-all"
              onClick={handleCreateStarterCategories}
            >
              <Tag className="mr-2 h-5 w-5" />
              {isSeedingStarterCategories ? 'Criando Sugestões...' : 'Adicionar Sugestões'}
            </Button>
            <DialogTrigger asChild>
              <Button variant="outline" size="lg" className="glass border-primary/20 hover:bg-primary/20 text-primary font-black uppercase tracking-widest px-8 h-14 rounded-2xl shadow-xl shadow-primary/10 transition-all hover:scale-105 active:scale-95">
                <Plus className="mr-2 h-5 w-5" />
                Definir Segmento
              </Button>
            </DialogTrigger>
          </div>
          <DialogContent className="glass-card premium-card border-white/10 backdrop-blur-3xl rounded-3xl p-0 overflow-hidden">
            <div className="p-8 space-y-6">
              <DialogHeader>
                <DialogTitle className="text-2xl font-black tracking-tighter uppercase leading-tight">
                  {editingCategory ? 'Modificar Categoria' : 'Registrar Nova Categoria'}
                </DialogTitle>
                <DialogDescription className="text-[10px] uppercase tracking-widest font-bold opacity-60">
                  Personalizando a lógica de classificação para análises precisas
                </DialogDescription>
              </DialogHeader>
              <form onSubmit={handleSubmit} className="space-y-6">
                <div className="space-y-4">
                  <div className="space-y-2">
                    <Label htmlFor="name" className="text-[10px] uppercase tracking-widest font-black ml-1 opacity-50">Nome do Segmento *</Label>
                    <Input
                      id="name"
                      className="glass-card border-white/5 h-12 rounded-xl px-4 font-bold"
                      placeholder="Ex: Iniciativas de P&D"
                      value={formData.name}
                      onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                      required
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="type" className="text-[10px] uppercase tracking-widest font-black ml-1 opacity-50">Tipo Operacional *</Label>
                    <Select
                      value={formData.type}
                      onValueChange={(value: 'income' | 'expense') => setFormData({ ...formData, type: value })}
                    >
                      <SelectTrigger className="glass-card border-white/5 h-12 rounded-xl px-4 font-bold">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="income">Receita (Entrada de Ativos)</SelectItem>
                        <SelectItem value="expense">Despesa (Saída de Capital)</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="space-y-2">
                    <Label className="text-[10px] uppercase tracking-widest font-black ml-1 opacity-50">Identificador Visual</Label>
                    <div className="grid grid-cols-8 gap-2 max-h-40 overflow-y-auto p-4 glass-card border-white/5 rounded-2xl">
                      {EMOJI_OPTIONS.map((emoji) => (
                        <button
                          key={emoji}
                          type="button"
                          onClick={() => setFormData({ ...formData, icon: emoji })}
                          className={`text-xl p-2 rounded-xl hover:bg-white/10 transition-all ${formData.icon === emoji ? 'bg-primary/20 ring-1 ring-primary/50' : ''
                            }`}
                        >
                          {emoji}
                        </button>
                      ))}
                    </div>
                  </div>
                  <div className="space-y-2">
                    <Label className="text-[10px] uppercase tracking-widest font-black ml-1 opacity-50">Atribuição de Cor</Label>
                    <div className="grid grid-cols-4 gap-2">
                      {COLOR_OPTIONS.map((color) => (
                        <button
                          key={color.value}
                          type="button"
                          onClick={() => setFormData({ ...formData, color: color.value })}
                          className={`p-3 rounded-xl border-2 transition-all flex items-center justify-center ${formData.color === color.value ? 'border-white/40 scale-95 shadow-lg' : 'border-transparent opacity-60 hover:opacity-100'
                            }`}
                          style={{ backgroundColor: color.value }}
                        >
                          <div className="size-2 rounded-full bg-white/20" />
                        </button>
                      ))}
                    </div>
                  </div>
                </div>
                <div className="flex justify-end gap-3 pt-4">
                  <Button type="button" variant="ghost" className="rounded-xl px-6 font-bold uppercase text-[10px] tracking-widest" onClick={() => setIsDialogOpen(false)}>
                    Cancelar
                  </Button>
                  <Button variant="outline" type="submit" className="glass border-primary/20 text-primary font-black uppercase tracking-widest px-8 h-12 rounded-xl">
                    {editingCategory ? 'Salvar Alterações' : 'Criar Categoria'}
                  </Button>
                </div>
              </form>
            </div>
          </DialogContent>
        </Dialog>
      </div>

      <div className="space-y-6">
        <Tabs defaultValue="all" className="w-full">
          <TabsList className="bg-white/5 p-1 rounded-2xl border border-white/5 mb-8">
            <TabsTrigger value="all" className="flex-1 rounded-xl font-black uppercase text-[10px] tracking-widest data-[state=active]:bg-white/10 data-[state=active]:text-primary">Global</TabsTrigger>
            <TabsTrigger value="income" className="flex-1 rounded-xl font-black uppercase text-[10px] tracking-widest data-[state=active]:bg-white/10 data-[state=active]:text-primary">Entradas</TabsTrigger>
            <TabsTrigger value="expense" className="flex-1 rounded-xl font-black uppercase text-[10px] tracking-widest data-[state=active]:bg-white/10 data-[state=active]:text-primary">Saídas</TabsTrigger>
            <TabsTrigger value="custom" className="flex-1 rounded-xl font-black uppercase text-[10px] tracking-widest data-[state=active]:bg-white/10 data-[state=active]:text-primary">Personalizadas</TabsTrigger>
          </TabsList>

          <div className="glass-card premium-card border-white/10 rounded-3xl overflow-hidden shadow-2xl">
            <TabsContent value="all" className="divide-y divide-white/5 mt-0 outline-none">
              {categories.map(category => (
                <CategoryCard key={category.id} category={category} />
              ))}
            </TabsContent>

            <TabsContent value="income" className="divide-y divide-white/5 mt-0 outline-none">
              {incomeCategories.map(category => (
                <CategoryCard key={category.id} category={category} />
              ))}
            </TabsContent>

            <TabsContent value="expense" className="divide-y divide-white/5 mt-0 outline-none">
              {expenseCategories.map(category => (
                <CategoryCard key={category.id} category={category} />
              ))}
            </TabsContent>

            <TabsContent value="custom" className="divide-y divide-white/5 mt-0 outline-none">
              {userCategories.length > 0 ? (
                userCategories.map(category => (
                  <CategoryCard key={category.id} category={category} />
                ))
              ) : (
                <div className="flex flex-col items-center justify-center py-24 px-4 bg-white/[0.02]">
                  <div className="relative group mb-6">
                    <div className="absolute inset-0 bg-primary/20 blur-3xl rounded-full transition-all group-hover:bg-primary/30" />
                    <Tag className="h-16 w-16 text-primary relative z-10 opacity-40 group-hover:opacity-60 transition-all group-hover:scale-110" />
                  </div>
                  <p className="text-xl font-black uppercase tracking-tighter mb-2">Sem Segmentos Personalizados</p>
                  <p className="text-sm text-muted-foreground font-medium uppercase tracking-widest opacity-50 max-w-xs text-center">
                    Nenhuma lógica de taxonomia especializada foi estabelecida ainda.
                  </p>
                  <div className="mt-5 flex flex-col gap-2 sm:flex-row">
                    <Button
                      disabled={isSeedingStarterCategories}
                      className="bg-blue-600 hover:bg-blue-700 text-white font-black text-[10px] uppercase tracking-widest h-10 px-6 rounded-lg"
                      onClick={handleCreateStarterCategories}
                    >
                      <Tag className="mr-2 h-4 w-4" />
                      {isSeedingStarterCategories ? 'Criando...' : 'Usar Sugestões'}
                    </Button>
                    <Button
                      variant="outline"
                      className="font-black text-[10px] uppercase tracking-widest h-10 px-6 rounded-lg"
                      onClick={() => setIsDialogOpen(true)}
                    >
                      <Plus className="mr-2 h-4 w-4" />
                      Criar Manualmente
                    </Button>
                  </div>
                </div>
              )}
            </TabsContent>
          </div>
        </Tabs>
      </div>
    </div>
  );
}
