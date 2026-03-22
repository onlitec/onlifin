import * as React from 'react';
import { Navigate, useNavigate } from 'react-router-dom';
import { useAuth } from 'miaoda-auth-react';
import {
    AlertCircle,
    BellRing,
    Bot,
    Loader2,
    Save,
    Settings2,
    ShieldCheck,
    Smartphone,
    Users
} from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { useToast } from '@/hooks/use-toast';
import { useAuthProfile } from '@/contexts/AuthProfileContext';
import { canAccessPlatformSettings } from '@/lib/access';
import { notificationChannelCredentialsApi, notificationSettingsApi } from '@/db/api';
import type { NotificationChannelCredentials, NotificationSettings } from '@/types/types';

const DEFAULT_NOTIFICATION_SETTINGS: Pick<NotificationSettings, 'email_from_name' | 'email_from_address'> = {
    email_from_name: 'OnliFin',
    email_from_address: null,
};

const DEFAULT_CHANNEL_CREDENTIALS: Pick<
    NotificationChannelCredentials,
    'smtp_host' | 'smtp_port' | 'smtp_secure' | 'smtp_user' | 'smtp_pass' | 'whatsapp_provider' | 'whatsapp_api_base_url' | 'whatsapp_api_token' | 'whatsapp_sender'
> = {
    smtp_host: null,
    smtp_port: 587,
    smtp_secure: false,
    smtp_user: null,
    smtp_pass: null,
    whatsapp_provider: 'generic',
    whatsapp_api_base_url: null,
    whatsapp_api_token: null,
    whatsapp_sender: null,
};

export default function Settings() {
    const navigate = useNavigate();
    const { user } = useAuth();
    const { profile } = useAuthProfile();
    const { toast } = useToast();
    const isPlatformAdmin = canAccessPlatformSettings(profile, user as any);
    const [isLoading, setIsLoading] = React.useState(true);
    const [isSaving, setIsSaving] = React.useState(false);
    const [notificationSettings, setNotificationSettings] = React.useState(DEFAULT_NOTIFICATION_SETTINGS);
    const [channelCredentials, setChannelCredentials] = React.useState(DEFAULT_CHANNEL_CREDENTIALS);

    React.useEffect(() => {
        void loadSettings();
    }, []);

    const loadSettings = async () => {
        setIsLoading(true);
        try {
            const [settingsData, credentialsData] = await Promise.all([
                notificationSettingsApi.getGlobal().catch(() => null),
                notificationChannelCredentialsApi.getGlobal().catch(() => null),
            ]);

            setNotificationSettings({
                email_from_name: settingsData?.email_from_name ?? DEFAULT_NOTIFICATION_SETTINGS.email_from_name,
                email_from_address: settingsData?.email_from_address ?? DEFAULT_NOTIFICATION_SETTINGS.email_from_address,
            });

            setChannelCredentials({
                smtp_host: credentialsData?.smtp_host ?? DEFAULT_CHANNEL_CREDENTIALS.smtp_host,
                smtp_port: credentialsData?.smtp_port ?? DEFAULT_CHANNEL_CREDENTIALS.smtp_port,
                smtp_secure: credentialsData?.smtp_secure ?? DEFAULT_CHANNEL_CREDENTIALS.smtp_secure,
                smtp_user: credentialsData?.smtp_user ?? DEFAULT_CHANNEL_CREDENTIALS.smtp_user,
                smtp_pass: credentialsData?.smtp_pass ?? DEFAULT_CHANNEL_CREDENTIALS.smtp_pass,
                whatsapp_provider: credentialsData?.whatsapp_provider ?? DEFAULT_CHANNEL_CREDENTIALS.whatsapp_provider,
                whatsapp_api_base_url: credentialsData?.whatsapp_api_base_url ?? DEFAULT_CHANNEL_CREDENTIALS.whatsapp_api_base_url,
                whatsapp_api_token: credentialsData?.whatsapp_api_token ?? DEFAULT_CHANNEL_CREDENTIALS.whatsapp_api_token,
                whatsapp_sender: credentialsData?.whatsapp_sender ?? DEFAULT_CHANNEL_CREDENTIALS.whatsapp_sender,
            });
        } catch (error) {
            console.error('Erro ao carregar configurações da plataforma:', error);
            toast({
                title: 'Erro',
                description: 'Não foi possível carregar as integrações globais da plataforma.',
                variant: 'destructive',
            });
        } finally {
            setIsLoading(false);
        }
    };

    const handleSave = async () => {
        setIsSaving(true);
        try {
            await Promise.all([
                notificationSettingsApi.upsertGlobal({
                    email_from_name: notificationSettings.email_from_name?.trim() || null,
                    email_from_address: notificationSettings.email_from_address?.trim() || null,
                }),
                notificationChannelCredentialsApi.upsertGlobal({
                    smtp_host: channelCredentials.smtp_host?.trim() || null,
                    smtp_port: Number(channelCredentials.smtp_port || 587),
                    smtp_secure: Boolean(channelCredentials.smtp_secure),
                    smtp_user: channelCredentials.smtp_user?.trim() || null,
                    smtp_pass: channelCredentials.smtp_pass?.trim() || null,
                    whatsapp_provider: channelCredentials.whatsapp_provider?.trim() || 'generic',
                    whatsapp_api_base_url: channelCredentials.whatsapp_api_base_url?.trim() || null,
                    whatsapp_api_token: channelCredentials.whatsapp_api_token?.trim() || null,
                    whatsapp_sender: channelCredentials.whatsapp_sender?.trim() || null,
                }),
            ]);

            toast({
                title: 'Integrações salvas',
                description: 'As credenciais globais da plataforma foram atualizadas.',
            });
        } catch (error: any) {
            console.error('Erro ao salvar integrações:', error);
            toast({
                title: 'Erro ao salvar',
                description: error.message || 'Não foi possível salvar as integrações globais.',
                variant: 'destructive',
            });
        } finally {
            setIsSaving(false);
        }
    };

    if (!isPlatformAdmin) {
        return <Navigate to="/preferences" replace />;
    }

    if (isLoading) {
        return (
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                <Loader2 className="h-4 w-4 animate-spin" />
                Carregando configurações da plataforma...
            </div>
        );
    }

    return (
        <div className="w-full max-w-[1200px] mx-auto p-6 space-y-8 animate-in fade-in duration-500">
            <div className="flex flex-col gap-1">
                <h1 className="text-3xl font-bold tracking-tight">Configurações da Plataforma</h1>
                <p className="text-muted-foreground text-lg">
                    Área restrita para integrações, parâmetros globais e superfícies administrativas da instância.
                </p>
            </div>

            <Alert className="border-blue-200 bg-blue-50 text-blue-950">
                <ShieldCheck className="h-4 w-4" />
                <AlertTitle>Escopo desta página</AlertTitle>
                <AlertDescription>
                    Os usuários do tenant configuram apenas seus destinos pessoais de recebimento em <strong>Preferências</strong>.
                    Nesta tela, apenas administradores da plataforma definem remetente SMTP, provedor WhatsApp e credenciais globais.
                </AlertDescription>
            </Alert>

            <div className="grid gap-6 lg:grid-cols-2">
                <Card className="border-slate-200">
                    <CardHeader>
                        <CardTitle>E-mail da plataforma</CardTitle>
                        <CardDescription>
                            Credenciais SMTP e remetente padrão usados para notificações externas por e-mail.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="smtp-host">SMTP host</Label>
                                <Input
                                    id="smtp-host"
                                    value={channelCredentials.smtp_host || ''}
                                    onChange={(event) => setChannelCredentials((current) => ({ ...current, smtp_host: event.target.value }))}
                                    placeholder="smtp.seuprovedor.com"
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="smtp-port">SMTP porta</Label>
                                <Input
                                    id="smtp-port"
                                    type="number"
                                    value={String(channelCredentials.smtp_port || 587)}
                                    onChange={(event) => setChannelCredentials((current) => ({ ...current, smtp_port: Number(event.target.value || 587) }))}
                                    placeholder="587"
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="smtp-user">SMTP usuário</Label>
                                <Input
                                    id="smtp-user"
                                    value={channelCredentials.smtp_user || ''}
                                    onChange={(event) => setChannelCredentials((current) => ({ ...current, smtp_user: event.target.value }))}
                                    placeholder="apikey ou usuario"
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="smtp-pass">SMTP senha</Label>
                                <Input
                                    id="smtp-pass"
                                    type="password"
                                    value={channelCredentials.smtp_pass || ''}
                                    onChange={(event) => setChannelCredentials((current) => ({ ...current, smtp_pass: event.target.value }))}
                                    placeholder="••••••••"
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="email-from-name">Nome do remetente</Label>
                                <Input
                                    id="email-from-name"
                                    value={notificationSettings.email_from_name || ''}
                                    onChange={(event) => setNotificationSettings((current) => ({ ...current, email_from_name: event.target.value }))}
                                    placeholder="OnliFin"
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="email-from-address">E-mail remetente</Label>
                                <Input
                                    id="email-from-address"
                                    type="email"
                                    value={notificationSettings.email_from_address || ''}
                                    onChange={(event) => setNotificationSettings((current) => ({ ...current, email_from_address: event.target.value }))}
                                    placeholder="notificacoes@onlifin.com"
                                />
                            </div>
                        </div>

                        <div className="flex items-center justify-between rounded-xl border p-4">
                            <div>
                                <Label>SMTP seguro</Label>
                                <p className="text-xs text-muted-foreground">Ative para provedores que exigem TLS/SSL direto.</p>
                            </div>
                            <Switch
                                checked={channelCredentials.smtp_secure}
                                onCheckedChange={(checked) => setChannelCredentials((current) => ({ ...current, smtp_secure: checked }))}
                            />
                        </div>
                    </CardContent>
                </Card>

                <Card className="border-slate-200">
                    <CardHeader>
                        <CardTitle>WhatsApp da plataforma</CardTitle>
                        <CardDescription>
                            Integração global do provedor e número remetente usado nos envios transacionais.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="whatsapp-provider">Provider</Label>
                                <Input
                                    id="whatsapp-provider"
                                    value={channelCredentials.whatsapp_provider || ''}
                                    onChange={(event) => setChannelCredentials((current) => ({ ...current, whatsapp_provider: event.target.value }))}
                                    placeholder="generic ou twilio"
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="whatsapp-sender">Telefone remetente</Label>
                                <Input
                                    id="whatsapp-sender"
                                    value={channelCredentials.whatsapp_sender || ''}
                                    onChange={(event) => setChannelCredentials((current) => ({ ...current, whatsapp_sender: event.target.value }))}
                                    placeholder="+5511999999999"
                                />
                            </div>
                            <div className="space-y-2 md:col-span-2">
                                <Label htmlFor="whatsapp-base-url">Base URL da integração</Label>
                                <Input
                                    id="whatsapp-base-url"
                                    value={channelCredentials.whatsapp_api_base_url || ''}
                                    onChange={(event) => setChannelCredentials((current) => ({ ...current, whatsapp_api_base_url: event.target.value }))}
                                    placeholder="https://api.seuprovedor.com"
                                />
                            </div>
                            <div className="space-y-2 md:col-span-2">
                                <Label htmlFor="whatsapp-token">Token da integração</Label>
                                <Input
                                    id="whatsapp-token"
                                    type="password"
                                    value={channelCredentials.whatsapp_api_token || ''}
                                    onChange={(event) => setChannelCredentials((current) => ({ ...current, whatsapp_api_token: event.target.value }))}
                                    placeholder="••••••••"
                                />
                            </div>
                        </div>

                        <Alert>
                            <Smartphone className="h-4 w-4" />
                            <AlertDescription>
                                O número remetente é global da plataforma. O número destino continua sendo definido por cada usuário em suas preferências pessoais.
                            </AlertDescription>
                        </Alert>
                    </CardContent>
                </Card>
            </div>

            <Card className="border-slate-200">
                <CardHeader>
                    <CardTitle>Comportamento operacional</CardTitle>
                    <CardDescription>
                        Os campos salvos aqui passam a ser lidos pelo worker com fallback para o deploy atual enquanto a configuração do banco estiver vazia.
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    <Alert>
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription>
                            Use <strong>Administração &gt; Notificações</strong> para validar canais, templates, testes administrativos, fila e histórico de entregas após salvar as integrações.
                        </AlertDescription>
                    </Alert>

                    <div className="flex justify-end">
                        <Button onClick={() => void handleSave()} disabled={isSaving}>
                            {isSaving ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Save className="mr-2 h-4 w-4" />}
                            Salvar integrações
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <Card className="border-blue-200 bg-blue-50/40">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <Settings2 className="h-5 w-5 text-blue-600" />
                        Administração da plataforma
                    </CardTitle>
                    <CardDescription>
                        Atalhos operacionais e de configuração global.
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
                            Criar, editar, resetar senha e acompanhar status dos usuários da instância.
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
                            Configurar canais globais, templates, testes administrativos, fila e histórico de entregas.
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
        </div>
    );
}
