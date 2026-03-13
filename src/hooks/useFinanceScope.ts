import { useParams, useLocation } from 'react-router-dom';
import { usePerson } from '@/contexts/PersonContext';
import { useCompany } from '@/contexts/CompanyContext';

/**
 * Hook para determinar o escopo financeiro atual (PF ou PJ)
 * com base na rota ativa.
 */
export function useFinanceScope() {
    const { companyId } = useParams<{ companyId: string }>();
    const location = useLocation();

    let selectedPerson = null;
    let selectedCompany = null;
    
    try {
        // eslint-disable-next-line react-hooks/rules-of-hooks
        const personContext = usePerson();
        selectedPerson = personContext.selectedPerson;
    } catch (e) {}

    try {
        // eslint-disable-next-line react-hooks/rules-of-hooks
        const companyContext = useCompany();
        selectedCompany = companyContext.selectedCompany;
    } catch (e) {}

    // PF se o caminho começa com /pf
    // PJ se o caminho começa com /pj
    const isPF = location.pathname.startsWith('/pf');
    const isPJ = location.pathname.startsWith('/pj') || location.pathname.startsWith('/companies');

    // companyId para chamadas de API:
    // - Para PJ: o ID da URL
    // - Para PF: null (filtra apenas contas sem empresa)
    const currentCompanyId = isPJ ? (companyId || selectedCompany?.id || null) : (isPF ? null : undefined);

    // personId para chamadas de API (apenas relevante para PF):
    // - Para PJ: undefined (não filtra por pessoa)
    // - Para PF: selectedPerson.id ou undefined (se Main - mostra todos)
    const currentPersonId = isPF ? (selectedPerson?.id || undefined) : undefined;

    return {
        isPF,
        isPJ,
        companyId: currentCompanyId,
        personId: currentPersonId,
        mode: isPF ? 'PF' as const : 'PJ' as const
    };
}
