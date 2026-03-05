/**
 * Context para gerenciamento de pessoas (PF)
 * 
 * Este contexto fornece acesso global ao estado de pessoas (familiares/membros),
 * incluindo a pessoa atualmente selecionada e operações CRUD.
 */

import * as React from 'react';
import { createContext, useContext, useState, useEffect, useCallback, ReactNode } from 'react';
import { personService } from '@/services/personService';
import { profileService, ProfileSettings } from '@/services/profileService';
import type {
    Person,
    CreatePersonDTO,
    UpdatePersonDTO,
    PersonContextType
} from '@/types/person';
import { useFinanceScope } from '@/hooks/useFinanceScope';

// Chave para armazenar pessoa selecionada no localStorage
const SELECTED_PERSON_KEY = 'onlifin_selected_person_id';

// Criar o contexto com valor inicial undefined
const PersonContext = createContext<PersonContextType & { settings: ProfileSettings }>({} as any);

interface PersonProviderProps {
    children: ReactNode;
}

/**
 * Provider para o contexto de pessoas
 */
export function PersonProvider({ children }: PersonProviderProps) {
    const [people, setPeople] = useState<Person[]>([]);
    const [selectedPerson, setSelectedPerson] = useState<Person | null>(null);
    const [settings, setSettings] = useState<ProfileSettings>({});
    const [isLoadingPeople, setIsLoadingPeople] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const { isPJ } = useFinanceScope();

    /**
     * Carrega todas as pessoas do usuário
     */
    const loadPeople = useCallback(async () => {
        setIsLoadingPeople(true);
        setError(null);

        try {
            const [peopleData, profile] = await Promise.all([
                personService.getAll(),
                profileService.getProfile()
            ]);

            setPeople(peopleData);
            const userSettings = profile?.settings || {};
            setSettings(userSettings);

            // Recuperar pessoa selecionada do localStorage
            const savedPersonId = localStorage.getItem(SELECTED_PERSON_KEY);

            if (savedPersonId) {
                const savedPerson = peopleData.find(p => p.id === savedPersonId);
                if (savedPerson) {
                    setSelectedPerson(savedPerson);
                    return;
                }
            }

            const defaultPerson = peopleData.find(p => p.is_default);
            if (defaultPerson) {
                setSelectedPerson(defaultPerson);
                localStorage.setItem(SELECTED_PERSON_KEY, defaultPerson.id);
            } else if (userSettings.hide_titular && peopleData.length > 0) {
                // Se deve esconder o titular e temos pessoas, seleciona a primeira como fallback
                setSelectedPerson(peopleData[0]);
                localStorage.setItem(SELECTED_PERSON_KEY, peopleData[0].id);
            } else {
                setSelectedPerson(null);
            }

        } catch (err) {
            console.error('Erro ao carregar pessoas:', err);
            setError(err instanceof Error ? err.message : 'Erro ao carregar pessoas');
        } finally {
            setIsLoadingPeople(false);
        }
    }, []);

    /**
     * Carrega pessoas ao montar
     */
    useEffect(() => {
        // Só carrega se NÃO for PJ (ou carrega sempre? melhor carregar sempre para ter disponível se trocar)
        // Mas se estiver em PJ, talvez não precise. Por enquanto carrega sempre.
        loadPeople();
    }, [loadPeople]);

    /**
     * Seleciona uma pessoa pelo ID
     */
    const selectPerson = useCallback((personId: string | null) => {
        if (personId === null) {
            setSelectedPerson(null);
            localStorage.removeItem(SELECTED_PERSON_KEY);
            return;
        }

        const person = people.find(p => p.id === personId);
        if (person) {
            setSelectedPerson(person);
            localStorage.setItem(SELECTED_PERSON_KEY, personId);
        } else {
            console.warn(`Pessoa com ID ${personId} não encontrada`);
            // Fallback to null
            setSelectedPerson(null);
            localStorage.removeItem(SELECTED_PERSON_KEY);
        }
    }, [people]);

    /**
     * Atualiza a lista
     */
    const refreshPeople = useCallback(async () => {
        await loadPeople();
    }, [loadPeople]);

    /**
     * Cria
     */
    const createPerson = useCallback(async (data: CreatePersonDTO): Promise<Person> => {
        try {
            const newPerson = await personService.create(data);
            setPeople(prev => [newPerson, ...prev]);

            // Se for marcado como default, seleciona
            if (data.is_default) {
                selectPerson(newPerson.id); // Re-set others as non-default handled by DB? 
                // DB doesn't ensure only one default usually unless trigger, assuming UI handles it or singular default.
                // For simplified logic:
                // We should probably update local list defaults if needed, but reloading is safer.
                // Let's just append for now.
            }
            return newPerson;
        } catch (err) {
            console.error('Erro ao criar pessoa:', err);
            throw err;
        }
    }, [selectPerson]);

    /**
     * Atualiza
     */
    const updatePerson = useCallback(async (id: string, data: UpdatePersonDTO): Promise<Person> => {
        try {
            const updatedPerson = await personService.update(id, data);
            setPeople(prev => prev.map(p => p.id === id ? updatedPerson : p));

            if (selectedPerson?.id === id) {
                setSelectedPerson(updatedPerson);
            }
            return updatedPerson;
        } catch (err) {
            console.error('Erro ao atualizar pessoa:', err);
            throw err;
        }
    }, [selectedPerson]);

    /**
     * Exclui
     */
    const deletePerson = useCallback(async (id: string): Promise<void> => {
        try {
            await personService.delete(id);
            setPeople(prev => prev.filter(p => p.id !== id));

            if (selectedPerson?.id === id) {
                setSelectedPerson(null);
                localStorage.removeItem(SELECTED_PERSON_KEY);
            }
        } catch (err) {
            console.error('Erro ao excluir pessoa:', err);
            throw err;
        }
    }, [selectedPerson]);


    const value: PersonContextType & { settings: ProfileSettings } = {
        people,
        selectedPerson,
        settings,
        isLoadingPeople,
        error,
        selectPerson,
        refreshPeople,
        createPerson,
        updatePerson,
        deletePerson
    };

    return (
        <PersonContext.Provider value={value}>
            {children}
        </PersonContext.Provider>
    );
}

/**
 * Hook para acessar o contexto de pessoas
 */
export function usePerson(): PersonContextType {
    const context = useContext(PersonContext);
    if (context === undefined) {
        throw new Error('usePerson deve ser usado dentro de um PersonProvider');
    }
    return context;
}

/**
 * Hook para obter o ID da pessoa selecionada
 */
export function useSelectedPersonId(): string | null {
    const { selectedPerson } = usePerson();
    return selectedPerson?.id || null;
}
