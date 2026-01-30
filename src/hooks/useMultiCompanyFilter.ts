/**
 * Hook para filtrar dados por múltiplas empresas
 * 
 * Este hook facilita a criação de filtros multi-empresa para consultas ao banco de dados.
 * Permite selecionar múltiplas empresas ou incluir dados pessoais (PF).
 */

import { useState, useCallback, useMemo } from 'react';
import { useCompany } from '@/contexts/CompanyContext';
import type { Company, CompanyFilter } from '@/types/company';

interface MultiCompanyFilterState {
    // Empresas selecionadas para filtro
    selectedCompanies: string[];

    // Incluir dados pessoais (company_id = null)
    includePersonal: boolean;

    // Range de datas
    dateFrom: string | null;
    dateTo: string | null;
}

interface UseMultiCompanyFilterReturn {
    // Estado do filtro
    filter: CompanyFilter;

    // Empresas disponíveis para seleção
    availableCompanies: Company[];

    // Empresas selecionadas
    selectedCompanies: string[];

    // Incluir dados pessoais
    includePersonal: boolean;

    // Range de datas
    dateFrom: string | null;
    dateTo: string | null;

    // Ações
    toggleCompany: (companyId: string) => void;
    selectAll: () => void;
    clearAll: () => void;
    setIncludePersonal: (include: boolean) => void;
    setDateRange: (from: string | null, to: string | null) => void;
    reset: () => void;

    // Helpers
    isCompanySelected: (companyId: string) => boolean;
    getSelectedCount: () => number;
    hasActiveFilter: () => boolean;

    // Para uso com Supabase
    buildSupabaseFilter: () => { column: string; values: (string | null)[] } | null;
}

const DEFAULT_STATE: MultiCompanyFilterState = {
    selectedCompanies: [],
    includePersonal: false,
    dateFrom: null,
    dateTo: null,
};

/**
 * Hook para gerenciamento de filtros multi-empresa
 * 
 * @param initialIncludePersonal - Se deve iniciar incluindo dados pessoais
 * @returns Objeto com estado e ações do filtro
 * 
 * @example
 * const { filter, toggleCompany, selectedCompanies } = useMultiCompanyFilter();
 * 
 * // Em uma query Supabase
 * let query = supabase.from('transactions').select('*');
 * 
 * const supabaseFilter = buildSupabaseFilter();
 * if (supabaseFilter) {
 *   query = query.in(supabaseFilter.column, supabaseFilter.values);
 * }
 */
export function useMultiCompanyFilter(
    initialIncludePersonal: boolean = false
): UseMultiCompanyFilterReturn {
    const { companies, selectedCompany } = useCompany();

    const [state, setState] = useState<MultiCompanyFilterState>({
        ...DEFAULT_STATE,
        includePersonal: initialIncludePersonal,
        // Por padrão, selecionar a empresa atual se houver
        selectedCompanies: selectedCompany ? [selectedCompany.id] : [],
    });

    /**
     * Toggle seleção de uma empresa
     */
    const toggleCompany = useCallback((companyId: string) => {
        setState(prev => {
            const isSelected = prev.selectedCompanies.includes(companyId);
            return {
                ...prev,
                selectedCompanies: isSelected
                    ? prev.selectedCompanies.filter(id => id !== companyId)
                    : [...prev.selectedCompanies, companyId],
            };
        });
    }, []);

    /**
     * Selecionar todas as empresas
     */
    const selectAll = useCallback(() => {
        setState(prev => ({
            ...prev,
            selectedCompanies: companies.map(c => c.id),
        }));
    }, [companies]);

    /**
     * Limpar todas as seleções
     */
    const clearAll = useCallback(() => {
        setState(prev => ({
            ...prev,
            selectedCompanies: [],
            includePersonal: false,
        }));
    }, []);

    /**
     * Toggle incluir dados pessoais
     */
    const setIncludePersonal = useCallback((include: boolean) => {
        setState(prev => ({
            ...prev,
            includePersonal: include,
        }));
    }, []);

    /**
     * Definir range de datas
     */
    const setDateRange = useCallback((from: string | null, to: string | null) => {
        setState(prev => ({
            ...prev,
            dateFrom: from,
            dateTo: to,
        }));
    }, []);

    /**
     * Reset para estado inicial
     */
    const reset = useCallback(() => {
        setState({
            ...DEFAULT_STATE,
            includePersonal: initialIncludePersonal,
            selectedCompanies: selectedCompany ? [selectedCompany.id] : [],
        });
    }, [initialIncludePersonal, selectedCompany]);

    /**
     * Verificar se uma empresa está selecionada
     */
    const isCompanySelected = useCallback((companyId: string) => {
        return state.selectedCompanies.includes(companyId);
    }, [state.selectedCompanies]);

    /**
     * Obter quantidade de filtros ativos
     */
    const getSelectedCount = useCallback(() => {
        let count = state.selectedCompanies.length;
        if (state.includePersonal) count++;
        return count;
    }, [state.selectedCompanies, state.includePersonal]);

    /**
     * Verificar se há filtro ativo
     */
    const hasActiveFilter = useCallback(() => {
        return (
            state.selectedCompanies.length > 0 ||
            state.includePersonal ||
            state.dateFrom !== null ||
            state.dateTo !== null
        );
    }, [state]);

    /**
     * Construir filtro para uso com Supabase
     * Retorna null se nenhum filtro deve ser aplicado (mostrar tudo)
     */
    const buildSupabaseFilter = useCallback(() => {
        // Se não há seleção específica, não filtrar
        if (state.selectedCompanies.length === 0 && !state.includePersonal) {
            return null;
        }

        const values: (string | null)[] = [...state.selectedCompanies];

        // null representa dados pessoais (PF)
        if (state.includePersonal) {
            values.push(null);
        }

        return {
            column: 'company_id',
            values,
        };
    }, [state.selectedCompanies, state.includePersonal]);

    /**
     * Filtro no formato CompanyFilter
     */
    const filter = useMemo<CompanyFilter>(() => ({
        companyIds: state.selectedCompanies.length > 0 ? state.selectedCompanies : undefined,
        includePersonal: state.includePersonal,
        dateFrom: state.dateFrom || undefined,
        dateTo: state.dateTo || undefined,
    }), [state]);

    return {
        filter,
        availableCompanies: companies,
        selectedCompanies: state.selectedCompanies,
        includePersonal: state.includePersonal,
        dateFrom: state.dateFrom,
        dateTo: state.dateTo,
        toggleCompany,
        selectAll,
        clearAll,
        setIncludePersonal,
        setDateRange,
        reset,
        isCompanySelected,
        getSelectedCount,
        hasActiveFilter,
        buildSupabaseFilter,
    };
}

/**
 * Hook simplificado que usa apenas a empresa selecionada no contexto
 * 
 * @returns O ID da empresa atual ou undefined para dados pessoais
 */
export function useCurrentCompanyId(): string | undefined {
    const { selectedCompany } = useCompany();
    return selectedCompany?.id;
}

/**
 * Hook para verificar se o usuário está no modo multi-empresa
 * 
 * @returns true se o usuário tem múltiplas empresas cadastradas
 */
export function useHasMultipleCompanies(): boolean {
    const { companies } = useCompany();
    return companies.length > 1;
}

export default useMultiCompanyFilter;
