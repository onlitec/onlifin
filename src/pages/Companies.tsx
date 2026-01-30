/**
 * Página de gerenciamento de empresas (PJ)
 */

import * as React from 'react';
import { useState, useCallback, useEffect } from 'react';
import {
    Building2,
    Plus,
    Search,
    LayoutGrid,
    List,
    RefreshCw,
    Wallet,
    ArrowUpRight,
    ArrowDownRight,
    AlertTriangle
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Alert,
    AlertDescription,
    AlertTitle,
} from '@/components/ui/alert';
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
import { cn } from '@/lib/utils';
import { useCompany } from '@/contexts/CompanyContext';
import { companyService } from '@/services/companyService';
import { CompanyCard, CompanyCardSkeleton, CompanyDialog } from '@/components/company';
import type { Company, CreateCompanyDTO, UpdateCompanyDTO, CompanyWithMetrics } from '@/types/company';

// Limite máximo de empresas por usuário
const MAX_COMPANIES = 50;

/**
 * Formata valor monetário
 */
const formatCurrency = (value: number): string => {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value);
};

export default function CompaniesPage() {
    const { toast } = useToast();
    const {
        companies,
        selectedCompany,
        isLoadingCompanies,
        createCompany,
        updateCompany,
        deleteCompany,
        setAsDefault,
        selectCompany,
        refreshCompanies,
    } = useCompany();

    // Estado local
    const [searchTerm, setSearchTerm] = useState('');
    const [viewMode, setViewMode] = useState<'grid' | 'list'>('grid');
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [editingCompany, setEditingCompany] = useState<Company | null>(null);
    const [deletingCompany, setDeletingCompany] = useState<Company | null>(null);
    const [isRefreshing, setIsRefreshing] = useState(false);
    const [companiesWithMetrics, setCompaniesWithMetrics] = useState<CompanyWithMetrics[]>([]);
    const [isLoadingMetrics, setIsLoadingMetrics] = useState(false);

    // Carregar métricas das empresas
    useEffect(() => {
        const loadMetrics = async () => {
            if (companies.length === 0) return;

            setIsLoadingMetrics(true);
            try {
                const data = await companyService.getWithMetrics();
                setCompaniesWithMetrics(data);
            } catch (error) {
                console.error('Erro ao carregar métricas:', error);
            } finally {
                setIsLoadingMetrics(false);
            }
        };

        loadMetrics();
    }, [companies]);

    // Filtrar empresas pelo termo de busca
    const filteredCompanies = companies.filter(company => {
        if (!searchTerm) return true;
        const term = searchTerm.toLowerCase();
        return (
            company.razao_social.toLowerCase().includes(term) ||
            company.nome_fantasia?.toLowerCase().includes(term) ||
            company.cnpj.includes(term)
        );
    });

    // Obter empresa com métricas
    const getCompanyWithMetrics = (companyId: string): CompanyWithMetrics | undefined => {
        return companiesWithMetrics.find(c => c.id === companyId);
    };

    // Handlers
    const handleAddCompany = useCallback(() => {
        if (companies.length >= MAX_COMPANIES) {
            toast({
                title: 'Limite atingido',
                description: `Você atingiu o limite máximo de ${MAX_COMPANIES} empresas.`,
                variant: 'destructive',
            });
            return;
        }
        setEditingCompany(null);
        setIsDialogOpen(true);
    }, [companies.length, toast]);

    const handleEditCompany = useCallback((company: Company) => {
        setEditingCompany(company);
        setIsDialogOpen(true);
    }, []);

    const handleDeleteCompany = useCallback((company: Company) => {
        setDeletingCompany(company);
    }, []);

    const handleSetDefault = useCallback(async (company: Company) => {
        try {
            await setAsDefault(company.id);
            toast({
                title: 'Empresa padrão definida',
                description: `${company.nome_fantasia || company.razao_social} agora é sua empresa padrão.`,
            });
        } catch (error) {
            toast({
                title: 'Erro ao definir empresa padrão',
                description: error instanceof Error ? error.message : 'Tente novamente',
                variant: 'destructive',
            });
        }
    }, [setAsDefault, toast]);

    const handleSelectCompany = useCallback((company: Company) => {
        selectCompany(company.id);
        toast({
            title: 'Empresa selecionada',
            description: `Você está visualizando ${company.nome_fantasia || company.razao_social}`,
        });
    }, [selectCompany, toast]);

    const handleSaveCompany = useCallback(async (data: CreateCompanyDTO | UpdateCompanyDTO) => {
        if (editingCompany) {
            await updateCompany(editingCompany.id, data as UpdateCompanyDTO);
        } else {
            await createCompany(data as CreateCompanyDTO);
        }
    }, [createCompany, editingCompany, updateCompany]);

    const confirmDelete = useCallback(async () => {
        if (!deletingCompany) return;

        try {
            await deleteCompany(deletingCompany.id);
            toast({
                title: 'Empresa excluída',
                description: `${deletingCompany.nome_fantasia || deletingCompany.razao_social} foi removida.`,
            });
        } catch (error) {
            toast({
                title: 'Erro ao excluir',
                description: error instanceof Error ? error.message : 'Não foi possível excluir a empresa',
                variant: 'destructive',
            });
        } finally {
            setDeletingCompany(null);
        }
    }, [deleteCompany, deletingCompany, toast]);

    const handleRefresh = useCallback(async () => {
        setIsRefreshing(true);
        try {
            await refreshCompanies();
            toast({
                title: 'Lista atualizada',
                description: 'As empresas foram atualizadas.',
            });
        } finally {
            setIsRefreshing(false);
        }
    }, [refreshCompanies, toast]);

    // Calcular totais
    const totals = React.useMemo(() => {
        const metrics = companiesWithMetrics.length > 0 ? companiesWithMetrics : [];
        return {
            totalCompanies: companies.length,
            totalBalance: metrics.reduce((sum, c) => sum + (c.total_balance || 0), 0),
            totalIncome: metrics.reduce((sum, c) => sum + (c.total_income || 0), 0),
            totalExpense: metrics.reduce((sum, c) => sum + (c.total_expense || 0), 0),
        };
    }, [companies, companiesWithMetrics]);

    return (
        <div className="container mx-auto p-6 space-y-6">
            {/* Header */}
            <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight flex items-center gap-2">
                        <Building2 className="h-8 w-8" />
                        Minhas Empresas
                    </h1>
                    <p className="text-muted-foreground mt-1">
                        Gerencie suas empresas e visualize dados consolidados
                    </p>
                </div>

                <div className="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="icon"
                        onClick={handleRefresh}
                        disabled={isRefreshing}
                    >
                        <RefreshCw className={cn("h-4 w-4", isRefreshing && "animate-spin")} />
                    </Button>
                    <Button onClick={handleAddCompany}>
                        <Plus className="h-4 w-4 mr-2" />
                        Nova Empresa
                    </Button>
                </div>
            </div>

            {/* Cards de Resumo */}
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">
                            Total de Empresas
                        </CardTitle>
                        <Building2 className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">{totals.totalCompanies}</div>
                        <p className="text-xs text-muted-foreground">
                            de {MAX_COMPANIES} disponíveis
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">
                            Saldo Total
                        </CardTitle>
                        <Wallet className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        {isLoadingMetrics ? (
                            <Skeleton className="h-8 w-32" />
                        ) : (
                            <div className={cn(
                                "text-2xl font-bold",
                                totals.totalBalance >= 0 ? "text-green-600" : "text-red-600"
                            )}>
                                {formatCurrency(totals.totalBalance)}
                            </div>
                        )}
                        <p className="text-xs text-muted-foreground">
                            todas as empresas
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">
                            Receitas Totais
                        </CardTitle>
                        <ArrowUpRight className="h-4 w-4 text-green-600" />
                    </CardHeader>
                    <CardContent>
                        {isLoadingMetrics ? (
                            <Skeleton className="h-8 w-32" />
                        ) : (
                            <div className="text-2xl font-bold text-green-600">
                                {formatCurrency(totals.totalIncome)}
                            </div>
                        )}
                        <p className="text-xs text-muted-foreground">
                            todas as empresas
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">
                            Despesas Totais
                        </CardTitle>
                        <ArrowDownRight className="h-4 w-4 text-red-600" />
                    </CardHeader>
                    <CardContent>
                        {isLoadingMetrics ? (
                            <Skeleton className="h-8 w-32" />
                        ) : (
                            <div className="text-2xl font-bold text-red-600">
                                {formatCurrency(totals.totalExpense)}
                            </div>
                        )}
                        <p className="text-xs text-muted-foreground">
                            todas as empresas
                        </p>
                    </CardContent>
                </Card>
            </div>

            {/* Barra de Ferramentas */}
            <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div className="relative flex-1 max-w-md">
                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input
                        placeholder="Buscar por nome, fantasia ou CNPJ..."
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        className="pl-10"
                    />
                </div>

                <div className="flex items-center gap-2">
                    <div className="flex items-center border rounded-lg">
                        <Button
                            variant={viewMode === 'grid' ? 'secondary' : 'ghost'}
                            size="icon"
                            className="h-9 w-9 rounded-r-none"
                            onClick={() => setViewMode('grid')}
                        >
                            <LayoutGrid className="h-4 w-4" />
                        </Button>
                        <Button
                            variant={viewMode === 'list' ? 'secondary' : 'ghost'}
                            size="icon"
                            className="h-9 w-9 rounded-l-none"
                            onClick={() => setViewMode('list')}
                        >
                            <List className="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </div>

            {/* Limite atingido */}
            {companies.length >= MAX_COMPANIES && (
                <Alert variant="destructive">
                    <AlertTriangle className="h-4 w-4" />
                    <AlertTitle>Limite de empresas atingido</AlertTitle>
                    <AlertDescription>
                        Você atingiu o limite máximo de {MAX_COMPANIES} empresas.
                        Para adicionar novas empresas, exclua algumas existentes.
                    </AlertDescription>
                </Alert>
            )}

            {/* Lista de Empresas */}
            {isLoadingCompanies ? (
                <div className={cn(
                    viewMode === 'grid'
                        ? "grid gap-4 md:grid-cols-2 lg:grid-cols-3"
                        : "space-y-4"
                )}>
                    {Array.from({ length: 6 }).map((_, i) => (
                        <CompanyCardSkeleton key={i} />
                    ))}
                </div>
            ) : filteredCompanies.length === 0 ? (
                <Card className="py-12">
                    <CardContent className="flex flex-col items-center justify-center text-center">
                        <Building2 className="h-12 w-12 text-muted-foreground mb-4" />
                        {searchTerm ? (
                            <>
                                <h3 className="text-lg font-semibold">Nenhuma empresa encontrada</h3>
                                <p className="text-muted-foreground mt-1">
                                    Nenhuma empresa corresponde a "{searchTerm}"
                                </p>
                                <Button
                                    variant="outline"
                                    className="mt-4"
                                    onClick={() => setSearchTerm('')}
                                >
                                    Limpar busca
                                </Button>
                            </>
                        ) : (
                            <>
                                <h3 className="text-lg font-semibold">Nenhuma empresa cadastrada</h3>
                                <p className="text-muted-foreground mt-1">
                                    Adicione sua primeira empresa para começar
                                </p>
                                <Button className="mt-4" onClick={handleAddCompany}>
                                    <Plus className="h-4 w-4 mr-2" />
                                    Nova Empresa
                                </Button>
                            </>
                        )}
                    </CardContent>
                </Card>
            ) : (
                <div className={cn(
                    viewMode === 'grid'
                        ? "grid gap-4 md:grid-cols-2 lg:grid-cols-3"
                        : "space-y-4"
                )}>
                    {filteredCompanies.map((company) => {
                        const companyWithMetrics = getCompanyWithMetrics(company.id);
                        return (
                            <CompanyCard
                                key={company.id}
                                company={companyWithMetrics || company}
                                isSelected={selectedCompany?.id === company.id}
                                showMetrics={true}
                                onEdit={handleEditCompany}
                                onDelete={handleDeleteCompany}
                                onSetDefault={handleSetDefault}
                                onSelect={handleSelectCompany}
                            />
                        );
                    })}
                </div>
            )}

            {/* Dialog de Empresa */}
            <CompanyDialog
                open={isDialogOpen}
                onOpenChange={setIsDialogOpen}
                company={editingCompany}
                onSave={handleSaveCompany}
            />

            {/* Dialog de Confirmação de Exclusão */}
            <AlertDialog open={!!deletingCompany} onOpenChange={() => setDeletingCompany(null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Excluir empresa?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Você está prestes a excluir a empresa{' '}
                            <strong>
                                {deletingCompany?.nome_fantasia || deletingCompany?.razao_social}
                            </strong>
                            . Esta ação não pode ser desfeita.
                            <br /><br />
                            Todos os dados associados a esta empresa (contas, transações, categorias)
                            serão mantidos inativos, mas não poderão ser acessados.
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
