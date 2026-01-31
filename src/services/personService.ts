/**
 * Serviço de gerenciamento de pessoas (PF)
 */

import { supabase } from '@/db/client';
import type {
    Person,
    CreatePersonDTO,
    UpdatePersonDTO
} from '@/types/person';

export const personService = {
    /**
     * Busca todas as pessoas do usuário
     */
    async getAll(): Promise<Person[]> {
        const { data, error } = await supabase
            .from('people')
            .select('*')
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
    async create(data: CreatePersonDTO): Promise<Person> {
        const { data: { user } } = await supabase.auth.getUser();
        if (!user) {
            throw new Error('Usuário não autenticado');
        }

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
