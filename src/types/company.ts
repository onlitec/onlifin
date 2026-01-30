/**
 * Types relacionados a Empresas (PJ) e sistema multi-tenant
 */

// Tipos de Conta
export type AccountTypeCode = 'PF' | 'PJ';

export interface AccountType {
    id: string;
    code: AccountTypeCode;
    name: string;
    description: string | null;
    created_at: string;
}

// Porte da Empresa
export type CompanySize = 'MEI' | 'ME' | 'EPP' | 'MEDIA' | 'GRANDE';

// Regime Tributário
export type TaxRegime = 'SIMPLES' | 'LUCRO_PRESUMIDO' | 'LUCRO_REAL';

// Interface principal de Empresa
export interface Company {
    id: string;
    user_id: string;

    // Dados da Empresa
    cnpj: string;
    razao_social: string;
    nome_fantasia?: string | null;
    inscricao_estadual?: string | null;
    inscricao_municipal?: string | null;

    // Endereço
    cep?: string | null;
    logradouro?: string | null;
    numero?: string | null;
    complemento?: string | null;
    bairro?: string | null;
    cidade?: string | null;
    uf?: string | null;

    // Contato
    email?: string | null;
    phone?: string | null;
    website?: string | null;

    // Classificação
    porte?: CompanySize | null;
    regime_tributario?: TaxRegime | null;

    // Dados Bancários Padrão
    banco_padrao?: string | null;
    agencia_padrao?: string | null;
    conta_padrao?: string | null;

    // Status
    is_active: boolean;
    is_default: boolean;

    // Configurações
    settings?: Record<string, unknown>;

    // Timestamps
    created_at: string;
    updated_at: string;
}

// DTO para criação de empresa
export interface CreateCompanyDTO {
    cnpj: string;
    razao_social: string;
    nome_fantasia?: string;
    inscricao_estadual?: string;
    inscricao_municipal?: string;
    cep?: string;
    logradouro?: string;
    numero?: string;
    complemento?: string;
    bairro?: string;
    cidade?: string;
    uf?: string;
    email?: string;
    phone?: string;
    website?: string;
    porte?: CompanySize;
    regime_tributario?: TaxRegime;
    banco_padrao?: string;
    agencia_padrao?: string;
    conta_padrao?: string;
    is_default?: boolean;
    settings?: Record<string, unknown>;
}

// DTO para atualização de empresa
export interface UpdateCompanyDTO extends Partial<Omit<CreateCompanyDTO, 'cnpj'>> {
    // CNPJ não pode ser alterado após criação
    is_active?: boolean;
}

// Empresa com métricas (usado em listagens e dashboards)
export interface CompanyWithMetrics extends Company {
    total_accounts: number;
    total_balance: number;
    total_transactions: number;
    total_income: number;
    total_expense: number;
}

// Resumo de empresa (usado em seletores)
export interface CompanySummary {
    id: string;
    cnpj: string;
    razao_social: string;
    nome_fantasia: string | null;
    is_default: boolean;
    is_active: boolean;
}

// Dados de endereço separados (para formulários)
export interface CompanyAddress {
    cep?: string;
    logradouro?: string;
    numero?: string;
    complemento?: string;
    bairro?: string;
    cidade?: string;
    uf?: string;
}

// Dados de contato separados (para formulários)
export interface CompanyContact {
    email?: string;
    phone?: string;
    website?: string;
}

// Dados bancários separados (para formulários)
export interface CompanyBankData {
    banco_padrao?: string;
    agencia_padrao?: string;
    conta_padrao?: string;
}

// Log de Auditoria
export interface AuditLog {
    id: string;
    user_id: string;
    company_id: string | null;
    action: 'INSERT' | 'UPDATE' | 'DELETE';
    table_name: string;
    record_id: string | null;
    old_data: Record<string, unknown> | null;
    new_data: Record<string, unknown> | null;
    ip_address?: string;
    user_agent?: string;
    created_at: string;
}

// Dashboard consolidado do usuário
export interface UserConsolidatedDashboard {
    user_id: string;
    full_name: string | null;
    account_type_id: string | null;

    // Totais de Pessoa Física
    pf_accounts: number;
    pf_balance: number;

    // Totais de Pessoa Jurídica
    total_companies: number;
    pj_accounts: number;
    pj_balance: number;

    // Total Geral
    total_balance: number;
}

// Filtro para consultas multi-empresa
export interface CompanyFilter {
    companyIds?: string[];
    includePersonal?: boolean; // Incluir dados PF (company_id = null)
    dateFrom?: string;
    dateTo?: string;
}

// Contexto de empresa atual (para o CompanyContext)
export interface CompanyContextState {
    companies: Company[];
    selectedCompany: Company | null;
    isLoadingCompanies: boolean;
    error: string | null;
}

// Ações do contexto de empresa
export interface CompanyContextActions {
    selectCompany: (companyId: string | null) => void;
    refreshCompanies: () => Promise<void>;
    createCompany: (data: CreateCompanyDTO) => Promise<Company>;
    updateCompany: (id: string, data: UpdateCompanyDTO) => Promise<Company>;
    deleteCompany: (id: string) => Promise<void>;
    setAsDefault: (id: string) => Promise<void>;
}

// Tipo completo do contexto
export interface CompanyContextType extends CompanyContextState, CompanyContextActions { }

// Resposta da API de consulta de CNPJ (ReceitaWS)
export interface CNPJApiResponse {
    status: 'OK' | 'ERROR';
    cnpj?: string;
    nome?: string; // Razão Social
    fantasia?: string; // Nome Fantasia
    logradouro?: string;
    numero?: string;
    complemento?: string;
    bairro?: string;
    municipio?: string;
    uf?: string;
    cep?: string;
    email?: string;
    telefone?: string;
    situacao?: string;
    porte?: string;
    natureza_juridica?: string;
    abertura?: string;
    capital_social?: string;
    message?: string;
}

// Mapeamento de porte da API para o sistema
export const PORTE_MAP: Record<string, CompanySize> = {
    'MICRO EMPRESA': 'ME',
    'MICROEMPRESA': 'ME',
    'EMPRESA DE PEQUENO PORTE': 'EPP',
    'MEDIA EMPRESA': 'MEDIA',
    'DEMAIS': 'GRANDE',
    'MEI': 'MEI',
};

// Labels para exibição
export const COMPANY_SIZE_LABELS: Record<CompanySize, string> = {
    MEI: 'MEI - Microempreendedor Individual',
    ME: 'ME - Microempresa',
    EPP: 'EPP - Empresa de Pequeno Porte',
    MEDIA: 'Média Empresa',
    GRANDE: 'Grande Empresa',
};

export const TAX_REGIME_LABELS: Record<TaxRegime, string> = {
    SIMPLES: 'Simples Nacional',
    LUCRO_PRESUMIDO: 'Lucro Presumido',
    LUCRO_REAL: 'Lucro Real',
};

export const UF_OPTIONS = [
    'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA',
    'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN',
    'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'
] as const;
