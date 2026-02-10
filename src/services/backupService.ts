/**
 * Serviço de Backup e Restauração
 * Lida com a exportação e importação de dados da plataforma Onlifin
 */

import { supabase } from '@/db/client';

export interface BackupData {
    version: string;
    timestamp: string;
    data: {
        people: any[];
        companies: any[];
        accounts: any[];
        cards: any[];
        transactions: any[];
        categories: any[];
        bills_to_pay: any[];
        bills_to_receive: any[];
        financial_forecasts: any[];
    };
}

const TABLES = [
    'people',
    'companies',
    'accounts',
    'cards',
    'transactions',
    'categories',
    'bills_to_pay',
    'bills_to_receive',
    'financial_forecasts'
];

export const backupService = {
    /**
     * Exporta todos os dados do usuário atual
     */
    async exportBackup(): Promise<BackupData> {
        const { data: { user } } = await supabase.auth.getUser();
        if (!user) throw new Error('Usuário não autenticado');

        const backup: BackupData = {
            version: '1.0',
            timestamp: new Date().toISOString(),
            data: {
                people: [],
                companies: [],
                accounts: [],
                cards: [],
                transactions: [],
                categories: [],
                bills_to_pay: [],
                bills_to_receive: [],
                financial_forecasts: []
            }
        };

        // Busca dados de todas as tabelas em paralelo
        const results = await Promise.all(
            TABLES.map(table =>
                supabase.from(table).select('*').eq('user_id', user.id)
            )
        );

        // Mapeia os resultados para o objeto de backup
        TABLES.forEach((table, index) => {
            const { data, error } = results[index];
            if (error) {
                console.error(`Erro ao exportar tabela ${table}:`, error);
                return;
            }
            if (data) {
                (backup.data as any)[table] = data;
            }
        });

        // Caso especial: categorias do sistema (user_id is null) - talvez não queiramos exportar, 
        // mas as personalizadas sim. O filtro .eq('user_id', user.id) já pega as personalizadas.

        return backup;
    },

    /**
     * Importa dados de um objeto de backup
     */
    async importBackup(backup: BackupData): Promise<{ success: boolean; errors: string[] }> {
        const { data: { user } } = await supabase.auth.getUser();
        if (!user) throw new Error('Usuário não autenticado');

        const errors: string[] = [];

        // A ordem de importação importa devido às chaves estrangeiras
        // 1. Entidades principais (People, Companies, Categories)
        // 2. Contas e Cartões (que dependem de people/companies)
        // 3. Transações e Contas a pagar/receber (que dependem de contas/cartões/categorias)
        const importOrder = [
            'people',
            'companies',
            'categories',
            'accounts',
            'cards',
            'transactions',
            'bills_to_pay',
            'bills_to_receive',
            'financial_forecasts'
        ];

        for (const table of importOrder) {
            const records = (backup.data as any)[table];
            if (!records || !Array.isArray(records) || records.length === 0) continue;

            // Garante que o user_id é o do usuário atual para segurança
            const recordsWithCorrectUser = records.map(r => ({
                ...r,
                user_id: user.id
            }));

            // Tenta fazer o upsert (insere ou atualiza se o ID já existir)
            const { error } = await supabase
                .from(table)
                .upsert(recordsWithCorrectUser, {
                    onConflict: 'id',
                    ignoreDuplicates: false
                });

            if (error) {
                console.error(`Erro ao importar tabela ${table}:`, error);
                errors.push(`Erro na tabela ${table}: ${error.message}`);
            }
        }

        return {
            success: errors.length === 0,
            errors
        };
    },

    /**
     * Gera o download do arquivo JSON
     */
    downloadAsJson(data: BackupData) {
        const json = JSON.stringify(data, null, 2);
        const blob = new Blob([json], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        const date = new Date().toISOString().split('T')[0];

        a.href = url;
        a.download = `onlifin-backup-${date}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
};
