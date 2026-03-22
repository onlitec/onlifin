/**
 * Serviço de gerenciamento de perfil e configurações
 */

import { requireCurrentUser, supabase } from '@/db/client';
import type { PlanCode } from '@/config/plans';

export interface ProfileSettings {
    hide_titular?: boolean;
    titular_name?: string;
    plan_code?: PlanCode;
    owner_person_id?: string;
    notification_email?: string | null;
    notification_whatsapp?: string | null;
    [key: string]: any;
}

export interface Profile {
    id: string;
    username: string;
    full_name: string | null;
    email?: string | null;
    role: string;
    settings: ProfileSettings;
    default_company_id: string | null;
}

export const profileService = {
    /**
     * Busca o perfil do usuário atual
     */
    async getProfile(): Promise<Profile | null> {
        const user = await requireCurrentUser().catch(() => null);
        if (!user) return null;

        const { data, error } = await supabase
            .from('profiles')
            .select('*')
            .eq('id', user.id)
            .maybeSingle();

        if (error) {
            console.error('Erro ao buscar perfil:', error);
            throw new Error('Não foi possível carregar o perfil');
        }

        return data;
    },

    /**
     * Atualiza as configurações do perfil
     */
    async updateSettings(settings: Partial<ProfileSettings>): Promise<Profile> {
        const user = await requireCurrentUser();

        // Buscar configurações atuais primeiro para fazer merge
        const currentProfile = await this.getProfile();
        const currentSettings = currentProfile?.settings || {};

        const newSettings = {
            ...currentSettings,
            ...settings
        };

        const { data, error } = await supabase
            .from('profiles')
            .update({
                settings: newSettings
            })
            .eq('id', user.id)
            .select()
            .single();

        if (error) {
            console.error('Erro ao atualizar configurações:', error);
            throw new Error('Não foi possível salvar as configurações');
        }

        return data;
    }
};
