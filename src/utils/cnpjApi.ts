/**
 * Serviço para consulta de CNPJ na Receita Federal
 * Utiliza a API ReceitaWS (https://receitaws.com.br)
 */

import {
    Company,
    CNPJApiResponse,
    CreateCompanyDTO,
    CompanySize,
    PORTE_MAP
} from '@/types/company';
import { onlyNumbers } from './validators';

const RECEITA_WS_API = 'https://receitaws.com.br/v1/cnpj';
const BRASIL_API = 'https://brasilapi.com.br/api/cnpj/v1';

/**
 * Consulta dados de um CNPJ na API da Receita Federal
 * 
 * @param cnpj - CNPJ a ser consultado (com ou sem formatação)
 * @returns Dados da empresa ou null se não encontrado
 * 
 * @example
 * const dados = await consultarCNPJ('12.345.678/0001-90');
 * if (dados) {
 *   console.log(dados.razao_social);
 * }
 */
export const consultarCNPJ = async (cnpj: string): Promise<CreateCompanyDTO | null> => {
    const cleanCNPJ = onlyNumbers(cnpj);

    if (cleanCNPJ.length !== 14) {
        throw new Error('CNPJ inválido: deve conter 14 dígitos');
    }

    // Tentar primeiro a BrasilAPI (sem limites de requisição)
    try {
        const response = await fetch(`${BRASIL_API}/${cleanCNPJ}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
            },
        });

        if (response.ok) {
            const data = await response.json();
            return parseBrasilAPIResponse(data);
        }
    } catch (error) {
        console.warn('BrasilAPI falhou, tentando ReceitaWS:', error);
    }

    // Fallback para ReceitaWS
    try {
        const response = await fetch(`${RECEITA_WS_API}/${cleanCNPJ}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
            },
        });

        if (!response.ok) {
            if (response.status === 429) {
                throw new Error('Limite de consultas excedido. Tente novamente em alguns segundos.');
            }
            throw new Error(`Erro na consulta: ${response.statusText}`);
        }

        const data: CNPJApiResponse = await response.json();

        if (data.status === 'ERROR') {
            throw new Error(data.message || 'CNPJ não encontrado');
        }

        return parseReceitaWSResponse(data);
    } catch (error) {
        if (error instanceof Error) {
            throw error;
        }
        throw new Error('Erro ao consultar CNPJ. Tente novamente.');
    }
};

/**
 * Parseia a resposta da BrasilAPI para o formato da aplicação
 */
const parseBrasilAPIResponse = (data: Record<string, unknown>): CreateCompanyDTO => {
    // Mapear porte da empresa
    let porte: CompanySize | undefined;
    const porteString = (data.porte as string)?.toUpperCase();
    if (porteString) {
        porte = PORTE_MAP[porteString] || undefined;
    }

    // Formatar o CNPJ
    const cnpj = onlyNumbers(data.cnpj as string || '');
    const formattedCNPJ = `${cnpj.slice(0, 2)}.${cnpj.slice(2, 5)}.${cnpj.slice(5, 8)}/${cnpj.slice(8, 12)}-${cnpj.slice(12)}`;

    return {
        cnpj: formattedCNPJ,
        razao_social: (data.razao_social as string) || '',
        nome_fantasia: (data.nome_fantasia as string) || undefined,
        logradouro: (data.logradouro as string) || undefined,
        numero: (data.numero as string) || undefined,
        complemento: (data.complemento as string) || undefined,
        bairro: (data.bairro as string) || undefined,
        cidade: (data.municipio as string) || undefined,
        uf: (data.uf as string) || undefined,
        cep: (data.cep as string)?.replace(/\D/g, '') || undefined,
        email: (data.email as string)?.toLowerCase() || undefined,
        phone: (data.ddd_telefone_1 as string) || undefined,
        porte,
    };
};

/**
 * Parseia a resposta da ReceitaWS para o formato da aplicação
 */
const parseReceitaWSResponse = (data: CNPJApiResponse): CreateCompanyDTO => {
    // Mapear porte da empresa
    let porte: CompanySize | undefined;
    if (data.porte) {
        const porteUpper = data.porte.toUpperCase();
        porte = PORTE_MAP[porteUpper] || undefined;
    }

    return {
        cnpj: data.cnpj || '',
        razao_social: data.nome || '',
        nome_fantasia: data.fantasia || undefined,
        logradouro: data.logradouro || undefined,
        numero: data.numero || undefined,
        complemento: data.complemento || undefined,
        bairro: data.bairro || undefined,
        cidade: data.municipio || undefined,
        uf: data.uf || undefined,
        cep: data.cep?.replace(/\D/g, '') || undefined,
        email: data.email?.toLowerCase() || undefined,
        phone: data.telefone || undefined,
        porte,
    };
};

/**
 * Consulta endereço por CEP usando a API ViaCEP
 * 
 * @param cep - CEP a ser consultado (apenas números)
 * @returns Dados do endereço ou null se não encontrado
 */
export const consultarCEP = async (cep: string): Promise<{
    logradouro: string;
    bairro: string;
    cidade: string;
    uf: string;
} | null> => {
    const cleanCEP = onlyNumbers(cep);

    if (cleanCEP.length !== 8) {
        throw new Error('CEP inválido: deve conter 8 dígitos');
    }

    try {
        const response = await fetch(`https://viacep.com.br/ws/${cleanCEP}/json/`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error('Erro ao consultar CEP');
        }

        const data = await response.json();

        if (data.erro) {
            throw new Error('CEP não encontrado');
        }

        return {
            logradouro: data.logradouro || '',
            bairro: data.bairro || '',
            cidade: data.localidade || '',
            uf: data.uf || '',
        };
    } catch (error) {
        if (error instanceof Error) {
            throw error;
        }
        throw new Error('Erro ao consultar CEP. Tente novamente.');
    }
};

/**
 * Verifica a situação cadastral de um CNPJ
 * 
 * @param cnpj - CNPJ a ser verificado
 * @returns Status da situação cadastral
 */
export const verificarSituacaoCNPJ = async (cnpj: string): Promise<{
    ativo: boolean;
    situacao: string;
    dataAbertura?: string;
}> => {
    const cleanCNPJ = onlyNumbers(cnpj);

    try {
        const response = await fetch(`${BRASIL_API}/${cleanCNPJ}`);

        if (!response.ok) {
            throw new Error('Não foi possível verificar a situação do CNPJ');
        }

        const data = await response.json();

        return {
            ativo: (data.descricao_situacao_cadastral as string)?.toUpperCase() === 'ATIVA',
            situacao: data.descricao_situacao_cadastral || 'Desconhecida',
            dataAbertura: data.data_inicio_atividade,
        };
    } catch (error) {
        console.error('Erro ao verificar situação do CNPJ:', error);
        return {
            ativo: false,
            situacao: 'Não foi possível verificar',
        };
    }
};

/**
 * Gera dados fictícios de empresa para testes (DEV ONLY)
 * 
 * @returns Dados de empresa de exemplo
 */
export const gerarEmpresaTeste = (): CreateCompanyDTO => {
    const cnpjsValidos = [
        '11.222.333/0001-81',
        '22.333.444/0001-91',
        '33.444.555/0001-01',
    ];

    return {
        cnpj: cnpjsValidos[Math.floor(Math.random() * cnpjsValidos.length)],
        razao_social: 'Empresa Teste Ltda',
        nome_fantasia: 'Empresa Teste',
        logradouro: 'Rua Teste',
        numero: '123',
        bairro: 'Centro',
        cidade: 'São Paulo',
        uf: 'SP',
        cep: '01234567',
        email: 'contato@empresateste.com.br',
        phone: '(11) 99999-9999',
        porte: 'ME',
        regime_tributario: 'SIMPLES',
    };
};
