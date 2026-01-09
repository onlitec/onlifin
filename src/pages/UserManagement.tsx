import * as React from 'react';
import { profilesApi } from '@/db/api';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Badge } from '@/components/ui/badge';
import { useToast } from '@/hooks/use-toast';
import {
  Users,
  UserPlus,
  Trash2,
  Loader2,
  Mail,
  Phone,
  MapPin,
  Calendar,
  Edit,
  Check,
  KeyRound
} from 'lucide-react';
import type { Profile, UserStatus } from '@/types/types';
import { format } from 'date-fns';
import { ptBR } from 'date-fns/locale';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { supabase } from '@/db/client';

interface UserFormData {
  username: string;
  password: string;
  full_name: string;
  email: string;
  phone: string;
  whatsapp: string;
  document: string;
  birth_date: string;
  address: string;
  city: string;
  state: string;
  postal_code: string;
  role: 'user' | 'financeiro' | 'admin';
  status: UserStatus;
  admin_notes: string;
}

const initialFormData: UserFormData = {
  username: '',
  password: '',
  full_name: '',
  email: '',
  phone: '',
  whatsapp: '',
  document: '',
  birth_date: '',
  address: '',
  city: '',
  state: '',
  postal_code: '',
  role: 'user',
  status: 'active',
  admin_notes: ''
};

export default function UserManagement() {
  const [users, setUsers] = React.useState<Profile[]>([]);
  const [isLoading, setIsLoading] = React.useState(false);
  const [isCreating, setIsCreating] = React.useState(false);
  const [isDialogOpen, setIsDialogOpen] = React.useState(false);
  const [isEditDialogOpen, setIsEditDialogOpen] = React.useState(false);
  const [deleteUserId, setDeleteUserId] = React.useState<string | null>(null);
  const [editingUser, setEditingUser] = React.useState<Profile | null>(null);
  const [formData, setFormData] = React.useState<UserFormData>(initialFormData);
  const [searchTerm, setSearchTerm] = React.useState('');
  const [filterRole, setFilterRole] = React.useState<string>('all');
  const [filterStatus, setFilterStatus] = React.useState<string>('all');
  const { toast } = useToast();

  React.useEffect(() => {
    loadUsers();
  }, []);

  const loadUsers = async () => {
    setIsLoading(true);
    try {
      const data = await profilesApi.getAllProfiles();
      setUsers(data);
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao carregar usuários',
        variant: 'destructive'
      });
    } finally {
      setIsLoading(false);
    }
  };

  const handleCreateUser = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!formData.username || !formData.password) {
      toast({
        title: 'Erro',
        description: 'Preencha todos os campos obrigatórios',
        variant: 'destructive'
      });
      return;
    }

    if (!/^[a-zA-Z0-9._-]+$/.test(formData.username)) {
      toast({
        title: 'Erro',
        description: 'Nome de usuário deve conter apenas letras, números, underscore (_), ponto (.) e hífen (-)',
        variant: 'destructive'
      });
      return;
    }

    if (formData.password.length < 6) {
      toast({
        title: 'Erro',
        description: 'A senha deve ter no mínimo 6 caracteres',
        variant: 'destructive'
      });
      return;
    }

    setIsCreating(true);
    try {
      const { userId, error } = await profilesApi.createUser(
        formData.username,
        formData.password,
        formData.role
      );

      if (error) throw error;

      // Update additional fields if provided
      if (userId) {
        const updateData: Partial<Profile> = {};
        if (formData.full_name) updateData.full_name = formData.full_name;
        if (formData.email) updateData.email = formData.email;
        if (formData.phone) updateData.phone = formData.phone;
        if (formData.whatsapp) updateData.whatsapp = formData.whatsapp;
        if (formData.document) updateData.document = formData.document;
        if (formData.birth_date) updateData.birth_date = formData.birth_date;
        if (formData.address) updateData.address = formData.address;
        if (formData.city) updateData.city = formData.city;
        if (formData.state) updateData.state = formData.state;
        if (formData.postal_code) updateData.postal_code = formData.postal_code;
        if (formData.admin_notes) updateData.admin_notes = formData.admin_notes;
        updateData.status = formData.status;

        if (Object.keys(updateData).length > 0) {
          await profilesApi.updateProfile(userId, updateData);
        }
      }

      toast({
        title: 'Sucesso',
        description: 'Usuário criado com sucesso'
      });

      setFormData(initialFormData);
      setIsDialogOpen(false);
      loadUsers();
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao criar usuário',
        variant: 'destructive'
      });
    } finally {
      setIsCreating(false);
    }
  };

  const handleEditUser = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingUser) return;

    try {
      const updateData: Partial<Profile> = {
        full_name: formData.full_name || null,
        email: formData.email || null,
        phone: formData.phone || null,
        whatsapp: formData.whatsapp || null,
        document: formData.document || null,
        birth_date: formData.birth_date || null,
        address: formData.address || null,
        city: formData.city || null,
        state: formData.state || null,
        postal_code: formData.postal_code || null,
        admin_notes: formData.admin_notes || null,
        role: formData.role,
        status: formData.status
      };

      await profilesApi.updateProfile(editingUser.id, updateData);

      toast({
        title: 'Sucesso',
        description: 'Usuário atualizado com sucesso'
      });

      setIsEditDialogOpen(false);
      setEditingUser(null);
      loadUsers();
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao atualizar usuário',
        variant: 'destructive'
      });
    }
  };

  const openEditDialog = (user: Profile) => {
    setEditingUser(user);
    setFormData({
      username: user.username,
      password: '',
      full_name: user.full_name || '',
      email: user.email || '',
      phone: user.phone || '',
      whatsapp: user.whatsapp || '',
      document: user.document || '',
      birth_date: user.birth_date || '',
      address: user.address || '',
      city: user.city || '',
      state: user.state || '',
      postal_code: user.postal_code || '',
      role: user.role,
      status: user.status,
      admin_notes: user.admin_notes || ''
    });
    setIsEditDialogOpen(true);
  };

  const handleResetPassword = async (userId: string) => {
    // Gerar uma senha aleatória segura: 10 caracteres, letras e números
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
    let newPassword = '';
    for (let i = 0; i < 12; i++) {
      newPassword += chars.charAt(Math.floor(Math.random() * chars.length));
    }

    if (!confirm(`Tem certeza que deseja resetar a senha deste usuário?\n\nA nova senha será gerada e o usuário deverá trocá-la no próximo login.`)) {
      return;
    }

    try {
      const { error } = await supabase.rpc('admin_reset_password', {
        p_user_id: userId,
        p_new_password: newPassword
      });

      if (error) throw error;

      toast({
        title: 'Senha Resetada!',
        description: (
          <div className="mt-2 p-2 bg-muted rounded border border-primary/20">
            <p className="text-xs text-muted-foreground mb-1">Nova senha provisória:</p>
            <code className="text-sm font-mono font-bold text-primary select-all">{newPassword}</code>
            <p className="text-[10px] text-muted-foreground mt-1 text-destructive font-medium">Copie agora, ela não será mostrada novamente!</p>
          </div>
        ),
        duration: 15000,
      });
    } catch (error: any) {
      toast({
        title: 'Erro ao resetar senha',
        description: error.message,
        variant: 'destructive'
      });
    }
  };

  const handleDeleteUser = async () => {
    if (!deleteUserId) return;

    try {
      await profilesApi.deleteUser(deleteUserId);
      toast({
        title: 'Sucesso',
        description: 'Usuário excluído com sucesso'
      });
      setDeleteUserId(null);
      loadUsers();
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao excluir usuário',
        variant: 'destructive'
      });
    }
  };

  const getStatusBadge = (status: UserStatus) => {
    const variants = {
      active: 'bg-green-500/10 text-green-500 border-green-500/20',
      suspended: 'bg-yellow-500/10 text-yellow-500 border-yellow-500/20',
      inactive: 'bg-gray-500/10 text-gray-500 border-gray-500/20'
    };
    const labels = {
      active: 'Ativo',
      suspended: 'Suspenso',
      inactive: 'Inativo'
    };
    return <Badge variant="outline" className={variants[status]}>{labels[status]}</Badge>;
  };

  const getRoleBadge = (role: string) => {
    const variants = {
      admin: 'bg-red-500/10 text-red-500 border-red-500/20',
      financeiro: 'bg-blue-500/10 text-blue-500 border-blue-500/20',
      user: 'bg-purple-500/10 text-purple-500 border-purple-500/20'
    };
    const labels = {
      admin: 'Admin',
      financeiro: 'Financeiro',
      user: 'Usuário'
    };
    return <Badge variant="outline" className={variants[role as keyof typeof variants]}>{labels[role as keyof typeof labels]}</Badge>;
  };

  const filteredUsers = users.filter(user => {
    const matchesSearch = searchTerm === '' ||
      user.username.toLowerCase().includes(searchTerm.toLowerCase()) ||
      user.full_name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      user.email?.toLowerCase().includes(searchTerm.toLowerCase());

    const matchesRole = filterRole === 'all' || user.role === filterRole;
    const matchesStatus = filterStatus === 'all' || user.status === filterStatus;

    return matchesSearch && matchesRole && matchesStatus;
  });

  return (
    <div className="container mx-auto p-6 space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold flex items-center gap-2">
            <Users className="h-8 w-8" />
            Gestão de Usuários
          </h1>
          <p className="text-muted-foreground mt-1">
            Sistema completo de gerenciamento de usuários
          </p>
        </div>
        <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
          <DialogTrigger asChild>
            <Button>
              <UserPlus className="mr-2 h-4 w-4" />
              Novo Usuário
            </Button>
          </DialogTrigger>
          <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
              <DialogTitle>Criar Novo Usuário</DialogTitle>
              <DialogDescription>
                Preencha os dados para criar um novo usuário no sistema
              </DialogDescription>
            </DialogHeader>
            <form onSubmit={handleCreateUser}>
              <Tabs defaultValue="basic" className="w-full">
                <TabsList className="grid w-full grid-cols-3">
                  <TabsTrigger value="basic">Básico</TabsTrigger>
                  <TabsTrigger value="contact">Contato</TabsTrigger>
                  <TabsTrigger value="address">Endereço</TabsTrigger>
                </TabsList>

                <TabsContent value="basic" className="space-y-4">
                  <div className="grid grid-cols-2 gap-4">
                    <div className="space-y-2">
                      <Label htmlFor="username">Nome de Usuário *</Label>
                      <Input
                        id="username"
                        value={formData.username}
                        onChange={(e) => setFormData({ ...formData, username: e.target.value })}
                        placeholder="usuario123"
                        disabled={isCreating}
                        required
                      />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="password">Senha *</Label>
                      <Input
                        id="password"
                        type="password"
                        value={formData.password}
                        onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                        placeholder="••••••••"
                        disabled={isCreating}
                        required
                      />
                    </div>
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="full_name">Nome Completo</Label>
                    <Input
                      id="full_name"
                      value={formData.full_name}
                      onChange={(e) => setFormData({ ...formData, full_name: e.target.value })}
                      placeholder="João Silva"
                      disabled={isCreating}
                    />
                  </div>
                  <div className="grid grid-cols-2 gap-4">
                    <div className="space-y-2">
                      <Label htmlFor="role">Papel</Label>
                      <Select
                        value={formData.role}
                        onValueChange={(value: any) => setFormData({ ...formData, role: value })}
                        disabled={isCreating}
                      >
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="user">Usuário</SelectItem>
                          <SelectItem value="financeiro">Financeiro</SelectItem>
                          <SelectItem value="admin">Administrador</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="status">Status</Label>
                      <Select
                        value={formData.status}
                        onValueChange={(value: any) => setFormData({ ...formData, status: value })}
                        disabled={isCreating}
                      >
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="active">Ativo</SelectItem>
                          <SelectItem value="suspended">Suspenso</SelectItem>
                          <SelectItem value="inactive">Inativo</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                  </div>
                  <div className="grid grid-cols-2 gap-4">
                    <div className="space-y-2">
                      <Label htmlFor="document">CPF/CNPJ</Label>
                      <Input
                        id="document"
                        value={formData.document}
                        onChange={(e) => setFormData({ ...formData, document: e.target.value })}
                        placeholder="000.000.000-00"
                        disabled={isCreating}
                      />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="birth_date">Data de Nascimento</Label>
                      <Input
                        id="birth_date"
                        type="date"
                        value={formData.birth_date}
                        onChange={(e) => setFormData({ ...formData, birth_date: e.target.value })}
                        disabled={isCreating}
                      />
                    </div>
                  </div>
                </TabsContent>

                <TabsContent value="contact" className="space-y-4">
                  <div className="space-y-2">
                    <Label htmlFor="email">Email</Label>
                    <Input
                      id="email"
                      type="email"
                      value={formData.email}
                      onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                      placeholder="usuario@exemplo.com"
                      disabled={isCreating}
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="phone">Telefone</Label>
                    <Input
                      id="phone"
                      value={formData.phone}
                      onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                      placeholder="(11) 98765-4321"
                      disabled={isCreating}
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="whatsapp">WhatsApp</Label>
                    <Input
                      id="whatsapp"
                      value={formData.whatsapp}
                      onChange={(e) => setFormData({ ...formData, whatsapp: e.target.value })}
                      placeholder="(11) 98765-4321"
                      disabled={isCreating}
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="admin_notes">Notas Administrativas</Label>
                    <Textarea
                      id="admin_notes"
                      value={formData.admin_notes}
                      onChange={(e) => setFormData({ ...formData, admin_notes: e.target.value })}
                      placeholder="Observações sobre o usuário..."
                      disabled={isCreating}
                      rows={4}
                    />
                  </div>
                </TabsContent>

                <TabsContent value="address" className="space-y-4">
                  <div className="space-y-2">
                    <Label htmlFor="postal_code">CEP</Label>
                    <Input
                      id="postal_code"
                      value={formData.postal_code}
                      onChange={(e) => setFormData({ ...formData, postal_code: e.target.value })}
                      placeholder="00000-000"
                      disabled={isCreating}
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="address">Endereço</Label>
                    <Input
                      id="address"
                      value={formData.address}
                      onChange={(e) => setFormData({ ...formData, address: e.target.value })}
                      placeholder="Rua, número, complemento"
                      disabled={isCreating}
                    />
                  </div>
                  <div className="grid grid-cols-2 gap-4">
                    <div className="space-y-2">
                      <Label htmlFor="city">Cidade</Label>
                      <Input
                        id="city"
                        value={formData.city}
                        onChange={(e) => setFormData({ ...formData, city: e.target.value })}
                        placeholder="São Paulo"
                        disabled={isCreating}
                      />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="state">Estado</Label>
                      <Input
                        id="state"
                        value={formData.state}
                        onChange={(e) => setFormData({ ...formData, state: e.target.value })}
                        placeholder="SP"
                        maxLength={2}
                        disabled={isCreating}
                      />
                    </div>
                  </div>
                </TabsContent>
              </Tabs>

              <DialogFooter className="mt-6">
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => setIsDialogOpen(false)}
                  disabled={isCreating}
                >
                  Cancelar
                </Button>
                <Button type="submit" disabled={isCreating}>
                  {isCreating && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                  Criar Usuário
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      {/* Filtros */}
      <Card>
        <CardHeader>
          <CardTitle>Filtros</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="space-y-2">
              <Label>Buscar</Label>
              <Input
                placeholder="Nome, usuário ou email..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label>Papel</Label>
              <Select value={filterRole} onValueChange={setFilterRole}>
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Todos</SelectItem>
                  <SelectItem value="user">Usuário</SelectItem>
                  <SelectItem value="financeiro">Financeiro</SelectItem>
                  <SelectItem value="admin">Administrador</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label>Status</Label>
              <Select value={filterStatus} onValueChange={setFilterStatus}>
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Todos</SelectItem>
                  <SelectItem value="active">Ativo</SelectItem>
                  <SelectItem value="suspended">Suspenso</SelectItem>
                  <SelectItem value="inactive">Inativo</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Lista de Usuários */}
      <Card>
        <CardHeader>
          <CardTitle>Usuários ({filteredUsers.length})</CardTitle>
          <CardDescription>
            Gerenciamento completo de usuários da plataforma
          </CardDescription>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="flex justify-center items-center py-8">
              <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
            </div>
          ) : filteredUsers.length === 0 ? (
            <div className="text-center py-8 text-muted-foreground">
              Nenhum usuário encontrado
            </div>
          ) : (
            <div className="space-y-3">
              {filteredUsers.map((user) => (
                <div
                  key={user.id}
                  className="flex items-start justify-between p-4 border rounded-lg hover:bg-muted/50 transition-colors"
                >
                  <div className="flex-1 space-y-2">
                    <div className="flex items-center gap-2">
                      <div className="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
                        <Users className="h-5 w-5 text-primary" />
                      </div>
                      <div>
                        <p className="font-medium">{user.username}</p>
                        {user.full_name && (
                          <p className="text-sm text-muted-foreground">{user.full_name}</p>
                        )}
                      </div>
                      <div className="flex gap-2 ml-3">
                        {getRoleBadge(user.role)}
                        {getStatusBadge(user.status)}
                      </div>
                    </div>

                    <div className="grid grid-cols-2 lg:grid-cols-4 gap-2 text-sm text-muted-foreground ml-12">
                      {user.email && (
                        <div className="flex items-center gap-1">
                          <Mail className="h-3 w-3" />
                          {user.email}
                        </div>
                      )}
                      {user.phone && (
                        <div className="flex items-center gap-1">
                          <Phone className="h-3 w-3" />
                          {user.phone}
                        </div>
                      )}
                      {(user.city || user.state) && (
                        <div className="flex items-center gap-1">
                          <MapPin className="h-3 w-3" />
                          {[user.city, user.state].filter(Boolean).join(', ')}
                        </div>
                      )}
                      <div className="flex items-center gap-1">
                        <Calendar className="h-3 w-3" />
                        {format(new Date(user.created_at), "dd/MM/yyyy", { locale: ptBR })}
                      </div>
                    </div>
                  </div>

                  <div className="flex items-center gap-2">
                    <Button
                      variant="ghost"
                      size="icon"
                      title="Resetar Senha"
                      className="text-amber-500 hover:text-amber-600 hover:bg-amber-50"
                      onClick={() => handleResetPassword(user.id)}
                    >
                      <KeyRound className="h-4 w-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => openEditDialog(user)}
                    >
                      <Edit className="h-4 w-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => setDeleteUserId(user.id)}
                      className="text-destructive hover:text-destructive hover:bg-destructive/10"
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>

      {/* Dialog de Edição */}
      <Dialog open={isEditDialogOpen} onOpenChange={setIsEditDialogOpen}>
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>Editar Usuário</DialogTitle>
            <DialogDescription>
              Atualize as informações do usuário
            </DialogDescription>
          </DialogHeader>
          <form onSubmit={handleEditUser}>
            <Tabs defaultValue="basic" className="w-full">
              <TabsList className="grid w-full grid-cols-3">
                <TabsTrigger value="basic">Básico</TabsTrigger>
                <TabsTrigger value="contact">Contato</TabsTrigger>
                <TabsTrigger value="address">Endereço</TabsTrigger>
              </TabsList>

              <TabsContent value="basic" className="space-y-4">
                <div className="space-y-2">
                  <Label>Nome de Usuário</Label>
                  <Input value={formData.username} disabled />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="edit_full_name">Nome Completo</Label>
                  <Input
                    id="edit_full_name"
                    value={formData.full_name}
                    onChange={(e) => setFormData({ ...formData, full_name: e.target.value })}
                    placeholder="João Silva"
                  />
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label htmlFor="edit_role">Papel</Label>
                    <Select
                      value={formData.role}
                      onValueChange={(value: any) => setFormData({ ...formData, role: value })}
                    >
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="user">Usuário</SelectItem>
                        <SelectItem value="financeiro">Financeiro</SelectItem>
                        <SelectItem value="admin">Administrador</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="edit_status">Status</Label>
                    <Select
                      value={formData.status}
                      onValueChange={(value: any) => setFormData({ ...formData, status: value })}
                    >
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="active">Ativo</SelectItem>
                        <SelectItem value="suspended">Suspenso</SelectItem>
                        <SelectItem value="inactive">Inativo</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label htmlFor="edit_document">CPF/CNPJ</Label>
                    <Input
                      id="edit_document"
                      value={formData.document}
                      onChange={(e) => setFormData({ ...formData, document: e.target.value })}
                      placeholder="000.000.000-00"
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="edit_birth_date">Data de Nascimento</Label>
                    <Input
                      id="edit_birth_date"
                      type="date"
                      value={formData.birth_date}
                      onChange={(e) => setFormData({ ...formData, birth_date: e.target.value })}
                    />
                  </div>
                </div>
              </TabsContent>

              <TabsContent value="contact" className="space-y-4">
                <div className="space-y-2">
                  <Label htmlFor="edit_email">Email</Label>
                  <Input
                    id="edit_email"
                    type="email"
                    value={formData.email}
                    onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                    placeholder="usuario@exemplo.com"
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="edit_phone">Telefone</Label>
                  <Input
                    id="edit_phone"
                    value={formData.phone}
                    onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                    placeholder="(11) 98765-4321"
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="edit_whatsapp">WhatsApp</Label>
                  <Input
                    id="edit_whatsapp"
                    value={formData.whatsapp}
                    onChange={(e) => setFormData({ ...formData, whatsapp: e.target.value })}
                    placeholder="(11) 98765-4321"
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="edit_admin_notes">Notas Administrativas</Label>
                  <Textarea
                    id="edit_admin_notes"
                    value={formData.admin_notes}
                    onChange={(e) => setFormData({ ...formData, admin_notes: e.target.value })}
                    placeholder="Observações sobre o usuário..."
                    rows={4}
                  />
                </div>
              </TabsContent>

              <TabsContent value="address" className="space-y-4">
                <div className="space-y-2">
                  <Label htmlFor="edit_postal_code">CEP</Label>
                  <Input
                    id="edit_postal_code"
                    value={formData.postal_code}
                    onChange={(e) => setFormData({ ...formData, postal_code: e.target.value })}
                    placeholder="00000-000"
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="edit_address">Endereço</Label>
                  <Input
                    id="edit_address"
                    value={formData.address}
                    onChange={(e) => setFormData({ ...formData, address: e.target.value })}
                    placeholder="Rua, número, complemento"
                  />
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label htmlFor="edit_city">Cidade</Label>
                    <Input
                      id="edit_city"
                      value={formData.city}
                      onChange={(e) => setFormData({ ...formData, city: e.target.value })}
                      placeholder="São Paulo"
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="edit_state">Estado</Label>
                    <Input
                      id="edit_state"
                      value={formData.state}
                      onChange={(e) => setFormData({ ...formData, state: e.target.value })}
                      placeholder="SP"
                      maxLength={2}
                    />
                  </div>
                </div>
              </TabsContent>
            </Tabs>

            <DialogFooter className="mt-6">
              <Button
                type="button"
                variant="outline"
                onClick={() => setIsEditDialogOpen(false)}
              >
                Cancelar
              </Button>
              <Button type="submit">
                <Check className="mr-2 h-4 w-4" />
                Salvar Alterações
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* Dialog de Confirmação de Exclusão */}
      <AlertDialog open={!!deleteUserId} onOpenChange={() => setDeleteUserId(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Confirmar Exclusão</AlertDialogTitle>
            <AlertDialogDescription>
              Tem certeza que deseja excluir este usuário? Esta ação não pode ser desfeita e todos os dados relacionados serão removidos.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancelar</AlertDialogCancel>
            <AlertDialogAction onClick={handleDeleteUser} className="bg-destructive hover:bg-destructive/90">
              Excluir
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
