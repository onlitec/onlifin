/**
 * Componente de seleção de empresa
 * 
 * Dropdown para selecionar a empresa ativa no contexto PJ.
 * Exibe nome fantasia ou razão social e CNPJ formatado.
 */

import * as React from 'react';
import { Building2, ChevronDown, Plus, Star, Check } from 'lucide-react';
import { useCompany } from '@/contexts/CompanyContext';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';

interface CompanySelectorProps {
    className?: string;
    onAddCompany?: () => void;
    showAddButton?: boolean;
    variant?: 'default' | 'outline' | 'ghost';
    size?: 'default' | 'sm' | 'lg';
}

/**
 * Formata o CNPJ para exibição resumida
 */
const formatCNPJShort = (cnpj: string): string => {
    const clean = cnpj.replace(/\D/g, '');
    if (clean.length !== 14) return cnpj;
    return `${clean.slice(0, 2)}.${clean.slice(2, 5)}.${clean.slice(5, 8)}/${clean.slice(8, 12)}-${clean.slice(12)}`;
};

export function CompanySelector({
    className,
    onAddCompany,
    showAddButton = true,
    variant = 'outline',
    size = 'default',
}: CompanySelectorProps) {
    const {
        companies,
        selectedCompany,
        isLoadingCompanies,
        selectCompany
    } = useCompany();

    // Loading state
    if (isLoadingCompanies) {
        return (
            <Skeleton className={cn("h-10 w-48", className)} />
        );
    }

    // No companies
    if (companies.length === 0) {
        return (
            <Button
                variant="outline"
                className={cn("gap-2", className)}
                onClick={onAddCompany}
            >
                <Plus className="h-4 w-4" />
                <span>Adicionar Empresa</span>
            </Button>
        );
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant={variant}
                    size={size}
                    className={cn("gap-2 justify-between min-w-[200px]", className)}
                >
                    <div className="flex items-center gap-2 truncate">
                        <Building2 className="h-4 w-4 shrink-0" />
                        <span className="truncate">
                            {selectedCompany?.nome_fantasia || selectedCompany?.razao_social || 'Selecionar empresa'}
                        </span>
                    </div>
                    <ChevronDown className="h-4 w-4 shrink-0 opacity-50" />
                </Button>
            </DropdownMenuTrigger>

            <DropdownMenuContent align="start" className="w-72">
                <DropdownMenuLabel>Empresas</DropdownMenuLabel>
                <DropdownMenuSeparator />

                {companies.map((company) => (
                    <DropdownMenuItem
                        key={company.id}
                        className={cn(
                            "flex flex-col items-start gap-1 cursor-pointer",
                            selectedCompany?.id === company.id && "bg-accent"
                        )}
                        onClick={() => selectCompany(company.id)}
                    >
                        <div className="flex items-center gap-2 w-full">
                            <Building2 className="h-4 w-4 shrink-0 text-muted-foreground" />
                            <span className="font-medium truncate flex-1">
                                {company.nome_fantasia || company.razao_social}
                            </span>
                            {company.is_default && (
                                <Star className="h-3 w-3 fill-yellow-500 text-yellow-500" />
                            )}
                            {selectedCompany?.id === company.id && (
                                <Check className="h-4 w-4 text-primary" />
                            )}
                        </div>
                        <span className="text-xs text-muted-foreground ml-6">
                            {formatCNPJShort(company.cnpj)}
                        </span>
                    </DropdownMenuItem>
                ))}

                {showAddButton && (
                    <>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            className="cursor-pointer"
                            onClick={onAddCompany}
                        >
                            <Plus className="h-4 w-4 mr-2" />
                            <span>Adicionar nova empresa</span>
                        </DropdownMenuItem>
                    </>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

/**
 * Versão compacta do seletor para uso em headers
 */
export function CompanySelectorCompact({
    className,
    onAddCompany,
}: {
    className?: string;
    onAddCompany?: () => void;
}) {
    const { companies, selectedCompany, isLoadingCompanies, selectCompany } = useCompany();

    if (isLoadingCompanies) {
        return <Skeleton className={cn("h-8 w-8", className)} />;
    }

    if (companies.length === 0) {
        return null;
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className={cn("h-8 w-8", className)}
                    title={selectedCompany?.nome_fantasia || selectedCompany?.razao_social}
                >
                    <Building2 className="h-4 w-4" />
                </Button>
            </DropdownMenuTrigger>

            <DropdownMenuContent align="end" className="w-64">
                <DropdownMenuLabel className="text-xs text-muted-foreground">
                    Empresa ativa
                </DropdownMenuLabel>

                {selectedCompany && (
                    <div className="px-2 py-1.5 mb-2">
                        <p className="font-medium text-sm truncate">
                            {selectedCompany.nome_fantasia || selectedCompany.razao_social}
                        </p>
                        <p className="text-xs text-muted-foreground">
                            {formatCNPJShort(selectedCompany.cnpj)}
                        </p>
                    </div>
                )}

                <DropdownMenuSeparator />
                <DropdownMenuLabel className="text-xs text-muted-foreground">
                    Trocar empresa
                </DropdownMenuLabel>

                {companies
                    .filter(c => c.id !== selectedCompany?.id)
                    .slice(0, 5)
                    .map((company) => (
                        <DropdownMenuItem
                            key={company.id}
                            className="cursor-pointer"
                            onClick={() => selectCompany(company.id)}
                        >
                            <span className="truncate">
                                {company.nome_fantasia || company.razao_social}
                            </span>
                            {company.is_default && (
                                <Badge variant="secondary" className="ml-auto text-xs">
                                    Padrão
                                </Badge>
                            )}
                        </DropdownMenuItem>
                    ))}

                {companies.length > 6 && (
                    <DropdownMenuItem className="text-muted-foreground text-xs">
                        +{companies.length - 6} empresas
                    </DropdownMenuItem>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

export default CompanySelector;
