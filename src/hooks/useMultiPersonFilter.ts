/**
 * Hook para filtro multi-person (PF)
 * 
 * Similar ao useMultiCompanyFilter, mas para o contexto de Pessoa Física.
 * Retorna os filtros necessários para isolar dados por pessoa selecionada.
 */

import { useMemo } from 'react';
import { usePerson } from '@/contexts/PersonContext';
import { useFinanceScope } from './useFinanceScope';

export interface PersonFilter {
    person_id?: string;
    company_id?: null;
}

export function useMultiPersonFilter() {
    const { selectedPerson } = usePerson();
    const { isPF } = useFinanceScope();

    const personFilter = useMemo((): PersonFilter => {
        // Só aplica filtro se estiver no contexto PF
        if (!isPF) {
            return {};
        }

        // Se não tem pessoa selecionada, não retorna dados (força seleção)
        if (!selectedPerson) {
            return { person_id: 'none' }; // ID impossível para forçar resultado vazio
        }

        return {
            person_id: selectedPerson.id,
            company_id: null // Garante que só pega dados PF
        };
    }, [selectedPerson, isPF]);

    return {
        personFilter,
        selectedPersonId: selectedPerson?.id || null,
        selectedPersonName: selectedPerson?.name || null,
        isPersonSelected: !!selectedPerson
    };
}
