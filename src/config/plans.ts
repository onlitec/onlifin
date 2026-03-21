export type PlanCode = 'basic' | 'medium' | 'full';

export interface PlanDefinition {
    code: PlanCode;
    name: string;
    audience: string;
    monthlyPriceBrl: number;
    limits: {
        managedPeople: number;
        companies: number;
    };
    features: {
        debts: boolean;
        reconciliation: boolean;
        advancedReports: boolean;
        financialForecast: boolean;
        prioritySupport: boolean;
        futureBankIntegrations: boolean;
    };
}

export const PLAN_DEFINITIONS: Record<PlanCode, PlanDefinition> = {
    basic: {
        code: 'basic',
        name: 'Plano Basico',
        audience: 'Pessoa Fisica',
        monthlyPriceBrl: 29,
        limits: {
            managedPeople: 1,
            companies: 1,
        },
        features: {
            debts: false,
            reconciliation: false,
            advancedReports: false,
            financialForecast: false,
            prioritySupport: false,
            futureBankIntegrations: false,
        },
    },
    medium: {
        code: 'medium',
        name: 'Plano Intermediario',
        audience: 'Pequeno Negocio',
        monthlyPriceBrl: 79,
        limits: {
            managedPeople: 2,
            companies: 2,
        },
        features: {
            debts: true,
            reconciliation: true,
            advancedReports: true,
            financialForecast: true,
            prioritySupport: false,
            futureBankIntegrations: false,
        },
    },
    full: {
        code: 'full',
        name: 'Plano Completo',
        audience: 'Operacao Estruturada',
        monthlyPriceBrl: 199,
        limits: {
            managedPeople: 10,
            companies: 10,
        },
        features: {
            debts: true,
            reconciliation: true,
            advancedReports: true,
            financialForecast: true,
            prioritySupport: true,
            futureBankIntegrations: true,
        },
    },
};

export const DEFAULT_PLAN_CODE: PlanCode = 'basic';

export function getPlanDefinition(planCode?: string | null): PlanDefinition {
    if (!planCode) {
        return PLAN_DEFINITIONS[DEFAULT_PLAN_CODE];
    }

    return PLAN_DEFINITIONS[planCode as PlanCode] || PLAN_DEFINITIONS[DEFAULT_PLAN_CODE];
}
