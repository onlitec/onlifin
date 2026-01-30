/**
 * Serviço de gerenciamento de empresas (PJ)
 * 
 * Este serviço fornece todas as operações CRUD para empresas,
 * incluindo definição de empresa padrão e cálculo de métricas.
 */

import { supabase } from '@/db/client';
import type {
    Company,
    CreateCompanyDTO,
    UpdateCompanyDTO,
    CompanySummary,
    CompanyWithMetrics,
    AccountType
} from '@/types/company';

/**
 * Serviço para gerenciamento de empresas (PJ)
 */
export const companyService = {
    /**
     * Busca todas as empresas do usuário atual
     */
    async getAll(): Promise<Company[]> {
        const { data, error } = await supabase
            .from('companies')
            .select('*')
            .eq('is_active', true)
            .order('is_default', { ascending: false })
            .order('created_at', { ascending: false });

        if (error) {
            console.error('Erro ao buscar empresas:', error);
            throw new Error('Não foi possível carregar as empresas');
        }

        return data || [];
    },

    /**
     * Busca todas as empresas incluindo inativas (para admin)
     */
    async getAllIncludingInactive(): Promise<Company[]> {
        const { data, error } = await supabase
            .from('companies')
            .select('*')
            .order('is_default', { ascending: false })
            .order('created_at', { ascending: false });

        if (error) {
            console.error('Erro ao buscar empresas:', error);
            throw new Error('Não foi possível carregar as empresas');
        }

        return data || [];
    },

    /**
     * Busca uma empresa específica pelo ID
     */
    async getById(id: string): Promise<Company> {
        const { data, error } = await supabase
            .from('companies')
            .select('*')
            .eq('id', id)
            .single();

        if (error) {
            console.error('Erro ao buscar empresa:', error);
            throw new Error('Empresa não encontrada');
        }

        return data;
    },

    /**
     * Busca empresa pelo CNPJ
     */
    async getByCNPJ(cnpj: string): Promise<Company | null> {
        const { data, error, status } = await supabase
            .from('companies')
            .select('*')
            .eq('cnpj', cnpj)
            .maybeSingle(); // maybeSingle é mais seguro, retorna null se não encontrar

        if (error) {
            // PGRST116 ou status 406 indicam que o objeto único não foi encontrado
            if (error.code === 'PGRST116' || status === 406) {
                return null;
            }
            console.error('Erro ao buscar empresa por CNPJ:', error);
            throw new Error('Erro ao verificar CNPJ');
        }

        return data;
    },

    /**
     * Cria uma nova empresa
     */
    async create(data: CreateCompanyDTO): Promise<Company> {
        // Buscar usuário atual
        const { data: { user } } = await supabase.auth.getUser();
        if (!user) {
            throw new Error('Usuário não autenticado');
        }

        // Verificar se CNPJ já existe
        const existingCompany = await this.getByCNPJ(data.cnpj);
        if (existingCompany) {
            throw new Error('CNPJ já cadastrado no sistema');
        }

        // Verificar se é a primeira empresa (será padrão)
        const companies = await this.getAll();
        const isFirst = companies.length === 0;

        // Inserir sem select().single() imediato para evitar URL complexa
        const { data: insertData, error: insertError } = await supabase
            .from('companies')
            .insert([{
                ...data,
                user_id: user.id,
                is_default: isFirst || data.is_default || false,
                is_active: true,
            }])
            .select();

        if (insertError) {
            console.error('Erro ao criar empresa (insert):', insertError);
            if (insertError.code === '23505') {
                throw new Error('CNPJ já cadastrado');
            }
            if (insertError.message?.includes('validate_cnpj')) {
                throw new Error('CNPJ inválido');
            }
            throw new Error('Não foi possível criar a empresa');
        }

        if (!insertData || insertData.length === 0) {
            throw new Error('Erro ao obter dados da empresa criada');
        }

        return insertData[0];
    },

    /**
     * Atualiza uma empresa existente
     */
    async update(id: string, data: UpdateCompanyDTO): Promise<Company> {
        const { data: updatedData, error } = await supabase
            .from('companies')
            .update({
                ...data,
                updated_at: new Date().toISOString(),
            })
            .eq('id', id)
            .select();

        if (error) {
            console.error('Erro ao atualizar empresa:', error);
            throw new Error('Não foi possível atualizar a empresa');
        }

        if (!updatedData || updatedData.length === 0) {
            throw new Error('Empresa não encontrada para atualização');
        }

        return updatedData[0];
    },

    /**
     * Exclui uma empresa (soft delete - marca como inativa)
     */
    async delete(id: string): Promise<void> {
        const { error } = await supabase
            .from('companies')
            .update({
                is_active: false,
                is_default: false,
                updated_at: new Date().toISOString(),
            })
            .eq('id', id);

        if (error) {
            console.error('Erro ao excluir empresa:', error);
            throw new Error('Não foi possível excluir a empresa');
        }
    },

    /**
     * Exclui uma empresa permanentemente (hard delete)
     */
    async deletePermanently(id: string): Promise<void> {
        const { error } = await supabase
            .from('companies')
            .delete()
            .eq('id', id);

        if (error) {
            console.error('Erro ao excluir empresa permanentemente:', error);
            throw new Error('Não foi possível excluir a empresa');
        }
    },

    /**
     * Define uma empresa como padrão
     */
    async setAsDefault(id: string): Promise<void> {
        // Usar a função RPC do banco de dados
        const { error } = await supabase.rpc('set_default_company', {
            company_uuid: id,
        });

        if (error) {
            console.error('Erro ao definir empresa padrão:', error);

            // Fallback: fazer manualmente
            const { data: { user } } = await supabase.auth.getUser();
            if (!user) {
                throw new Error('Usuário não autenticado');
            }

            // Remover default de todas as empresas
            await supabase
                .from('companies')
                .update({ is_default: false })
                .eq('user_id', user.id)
                .neq('id', id);

            // Definir a empresa como padrão
            const { error: updateError } = await supabase
                .from('companies')
                .update({ is_default: true })
                .eq('id', id);

            if (updateError) {
                throw new Error('Não foi possível definir a empresa padrão');
            }
        }
    },

    /**
     * Busca a empresa padrão do usuário
     */
    async getDefault(): Promise<Company | null> {
        const { data, error } = await supabase
            .from('companies')
            .select('*')
            .eq('is_default', true)
            .eq('is_active', true)
            .single();

        if (error) {
            if (error.code === 'PGRST116') {
                return null; // Nenhuma empresa padrão
            }
            console.error('Erro ao buscar empresa padrão:', error);
            return null;
        }

        return data;
    },

    /**
     * Busca o resumo de empresas para uso em seletores
     */
    async getSummaries(): Promise<CompanySummary[]> {
        const { data, error } = await supabase
            .from('companies')
            .select('id, cnpj, razao_social, nome_fantasia, is_default, is_active')
            .eq('is_active', true)
            .order('is_default', { ascending: false })
            .order('razao_social', { ascending: true });

        if (error) {
            console.error('Erro ao buscar resumo de empresas:', error);
            throw new Error('Não foi possível carregar as empresas');
        }

        return data || [];
    },

    /**
     * Busca empresas com métricas (para dashboard/listagem)
     */
    async getWithMetrics(): Promise<CompanyWithMetrics[]> {
        // Tentar usar a view de resumo
        const { data, error } = await supabase
            .from('v_company_summary')
            .select('*');

        if (error) {
            console.warn('View não disponível, calculando métricas manualmente:', error);

            // Fallback: buscar empresas e calcular métricas
            const companies = await this.getAll();

            const companiesWithMetrics: CompanyWithMetrics[] = await Promise.all(
                companies.map(async (company) => {
                    const metrics = await this.getCompanyMetrics(company.id);
                    return {
                        ...company,
                        ...metrics,
                    };
                })
            );

            return companiesWithMetrics;
        }

        // Converter dados da view para o formato esperado
        return (data || []).map((item: Record<string, unknown>) => ({
            id: item.company_id as string,
            user_id: item.user_id as string,
            cnpj: item.cnpj as string,
            razao_social: item.razao_social as string,
            nome_fantasia: item.nome_fantasia as string | null,
            is_active: item.is_active as boolean,
            is_default: item.is_default as boolean,
            created_at: item.created_at as string,
            updated_at: '',
            total_accounts: Number(item.total_accounts) || 0,
            total_balance: Number(item.total_balance) || 0,
            total_transactions: Number(item.total_transactions) || 0,
            total_income: Number(item.total_income) || 0,
            total_expense: Number(item.total_expense) || 0,
        }));
    },

    /**
     * Calcula métricas de uma empresa específica
     */
    async getCompanyMetrics(companyId: string): Promise<{
        total_accounts: number;
        total_balance: number;
        total_transactions: number;
        total_income: number;
        total_expense: number;
    }> {
        // Buscar contas
        const { data: accounts, error: accountsError } = await supabase
            .from('accounts')
            .select('id, balance')
            .eq('company_id', companyId);

        if (accountsError) {
            console.error('Erro ao buscar contas:', accountsError);
        }

        // Buscar transações
        const { data: transactions, error: transactionsError } = await supabase
            .from('transactions')
            .select('id, type, amount')
            .eq('company_id', companyId);

        if (transactionsError) {
            console.error('Erro ao buscar transações:', transactionsError);
        }

        const total_accounts = accounts?.length || 0;
        const total_balance = accounts?.reduce((sum, acc) => sum + (Number(acc.balance) || 0), 0) || 0;
        const total_transactions = transactions?.length || 0;
        const total_income = transactions?.filter(t => t.type === 'income').reduce((sum, t) => sum + (Number(t.amount) || 0), 0) || 0;
        const total_expense = transactions?.filter(t => t.type === 'expense').reduce((sum, t) => sum + (Number(t.amount) || 0), 0) || 0;

        return {
            total_accounts,
            total_balance,
            total_transactions,
            total_income,
            total_expense,
        };
    },

    /**
     * Busca os tipos de conta disponíveis
     */
    async getAccountTypes(): Promise<AccountType[]> {
        const { data, error } = await supabase
            .from('account_types')
            .select('*')
            .order('code', { ascending: true });

        if (error) {
            console.error('Erro ao buscar tipos de conta:', error);
            return [];
        }

        return data || [];
    },

    /**
     * Conta o número total de empresas do usuário
     */
    async count(): Promise<number> {
        const { count, error } = await supabase
            .from('companies')
            .select('*', { count: 'exact', head: true })
            .eq('is_active', true);

        if (error) {
            console.error('Erro ao contar empresas:', error);
            return 0;
        }

        return count || 0;
    },

    /**
     * Verifica se o usuário atingiu o limite de empresas
     * 
     * @param limit - Limite máximo de empresas (padrão: 50)
     */
    async hasReachedLimit(limit: number = 50): Promise<boolean> {
        const count = await this.count();
        return count >= limit;
    },

    /**
     * Busca empresas por termo de pesquisa
     */
    async search(term: string): Promise<Company[]> {
        const { data, error } = await supabase
            .from('companies')
            .select('*')
            .eq('is_active', true)
            .or(`razao_social.ilike.%${term}%,nome_fantasia.ilike.%${term}%,cnpj.ilike.%${term}%`)
            .order('razao_social', { ascending: true })
            .limit(20);

        if (error) {
            console.error('Erro ao pesquisar empresas:', error);
            throw new Error('Erro na pesquisa');
        }

        return data || [];
    },

    /**
     * Restaura uma empresa inativa
     */
    async restore(id: string): Promise<Company> {
        const { data, error } = await supabase
            .from('companies')
            .update({
                is_active: true,
                updated_at: new Date().toISOString(),
            })
            .eq('id', id)
            .select()
            .single();

        if (error) {
            console.error('Erro ao restaurar empresa:', error);
            throw new Error('Não foi possível restaurar a empresa');
        }

        return data;
    },
};

export default companyService;
