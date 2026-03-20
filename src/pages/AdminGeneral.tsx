import * as React from 'react';
import { Navigate } from 'react-router-dom';
import { supabase } from '@/db/client';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useToast } from '@/hooks/use-toast';
import {
    Database,
    AlertTriangle,
    CreditCard,
    Wallet,
    Receipt,
    FolderOpen,
    MessageSquare,
    FileUp,
    Loader2,
    Activity,
    PlusCircle,
    Trash2,
    Trash
} from 'lucide-react';
import { adminApi } from '@/db/api';
import { format } from 'date-fns';
import { ptBR } from 'date-fns/locale';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useAuth } from 'miaoda-auth-react';

interface DataCounts {
    transactions: number;
    accounts: number;
    cards: number;
    categories: number;
    ai_chat_logs: number;
    import_history: number;
}

export default function AdminGeneral() {
    const { user } = useAuth();
    const [counts, setCounts] = React.useState<DataCounts>({
        transactions: 0,
        accounts: 0,
        cards: 0,
        categories: 0,
        ai_chat_logs: 0,
        import_history: 0
    });
    const [isLoading, setIsLoading] = React.useState(false);
    const [isDeleting, setIsDeleting] = React.useState(false);
    const [showDeleteDialog, setShowDeleteDialog] = React.useState(false);
    const [confirmText, setConfirmText] = React.useState('');
    const [auditLogs, setAuditLogs] = React.useState<any[]>([]);
    const { toast } = useToast();
    const userRole = ((user as any)?.app_metadata?.role || (user as any)?.role || 'user').toString();
    const isAdmin = userRole === 'admin';

    React.useEffect(() => {
        if (!isAdmin) return;
        loadData();
    }, [isAdmin]);

    const loadData = async () => {
        setIsLoading(true);
        await Promise.all([
            loadDataCounts(),
            loadAuditLogs()
        ]);
        setIsLoading(false);
    };

    const loadAuditLogs = async () => {
        try {
            const logs = await adminApi.getAuditLogs(10);
            setAuditLogs(logs);
        } catch (error) {
            console.error('Erro ao logs:', error);
        }
    };

    const loadDataCounts = async () => {
        setIsLoading(true);
        try {
            const [
                { count: transactionsCount },
                { count: accountsCount },
                { count: cardsCount },
                { count: categoriesCount },
                { count: aiLogsCount },
                { count: importCount }
            ] = await Promise.all([
                supabase.from('transactions').select('*', { count: 'exact', head: true }),
                supabase.from('accounts').select('*', { count: 'exact', head: true }),
                supabase.from('cards').select('*', { count: 'exact', head: true }),
                supabase.from('categories').select('*', { count: 'exact', head: true }),
                supabase.from('ai_chat_logs').select('*', { count: 'exact', head: true }),
                supabase.from('import_history').select('*', { count: 'exact', head: true })
            ]);

            setCounts({
                transactions: transactionsCount || 0,
                accounts: accountsCount || 0,
                cards: cardsCount || 0,
                categories: categoriesCount || 0,
                ai_chat_logs: aiLogsCount || 0,
                import_history: importCount || 0
            });
        } catch (error: any) {
            console.error('Erro ao carregar contadores:', error);
            toast({
                title: 'Erro',
                description: error.message || 'Erro ao carregar dados',
                variant: 'destructive'
            });
        } finally {
            setIsLoading(false);
        }
    };

    const handleDeleteAllData = async () => {
        if (confirmText !== 'CONFIRMAR') {
            toast({
                title: 'Confirmação inválida',
                description: 'Digite CONFIRMAR para prosseguir',
                variant: 'destructive'
            });
            return;
        }

        setIsDeleting(true);
        try {
            // Delete in order respecting foreign keys
            // 1. ai_chat_logs (depends on profiles)
            const { error: aiError } = await supabase.from('ai_chat_logs').delete().neq('id', '00000000-0000-0000-0000-000000000000');
            if (aiError) throw new Error(`Erro ao apagar logs de IA: ${aiError.message}`);

            // 2. import_history (depends on profiles)
            const { error: importError } = await supabase.from('import_history').delete().neq('id', '00000000-0000-0000-0000-000000000000');
            if (importError) throw new Error(`Erro ao apagar histórico de importação: ${importError.message}`);

            // 3. transactions (depends on profiles, accounts, cards, categories)
            const { error: transError } = await supabase.from('transactions').delete().neq('id', '00000000-0000-0000-0000-000000000000');
            if (transError) throw new Error(`Erro ao apagar transações: ${transError.message}`);

            // 4. cards (depends on profiles, accounts)
            const { error: cardsError } = await supabase.from('cards').delete().neq('id', '00000000-0000-0000-0000-000000000000');
            if (cardsError) throw new Error(`Erro ao apagar cartões: ${cardsError.message}`);

            // 5. accounts (depends on profiles)
            const { error: accountsError } = await supabase.from('accounts').delete().neq('id', '00000000-0000-0000-0000-000000000000');
            if (accountsError) throw new Error(`Erro ao apagar contas: ${accountsError.message}`);

            // 6. categories (depends on profiles) - only user categories, not system ones
            const { error: catError } = await supabase.from('categories').delete().not('user_id', 'is', null);
            if (catError) throw new Error(`Erro ao apagar categorias: ${catError.message}`);

            toast({
                title: 'Sucesso',
                description: 'Todos os dados foram apagados com sucesso'
            });

            setShowDeleteDialog(false);
            setConfirmText('');
            loadDataCounts();
        } catch (error: any) {
            console.error('Erro ao apagar dados:', error);
            toast({
                title: 'Erro',
                description: error.message || 'Erro ao apagar dados',
                variant: 'destructive'
            });
        } finally {
            setIsDeleting(false);
        }
    };

    const totalRecords = counts.transactions + counts.accounts + counts.cards +
        counts.categories + counts.ai_chat_logs + counts.import_history;

    const dataItems = [
        { label: 'Transações', count: counts.transactions, icon: Receipt, color: 'text-blue-500' },
        { label: 'Contas', count: counts.accounts, icon: Wallet, color: 'text-green-500' },
        { label: 'Cartões', count: counts.cards, icon: CreditCard, color: 'text-purple-500' },
        { label: 'Categorias', count: counts.categories, icon: FolderOpen, color: 'text-orange-500' },
        { label: 'Logs de IA', count: counts.ai_chat_logs, icon: MessageSquare, color: 'text-cyan-500' },
        { label: 'Importações', count: counts.import_history, icon: FileUp, color: 'text-pink-500' }
    ];

    if (!isAdmin) {
        return <Navigate to="/pf" replace />;
    }

    return (
        <div className="w-full max-w-[1600px] mx-auto p-6 space-y-6">
            <div className="flex justify-between items-center">
                <h1 className="text-3xl font-bold">Configurações Gerais</h1>
            </div>

            {/* Data Summary Card */}
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <Database className="h-5 w-5" />
                        Dados da Plataforma
                    </CardTitle>
                    <CardDescription>
                        Resumo de todos os registros salvos na plataforma
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    {isLoading ? (
                        <div className="flex items-center justify-center py-8">
                            <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                        </div>
                    ) : (
                        <>
                            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
                                {dataItems.map((item) => (
                                    <div key={item.label} className="p-4 border rounded-lg text-center">
                                        <item.icon className={`h-6 w-6 mx-auto mb-2 ${item.color}`} />
                                        <p className="text-2xl font-bold">{item.count.toLocaleString('pt-BR')}</p>
                                        <p className="text-xs text-muted-foreground">{item.label}</p>
                                    </div>
                                ))}
                            </div>

                            <div className="flex items-center justify-between p-4 bg-muted/50 rounded-lg">
                                <div>
                                    <p className="text-lg font-semibold">Total de Registros</p>
                                    <p className="text-sm text-muted-foreground">
                                        {totalRecords.toLocaleString('pt-BR')} registros na plataforma
                                    </p>
                                </div>
                                <Button
                                    variant="outline"
                                    onClick={loadDataCounts}
                                    disabled={isLoading}
                                >
                                    Atualizar
                                </Button>
                            </div>
                        </>
                    )}
                </CardContent>
            </Card>

            {/* Danger Zone Card */}
            <Card className="border-red-500/50">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-red-500">
                        <AlertTriangle className="h-5 w-5" />
                        Zona de Perigo
                    </CardTitle>
                    <CardDescription>
                        Ações irreversíveis que afetam permanentemente os dados da plataforma
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="p-4 border border-red-500/30 bg-red-500/5 rounded-lg">
                        <div className="flex items-start justify-between">
                            <div className="space-y-1">
                                <p className="font-medium">Apagar Todos os Dados</p>
                                <p className="text-sm text-muted-foreground">
                                    Remove permanentemente todas as transações, contas, cartões, categorias personalizadas,
                                    logs de IA e histórico de importação. Os perfis de usuários serão mantidos.
                                </p>
                            </div>
                            <Button
                                variant="destructive"
                                onClick={() => setShowDeleteDialog(true)}
                                disabled={totalRecords === 0}
                            >
                                <Trash2 className="mr-2 h-4 w-4" />
                                Apagar Dados
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Audit Logs Card */}
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <Activity className="h-5 w-5" />
                        Auditoria de Sistema
                    </CardTitle>
                    <CardDescription>
                        Registros recentes de atividades administrativas e operacionais
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="space-y-4">
                        {auditLogs.length === 0 ? (
                            <div className="text-center py-8 text-muted-foreground border-2 border-dashed rounded-xl">
                                Nenhuma atividade registrada no momento.
                            </div>
                        ) : (
                            <div className="rounded-xl border overflow-hidden">
                                <table className="w-full text-sm">
                                    <thead className="bg-muted/50 border-b">
                                        <tr className="text-left font-black uppercase text-[10px] tracking-widest text-slate-500">
                                            <th className="p-4">Evento</th>
                                            <th className="p-4">Entidade</th>
                                            <th className="p-4">Usuário</th>
                                            <th className="p-4 text-right">Data/Hora</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {auditLogs.map((log) => (
                                            <tr key={log.id} className="hover:bg-muted/20 transition-colors">
                                                <td className="p-4">
                                                    <div className="flex items-center gap-2">
                                                        {log.action === 'INSERT' && <PlusCircle className="h-3 w-3 text-emerald-500" />}
                                                        {log.action === 'UPDATE' && <Activity className="h-3 w-3 text-blue-500" />}
                                                        {log.action === 'DELETE' && <Trash className="h-3 w-3 text-red-500" />}
                                                        <span className="font-bold uppercase text-[11px] tracking-tighter">
                                                            {log.action === 'INSERT' ? 'Criação' : log.action === 'UPDATE' ? 'Alteração' : 'Exclusão'}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="p-4 text-xs font-medium text-slate-600">
                                                    {log.entity_type}
                                                </td>
                                                <td className="p-4 text-xs">
                                                    {log.profiles?.username || 'Sistema'}
                                                </td>
                                                <td className="p-4 text-right text-[10px] font-bold text-slate-400">
                                                    {format(new Date(log.created_at), "dd/MM/yyyy HH:mm:ss", { locale: ptBR })}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                        <Button 
                            variant="link" 
                            className="w-full text-xs uppercase font-black tracking-widest opacity-50"
                            onClick={() => toast({ title: "Funcionalidade em desenvolvimento", description: "A visualização completa dos logs estará disponível em breve." })}
                        >
                            Ver Log Completo
                        </Button>
                    </div>
                </CardContent>
            </Card>

            {/* Delete Confirmation Dialog */}
            <Dialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-red-500">
                            <AlertTriangle className="h-5 w-5" />
                            Confirmar Exclusão
                        </DialogTitle>
                        <DialogDescription className="space-y-2">
                            <p>
                                Você está prestes a apagar <strong>{totalRecords.toLocaleString('pt-BR')} registros</strong> da plataforma.
                            </p>
                            <p className="text-red-500 font-medium">
                                Esta ação é IRREVERSÍVEL e não pode ser desfeita!
                            </p>
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4 py-4">
                        <div className="p-3 bg-red-500/10 border border-red-500/30 rounded-lg">
                            <p className="text-sm font-medium mb-2">Serão apagados:</p>
                            <ul className="text-sm text-muted-foreground space-y-1">
                                <li>• {counts.transactions} transações</li>
                                <li>• {counts.accounts} contas</li>
                                <li>• {counts.cards} cartões</li>
                                <li>• {counts.categories} categorias</li>
                                <li>• {counts.ai_chat_logs} logs de IA</li>
                                <li>• {counts.import_history} históricos de importação</li>
                            </ul>
                        </div>

                        <div className="space-y-2">
                            <p className="text-sm font-medium">
                                Digite <span className="font-mono bg-muted px-1 rounded">CONFIRMAR</span> para prosseguir:
                            </p>
                            <Input
                                value={confirmText}
                                onChange={(e) => setConfirmText(e.target.value.toUpperCase())}
                                placeholder="Digite CONFIRMAR"
                                className="font-mono"
                            />
                        </div>
                    </div>

                    <DialogFooter className="gap-2">
                        <Button
                            variant="outline"
                            onClick={() => {
                                setShowDeleteDialog(false);
                                setConfirmText('');
                            }}
                            disabled={isDeleting}
                        >
                            Cancelar
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={handleDeleteAllData}
                            disabled={confirmText !== 'CONFIRMAR' || isDeleting}
                        >
                            {isDeleting ? (
                                <>
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                    Apagando...
                                </>
                            ) : (
                                <>
                                    <Trash2 className="mr-2 h-4 w-4" />
                                    Apagar Tudo
                                </>
                            )}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
