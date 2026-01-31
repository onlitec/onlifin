import { useParams, useLocation } from 'react-router-dom';
import { usePerson } from '@/contexts/PersonContext';

/**
 * Hook para determinar o escopo financeiro atual (PF ou PJ)
 * com base na rota ativa.
 */
export function useFinanceScope() {
    const { companyId } = useParams<{ companyId: string }>();
    const location = useLocation();

    // Tentar obter o contexto de pessoa com segurança
    // Caso usado fora do provider (ex: login), falha silenciosamente
    let selectedPerson = null;
    try {
        // eslint-disable-next-line react-hooks/rules-of-hooks
        const personContext = usePerson();
        selectedPerson = personContext.selectedPerson;
    } catch (e) {
        // Ignora erro se fora do contexto
    }

    // PF se o caminho começa com /pf
    // PJ se o caminho começa com /pj
    const isPF = location.pathname.startsWith('/pf');
    const isPJ = location.pathname.startsWith('/pj');

    // companyId para chamadas de API:
    // - Para PJ: o ID da URL
    // - Para PF: null explicitly
    const currentCompanyId = isPJ ? (companyId || null) : null;

    // personId para chamadas de API (apenas relevante para PF):
    // - Para PJ: undefined (não filtra por pessoa)
    // - Para PF: selectedPerson.id ou null (se Main)
    const currentPersonId = isPF ? (selectedPerson?.id || null) : undefined;

    return {
        isPF,
        isPJ,
        companyId: currentCompanyId,
        personId: currentPersonId,
        mode: isPF ? 'PF' as const : 'PJ' as const
    };
}
