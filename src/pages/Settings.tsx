import * as React from 'react';
import { Navigate, useNavigate } from 'react-router-dom';
import { useAuth } from 'miaoda-auth-react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { useToast } from '@/hooks/use-toast';
import {
    Download,
    Upload,
    Database,
    FileJson,
    AlertCircle,
    CheckCircle2,
    Loader2,
    ShieldCheck,
    Users,
    Bot,
    Settings2,
    BellRing
} from 'lucide-react';
import { backupService, BackupData } from '@/services/backupService';
import { profileService, ProfileSettings } from '@/services/profileService';
import { getCurrentPlanInfo, getCurrentPlanUsage, getPlanSourceLabel } from '@/services/planService';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Switch } from '@/components/ui/switch';
import { AlertSettings } from '@/components/admin/AlertSettings';
import { useAuthProfile } from '@/contexts/AuthProfileContext';
import { canAccessAdministration, canAccessPlatformSettings } from '@/lib/access';

export default function Settings() {
    const navigate = useNavigate();
    const { user } = useAuth();
    const { profile } = useAuthProfile();
    const [isExporting, setIsExporting] = React.useState(false);
    const [isImporting, setIsImporting] = React.useState(false);
    const [isUpdatingSettings, setIsUpdatingSettings] = React.useState(false);
    const [settings, setSettings] = React.useState<ProfileSettings>({});
    const [planSummary, setPlanSummary] = React.useState<{
        name: string;
        source: string;
        peopleLimit: number;
        companiesLimit: number;
        peopleCount: number;
        companiesCount: number;
        isConfigured: boolean;
    } | null>(null);
    const [importProgress, setImportProgress] = React.useState<string | null>(null);
    const fileInputRef = React.useRef<HTMLInputElement>(null);
    const { toast } = useToast();
    const isPlatformAdmin = canAccessPlatformSettings(profile, user as any);
    const canManageAdministration = canAccessAdministration(profile, user as any);
    const hasPrimaryPerson = Boolean(settings.owner_person_id);

    React.useEffect(() => {
        loadSettings();
        loadPlanSummary();
    }, []);

    const loadSettings = async () => {
        try {
            const profile = await profileService.getProfile();
            if (profile?.settings) {
                setSettings(profile.settings);
            }
        } catch (error) {
            console.error('Error loading settings:', error);
        }
    };

    const loadPlanSummary = async () => {
        try {
            const [planInfo, usage] = await Promise.all([
                getCurrentPlanInfo(),
                getCurrentPlanUsage(),
            ]);

            setPlanSummary({
                name: planInfo.plan.name,
                source: getPlanSourceLabel(planInfo.source),
                peopleLimit: planInfo.plan.limits.managedPeople,
                companiesLimit: planInfo.plan.limits.companies,
                peopleCount: usage.peopleCount,
                companiesCount: usage.companiesCount,
                isConfigured: planInfo.isConfigured,
            });
        } catch (error) {
            console.error('Error loading plan summary:', error);
        }
    };

    const handleUpdateSetting = async (key: keyof ProfileSettings, value: any) => {
        setIsUpdatingSettings(true);
        try {
            const newSettings = { [key]: value };
            await profileService.updateSettings(newSettings);
            setSettings(prev => ({ ...prev, ...newSettings }));
            toast({
                title: 'Configuração Salva',
                description: 'Suas preferências foram atualizadas.',
            });
        } catch (error: any) {
            toast({
                title: 'Erro ao Salvar',
                description: error.message,
                variant: 'destructive',
            });
        } finally {
            setIsUpdatingSettings(false);
        }
    };

    const handleExport = async () => {
        setIsExporting(true);
        try {
            const data = await backupService.exportBackup();
            backupService.downloadAsJson(data);
            toast({
                title: 'Backup Concluído',
                description: 'Seus dados foram exportados com sucesso.',
            });
        } catch (error: any) {
            console.error('Export error:', error);
            toast({
                title: 'Erro na Exportação',
                description: error.message || 'Não foi possível gerar o backup.',
                variant: 'destructive',
            });
        } finally {
            setIsExporting(false);
        }
    };

    const handleImportClick = () => {
        fileInputRef.current?.click();
    };

    const handleFileChange = async (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        if (!file) return;

        setIsImporting(true);
        setImportProgress('Lendo arquivo...');

        const reader = new FileReader();
        reader.onload = async (e) => {
            try {
                const content = e.target?.result as string;
                const backupData = JSON.parse(content) as BackupData;

                // Validação básica
                if (!backupData.version || !backupData.data) {
                    throw new Error('Arquivo de backup inválido ou corrompido.');
                }

                setImportProgress('Restaurando dados no banco...');
                const result = await backupService.importBackup(backupData);

                if (result.success) {
                    toast({
                        title: 'Restauração Concluída',
                        description: 'Todos os registros foram importados com sucesso.',
                    });
                } else {
                    toast({
                        title: 'Concluído com Alertas',
                        description: `Importação finalizada com ${result.errors.length} erros.`,
                        variant: 'destructive',
                    });
                }
            } catch (error: any) {
                console.error('Import error:', error);
                toast({
                    title: 'Erro na Importação',
                    description: error.message || 'Falha ao processar o arquivo de backup.',
                    variant: 'destructive',
                });
            } finally {
                setIsImporting(false);
                setImportProgress(null);
                if (fileInputRef.current) fileInputRef.current.value = '';
            }
        };

        reader.onerror = () => {
            toast({
                title: 'Erro de Leitura',
                description: 'Não foi possível ler o arquivo selecionado.',
                variant: 'destructive',
            });
            setIsImporting(false);
            setImportProgress(null);
        };

        reader.readAsText(file);
    };

    if (!isPlatformAdmin) {
        return <Navigate to={canManageAdministration ? '/admin-general' : '/pf'} replace />;
    }

    return (
        <div className="w-full max-w-[1200px] mx-auto p-6 space-y-8 animate-in fade-in duration-500">
            <div className="flex flex-col gap-1">
                <h1 className="text-3xl font-bold tracking-tight">Configurações</h1>
                <p className="text-muted-foreground text-lg">
                    Gerencie seus dados, preferências e segurança da conta.
                </p>
            </div>

            <Card className="border-blue-200 bg-blue-50/40">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <Settings2 className="h-5 w-5 text-blue-600" />
                        Administração
                    </CardTitle>
                    <CardDescription>
                        Atalhos das configurações administrativas da plataforma.
                    </CardDescription>
                </CardHeader>
                <CardContent className="grid gap-4 md:grid-cols-4">
                    <Button
                        variant="outline"
                        className="h-auto min-h-24 flex-col items-start gap-2 border-blue-200 bg-white px-4 py-4 text-left hover:bg-blue-50"
                        onClick={() => navigate('/user-management')}
                    >
                        <div className="flex items-center gap-2 text-slate-900">
                            <Users className="h-4 w-4 text-blue-600" />
                            <span className="font-bold">Gestão de Usuários</span>
                        </div>
                        <span className="text-xs text-muted-foreground whitespace-normal">
                            Criar, editar, resetar senha e acompanhar status dos usuários.
                        </span>
                    </Button>

                    <Button
                        variant="outline"
                        className="h-auto min-h-24 flex-col items-start gap-2 border-blue-200 bg-white px-4 py-4 text-left hover:bg-blue-50"
                        onClick={() => navigate('/ai-admin')}
                    >
                        <div className="flex items-center gap-2 text-slate-900">
                            <Bot className="h-4 w-4 text-blue-600" />
                            <span className="font-bold">Configuração IA</span>
                        </div>
                        <span className="text-xs text-muted-foreground whitespace-normal">
                            Ajustar modelo, permissões e parâmetros operacionais da IA.
                        </span>
                    </Button>

                    <Button
                        variant="outline"
                        className="h-auto min-h-24 flex-col items-start gap-2 border-blue-200 bg-white px-4 py-4 text-left hover:bg-blue-50"
                        onClick={() => navigate('/admin-notifications')}
                    >
                        <div className="flex items-center gap-2 text-slate-900">
                            <BellRing className="h-4 w-4 text-blue-600" />
                            <span className="font-bold">Notificações</span>
                        </div>
                        <span className="text-xs text-muted-foreground whitespace-normal">
                            Configurar canais, templates, testes e entregas do sistema.
                        </span>
                    </Button>

                    <Button
                        variant="outline"
                        className="h-auto min-h-24 flex-col items-start gap-2 border-blue-200 bg-white px-4 py-4 text-left hover:bg-blue-50"
                        onClick={() => navigate('/admin-general')}
                    >
                        <div className="flex items-center gap-2 text-slate-900">
                            <Settings2 className="h-4 w-4 text-blue-600" />
                            <span className="font-bold">Painel Geral</span>
                        </div>
                        <span className="text-xs text-muted-foreground whitespace-normal">
                            Ver logs, manutenção e controles globais da instância.
                        </span>
                    </Button>
                </CardContent>
            </Card>

            <Card className="border-slate-200">
                <CardHeader>
                    <CardTitle>Plano Atual</CardTitle>
                    <CardDescription>
                        Limites comerciais aplicados a pessoas cadastradas e CNPJs ativos.
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    {planSummary ? (
                        <>
                            <div className="grid gap-4 md:grid-cols-3">
                                <div className="rounded-lg border bg-muted/30 p-4">
                                    <p className="text-xs font-medium text-muted-foreground">Plano</p>
                                    <p className="mt-1 text-lg font-semibold">{planSummary.name}</p>
                                </div>
                                <div className="rounded-lg border bg-muted/30 p-4">
                                    <p className="text-xs font-medium text-muted-foreground">Pessoas</p>
                                    <p className="mt-1 text-lg font-semibold">
                                        {planSummary.peopleCount} / {planSummary.peopleLimit}
                                    </p>
                                </div>
                                <div className="rounded-lg border bg-muted/30 p-4">
                                    <p className="text-xs font-medium text-muted-foreground">CNPJs</p>
                                    <p className="mt-1 text-lg font-semibold">
                                        {planSummary.companiesCount} / {planSummary.companiesLimit}
                                    </p>
                                </div>
                            </div>

                            <Alert>
                                <AlertCircle className="h-4 w-4" />
                                <AlertTitle>Origem da regra</AlertTitle>
                                <AlertDescription>
                                    O plano foi resolvido a partir de {planSummary.source}.
                                    {!planSummary.isConfigured && ' Nenhum plano explicito foi encontrado; a aplicacao esta usando fallback de compatibilidade.'}
                                </AlertDescription>
                            </Alert>
                        </>
                    ) : (
                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                            <Loader2 className="h-4 w-4 animate-spin" />
                            Carregando informacoes do plano...
                        </div>
                    )}
                </CardContent>
            </Card>

            {user?.id && (
                <Card className="border-slate-200">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <BellRing className="h-5 w-5 text-blue-500" />
                            Notificações
                        </CardTitle>
                        <CardDescription>
                            Ajuste seus canais e preferências individuais de notificações.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <AlertSettings userId={user.id} />
                    </CardContent>
                </Card>
            )}

            <div className="grid gap-6 md:grid-cols-2">
                {/* Export Card */}
                <Card className="hover:shadow-md transition-shadow">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Download className="h-5 w-5 text-blue-500" />
                            Exportar Dados
                        </CardTitle>
                        <CardDescription>
                            Baixe uma cópia completa de seus dados financeiros (PF e PJ) em formato JSON.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="p-4 rounded-lg bg-blue-500/5 border border-blue-500/20 flex gap-3 text-sm">
                            <ShieldCheck className="h-5 w-5 text-blue-500 shrink-0" />
                            <p className="text-blue-700 dark:text-blue-300">
                                Seus backups são protegidos por criptografia de transporte e só podem ser restaurados na sua conta ou em instâncias compatíveis.
                            </p>
                        </div>
                        <Button
                            className="w-full gap-2"
                            size="lg"
                            onClick={handleExport}
                            disabled={isExporting || isImporting}
                        >
                            {isExporting ? (
                                <Loader2 className="h-4 w-4 animate-spin" />
                            ) : (
                                <FileJson className="h-4 w-4" />
                            )}
                            Gerar Backup Completo
                        </Button>
                    </CardContent>
                </Card>

                {/* Import Card */}
                <Card className="hover:shadow-md transition-shadow">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Upload className="h-5 w-5 text-green-500" />
                            Restaurar Backup
                        </CardTitle>
                        <CardDescription>
                            Suba um arquivo de backup previamente gerado para restaurar seus registros.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <Alert variant="default" className="bg-amber-500/5 border-amber-500/20 text-amber-700 dark:text-amber-300">
                            <AlertCircle className="h-4 w-4 !text-amber-500" />
                            <AlertTitle>Importante</AlertTitle>
                            <AlertDescription className="text-xs">
                                O processo de restauração irá manter registros existentes e inserir apenas os novos, ou atualizar registros modificados baseados no ID.
                            </AlertDescription>
                        </Alert>

                        <input
                            type="file"
                            accept=".json"
                            className="hidden"
                            ref={fileInputRef}
                            onChange={handleFileChange}
                        />

                        <Button
                            variant="outline"
                            className="w-full gap-2 h-12 border-dashed border-2 hover:border-green-500 hover:bg-green-500/5 transition-all"
                            onClick={handleImportClick}
                            disabled={isExporting || isImporting}
                        >
                            {isImporting ? (
                                <Loader2 className="h-4 w-4 animate-spin" />
                            ) : (
                                <Database className="h-4 w-4" />
                            )}
                            {importProgress || 'Selecionar Arquivo JSON'}
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <div className="grid gap-6 md:grid-cols-2">
                {/* Interface Preferences */}
                <Card className="hover:shadow-md transition-shadow">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <ShieldCheck className="h-5 w-5 text-purple-500" />
                            Preferências de Interface
                        </CardTitle>
                        <CardDescription>
                            Personalize como o sistema exibe informações para você.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        <div className="flex items-center justify-between p-4 rounded-xl border bg-slate-50/50">
                            <div className="space-y-1">
                                <p className="text-sm font-bold leading-none">
                                    {hasPrimaryPerson ? 'Pessoa titular principal' : 'Ocultar Membro "Titular"'}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {hasPrimaryPerson
                                        ? 'Sua conta já usa uma pessoa titular real e protegida como referência principal.'
                                        : 'Remove o perfil padrão do sistema caso existam outros membros.'}
                                </p>
                            </div>
                            <Switch
                                checked={settings.hide_titular}
                                onCheckedChange={(val) => handleUpdateSetting('hide_titular', val)}
                                disabled={isUpdatingSettings || hasPrimaryPerson}
                            />
                        </div>

                        <div className="p-4 rounded-lg bg-purple-500/5 border border-purple-500/10 text-xs">
                            <AlertCircle className="h-4 w-4 text-purple-500 inline mr-2 mb-1" />
                            {hasPrimaryPerson
                                ? 'A visualização antiga de titular genérico foi substituída por uma pessoa titular real da conta.'
                                : 'Esta opção facilita o uso se você gerencia apenas familiares ou perfis específicos, eliminando o "Titular" genérico das listas.'}
                        </div>
                    </CardContent>
                </Card>

                <Card className="border-muted bg-muted/20">
                    <CardHeader>
                        <CardTitle className="text-xl">Status do Sistema</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4">
                        <div className="flex items-center justify-between p-4 rounded-lg bg-white/50 border">
                            <div className="flex items-center gap-3">
                                <div className="h-2 w-2 rounded-full bg-green-500" />
                                <div>
                                    <p className="font-medium text-sm">Integridade dos Dados</p>
                                    <p className="text-[10px] text-muted-foreground uppercase font-bold">Sincronizado</p>
                                </div>
                            </div>
                            <CheckCircle2 className="h-5 w-5 text-green-500" />
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}
