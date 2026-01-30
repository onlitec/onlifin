import { useParams, useLocation } from 'react-router-dom';

/**
 * Hook para determinar o escopo financeiro atual (PF ou PJ)
 * com base na rota ativa.
 */
export function useFinanceScope() {
    const { companyId } = useParams<{ companyId: string }>();
    const location = useLocation();

    // PF se o caminho começa com /pf
    // PJ se o caminho começa com /pj
    const isPF = location.pathname.startsWith('/pf');
    const isPJ = location.pathname.startsWith('/pj');

    // companyId para chamadas de API:
    // - Para PJ: o ID da URL
    // - Para PF: null explicitly
    const currentCompanyId = isPJ ? (companyId || null) : null;

    return {
        isPF,
        isPJ,
        companyId: currentCompanyId,
        mode: isPF ? 'PF' as const : 'PJ' as const
    };
}
