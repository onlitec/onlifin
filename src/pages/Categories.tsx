import * as React from 'react';
import { supabase } from '@/db/client';
import { categoriesApi } from '@/db/api';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useToast } from '@/hooks/use-toast';
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

export default function Categories() {
  const [categories, setCategories] = React.useState<Category[]>([]);
  const [isDialogOpen, setIsDialogOpen] = React.useState(false);
  const [editingCategory, setEditingCategory] = React.useState<Category | null>(null);
  const [formData, setFormData] = React.useState({
    name: '',
    type: 'expense' as 'income' | 'expense',
    icon: '💰',
    color: '#27AE60'
  });
  const { toast } = useToast();

  React.useEffect(() => {
    loadCategories();
  }, []);

  const loadCategories = async () => {
    try {
      const data = await categoriesApi.getCategories();
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
      const { data: { user } } = await supabase.auth.getUser();
      if (!user) return;

      if (editingCategory) {
        await categoriesApi.updateCategory(editingCategory.id, formData);
        toast({ title: 'Sucesso', description: 'Categoria atualizada com sucesso' });
      } else {
        await categoriesApi.createCategory({
          ...formData,
          user_id: user.id
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
  const systemCategories = categories.filter(c => c.user_id === null);

  // Helper to render category icon (Lucide or emoji)
  const renderCategoryIcon = (iconName: string | null, color: string | null) => {
    if (!iconName) return <Tag className="w-6 h-6" style={{ color: color || undefined }} />;

    // Check if it's a Lucide icon name
    const LucideIcon = LUCIDE_ICONS[iconName];
    if (LucideIcon) {
      return <LucideIcon className="w-6 h-6" style={{ color: color || undefined }} />;
    }

    // Otherwise render as emoji
    return <span>{iconName}</span>;
  };

  const CategoryCard = ({ category }: { category: Category }) => (
    <Card className="shadow-sm hover:shadow-md transition-shadow">
      <CardContent className="flex items-center justify-between p-5">
        <div className="flex items-center gap-4 flex-1 min-w-0">
          <div
            className="w-12 h-12 rounded-full flex items-center justify-center text-2xl shrink-0"
            style={{ backgroundColor: `${category.color || '#6b7280'}20` }}
          >
            {renderCategoryIcon(category.icon, category.color)}
          </div>
          <div className="flex-1 min-w-0">
            <p className="font-semibold text-base truncate">{category.name}</p>
            <div className="flex items-center gap-2 mt-1">
              <span className="text-sm text-muted-foreground">
                {category.user_id === null ? 'Sistema' : 'Personalizada'}
              </span>
              <span className="text-xs px-2 py-0.5 rounded-full" style={{
                backgroundColor: `${category.color || '#6b7280'}15`,
                color: category.color || '#6b7280'
              }}>
                {category.type === 'income' ? 'Receita' : 'Despesa'}
              </span>
            </div>
          </div>
        </div>
        <div className="flex gap-2">
          <Button
            variant="outline"
            size="sm"
            onClick={() => openEditDialog(category)}
          >
            <Pencil className="h-4 w-4" />
          </Button>
          <Button
            variant="outline"
            size="sm"
            onClick={() => handleDelete(category)}
          >
            <Trash2 className="h-4 w-4" />
          </Button>
        </div>
      </CardContent>
    </Card>
  );

  return (
    <div className="w-full max-w-[1600px] mx-auto p-4 xl:p-8 space-y-6">
      {/* Header Section */}
      <div className="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-4 pb-2">
        <div>
          <h1 className="text-3xl xl:text-4xl font-bold tracking-tight">Categorias</h1>
          <p className="text-muted-foreground mt-1">Organize suas transações com categorias personalizadas</p>
        </div>
        <Dialog open={isDialogOpen} onOpenChange={(open) => {
          setIsDialogOpen(open);
          if (!open) resetForm();
        }}>
          <DialogTrigger asChild>
            <Button size="lg" className="w-full xl:w-auto">
              <Plus className="mr-2 h-5 w-5" />
              Nova Categoria
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>{editingCategory ? 'Editar Categoria' : 'Nova Categoria'}</DialogTitle>
              <DialogDescription>
                Crie categorias personalizadas para organizar suas transações
              </DialogDescription>
            </DialogHeader>
            <form onSubmit={handleSubmit}>
              <div className="space-y-4 py-4">
                <div className="space-y-2">
                  <Label htmlFor="name">Nome *</Label>
                  <Input
                    id="name"
                    placeholder="Ex: Streaming"
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="type">Tipo *</Label>
                  <Select
                    value={formData.type}
                    onValueChange={(value: 'income' | 'expense') => setFormData({ ...formData, type: value })}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="income">Receita</SelectItem>
                      <SelectItem value="expense">Despesa</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>Ícone</Label>
                  <div className="grid grid-cols-10 gap-2 max-h-40 overflow-y-auto p-2 border rounded-md">
                    {EMOJI_OPTIONS.map((emoji) => (
                      <button
                        key={emoji}
                        type="button"
                        onClick={() => setFormData({ ...formData, icon: emoji })}
                        className={`text-2xl p-2 rounded hover:bg-muted ${formData.icon === emoji ? 'bg-primary/20 ring-2 ring-primary' : ''
                          }`}
                      >
                        {emoji}
                      </button>
                    ))}
                  </div>
                </div>
                <div className="space-y-2">
                  <Label>Cor</Label>
                  <div className="grid grid-cols-4 gap-2">
                    {COLOR_OPTIONS.map((color) => (
                      <button
                        key={color.value}
                        type="button"
                        onClick={() => setFormData({ ...formData, color: color.value })}
                        className={`p-3 rounded border-2 ${formData.color === color.value ? 'border-primary' : 'border-transparent'
                          }`}
                        style={{ backgroundColor: color.value }}
                      >
                        <span className="text-white text-xs font-medium">{color.name}</span>
                      </button>
                    ))}
                  </div>
                </div>
              </div>
              <DialogFooter>
                <Button type="submit">{editingCategory ? 'Atualizar' : 'Criar'}</Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <Tabs defaultValue="all" className="w-full">
        <TabsList className="grid w-full grid-cols-4">
          <TabsTrigger value="all">Todas</TabsTrigger>
          <TabsTrigger value="income">Receitas</TabsTrigger>
          <TabsTrigger value="expense">Despesas</TabsTrigger>
          <TabsTrigger value="custom">Personalizadas</TabsTrigger>
        </TabsList>

        <TabsContent value="all" className="space-y-3 mt-6">
          {categories.map(category => (
            <CategoryCard key={category.id} category={category} />
          ))}
        </TabsContent>

        <TabsContent value="income" className="space-y-3 mt-6">
          {incomeCategories.map(category => (
            <CategoryCard key={category.id} category={category} />
          ))}
        </TabsContent>

        <TabsContent value="expense" className="space-y-3 mt-6">
          {expenseCategories.map(category => (
            <CategoryCard key={category.id} category={category} />
          ))}
        </TabsContent>

        <TabsContent value="custom" className="space-y-3 mt-6">
          {userCategories.length > 0 ? (
            userCategories.map(category => (
              <CategoryCard key={category.id} category={category} />
            ))
          ) : (
            <Card>
              <CardContent className="flex flex-col items-center justify-center py-12">
                <Tag className="h-12 w-12 text-muted-foreground mb-4" />
                <p className="text-lg font-medium mb-2">Nenhuma categoria personalizada</p>
                <p className="text-sm text-muted-foreground">
                  Crie suas próprias categorias para melhor organização
                </p>
              </CardContent>
            </Card>
          )}
        </TabsContent>
      </Tabs>
    </div>
  );
}
