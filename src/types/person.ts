/**
 * Types relacionados a Pessoas (PF) e sistema de membros da família
 */

export interface Person {
    id: string;
    user_id: string;
    name: string;
    cpf?: string | null;
    email?: string | null;
    is_default: boolean;
    created_at: string;
    updated_at: string;
}

export interface CreatePersonDTO {
    name: string;
    cpf?: string;
    email?: string;
    is_default?: boolean;
}

export interface UpdatePersonDTO {
    name?: string;
    cpf?: string;
    email?: string;
    is_default?: boolean;
}

export interface PersonContextState {
    people: Person[];
    selectedPerson: Person | null;
    isLoadingPeople: boolean;
    error: string | null;
}

export interface PersonContextActions {
    selectPerson: (personId: string | null) => void;
    refreshPeople: () => Promise<void>;
    createPerson: (data: CreatePersonDTO) => Promise<Person>;
    updatePerson: (id: string, data: UpdatePersonDTO) => Promise<Person>;
    deletePerson: (id: string) => Promise<void>;
}

export interface PersonContextType extends PersonContextState, PersonContextActions { }
