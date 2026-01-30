/**
 * Card de empresa para exibição em listagens e dashboards
 */

import {
    Building2,
    MapPin,
    Phone,
    Mail,
    Globe,
    Star,
    MoreHorizontal,
    Pencil,
    Trash2
} from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';
import type { Company, CompanyWithMetrics } from '@/types/company';
import { COMPANY_SIZE_LABELS, TAX_REGIME_LABELS } from '@/types/company';

interface CompanyCardProps {
    company: Company | CompanyWithMetrics;
    onEdit?: (company: Company) => void;
    onDelete?: (company: Company) => void;
    onSetDefault?: (company: Company) => void;
    onSelect?: (company: Company) => void;
    isSelected?: boolean;
    showMetrics?: boolean;
    showActions?: boolean;
    className?: string;
}

/**
 * Formata o CNPJ para exibição
 */
const formatCNPJ = (cnpj: string): string => {
    const clean = cnpj.replace(/\D/g, '');
    if (clean.length !== 14) return cnpj;
    return `${clean.slice(0, 2)}.${clean.slice(2, 5)}.${clean.slice(5, 8)}/${clean.slice(8, 12)}-${clean.slice(12)}`;
};

/**
 * Formata valor monetário
 */
const formatCurrency = (value: number): string => {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value);
};

export function CompanyCard({
    company,
    onEdit,
    onDelete,
    onSetDefault,
    onSelect,
    isSelected = false,
    showMetrics = false,
    showActions = true,
    className,
}: CompanyCardProps) {
    const hasMetrics = 'total_balance' in company;
    const displayName = company.nome_fantasia || company.razao_social;
    const fullAddress = [
        company.logradouro,
        company.numero,
        company.bairro,
        company.cidade,
        company.uf,
    ].filter(Boolean).join(', ');

    return (
        <Card
            className={cn(
                "transition-all hover:shadow-md cursor-pointer",
                isSelected && "ring-2 ring-primary",
                className
            )}
            onClick={() => onSelect?.(company)}
        >
            <CardHeader className="pb-2">
                <div className="flex items-start justify-between gap-2">
                    <div className="flex items-center gap-3 min-w-0">
                        <div className="h-10 w-10 rounded-lg bg-gradient-to-br from-primary/20 to-primary/5 flex items-center justify-center shrink-0">
                            <Building2 className="h-5 w-5 text-primary" />
                        </div>
                        <div className="min-w-0">
                            <div className="flex items-center gap-2">
                                <CardTitle className="text-base truncate">
                                    {displayName}
                                </CardTitle>
                                {company.is_default && (
                                    <Star className="h-4 w-4 fill-yellow-500 text-yellow-500 shrink-0" />
                                )}
                            </div>
                            <p className="text-sm text-muted-foreground font-mono">
                                {formatCNPJ(company.cnpj)}
                            </p>
                        </div>
                    </div>

                    {showActions && (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild onClick={(e) => e.stopPropagation()}>
                                <Button variant="ghost" size="icon" className="h-8 w-8 shrink-0">
                                    <MoreHorizontal className="h-4 w-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                {onEdit && (
                                    <DropdownMenuItem onClick={(e) => { e.stopPropagation(); onEdit(company); }}>
                                        <Pencil className="h-4 w-4 mr-2" />
                                        Editar
                                    </DropdownMenuItem>
                                )}
                                {onSetDefault && !company.is_default && (
                                    <DropdownMenuItem onClick={(e) => { e.stopPropagation(); onSetDefault(company); }}>
                                        <Star className="h-4 w-4 mr-2" />
                                        Definir como padrão
                                    </DropdownMenuItem>
                                )}
                                {onDelete && (
                                    <>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem
                                            className="text-destructive"
                                            onClick={(e) => { e.stopPropagation(); onDelete(company); }}
                                        >
                                            <Trash2 className="h-4 w-4 mr-2" />
                                            Excluir
                                        </DropdownMenuItem>
                                    </>
                                )}
                            </DropdownMenuContent>
                        </DropdownMenu>
                    )}
                </div>
            </CardHeader>

            <CardContent className="space-y-3">
                {/* Badges de classificação */}
                <div className="flex flex-wrap gap-2">
                    {company.porte && (
                        <Badge variant="secondary" className="text-xs">
                            {COMPANY_SIZE_LABELS[company.porte] || company.porte}
                        </Badge>
                    )}
                    {company.regime_tributario && (
                        <Badge variant="outline" className="text-xs">
                            {TAX_REGIME_LABELS[company.regime_tributario] || company.regime_tributario}
                        </Badge>
                    )}
                </div>

                {/* Informações de contato */}
                <div className="space-y-1.5 text-sm text-muted-foreground">
                    {fullAddress && (
                        <div className="flex items-center gap-2">
                            <MapPin className="h-3.5 w-3.5 shrink-0" />
                            <span className="truncate">{fullAddress}</span>
                        </div>
                    )}
                    {company.phone && (
                        <div className="flex items-center gap-2">
                            <Phone className="h-3.5 w-3.5 shrink-0" />
                            <span>{company.phone}</span>
                        </div>
                    )}
                    {company.email && (
                        <div className="flex items-center gap-2">
                            <Mail className="h-3.5 w-3.5 shrink-0" />
                            <span className="truncate">{company.email}</span>
                        </div>
                    )}
                    {company.website && (
                        <div className="flex items-center gap-2">
                            <Globe className="h-3.5 w-3.5 shrink-0" />
                            <a
                                href={company.website.startsWith('http') ? company.website : `https://${company.website}`}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="truncate hover:underline text-primary"
                                onClick={(e) => e.stopPropagation()}
                            >
                                {company.website}
                            </a>
                        </div>
                    )}
                </div>

                {/* Métricas */}
                {showMetrics && hasMetrics && (
                    <>
                        <div className="border-t pt-3 mt-3">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-xs text-muted-foreground">Saldo Total</p>
                                    <p className={cn(
                                        "text-lg font-semibold",
                                        (company as CompanyWithMetrics).total_balance >= 0
                                            ? "text-green-600"
                                            : "text-red-600"
                                    )}>
                                        {formatCurrency((company as CompanyWithMetrics).total_balance)}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">Contas</p>
                                    <p className="text-lg font-semibold">
                                        {(company as CompanyWithMetrics).total_accounts}
                                    </p>
                                </div>
                            </div>
                            <div className="grid grid-cols-2 gap-4 mt-2">
                                <div>
                                    <p className="text-xs text-muted-foreground">Receitas</p>
                                    <p className="text-sm font-medium text-green-600">
                                        {formatCurrency((company as CompanyWithMetrics).total_income)}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">Despesas</p>
                                    <p className="text-sm font-medium text-red-600">
                                        {formatCurrency((company as CompanyWithMetrics).total_expense)}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </>
                )}
            </CardContent>
        </Card>
    );
}

/**
 * Skeleton para loading state
 */
export function CompanyCardSkeleton() {
    return (
        <Card className="animate-pulse">
            <CardHeader className="pb-2">
                <div className="flex items-start gap-3">
                    <Skeleton className="h-10 w-10 rounded-lg" />
                    <div className="space-y-2">
                        <Skeleton className="h-5 w-40" />
                        <Skeleton className="h-4 w-32" />
                    </div>
                </div>
            </CardHeader>
            <CardContent className="space-y-3">
                <div className="flex gap-2">
                    <Skeleton className="h-5 w-16" />
                    <Skeleton className="h-5 w-24" />
                </div>
                <div className="space-y-2">
                    <Skeleton className="h-4 w-full" />
                    <Skeleton className="h-4 w-2/3" />
                </div>
            </CardContent>
        </Card>
    );
}

export default CompanyCard;
