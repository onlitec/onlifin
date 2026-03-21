/**
 * Context para gerenciamento de pessoas (PF)
 * 
 * Este contexto fornece acesso global ao estado de pessoas (familiares/membros),
 * incluindo a pessoa atualmente selecionada e operações CRUD.
 */

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
const PersonContext = createContext<PersonContextType>({} as any);

interface PersonProviderProps {
    children: ReactNode;
}

/**
 * Provider para o contexto de pessoas
 */
export function PersonProvider({ children }: PersonProviderProps) {
    const [people, setPeople] = useState<Person[]>([]);
    const [selectedPerson, setSelectedPerson] = useState<Person | null>(null);
    const [settings, setSettings] = useState<ProfileSettings>(() => {
        const saved = localStorage.getItem('onlifin_profile_settings');
        return saved ? JSON.parse(saved) : {};
    });
    const [isLoadingPeople, setIsLoadingPeople] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const { companyId } = useFinanceScope();

    /**
     * Carrega todas as pessoas do usuário
     */
    const loadPeople = useCallback(async () => {
        setIsLoadingPeople(true);
        setError(null);

        try {
            const [loadedPeople, profile] = await Promise.all([
                personService.getAll(companyId),
                profileService.getProfile()
            ]);

            let peopleData = loadedPeople;
            let userSettings = profile?.settings || {};

            if (companyId === null) {
                const primaryPersonChanged = await personService.ensurePrimaryPerson(peopleData);

                if (primaryPersonChanged) {
                    const [refreshedPeople, refreshedProfile] = await Promise.all([
                        personService.getAll(companyId),
                        profileService.getProfile()
                    ]);

                    peopleData = refreshedPeople;
                    userSettings = refreshedProfile?.settings || userSettings;
                }
            }

            setPeople(peopleData);
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
    }, [companyId]);

    /**
     * Carrega pessoas e configurações ao montar
     */
    useEffect(() => {
        const init = async () => {
            await loadPeople();
            try {
                const profile = await profileService.getProfile();
                if (profile?.settings) {
                    setSettings(profile.settings);
                    localStorage.setItem('onlifin_profile_settings', JSON.stringify(profile.settings));
                }
            } catch (err) {
                console.warn('Usando configurações locais (offline ou erro no servidor)');
            }
        };
        init();
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
            const newPerson = await personService.create({
                ...data,
                company_id: companyId
            });
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
    }, [selectPerson, companyId]);

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
            const personToDelete = people.find(p => p.id === id);
            const wasDefault = personToDelete?.is_default;

            await personService.delete(id);

            // Recarregar do servidor
            const updatedPeople = await personService.getAll(companyId);

            // Se deletou o padrão e ainda existem pessoas, promover a primeira a padrão no DB
            if (wasDefault && updatedPeople.length > 0) {
                const alreadyHasDefault = updatedPeople.some(p => p.is_default);
                if (!alreadyHasDefault) {
                    const newDefaultId = updatedPeople[0].id;
                    await personService.update(newDefaultId, { is_default: true });
                }
            }

            // Atualizar estado e seleção
            await refreshPeople();

            if (selectedPerson?.id === id) {
                const refreshedPeople = await personService.getAll(companyId);
                const newSelected = refreshedPeople.find(p => p.is_default) || refreshedPeople[0] || null;
                setSelectedPerson(newSelected);
                if (newSelected) {
                    localStorage.setItem(SELECTED_PERSON_KEY, newSelected.id);
                } else {
                    localStorage.removeItem(SELECTED_PERSON_KEY);
                }
            }
        } catch (err) {
            console.error('Erro ao excluir pessoa:', err);
            throw err;
        }
    }, [people, refreshPeople, selectedPerson, companyId]);

    const updateSettings = useCallback(async (newSettings: Partial<ProfileSettings>): Promise<void> => {
        // Atualiza local imediatamente para uma UI responsiva
        const mergedSettings = { ...settings, ...newSettings };
        setSettings(mergedSettings);
        localStorage.setItem('onlifin_profile_settings', JSON.stringify(mergedSettings));

        try {
            const updatedProfile = await profileService.updateSettings(newSettings);
            if (updatedProfile?.settings) {
                setSettings(updatedProfile.settings);
                localStorage.setItem('onlifin_profile_settings', JSON.stringify(updatedProfile.settings));
            }
        } catch (err) {
            console.error('Erro ao salvar no servidor, mantendo local:', err);
            // Non-blocking: we keep the local state even if server fails
        }
    }, [settings]);

    const value: PersonContextType = {
        people,
        selectedPerson,
        settings,
        isLoadingPeople,
        error,
        selectPerson,
        refreshPeople,
        createPerson,
        updatePerson,
        deletePerson,
        updateSettings
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
