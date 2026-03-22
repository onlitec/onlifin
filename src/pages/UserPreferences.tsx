import * as React from 'react';
import { useAuth } from 'miaoda-auth-react';
import { Navigate } from 'react-router-dom';
import {
    AlertCircle,
    BellRing,
    CheckCircle2,
    Database,
    Download,
    FileJson,
    Loader2,
    Mail,
    Settings2,
    ShieldCheck,
    Smartphone,
    Upload
} from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { AlertSettings } from '@/components/admin/AlertSettings';
import { backupService, BackupData } from '@/services/backupService';
import { profileService, ProfileSettings } from '@/services/profileService';
import { getCurrentPlanInfo, getCurrentPlanUsage, getPlanSourceLabel } from '@/services/planService';
import { useToast } from '@/hooks/use-toast';
import { useAuthProfile } from '@/contexts/AuthProfileContext';

function readStringSetting(settings: ProfileSettings, key: 'notification_email' | 'notification_whatsapp'): string {
    const value = settings[key];
    return typeof value === 'string' ? value : '';
}

export default function UserPreferences() {
    const { user } = useAuth();
    const { profile, refreshProfile, isLoading: isLoadingProfile } = useAuthProfile();
    const { toast } = useToast();
    const [isExporting, setIsExporting] = React.useState(false);
    const [isImporting, setIsImporting] = React.useState(false);
    const [isUpdatingSettings, setIsUpdatingSettings] = React.useState(false);
    const [isSavingDestinations, setIsSavingDestinations] = React.useState(false);
    const [settings, setSettings] = React.useState<ProfileSettings>({});
    const [notificationTargets, setNotificationTargets] = React.useState({ email: '', whatsapp: '' });
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
    const hasPrimaryPerson = Boolean(settings.owner_person_id);

    React.useEffect(() => {
        const nextSettings = (profile?.settings as ProfileSettings | null) || {};
        setSettings(nextSettings);
        setNotificationTargets({
            email: readStringSetting(nextSettings, 'notification_email'),
            whatsapp: readStringSetting(nextSettings, 'notification_whatsapp'),
        });
    }, [profile?.settings]);

    React.useEffect(() => {
        void loadPlanSummary();
    }, []);

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

    const handleUpdateSetting = async (key: keyof ProfileSettings, value: unknown) => {
        setIsUpdatingSettings(true);
        try {
            const newSettings = { [key]: value };
            await profileService.updateSettings(newSettings);
            setSettings((prev) => ({ ...prev, ...newSettings }));
            await refreshProfile();
            toast({
                title: 'Configuração salva',
                description: 'Suas preferências foram atualizadas.',
            });
        } catch (error: any) {
            toast({
                title: 'Erro ao salvar',
                description: error.message,
                variant: 'destructive',
            });
        } finally {
            setIsUpdatingSettings(false);
        }
    };

    const handleSaveNotificationTargets = async () => {
        setIsSavingDestinations(true);
        try {
            const nextSettings: Partial<ProfileSettings> = {
                notification_email: notificationTargets.email.trim() || null,
                notification_whatsapp: notificationTargets.whatsapp.trim() || null,
            };

            await profileService.updateSettings(nextSettings);
            setSettings((prev) => ({ ...prev, ...nextSettings }));
            await refreshProfile();
            toast({
                title: 'Destinos salvos',
                description: 'Os canais pessoais de recebimento foram atualizados.',
            });
        } catch (error: any) {
            toast({
                title: 'Erro ao salvar destinos',
                description: error.message || 'Não foi possível atualizar seus destinos de notificação.',
                variant: 'destructive',
            });
        } finally {
            setIsSavingDestinations(false);
        }
    };

    const handleExport = async () => {
        setIsExporting(true);
        try {
            const data = await backupService.exportBackup();
            backupService.downloadAsJson(data);
            toast({
                title: 'Backup concluído',
                description: 'Seus dados foram exportados com sucesso.',
            });
        } catch (error: any) {
            console.error('Export error:', error);
            toast({
                title: 'Erro na exportação',
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

                if (!backupData.version || !backupData.data) {
                    throw new Error('Arquivo de backup inválido ou corrompido.');
                }

                setImportProgress('Restaurando dados no banco...');
                const result = await backupService.importBackup(backupData);

                if (result.success) {
                    toast({
                        title: 'Restauração concluída',
                        description: 'Todos os registros foram importados com sucesso.',
                    });
                } else {
                    toast({
                        title: 'Concluído com alertas',
                        description: `Importação finalizada com ${result.errors.length} erros.`,
                        variant: 'destructive',
                    });
                }
            } catch (error: any) {
                console.error('Import error:', error);
                toast({
                    title: 'Erro na importação',
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
                title: 'Erro de leitura',
                description: 'Não foi possível ler o arquivo selecionado.',
                variant: 'destructive',
            });
            setIsImporting(false);
            setImportProgress(null);
        };

        reader.readAsText(file);
    };

    if (!user) {
        return <Navigate to="/login" replace />;
    }

    if (isLoadingProfile) {
        return (
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                <Loader2 className="h-4 w-4 animate-spin" />
                Carregando preferências...
            </div>
        );
    }

    return (
        <div className="w-full max-w-[1200px] mx-auto p-6 space-y-8 animate-in fade-in duration-500">
            <div className="flex flex-col gap-1">
                <h1 className="text-3xl font-bold tracking-tight">Preferências</h1>
                <p className="text-muted-foreground text-lg">
                    Ajuste seus canais pessoais de recebimento, backup e preferências individuais.
                </p>
            </div>

            <Card className="border-blue-200 bg-blue-50/40">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <BellRing className="h-5 w-5 text-blue-600" />
                        Destinos pessoais de notificação
                    </CardTitle>
                    <CardDescription>
                        Informe para onde a plataforma deve enviar seus alertas. As credenciais de integração ficam com os administradores da plataforma.
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="notification-email">E-mail para receber notificações</Label>
                            <div className="relative">
                                <Mail className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <Input
                                    id="notification-email"
                                    type="email"
                                    className="pl-10"
                                    value={notificationTargets.email}
                                    onChange={(event) => setNotificationTargets((current) => ({ ...current, email: event.target.value }))}
                                    placeholder="financeiro@empresa.com"
                                />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="notification-whatsapp">WhatsApp para receber notificações</Label>
                            <div className="relative">
                                <Smartphone className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <Input
                                    id="notification-whatsapp"
                                    className="pl-10"
                                    value={notificationTargets.whatsapp}
                                    onChange={(event) => setNotificationTargets((current) => ({ ...current, whatsapp: event.target.value }))}
                                    placeholder="+5511999999999"
                                />
                            </div>
                        </div>
                    </div>

                    <Alert>
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription>
                            O canal só entrega externamente se estiver habilitado nas suas preferências abaixo e liberado globalmente pela plataforma.
                        </AlertDescription>
                    </Alert>

                    <div className="flex justify-end">
                        <Button onClick={() => void handleSaveNotificationTargets()} disabled={isSavingDestinations}>
                            {isSavingDestinations ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <BellRing className="mr-2 h-4 w-4" />}
                            Salvar destinos
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <Card className="border-slate-200">
                <CardHeader>
                    <CardTitle>Plano atual</CardTitle>
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
                                    {!planSummary.isConfigured && ' Nenhum plano explícito foi encontrado; a aplicação está usando fallback de compatibilidade.'}
                                </AlertDescription>
                            </Alert>
                        </>
                    ) : (
                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                            <Loader2 className="h-4 w-4 animate-spin" />
                            Carregando informações do plano...
                        </div>
                    )}
                </CardContent>
            </Card>

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
                    <AlertSettings
                        userId={user.id}
                        emailDestination={notificationTargets.email.trim()}
                        whatsappDestination={notificationTargets.whatsapp.trim()}
                    />
                </CardContent>
            </Card>

            <div className="grid gap-6 md:grid-cols-2">
                <Card className="hover:shadow-md transition-shadow">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Download className="h-5 w-5 text-blue-500" />
                            Exportar dados
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
                            Gerar backup completo
                        </Button>
                    </CardContent>
                </Card>

                <Card className="hover:shadow-md transition-shadow">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Upload className="h-5 w-5 text-green-500" />
                            Restaurar backup
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
                            {importProgress || 'Selecionar arquivo JSON'}
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <div className="grid gap-6 md:grid-cols-2">
                <Card className="hover:shadow-md transition-shadow">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Settings2 className="h-5 w-5 text-purple-500" />
                            Preferências de interface
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
                                checked={Boolean(settings.hide_titular)}
                                onCheckedChange={(value) => void handleUpdateSetting('hide_titular', value)}
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
                        <CardTitle className="text-xl">Status do sistema</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4">
                        <div className="flex items-center justify-between p-4 rounded-lg bg-white/50 border">
                            <div className="flex items-center gap-3">
                                <div className="h-2 w-2 rounded-full bg-green-500" />
                                <div>
                                    <p className="font-medium text-sm">Integridade dos dados</p>
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
