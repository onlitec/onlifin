/**
 * Página de gerenciamento de pessoas (PF)
 */

import { useState, useCallback } from 'react';
import {
    User,
    Plus,
    Search,
    Users,
    Trash2,
    Edit
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
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
import { useToast } from '@/hooks/use-toast';
import { usePerson } from '@/contexts/PersonContext';
import { PersonDialog } from '@/components/person/PersonDialog';
import type { Person, CreatePersonDTO, UpdatePersonDTO } from '@/types/person';
import { Badge } from '@/components/ui/badge';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/components/ui/tooltip";

export default function PeoplePage() {
    const { toast } = useToast();
    const {
        people,
        isLoadingPeople,
        createPerson,
        updatePerson,
        deletePerson,
        updateSettings,
        settings
    } = usePerson();

    // Estado local
    const [searchTerm, setSearchTerm] = useState('');
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [editingPerson, setEditingPerson] = useState<Person | null>(null);
    const [deletingPerson, setDeletingPerson] = useState<Person | null>(null);

    // Filtrar pessoas pelo termo de busca
    const filteredPeople = people.filter(person => {
        if (!searchTerm) return true;
        const term = searchTerm.toLowerCase();
        return (
            person.name.toLowerCase().includes(term) ||
            person.email?.toLowerCase().includes(term) ||
            person.cpf?.includes(term)
        );
    });

    const handleAddPerson = useCallback(() => {
        setEditingPerson(null);
        setIsDialogOpen(true);
    }, []);

    const handleEditPerson = useCallback((person: Person) => {
        setEditingPerson(person);
        setIsDialogOpen(true);
    }, []);

    const handleDeletePerson = useCallback((person: Person) => {
        setDeletingPerson(person);
    }, []);

    const handleSavePerson = useCallback(async (data: CreatePersonDTO | UpdatePersonDTO) => {
        if (editingPerson) {
            await updatePerson(editingPerson.id, data as UpdatePersonDTO);
            toast({
                title: 'Pessoa atualizada',
                description: 'Os dados foram salvos com sucesso.',
            });
        } else {
            await createPerson(data as CreatePersonDTO);
            toast({
                title: 'Pessoa criada',
                description: 'Novo membro adicionado com sucesso.',
            });
        }
    }, [createPerson, editingPerson, toast, updatePerson]);

    const confirmDelete = useCallback(async () => {
        if (!deletingPerson) return;

        try {
            // Se for a pessoa virtual Principal (Geral)
            if (deletingPerson.id === 'titular-virtual') {
                await updateSettings({ hide_titular: true });
                toast({
                    title: 'Opção desabilitada',
                    description: 'A visualização Geral foi removida do seletor.',
                });
            } else {
                await deletePerson(deletingPerson.id);
                toast({
                    title: 'Pessoa excluída',
                    description: `${deletingPerson.name} foi removida com sucesso.`,
                });
            }
        } catch (error: any) {
            console.error('Erro detalhado ao excluir:', error);
            toast({
                title: 'Não foi possível excluir',
                description: error.message || 'O banco de dados impediu a exclusão. Verifique se existem vínculos pendentes.',
                variant: 'destructive',
            });
        } finally {
            setDeletingPerson(null);
        }
    }, [deletePerson, deletingPerson, toast, updateSettings]);

    return (
        <div className="container mx-auto p-6 space-y-6">
            {/* Header */}
            <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight flex items-center gap-2">
                        <Users className="h-8 w-8" />
                        Membros da Família
                    </h1>
                    <p className="text-muted-foreground mt-1">
                        Gerencie as pessoas que fazem parte das suas finanças pessoais.
                    </p>
                </div>

                <div className="flex items-center gap-2">
                    <Button onClick={handleAddPerson}>
                        <Plus className="h-4 w-4 mr-2" />
                        Nova Pessoa
                    </Button>
                </div>
            </div>

            {/* Barra de Busca */}
            <div className="flex items-center space-x-2">
                <div className="relative flex-1 max-w-md">
                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input
                        placeholder="Buscar por nome..."
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        className="pl-10"
                    />
                </div>
            </div>

            {/* Lista Cards */}
            {isLoadingPeople ? (
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {Array.from({ length: 3 }).map((_, i) => (
                        <Skeleton key={i} className="h-32 w-full" />
                    ))}
                </div>
            ) : filteredPeople.length === 0 ? (
                <Card className="py-12">
                    <CardContent className="flex flex-col items-center justify-center text-center">
                        <User className="h-12 w-12 text-muted-foreground mb-4" />
                        <h3 className="text-lg font-semibold">Nenhuma pessoa encontrada</h3>
                        <p className="text-muted-foreground mt-1">
                            {searchTerm ? `Nenhum resultado para "${searchTerm}"` : "Adicione membros da família para começar."}
                        </p>
                    </CardContent>
                </Card>
            ) : (
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {/* Card Virtual para Principal (Geral) */}
                    {!settings.hide_titular && (
                        <Card className="border-primary/20 bg-primary/5">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium flex items-center gap-2">
                                    <User className="h-3 w-3 text-primary" />
                                    PRINCIPAL (GERAL)
                                </CardTitle>
                                <Badge variant="default" className="bg-primary/20 text-primary border-none text-[10px]">SISTEMA</Badge>
                            </CardHeader>
                            <CardContent>
                                <div className="text-xs text-muted-foreground mb-4">
                                    Visualização consolidada de todos os membros.
                                </div>
                                <div className="flex justify-end gap-2">
                                    <TooltipProvider>
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <span>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="text-destructive hover:text-destructive"
                                                        onClick={() => setDeletingPerson({ id: 'titular-virtual', name: 'Principal (Geral)', is_default: false } as any)}
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </span>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>Remover esta opção do seletor.</p>
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {filteredPeople.map((person) => (
                        <Card key={person.id}>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium flex items-center gap-2">
                                    <div
                                        className="h-3 w-3 rounded-full border border-white/20 shadow-sm"
                                        style={{ backgroundColor: person.color || '#10b981' }}
                                    />
                                    {person.name}
                                </CardTitle>
                                {person.is_default && (
                                    <Badge variant="secondary">Padrão</Badge>
                                )}
                            </CardHeader>
                            <CardContent>
                                <div className="text-xs text-muted-foreground mb-4">
                                    {person.email || 'Sem e-mail'}
                                </div>
                                <div className="flex justify-end gap-2">
                                    <Button variant="ghost" size="icon" onClick={() => handleEditPerson(person)}>
                                        <Edit className="h-4 w-4" />
                                    </Button>

                                    <TooltipProvider>
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <span>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="text-destructive hover:text-destructive"
                                                        onClick={() => handleDeletePerson(person)}
                                                        disabled={people.length <= 1}
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </span>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                {people.length <= 1 ? (
                                                    <p>É necessário pelo menos um membro na família.</p>
                                                ) : person.is_default ? (
                                                    <p>Ao excluir o membro principal, outro será promovido automaticamente.</p>
                                                ) : (
                                                    <p>Excluir membro.</p>
                                                )}
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            )}

            <PersonDialog
                open={isDialogOpen}
                onOpenChange={setIsDialogOpen}
                person={editingPerson}
                onSave={handleSavePerson}
            />

            <AlertDialog open={!!deletingPerson} onOpenChange={() => setDeletingPerson(null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Excluir pessoa?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Tem certeza que deseja excluir <strong>{deletingPerson?.name}</strong>?
                            Isso não excluirá as transações associadas, mas removerá a associação a esta pessoa.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={confirmDelete}
                            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        >
                            Excluir
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    );
}
