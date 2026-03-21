/**
 * Serviço de gerenciamento de pessoas (PF)
 */

import { supabase } from '@/db/client';
import { assertCanCreateManagedPerson } from '@/services/planService';
import { profileService } from '@/services/profileService';
import type {
    Person,
    CreatePersonDTO,
    UpdatePersonDTO
} from '@/types/person';

export const personService = {
    async ensurePrimaryPerson(existingPeople?: Person[]): Promise<boolean> {
        const { data: { user } } = await supabase.auth.getUser();
        if (!user) {
            return false;
        }

        const profile = await profileService.getProfile();
        if (!profile) {
            return false;
        }

        const people = existingPeople ?? await personService.getAll(null);
        const ownerPersonId = profile.settings?.owner_person_id || null;
        const ownerPerson = ownerPersonId ? people.find((person) => person.id === ownerPersonId) : null;
        const hasDefaultPerson = people.some((person) => person.is_default);

        if (ownerPerson) {
            let hasChanged = false;

            if (!hasDefaultPerson) {
                await personService.update(ownerPerson.id, { is_default: true });
                hasChanged = true;
            }

            if (profile.settings?.hide_titular !== true) {
                await profileService.updateSettings({ hide_titular: true });
                hasChanged = true;
            }

            return hasChanged;
        }

        if (people.length > 0) {
            const primaryPerson = people.find((person) => person.is_default) || people[0];

            if (!primaryPerson.is_default) {
                await personService.update(primaryPerson.id, { is_default: true });
            }

            await profileService.updateSettings({
                owner_person_id: primaryPerson.id,
                hide_titular: true,
            });

            return true;
        }

        const personName = profile.full_name?.trim()
            || profile.username?.trim()
            || user.email?.split('@')[0]
            || 'Titular';

        const { data: createdPerson, error } = await supabase
            .from('people')
            .insert([{
                user_id: user.id,
                company_id: null,
                name: personName,
                email: profile.email || user.email || null,
                is_default: true,
                color: '#2563eb',
            }])
            .select()
            .single();

        if (error) {
            console.error('Erro ao criar pessoa titular:', error);
            throw new Error('Não foi possível criar a pessoa titular da conta');
        }

        await profileService.updateSettings({
            owner_person_id: createdPerson.id,
            hide_titular: true,
        });

        return true;
    },

    /**
     * Busca todas as pessoas do usuário
     * @param companyId - Opcional: ID da empresa para filtrar (null para PF)
     */
    async getAll(companyId?: string | null): Promise<Person[]> {
        let query = supabase
            .from('people')
            .select('*');

        if (companyId !== undefined) {
            if (companyId === null) {
                query = query.is('company_id', null);
            } else {
                query = query.eq('company_id', companyId);
            }
        }

        const { data, error } = await query
            .order('is_default', { ascending: false })
            .order('name', { ascending: true });

        if (error) {
            console.error('Erro ao buscar pessoas:', error);
            throw new Error('Não foi possível carregar as pessoas');
        }

        return data || [];
    },

    /**
     * Cria uma nova pessoa
     */
    async create(data: CreatePersonDTO & { company_id?: string | null }): Promise<Person> {
        const { data: { user } } = await supabase.auth.getUser();
        if (!user) {
            throw new Error('Usuário não autenticado');
        }

        await assertCanCreateManagedPerson();

        const { data: insertData, error } = await supabase
            .from('people')
            .insert([{
                ...data,
                user_id: user.id
            }])
            .select()
            .single();

        if (error) {
            console.error('Erro ao criar pessoa:', error);
            throw new Error('Não foi possível criar a pessoa');
        }

        return insertData;
    },

    /**
     * Atualiza uma pessoa
     */
    async update(id: string, data: UpdatePersonDTO): Promise<Person> {
        const { data: updatedData, error } = await supabase
            .from('people')
            .update({
                ...data,
                updated_at: new Date().toISOString()
            })
            .eq('id', id)
            .select()
            .single();

        if (error) {
            console.error('Erro ao atualizar pessoa:', error);
            throw new Error('Não foi possível atualizar a pessoa');
        }

        return updatedData;
    },

    /**
     * Exclui uma pessoa
     */
    async delete(id: string): Promise<void> {
        const profile = await profileService.getProfile();
        if (profile?.settings?.owner_person_id === id) {
            throw new Error('A pessoa titular da conta não pode ser excluída');
        }

        const { error } = await supabase
            .from('people')
            .delete()
            .eq('id', id);

        if (error) {
            console.error('Erro ao excluir pessoa:', error);
            throw new Error('Não foi possível excluir a pessoa');
        }
    }
};
