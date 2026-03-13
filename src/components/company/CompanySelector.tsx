/**
 * Componente de seleção de empresa
 * 
 * Dropdown para selecionar a empresa ativa no contexto PJ.
 * Exibe nome fantasia ou razão social e CNPJ formatado.
 */

import { Building2, ChevronDown, Plus, Star } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
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
        selectCompany: baseSelectCompany
    } = useCompany();
    const navigate = useNavigate();

    const selectCompany = (id: string) => {
        baseSelectCompany(id);
        navigate(`/pj/${id}`);
    };

    // Loading state
    if (isLoadingCompanies) {
        return (
            <Skeleton className={cn("h-10 w-48 rounded-xl", className)} />
        );
    }

    // No companies
    if (companies.length === 0) {
        return (
            <Button
                variant="outline"
                className={cn("gap-2 rounded-xl glass-card border-white/10 hover:bg-white/5 transition-all text-[10px] font-black uppercase tracking-widest", className)}
                onClick={onAddCompany}
            >
                <Plus className="h-3 w-3" />
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
                    className={cn("gap-2 justify-between min-w-[200px] glass-card border-white/10 hover:bg-white/5 transition-all", className)}
                >
                    <div className="flex items-center gap-2 truncate">
                        {selectedCompany?.color ? (
                            <div
                                className="h-3 w-3 rounded-full shrink-0 border border-white/20 shadow-[0_0_8px_rgba(var(--primary),0.5)]"
                                style={{ backgroundColor: selectedCompany.color }}
                            />
                        ) : (
                            <Building2 className="h-4 w-4 shrink-0 opacity-70 text-primary" />
                        )}
                        <span className="truncate font-black tracking-tighter uppercase text-[10px]">
                            {selectedCompany?.nome_fantasia || selectedCompany?.razao_social || 'Selecionar empresa'}
                        </span>
                    </div>
                    <ChevronDown className="h-3 w-3 shrink-0 opacity-30" />
                </Button>
            </DropdownMenuTrigger>

            <DropdownMenuContent align="start" className="w-72 glass-card premium-card border-white/10 backdrop-blur-2xl p-2">
                <DropdownMenuLabel className="text-[10px] font-black uppercase tracking-widest text-muted-foreground pb-2 px-2">Portfolio</DropdownMenuLabel>
                <DropdownMenuSeparator className="bg-white/5 mx--2" />

                <div className="space-y-1 mt-1">
                    {companies.map((company) => (
                        <DropdownMenuItem
                            key={company.id}
                            className={cn(
                                "flex flex-col items-start gap-0.5 cursor-pointer rounded-xl transition-all duration-200 py-2.5 px-3 hover:bg-white/5 group",
                                selectedCompany?.id === company.id && "bg-primary/10 border-white/5 shadow-inner"
                            )}
                            onClick={() => selectCompany(company.id)}
                        >
                            <div className="flex items-center gap-3 w-full">
                                <div
                                    className="h-4 w-4 rounded-full shrink-0 border-2 border-white/10 shadow-lg transition-transform group-hover:scale-110"
                                    style={{ backgroundColor: company.color || '#10b981' }}
                                />
                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center justify-between gap-2">
                                        <span className="font-bold tracking-tight truncate text-sm uppercase">
                                            {company.nome_fantasia || company.razao_social}
                                        </span>
                                        {company.is_default && (
                                            <Star className="h-3 w-3 fill-primary/40 text-primary/40" />
                                        )}
                                    </div>
                                    <span className="text-[10px] font-medium text-muted-foreground/60 uppercase tracking-tighter block mt-0.5">
                                        {formatCNPJShort(company.cnpj)}
                                    </span>
                                </div>
                                {selectedCompany?.id === company.id && (
                                    <div className="h-2 w-2 rounded-full bg-primary animate-pulse" />
                                )}
                            </div>
                        </DropdownMenuItem>
                    ))}
                </div>

                {showAddButton && (
                    <>
                        <DropdownMenuSeparator className="bg-white/5 mx--2 mt-2" />
                        <DropdownMenuItem
                            className="cursor-pointer mt-1 rounded-xl py-2.5 px-3 hover:bg-primary/20 text-primary transition-colors font-bold uppercase text-[10px] tracking-widest"
                            onClick={onAddCompany}
                        >
                            <Plus className="h-4 w-4 mr-2" />
                            <span>Empower New Business</span>
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
}: {
    className?: string;
}) {
    const { companies, selectedCompany, isLoadingCompanies, selectCompany } = useCompany();

    if (isLoadingCompanies) {
        return <Skeleton className={cn("h-8 w-8 rounded-lg", className)} />;
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
                    className={cn("h-8 w-8 glass-card border-white/5 hover:bg-white/10 transition-all rounded-lg group", className)}
                    title={selectedCompany?.nome_fantasia || selectedCompany?.razao_social}
                >
                    <Building2 className="h-4 w-4 text-primary group-hover:scale-110 transition-transform" />
                </Button>
            </DropdownMenuTrigger>

            <DropdownMenuContent align="end" className="w-[300px] glass-card premium-card border-white/10 backdrop-blur-2xl p-2">
                <DropdownMenuLabel className="text-[10px] font-black uppercase tracking-widest text-muted-foreground pb-2 px-2">Active Entity</DropdownMenuLabel>

                {selectedCompany && (
                    <div className="px-3 py-3 mb-2 bg-primary/10 rounded-xl border border-white/5">
                        <div className="flex items-center gap-3">
                            <div
                                className="size-4 rounded-full border-2 border-white/10 shadow-lg"
                                style={{ backgroundColor: selectedCompany.color || '#10b981' }}
                            />
                            <div className="flex-1 min-w-0">
                                <p className="font-bold text-sm uppercase tracking-tight truncate leading-none">
                                    {selectedCompany.nome_fantasia || selectedCompany.razao_social}
                                </p>
                                <p className="text-[10px] font-medium text-muted-foreground uppercase tracking-widest mt-1">
                                    {formatCNPJShort(selectedCompany.cnpj)}
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                <DropdownMenuSeparator className="bg-white/5 mx--2" />
                <DropdownMenuLabel className="text-[10px] font-black uppercase tracking-widest text-muted-foreground py-2 px-2 mt-1">Swith Environment</DropdownMenuLabel>

                <div className="space-y-1">
                    {companies
                        .filter(c => c.id !== selectedCompany?.id)
                        .slice(0, 5)
                        .map((company) => (
                            <DropdownMenuItem
                                key={company.id}
                                className="cursor-pointer rounded-xl py-2.5 px-3 hover:bg-white/5 group transition-all"
                                onClick={() => selectCompany(company.id)}
                            >
                                <div className="flex items-center gap-3 w-full">
                                    <div
                                        className="size-3 rounded-full opacity-60 group-hover:opacity-100 transition-opacity"
                                        style={{ backgroundColor: company.color || '#10b981' }}
                                    />
                                    <span className="truncate font-bold text-xs uppercase tracking-tight flex-1">
                                        {company.nome_fantasia || company.razao_social}
                                    </span>
                                    {company.is_default && (
                                        <Badge variant="secondary" className="bg-primary/20 text-primary text-[8px] font-black uppercase border-0">
                                            Default
                                        </Badge>
                                    )}
                                </div>
                            </DropdownMenuItem>
                        ))}
                </div>

                {companies.length > 6 && (
                    <DropdownMenuItem className="text-muted-foreground/50 text-[10px] font-black uppercase tracking-widest justify-center py-2">
                        +{companies.length - 6} More Entities
                    </DropdownMenuItem>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

export default CompanySelector;
