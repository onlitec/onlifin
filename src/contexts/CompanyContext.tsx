/**
 * Context para gerenciamento de empresas (PJ)
 * 
 * Este contexto fornece acesso global ao estado de empresas,
 * incluindo a empresa atualmente selecionada e operações CRUD.
 */

import * as React from 'react';
import { createContext, useContext, useState, useEffect, useCallback, ReactNode } from 'react';
import { companyService } from '@/services/companyService';
import type {
    Company,
    CreateCompanyDTO,
    UpdateCompanyDTO,
    CompanyContextType
} from '@/types/company';
import { supabase } from '@/db/client';

// Chave para armazenar empresa selecionada no localStorage
const SELECTED_COMPANY_KEY = 'onlifin_selected_company_id';

// Criar o contexto com valor inicial undefined
const CompanyContext = createContext<CompanyContextType | undefined>(undefined);

interface CompanyProviderProps {
    children: ReactNode;
}

/**
 * Provider para o contexto de empresas
 */
export function CompanyProvider({ children }: CompanyProviderProps) {
    const [companies, setCompanies] = useState<Company[]>([]);
    const [selectedCompany, setSelectedCompany] = useState<Company | null>(null);
    const [isLoadingCompanies, setIsLoadingCompanies] = useState(true);
    const [error, setError] = useState<string | null>(null);

    /**
     * Carrega todas as empresas do usuário
     */
    const loadCompanies = useCallback(async () => {
        setIsLoadingCompanies(true);
        setError(null);

        try {
            const data = await companyService.getAll();
            setCompanies(data);

            // Recuperar empresa selecionada do localStorage
            const savedCompanyId = localStorage.getItem(SELECTED_COMPANY_KEY);

            if (savedCompanyId) {
                const savedCompany = data.find(c => c.id === savedCompanyId);
                if (savedCompany) {
                    setSelectedCompany(savedCompany);
                    return;
                }
            }

            // Selecionar empresa padrão ou primeira
            const defaultCompany = data.find(c => c.is_default) || data[0] || null;
            setSelectedCompany(defaultCompany);

            if (defaultCompany) {
                localStorage.setItem(SELECTED_COMPANY_KEY, defaultCompany.id);
            }
        } catch (err) {
            console.error('Erro ao carregar empresas:', err);
            setError(err instanceof Error ? err.message : 'Erro ao carregar empresas');
        } finally {
            setIsLoadingCompanies(false);
        }
    }, []);

    /**
     * Carrega empresas ao montar o componente
     */
    useEffect(() => {
        loadCompanies();
    }, [loadCompanies]);

    /**
     * Configura subscription para mudanças em tempo real
     */
    useEffect(() => {
        const channel = supabase
            .channel('companies_changes')
            .on(
                'postgres_changes',
                {
                    event: '*',
                    schema: 'public',
                    table: 'companies',
                },
                (payload) => {
                    console.log('Mudança em companies:', payload);
                    // Recarregar empresas quando houver mudanças
                    loadCompanies();
                }
            )
            .subscribe();

        return () => {
            supabase.removeChannel(channel);
        };
    }, [loadCompanies]);

    /**
     * Seleciona uma empresa pelo ID
     */
    const selectCompany = useCallback((companyId: string | null) => {
        if (companyId === null) {
            setSelectedCompany(null);
            localStorage.removeItem(SELECTED_COMPANY_KEY);
            return;
        }

        const company = companies.find(c => c.id === companyId);
        if (company) {
            setSelectedCompany(company);
            localStorage.setItem(SELECTED_COMPANY_KEY, companyId);
        } else {
            console.warn(`Empresa com ID ${companyId} não encontrada`);
        }
    }, [companies]);

    /**
     * Atualiza a lista de empresas
     */
    const refreshCompanies = useCallback(async () => {
        await loadCompanies();
    }, [loadCompanies]);

    /**
     * Cria uma nova empresa
     */
    const createCompany = useCallback(async (data: CreateCompanyDTO): Promise<Company> => {
        try {
            const newCompany = await companyService.create(data);

            // Atualizar lista de empresas
            setCompanies(prev => [newCompany, ...prev]);

            // Se for a primeira empresa ou for padrão, selecionar
            if (newCompany.is_default || companies.length === 0) {
                setSelectedCompany(newCompany);
                localStorage.setItem(SELECTED_COMPANY_KEY, newCompany.id);
            }

            return newCompany;
        } catch (err) {
            console.error('Erro ao criar empresa:', err);
            throw err;
        }
    }, [companies.length]);

    /**
     * Atualiza uma empresa existente
     */
    const updateCompany = useCallback(async (id: string, data: UpdateCompanyDTO): Promise<Company> => {
        try {
            const updatedCompany = await companyService.update(id, data);

            // Atualizar lista de empresas
            setCompanies(prev =>
                prev.map(c => c.id === id ? updatedCompany : c)
            );

            // Atualizar empresa selecionada se for a mesma
            if (selectedCompany?.id === id) {
                setSelectedCompany(updatedCompany);
            }

            return updatedCompany;
        } catch (err) {
            console.error('Erro ao atualizar empresa:', err);
            throw err;
        }
    }, [selectedCompany]);

    /**
     * Exclui uma empresa (soft delete)
     */
    const deleteCompany = useCallback(async (id: string): Promise<void> => {
        try {
            await companyService.delete(id);

            // Remover da lista
            setCompanies(prev => prev.filter(c => c.id !== id));

            // Se era a empresa selecionada, selecionar outra
            if (selectedCompany?.id === id) {
                const remainingCompanies = companies.filter(c => c.id !== id);
                const nextCompany = remainingCompanies.find(c => c.is_default) || remainingCompanies[0] || null;
                setSelectedCompany(nextCompany);

                if (nextCompany) {
                    localStorage.setItem(SELECTED_COMPANY_KEY, nextCompany.id);
                } else {
                    localStorage.removeItem(SELECTED_COMPANY_KEY);
                }
            }
        } catch (err) {
            console.error('Erro ao excluir empresa:', err);
            throw err;
        }
    }, [companies, selectedCompany]);

    /**
     * Define uma empresa como padrão
     */
    const setAsDefault = useCallback(async (id: string): Promise<void> => {
        try {
            await companyService.setAsDefault(id);

            // Atualizar lista de empresas
            setCompanies(prev =>
                prev.map(c => ({
                    ...c,
                    is_default: c.id === id,
                }))
            );

            // Selecionar a nova empresa padrão
            const defaultCompany = companies.find(c => c.id === id);
            if (defaultCompany) {
                setSelectedCompany({ ...defaultCompany, is_default: true });
                localStorage.setItem(SELECTED_COMPANY_KEY, id);
            }
        } catch (err) {
            console.error('Erro ao definir empresa padrão:', err);
            throw err;
        }
    }, [companies]);

    // Valor do contexto
    const value: CompanyContextType = {
        companies,
        selectedCompany,
        isLoadingCompanies,
        error,
        selectCompany,
        refreshCompanies,
        createCompany,
        updateCompany,
        deleteCompany,
        setAsDefault,
    };

    return (
        <CompanyContext.Provider value={value}>
            {children}
        </CompanyContext.Provider>
    );
}

/**
 * Hook para acessar o contexto de empresas
 * 
 * @returns O contexto de empresas
 * @throws Error se usado fora do CompanyProvider
 * 
 * @example
 * const { companies, selectedCompany, selectCompany } = useCompany();
 */
export function useCompany(): CompanyContextType {
    const context = useContext(CompanyContext);

    if (context === undefined) {
        throw new Error('useCompany deve ser usado dentro de um CompanyProvider');
    }

    return context;
}

/**
 * Hook para verificar se o usuário tem empresas
 */
export function useHasCompanies(): boolean {
    const { companies, isLoadingCompanies } = useCompany();
    return !isLoadingCompanies && companies.length > 0;
}

/**
 * Hook para obter a empresa selecionada ou null
 * Útil para componentes que precisam apenas leitura
 */
export function useSelectedCompany(): Company | null {
    const { selectedCompany } = useCompany();
    return selectedCompany;
}

/**
 * Hook para obter o ID da empresa selecionada para filtros
 * Retorna undefined se nenhuma empresa selecionada (mantém retrocompatibilidade)
 */
export function useSelectedCompanyId(): string | undefined {
    const { selectedCompany } = useCompany();
    return selectedCompany?.id;
}

export default CompanyProvider;
