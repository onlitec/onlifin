import { DEFAULT_PLAN_CODE, getPlanDefinition, type PlanCode, type PlanDefinition } from '@/config/plans';
import { supabase } from '@/db/client';

type PlanSource = 'profile_settings' | 'tenant_record' | 'auth_metadata' | 'legacy_default';

interface ProfilePlanRow {
    settings?: {
        plan_code?: string;
        [key: string]: unknown;
    } | null;
    tenant_id?: string | null;
}

interface TenantPlanRow {
    plan_code?: string | null;
    plan?: string | null;
}

export interface CurrentPlanInfo {
    plan: PlanDefinition;
    planCode: PlanCode;
    source: PlanSource;
    tenantId: string | null;
    isConfigured: boolean;
}

export interface PlanUsageSnapshot {
    peopleCount: number;
    companiesCount: number;
}

const LEGACY_FALLBACK_PLAN_CODE: PlanCode = 'full';

function coercePlanCode(value: unknown): PlanCode | null {
    if (value !== 'basic' && value !== 'medium' && value !== 'full') {
        return null;
    }

    return value;
}

async function getCurrentProfilePlanRow(): Promise<ProfilePlanRow | null> {
    const { data: { user } } = await supabase.auth.getUser();
    if (!user) {
        return null;
    }

    const { data, error } = await supabase
        .from('profiles')
        .select('settings, tenant_id')
        .eq('id', user.id)
        .maybeSingle();

    if (error) {
        console.warn('Nao foi possivel carregar settings/tenant do perfil:', error);
        return null;
    }

    return (data as ProfilePlanRow | null) || null;
}

async function getTenantPlanCode(tenantId: string | null | undefined): Promise<PlanCode | null> {
    if (!tenantId) {
        return null;
    }

    const { data, error } = await supabase
        .from('tenants')
        .select('plan_code, plan')
        .eq('id', tenantId)
        .maybeSingle();

    if (error) {
        console.warn('Nao foi possivel carregar tenant para resolver plano:', error);
        return null;
    }

    const tenant = data as TenantPlanRow | null;
    return coercePlanCode(tenant?.plan_code) || coercePlanCode(tenant?.plan);
}

async function persistResolvedPlanCode(planCode: PlanCode, profile: ProfilePlanRow | null): Promise<void> {
    const { data: { user } } = await supabase.auth.getUser();
    if (!user) {
        return;
    }

    const nextSettings = {
        ...(profile?.settings || {}),
        plan_code: planCode,
    };

    const { error } = await supabase
        .from('profiles')
        .update({ settings: nextSettings })
        .eq('id', user.id);

    if (error) {
        console.warn('Nao foi possivel persistir plan_code resolvido no perfil:', error);
    }
}

export async function getCurrentPlanInfo(): Promise<CurrentPlanInfo> {
    const { data: { user } } = await supabase.auth.getUser();

    const metadataPlanCode = coercePlanCode(
        (user as any)?.app_metadata?.plan_code ||
        (user as any)?.app_metadata?.plan ||
        (user as any)?.user_metadata?.plan_code ||
        (user as any)?.user_metadata?.plan
    );

    const profile = await getCurrentProfilePlanRow();
    const profilePlanCode = coercePlanCode(profile?.settings?.plan_code);

    if (profilePlanCode) {
        return {
            plan: getPlanDefinition(profilePlanCode),
            planCode: profilePlanCode,
            source: 'profile_settings',
            tenantId: profile?.tenant_id || null,
            isConfigured: true,
        };
    }

    const tenantPlanCode = await getTenantPlanCode(profile?.tenant_id);
    if (tenantPlanCode) {
        if (!profilePlanCode) {
            void persistResolvedPlanCode(tenantPlanCode, profile);
        }
        return {
            plan: getPlanDefinition(tenantPlanCode),
            planCode: tenantPlanCode,
            source: 'tenant_record',
            tenantId: profile?.tenant_id || null,
            isConfigured: true,
        };
    }

    if (metadataPlanCode) {
        if (!profilePlanCode) {
            void persistResolvedPlanCode(metadataPlanCode, profile);
        }
        return {
            plan: getPlanDefinition(metadataPlanCode),
            planCode: metadataPlanCode,
            source: 'auth_metadata',
            tenantId: profile?.tenant_id || null,
            isConfigured: true,
        };
    }

    return {
        plan: getPlanDefinition(LEGACY_FALLBACK_PLAN_CODE || DEFAULT_PLAN_CODE),
        planCode: LEGACY_FALLBACK_PLAN_CODE,
        source: 'legacy_default',
        tenantId: profile?.tenant_id || null,
        isConfigured: false,
    };
}

export async function getCurrentPlanUsage(): Promise<PlanUsageSnapshot> {
    const { data: { user } } = await supabase.auth.getUser();
    if (!user) {
        throw new Error('Usuario nao autenticado');
    }

    const [
        { count: peopleCount, error: peopleError },
        { count: companiesCount, error: companiesError },
    ] = await Promise.all([
        supabase
            .from('people')
            .select('*', { count: 'exact', head: true })
            .eq('user_id', user.id),
        supabase
            .from('companies')
            .select('*', { count: 'exact', head: true })
            .eq('user_id', user.id)
            .eq('is_active', true),
    ]);

    if (peopleError) {
        throw new Error('Nao foi possivel verificar o consumo de pessoas do plano');
    }

    if (companiesError) {
        throw new Error('Nao foi possivel verificar o consumo de empresas do plano');
    }

    return {
        peopleCount: peopleCount || 0,
        companiesCount: companiesCount || 0,
    };
}

export async function assertCanCreateManagedPerson(): Promise<void> {
    const [{ plan }, usage] = await Promise.all([
        getCurrentPlanInfo(),
        getCurrentPlanUsage(),
    ]);

    if (usage.peopleCount >= plan.limits.managedPeople) {
        throw new Error(`Seu ${plan.name} permite ate ${plan.limits.managedPeople} pessoa(s) cadastrada(s).`);
    }
}

export async function assertCanCreateCompany(): Promise<void> {
    const [{ plan }, usage] = await Promise.all([
        getCurrentPlanInfo(),
        getCurrentPlanUsage(),
    ]);

    if (usage.companiesCount >= plan.limits.companies) {
        throw new Error(`Seu ${plan.name} permite ate ${plan.limits.companies} CNPJ(s) ativo(s).`);
    }
}

export function getPlanSourceLabel(source: PlanSource): string {
    switch (source) {
        case 'profile_settings':
            return 'perfil';
        case 'tenant_record':
            return 'tenant';
        case 'auth_metadata':
            return 'metadados';
        case 'legacy_default':
            return 'compatibilidade';
        default:
            return 'desconhecida';
    }
}
