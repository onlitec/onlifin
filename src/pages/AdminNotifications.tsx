import * as React from 'react';
import { useAuth } from 'miaoda-auth-react';
import { AlertService } from '@/services/alertService';
import {
  notificationsApi,
  profilesApi,
  notificationSettingsApi,
  notificationTemplatesApi,
  notificationQueueApi,
  notificationDeliveriesApi,
  notificationWorkerCommandsApi
} from '@/db/api';
import type {
  Notification,
  NotificationDelivery,
  NotificationDeliveryQueueItem,
  NotificationSettings,
  NotificationTemplate,
  NotificationWorkerCommand
} from '@/types/types';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { useToast } from '@/hooks/use-toast';
import {
  BellRing,
  CheckCircle2,
  CircleAlert,
  Loader2,
  Mail,
  MessageSquareText,
  RefreshCcw,
  RotateCcw,
  Save,
  Settings2,
  Smartphone
} from 'lucide-react';

type NotificationChannel = NotificationTemplate['channel'];
type EditableNotificationTemplate = NotificationTemplate & { isSeeded?: boolean };

const TEMPLATE_CHANNELS: NotificationChannel[] = ['toast', 'email', 'whatsapp'];

const EVENT_LABELS: Record<string, string> = {
  bill_due_soon: 'Conta vencendo em breve',
  bill_overdue: 'Conta vencida',
  bill_paid: 'Conta paga',
  bill_to_receive_due_soon: 'Recebimento em breve',
  bill_to_receive_received: 'Valor recebido',
  system_critical: 'Sistema crítico',
  custom: 'Personalizado'
};

const DEFAULT_TEMPLATE_CONTENT: Record<
  string,
  Record<NotificationChannel, Pick<NotificationTemplate, 'title_template' | 'subject_template' | 'body_template' | 'is_active'>>
> = {
  bill_due_soon: {
    toast: {
      title_template: 'Conta vencendo em breve',
      subject_template: null,
      body_template: '{{description}} vence em {{days_until_due}} dia(s) no valor de R$ {{amount}}.',
      is_active: true
    },
    email: {
      title_template: 'Conta vencendo em breve',
      subject_template: 'OnliFin: {{description}} vence em breve',
      body_template: '{{description}} vence em {{days_until_due}} dia(s) no valor de R$ {{amount}}. Vencimento: {{due_date}}.',
      is_active: true
    },
    whatsapp: {
      title_template: 'Conta vencendo em breve',
      subject_template: null,
      body_template: 'OnliFin: {{description}} vence em {{days_until_due}} dia(s) no valor de R$ {{amount}}.',
      is_active: true
    }
  },
  bill_overdue: {
    toast: {
      title_template: 'Conta vencida',
      subject_template: null,
      body_template: '{{description}} está vencida há {{days_overdue}} dia(s) no valor de R$ {{amount}}.',
      is_active: true
    },
    email: {
      title_template: 'Conta vencida',
      subject_template: 'OnliFin: {{description}} está vencida',
      body_template: '{{description}} está vencida há {{days_overdue}} dia(s) no valor de R$ {{amount}}. Vencimento: {{due_date}}.',
      is_active: true
    },
    whatsapp: {
      title_template: 'Conta vencida',
      subject_template: null,
      body_template: 'OnliFin: {{description}} está vencida há {{days_overdue}} dia(s) no valor de R$ {{amount}}.',
      is_active: true
    }
  },
  bill_paid: {
    toast: {
      title_template: 'Conta paga',
      subject_template: null,
      body_template: '{{description}} foi paga no valor de R$ {{amount}}.',
      is_active: true
    },
    email: {
      title_template: 'Conta paga',
      subject_template: 'OnliFin: pagamento registrado',
      body_template: '{{description}} foi paga no valor de R$ {{amount}}.',
      is_active: true
    },
    whatsapp: {
      title_template: 'Conta paga',
      subject_template: null,
      body_template: 'OnliFin: {{description}} foi paga no valor de R$ {{amount}}.',
      is_active: true
    }
  },
  bill_to_receive_due_soon: {
    toast: {
      title_template: 'Recebimento em breve',
      subject_template: null,
      body_template: '{{description}} será recebido em {{days_until_due}} dia(s) no valor de R$ {{amount}}.',
      is_active: true
    },
    email: {
      title_template: 'Recebimento em breve',
      subject_template: 'OnliFin: recebimento próximo',
      body_template: '{{description}} será recebido em {{days_until_due}} dia(s) no valor de R$ {{amount}}.',
      is_active: true
    },
    whatsapp: {
      title_template: 'Recebimento em breve',
      subject_template: null,
      body_template: 'OnliFin: {{description}} será recebido em {{days_until_due}} dia(s) no valor de R$ {{amount}}.',
      is_active: true
    }
  },
  bill_to_receive_received: {
    toast: {
      title_template: 'Valor recebido',
      subject_template: null,
      body_template: '{{description}} foi recebido no valor de R$ {{amount}}.',
      is_active: true
    },
    email: {
      title_template: 'Valor recebido',
      subject_template: 'OnliFin: valor recebido',
      body_template: '{{description}} foi recebido no valor de R$ {{amount}}.',
      is_active: true
    },
    whatsapp: {
      title_template: 'Valor recebido',
      subject_template: null,
      body_template: 'OnliFin: {{description}} foi recebido no valor de R$ {{amount}}.',
      is_active: true
    }
  },
  system_critical: {
    toast: {
      title_template: 'Alerta do sistema',
      subject_template: null,
      body_template: '{{message}}',
      is_active: true
    },
    email: {
      title_template: 'Alerta do sistema',
      subject_template: 'OnliFin: alerta crítico',
      body_template: '{{message}}',
      is_active: true
    },
    whatsapp: {
      title_template: 'Alerta do sistema',
      subject_template: null,
      body_template: 'OnliFin: {{message}}',
      is_active: true
    }
  },
  custom: {
    toast: {
      title_template: '{{title}}',
      subject_template: null,
      body_template: '{{message}}',
      is_active: true
    },
    email: {
      title_template: '{{title}}',
      subject_template: '{{title}}',
      body_template: '{{message}}',
      is_active: true
    },
    whatsapp: {
      title_template: '{{title}}',
      subject_template: null,
      body_template: '{{message}}',
      is_active: true
    }
  }
};

function buildDefaultTemplate(eventKey: string, channel: NotificationChannel): EditableNotificationTemplate {
  const content = DEFAULT_TEMPLATE_CONTENT[eventKey][channel];

  return {
    id: `${eventKey}:${channel}`,
    event_key: eventKey,
    channel,
    title_template: content.title_template,
    subject_template: content.subject_template,
    body_template: content.body_template,
    is_active: content.is_active,
    created_at: '',
    updated_at: '',
    isSeeded: false
  };
}

function mergeTemplatesWithDefaults(existingTemplates: NotificationTemplate[]): EditableNotificationTemplate[] {
  const byKey = new Map(existingTemplates.map((template) => [`${template.event_key}:${template.channel}`, template]));
  const merged: EditableNotificationTemplate[] = [];

  for (const [eventKey] of Object.entries(EVENT_LABELS)) {
    for (const channel of TEMPLATE_CHANNELS) {
      const templateKey = `${eventKey}:${channel}`;
      const existing = byKey.get(templateKey);

      if (existing) {
        merged.push({ ...existing, isSeeded: true });
        continue;
      }

      merged.push(buildDefaultTemplate(eventKey, channel));
    }
  }

  return merged;
}

interface NotificationWorkerHealth {
  status: string;
  running: boolean;
  generating: boolean;
  processingCommands: boolean;
  lastRunAt: string | null;
  lastError: string | null;
  lastGenerationRunAt: string | null;
  lastGenerationError: string | null;
  lastCommandRunAt: string | null;
  lastCommandError: string | null;
  smtpConfigured: boolean;
  whatsappConfigured: boolean;
  missingSmtpEnvKeys: string[];
  missingWhatsappEnvKeys: string[];
  workerIntervalMs: number;
  generatorIntervalMs: number;
  commandPollIntervalMs: number;
}

const COMMAND_LABELS: Record<NotificationWorkerCommand['command'], string> = {
  process_queue: 'Processar fila agora',
  generate_notifications: 'Gerar notificações agora'
};

function formatCommandResult(result: Record<string, unknown>): string | null {
  if (typeof result.processedCount === 'number') {
    return `${result.processedCount} item(ns) processados.`;
  }

  if (typeof result.generatedAt === 'string') {
    return `Geração concluída em ${new Date(result.generatedAt).toLocaleString('pt-BR')}.`;
  }

  if (result.skipped === true && typeof result.reason === 'string') {
    if (result.reason === 'already_running') {
      return 'Comando ignorado porque a rotina já estava em execução.';
    }

    if (result.reason === 'notifications_disabled') {
      return 'Comando ignorado porque as notificações globais estão desativadas.';
    }

    return `Comando ignorado: ${result.reason}.`;
  }

  return null;
}

export default function AdminNotifications() {
  const { user } = useAuth();
  const { toast } = useToast();
  const [settings, setSettings] = React.useState<NotificationSettings | null>(null);
  const [templates, setTemplates] = React.useState<EditableNotificationTemplate[]>([]);
  const [notifications, setNotifications] = React.useState<Notification[]>([]);
  const [queueItems, setQueueItems] = React.useState<NotificationDeliveryQueueItem[]>([]);
  const [deliveries, setDeliveries] = React.useState<NotificationDelivery[]>([]);
  const [workerCommands, setWorkerCommands] = React.useState<NotificationWorkerCommand[]>([]);
  const [profileNames, setProfileNames] = React.useState<Record<string, string>>({});
  const [workerHealth, setWorkerHealth] = React.useState<NotificationWorkerHealth | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [refreshing, setRefreshing] = React.useState(false);
  const [activeTab, setActiveTab] = React.useState('config');
  const [savingSettings, setSavingSettings] = React.useState(false);
  const [savingTemplateId, setSavingTemplateId] = React.useState<string | null>(null);
  const [retryingQueueId, setRetryingQueueId] = React.useState<string | null>(null);
  const [retryingAllFailed, setRetryingAllFailed] = React.useState(false);
  const [runningWorkerCommand, setRunningWorkerCommand] = React.useState<NotificationWorkerCommand['command'] | null>(null);
  const [queueFilter, setQueueFilter] = React.useState<NotificationDeliveryQueueItem['status'] | 'all'>('all');
  const [deliveryFilter, setDeliveryFilter] = React.useState<NotificationDelivery['status'] | 'all'>('all');
  const [testTargets, setTestTargets] = React.useState({
    email: '',
    whatsapp: ''
  });
  const [testMessage, setTestMessage] = React.useState('Teste de notificação administrativa do OnliFin.');
  const [sendingTestChannel, setSendingTestChannel] = React.useState<'toast' | 'email' | 'whatsapp' | null>(null);

  const userId = user?.id || null;

  const loadData = React.useCallback(async (options?: { background?: boolean; includeStatic?: boolean }) => {
    const isBackground = options?.background ?? false;
    const includeStatic = options?.includeStatic ?? !isBackground;

    if (isBackground) {
      setRefreshing(true);
    } else {
      setLoading(true);
    }

    try {
      const [staticData, operationalData] = await Promise.all([
        includeStatic
          ? Promise.all([
              notificationSettingsApi.getGlobal(),
              notificationTemplatesApi.getAll()
            ])
          : Promise.resolve([null, null] as const),
        Promise.all([
        notificationsApi.getRecentAdmin(20).catch(() => []),
        notificationQueueApi.getRecent(20),
        notificationDeliveriesApi.getRecent(20),
        notificationWorkerCommandsApi.getRecent(10).catch(() => []),
        fetch('/api/worker/notification-health').catch(() => null)
        ])
      ]);

      const [settingsData, templatesData] = staticData;
      const [notificationsData, queueData, deliveriesData, workerCommandsData, workerHealthResponse] = operationalData;
      const profilesData = await profilesApi.getProfilesByIds(
        notificationsData.map((notification) => notification.user_id)
      ).catch(() => []);

      if (workerHealthResponse?.ok) {
        const health = await workerHealthResponse.json() as NotificationWorkerHealth;
        setWorkerHealth(health);
      } else {
        setWorkerHealth(null);
      }

      if (settingsData) {
        setSettings(settingsData);
        setTestTargets({
          email: settingsData.email_test_destination || '',
          whatsapp: settingsData.whatsapp_test_destination || ''
        });
      }

      if (templatesData) {
        setTemplates(mergeTemplatesWithDefaults(templatesData));
      }

      setNotifications(notificationsData);
      setQueueItems(queueData);
      setDeliveries(deliveriesData);
      setWorkerCommands(workerCommandsData);
      setProfileNames((current) => ({
        ...current,
        ...Object.fromEntries(
          profilesData.map((profile) => [
            profile.id,
            profile.full_name || profile.username || profile.email || profile.id
          ])
        )
      }));
    } catch (error) {
      console.error(error);
      if (!isBackground) {
        toast({
          title: 'Erro',
          description: 'Nao foi possivel carregar as configuracoes de notificacao.',
          variant: 'destructive'
        });
      }
    } finally {
      if (isBackground) {
        setRefreshing(false);
      } else {
        setLoading(false);
      }
    }
  }, [toast]);

  React.useEffect(() => {
    void loadData();
  }, [loadData]);

  const hasActiveWorkerCommands = React.useMemo(() => (
    workerCommands.some((command) => command.status === 'pending' || command.status === 'processing')
  ), [workerCommands]);

  React.useEffect(() => {
    if (!hasActiveWorkerCommands && runningWorkerCommand === null) {
      return;
    }

    const intervalId = window.setInterval(() => {
      void loadData({ background: true });
    }, 3000);

    return () => {
      window.clearInterval(intervalId);
    };
  }, [hasActiveWorkerCommands, loadData, runningWorkerCommand]);

  const handleSaveSettings = async () => {
    if (!settings) return;

    setSavingSettings(true);
    try {
      const updated = await notificationSettingsApi.upsertGlobal({
        ...settings,
        email_test_destination: testTargets.email || null,
        whatsapp_test_destination: testTargets.whatsapp || null
      });
      setSettings(updated);
      toast({
        title: 'Configuracoes salvas',
        description: 'As configuracoes globais de notificacao foram atualizadas.'
      });
    } catch (error) {
      console.error(error);
      toast({
        title: 'Erro',
        description: 'Nao foi possivel salvar as configuracoes.',
        variant: 'destructive'
      });
    } finally {
      setSavingSettings(false);
    }
  };

  const handleSaveTemplate = async (template: EditableNotificationTemplate) => {
    setSavingTemplateId(`${template.event_key}:${template.channel}`);
    try {
      const updated = await notificationTemplatesApi.upsert({
        event_key: template.event_key,
        channel: template.channel,
        title_template: template.title_template,
        subject_template: template.subject_template,
        body_template: template.body_template,
        is_active: template.is_active
      });
      setTemplates((current) =>
        current.map((item) => (
          item.event_key === template.event_key && item.channel === template.channel
            ? { ...(updated || item), isSeeded: true }
            : item
        ))
      );
      toast({
        title: 'Template salvo',
        description: `${EVENT_LABELS[template.event_key] || template.event_key} (${template.channel}) atualizado.`
      });
    } catch (error) {
      console.error(error);
      toast({
        title: 'Erro',
        description: 'Nao foi possivel salvar o template.',
        variant: 'destructive'
      });
    } finally {
      setSavingTemplateId(null);
    }
  };

  const handleTemplateChange = (templateId: string, field: keyof NotificationTemplate, value: string | boolean) => {
    setTemplates((current) =>
      current.map((item) => (item.id === templateId ? { ...item, [field]: value } : item))
    );
  };

  const handleRunWorkerCommand = async (command: NotificationWorkerCommand['command']) => {
    if (!userId) return;

    setRunningWorkerCommand(command);
    try {
      await notificationWorkerCommandsApi.enqueue(command, {}, userId);
      toast({
        title: 'Comando enviado',
        description: `${COMMAND_LABELS[command]} foi solicitado ao worker.`
      });
      await loadData();
    } catch (error) {
      console.error(error);
      toast({
        title: 'Erro',
        description: 'Nao foi possivel enviar o comando ao worker.',
        variant: 'destructive'
      });
    } finally {
      setRunningWorkerCommand(null);
    }
  };

  const handleRetryQueueItem = async (queueItemId: string) => {
    setRetryingQueueId(queueItemId);
    try {
      await notificationQueueApi.retry(queueItemId);
      toast({
        title: 'Item reenfileirado',
        description: 'A entrega voltou para a fila imediatamente.'
      });
      await loadData();
    } catch (error) {
      console.error(error);
      toast({
        title: 'Erro',
        description: 'Nao foi possivel reenfileirar este item.',
        variant: 'destructive'
      });
    } finally {
      setRetryingQueueId(null);
    }
  };

  const handleRetryFailedQueue = async () => {
    setRetryingAllFailed(true);
    try {
      const retriedCount = await notificationQueueApi.retryFailed();
      toast({
        title: 'Falhas reenfileiradas',
        description: retriedCount > 0
          ? `${retriedCount} item(ns) voltaram para a fila.`
          : 'Nenhum item com falha estava disponivel para reenfileirar.'
      });
      await loadData();
    } catch (error) {
      console.error(error);
      toast({
        title: 'Erro',
        description: 'Nao foi possivel reenfileirar as falhas.',
        variant: 'destructive'
      });
    } finally {
      setRetryingAllFailed(false);
    }
  };

  const sendTest = async (channel: 'toast' | 'email' | 'whatsapp') => {
    if (!userId) return;

    if (channel === 'email' && !testTargets.email) {
      toast({
        title: 'Destino ausente',
        description: 'Informe um e-mail de teste.',
        variant: 'destructive'
      });
      return;
    }

    if (channel === 'whatsapp' && !testTargets.whatsapp) {
      toast({
        title: 'Destino ausente',
        description: 'Informe um WhatsApp de teste.',
        variant: 'destructive'
      });
      return;
    }

    setSendingTestChannel(channel);
    try {
      await AlertService.createNotification({
        userId,
        title: 'Teste administrativo',
        message: testMessage,
        type: channel === 'toast' ? 'info' : 'warning',
        severity: 'medium',
        eventKey: 'system_critical',
        overrideChannels: {
          toast: channel === 'toast',
          email: channel === 'email',
          whatsapp: channel === 'whatsapp'
        },
        destinations: {
          email: testTargets.email,
          whatsapp: testTargets.whatsapp
        },
        ignoreGlobalSystemState: true,
        ignoreUserChannelPreferences: true,
        ignoreUserEventPreferences: true,
        metadata: {
          message: testMessage
        }
      });

      toast({
        title: 'Teste disparado',
        description: `Notificacao de teste enviada para ${channel}.`
      });

      await loadData();
    } catch (error) {
      console.error(error);
      toast({
        title: 'Erro',
        description: 'Nao foi possivel disparar o teste.',
        variant: 'destructive'
      });
    } finally {
      setSendingTestChannel(null);
    }
  };

  const groupedTemplates = React.useMemo(() => {
    const map = new Map<string, EditableNotificationTemplate[]>();

    for (const template of templates) {
      const current = map.get(template.event_key) || [];
      current.push(template);
      map.set(template.event_key, current.sort((left, right) => left.channel.localeCompare(right.channel)));
    }

    return Array.from(map.entries()).sort((left, right) => left[0].localeCompare(right[0]));
  }, [templates]);

  const queueCounts = React.useMemo(() => ({
    pending: queueItems.filter((item) => item.status === 'pending').length,
    processing: queueItems.filter((item) => item.status === 'processing').length,
    retrying: queueItems.filter((item) => item.status === 'retrying').length,
    failed: queueItems.filter((item) => item.status === 'failed').length,
    sent: queueItems.filter((item) => item.status === 'sent').length
  }), [queueItems]);

  const filteredQueueItems = React.useMemo(() => (
    queueFilter === 'all'
      ? queueItems
      : queueItems.filter((item) => item.status === queueFilter)
  ), [queueFilter, queueItems]);

  const deliveryCounts = React.useMemo(() => ({
    sent: deliveries.filter((item) => item.status === 'sent').length,
    failed: deliveries.filter((item) => item.status === 'failed').length
  }), [deliveries]);

  const filteredDeliveries = React.useMemo(() => (
    deliveryFilter === 'all'
      ? deliveries
      : deliveries.filter((item) => item.status === deliveryFilter)
  ), [deliveries, deliveryFilter]);

  const emailChannelState = React.useMemo(() => {
    if (!settings?.email_enabled) {
      return {
        label: 'E-mail desligado',
        variant: 'outline' as const,
        description: 'Canal desabilitado nas configurações globais.'
      };
    }

    if (!workerHealth?.smtpConfigured) {
      return {
        label: 'E-mail sem credencial',
        variant: 'destructive' as const,
        description: 'O canal está ligado na plataforma, mas o worker ainda não tem SMTP configurado.'
      };
    }

    return {
      label: 'E-mail pronto',
      variant: 'default' as const,
      description: 'Canal ativo e pronto para entrega externa.'
    };
  }, [settings?.email_enabled, workerHealth?.smtpConfigured]);

  const whatsappChannelState = React.useMemo(() => {
    if (!settings?.whatsapp_enabled) {
      return {
        label: 'WhatsApp desligado',
        variant: 'outline' as const,
        description: 'Canal desabilitado nas configurações globais.'
      };
    }

    if (!workerHealth?.whatsappConfigured) {
      return {
        label: 'WhatsApp sem credencial',
        variant: 'destructive' as const,
        description: 'O canal está ligado na plataforma, mas o worker ainda não tem provider configurado.'
      };
    }

    return {
      label: 'WhatsApp pronto',
      variant: 'default' as const,
      description: 'Canal ativo e pronto para entrega externa.'
    };
  }, [settings?.whatsapp_enabled, workerHealth?.whatsappConfigured]);

  const hasExternalChannelGap = (
    (settings?.email_enabled && !workerHealth?.smtpConfigured)
    || (settings?.whatsapp_enabled && !workerHealth?.whatsappConfigured)
  );
  const hasMissingWorkerCredentials = !workerHealth?.smtpConfigured || !workerHealth?.whatsappConfigured;

  if (loading || !settings) {
    return (
      <div className="w-full max-w-[1600px] mx-auto p-6">
        <div className="flex items-center gap-3 text-slate-500">
          <Loader2 className="h-5 w-5 animate-spin" />
          <span>Carregando gestão de notificações...</span>
        </div>
      </div>
    );
  }

  return (
    <div className="w-full max-w-[1600px] mx-auto p-6 space-y-6">
      <div className="flex items-center justify-between gap-4">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Gestão de Notificações</h1>
          <p className="text-muted-foreground">
            Configure canais, templates, testes e acompanhe a fila de entregas.
          </p>
        </div>

        <Button variant="outline" onClick={() => void loadData({ background: true, includeStatic: true })} disabled={refreshing}>
          {refreshing ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <RefreshCcw className="mr-2 h-4 w-4" />}
          Atualizar
        </Button>
      </div>

      <div className="grid gap-4 md:grid-cols-4">
        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-sm">Fila ativa</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-3xl font-black">{queueCounts.pending + queueCounts.processing + queueCounts.retrying}</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-sm">Notificações recentes</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-3xl font-black">{notifications.length}</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-sm">Entregas enviadas</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-3xl font-black">{deliveryCounts.sent}</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-sm">Canais externos</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            <div className="flex flex-wrap gap-2">
              <Badge variant={emailChannelState.variant}>
                {emailChannelState.label}
              </Badge>
              <Badge variant={whatsappChannelState.variant}>
                {whatsappChannelState.label}
              </Badge>
            </div>
            <div className="space-y-1 text-xs text-slate-600">
              <p>{emailChannelState.description}</p>
              <p>{whatsappChannelState.description}</p>
            </div>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Saúde do Worker</CardTitle>
          <CardDescription>
            Status real da fila assíncrona e da prontidão dos canais externos.
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex flex-wrap items-center gap-2">
            <Badge variant={workerHealth?.status === 'ok' ? 'default' : 'destructive'}>
              {workerHealth?.status === 'ok' ? 'Worker online' : 'Worker indisponível'}
            </Badge>
            <Badge variant={workerHealth?.processingCommands ? 'default' : 'outline'}>
              {workerHealth?.processingCommands ? 'Comandos em execução' : 'Comandos ociosos'}
            </Badge>
            <Badge variant={workerHealth?.smtpConfigured ? 'default' : 'outline'}>
              {workerHealth?.smtpConfigured ? 'SMTP configurado' : 'SMTP pendente'}
            </Badge>
            <Badge variant={workerHealth?.whatsappConfigured ? 'default' : 'outline'}>
              {workerHealth?.whatsappConfigured ? 'WhatsApp configurado' : 'WhatsApp pendente'}
            </Badge>
          </div>

          {hasMissingWorkerCredentials && (
            <div className="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
              <CircleAlert className="mt-0.5 h-4 w-4 shrink-0" />
              <div className="space-y-1">
                <p>
                  O worker ainda não recebeu todas as credenciais reais de entrega externa. Enquanto isso, a plataforma pode até enfileirar testes,
                  mas a entrega por e-mail/WhatsApp continuará indisponível ou falhará no processamento.
                </p>
                {hasExternalChannelGap && (
                  <p className="text-xs">
                    Existe também divergência entre toggle da plataforma e prontidão real do worker.
                  </p>
                )}
                {!workerHealth?.smtpConfigured && (
                  <p className="text-xs">
                    SMTP faltando: {(workerHealth?.missingSmtpEnvKeys || ['SMTP_HOST', 'SMTP_FROM_ADDRESS']).join(', ')}
                  </p>
                )}
                {!workerHealth?.whatsappConfigured && (
                  <p className="text-xs">
                    WhatsApp faltando: {(workerHealth?.missingWhatsappEnvKeys || ['WHATSAPP_API_BASE_URL']).join(', ')}
                  </p>
                )}
              </div>
            </div>
          )}

          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
              <div className="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-900">
                {workerHealth?.running ? <Loader2 className="h-4 w-4 animate-spin" /> : <CheckCircle2 className="h-4 w-4 text-emerald-600" />}
                Processamento da fila
              </div>
              <p className="text-xs text-slate-600">
                Última execução: {workerHealth?.lastRunAt ? new Date(workerHealth.lastRunAt).toLocaleString('pt-BR') : 'n/d'}
              </p>
              {workerHealth?.lastError && (
                <p className="mt-2 text-xs text-red-600">Erro: {workerHealth.lastError}</p>
              )}
            </div>

            <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
              <div className="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-900">
                {workerHealth?.generating ? <Loader2 className="h-4 w-4 animate-spin" /> : <CheckCircle2 className="h-4 w-4 text-emerald-600" />}
                Geração automática
              </div>
              <p className="text-xs text-slate-600">
                Última geração: {workerHealth?.lastGenerationRunAt ? new Date(workerHealth.lastGenerationRunAt).toLocaleString('pt-BR') : 'n/d'}
              </p>
              {workerHealth?.lastGenerationError && (
                <p className="mt-2 text-xs text-red-600">Erro: {workerHealth.lastGenerationError}</p>
              )}
            </div>

            <div className="rounded-xl border border-dashed border-slate-300 p-4">
              <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Intervalo da fila</p>
              <p className="mt-1 text-sm font-semibold text-slate-900">
                {workerHealth ? `${Math.round(workerHealth.workerIntervalMs / 1000)}s` : 'n/d'}
              </p>
            </div>

            <div className="rounded-xl border border-dashed border-slate-300 p-4">
              <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Intervalo do gerador</p>
              <p className="mt-1 text-sm font-semibold text-slate-900">
                {workerHealth ? `${Math.round(workerHealth.generatorIntervalMs / 1000)}s` : 'n/d'}
              </p>
            </div>

            <div className="rounded-xl border border-dashed border-slate-300 p-4">
              <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Poll de comandos</p>
              <p className="mt-1 text-sm font-semibold text-slate-900">
                {workerHealth ? `${Math.round(workerHealth.commandPollIntervalMs / 1000)}s` : 'n/d'}
              </p>
            </div>
          </div>

          <div className="grid gap-4 xl:grid-cols-[1.1fr,0.9fr]">
            <div className="rounded-xl border p-4 space-y-3">
              <div>
                <p className="font-semibold text-slate-900">Ações manuais</p>
                <p className="text-xs text-slate-600">Dispare rotinas do worker sem esperar o próximo ciclo automático.</p>
              </div>
              <div className="flex flex-wrap gap-3">
                <Button
                  variant="outline"
                  onClick={() => void handleRunWorkerCommand('process_queue')}
                  disabled={runningWorkerCommand !== null}
                >
                  {runningWorkerCommand === 'process_queue' ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <RefreshCcw className="mr-2 h-4 w-4" />}
                  Processar fila agora
                </Button>
                <Button
                  variant="outline"
                  onClick={() => void handleRunWorkerCommand('generate_notifications')}
                  disabled={runningWorkerCommand !== null}
                >
                  {runningWorkerCommand === 'generate_notifications' ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <BellRing className="mr-2 h-4 w-4" />}
                  Gerar notificações agora
                </Button>
              </div>
              {workerHealth?.lastCommandError && (
                <div className="rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-700">
                  Último erro de comando: {workerHealth.lastCommandError}
                </div>
              )}
            </div>

            <div className="rounded-xl border p-4 space-y-3">
              <div>
                <p className="font-semibold text-slate-900">Comandos recentes</p>
                <p className="text-xs text-slate-600">
                  Última execução: {workerHealth?.lastCommandRunAt ? new Date(workerHealth.lastCommandRunAt).toLocaleString('pt-BR') : 'n/d'}
                </p>
              </div>
              {workerCommands.length === 0 ? (
                <div className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                  Nenhum comando manual registrado ainda.
                </div>
              ) : workerCommands.map((command) => (
                <div key={command.id} className="rounded-lg border p-3">
                  <div className="flex flex-wrap items-center justify-between gap-2">
                    <p className="text-sm font-semibold">{COMMAND_LABELS[command.command]}</p>
                    <Badge variant={command.status === 'failed' ? 'destructive' : command.status === 'completed' ? 'default' : 'outline'}>
                      {command.status}
                    </Badge>
                  </div>
                  <p className="mt-1 text-xs text-muted-foreground">
                    Solicitado em {new Date(command.requested_at).toLocaleString('pt-BR')}
                  </p>
                  {formatCommandResult(command.result) && (
                    <p className="mt-2 text-xs text-emerald-700">{formatCommandResult(command.result)}</p>
                  )}
                  {command.error_message && (
                    <p className="mt-2 text-xs text-red-600">{command.error_message}</p>
                  )}
                </div>
              ))}
            </div>
          </div>

          {!workerHealth && (
            <div className="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
              <CircleAlert className="mt-0.5 h-4 w-4 shrink-0" />
              <span>Não foi possível consultar o status do worker nesta sessão.</span>
            </div>
          )}
        </CardContent>
      </Card>

      <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-4">
        <TabsList className="grid w-full grid-cols-3">
          <TabsTrigger value="config">
            <Settings2 className="mr-2 h-4 w-4" />
            Configurações
          </TabsTrigger>
          <TabsTrigger value="templates">
            <MessageSquareText className="mr-2 h-4 w-4" />
            Templates
          </TabsTrigger>
          <TabsTrigger value="deliveries">
            <BellRing className="mr-2 h-4 w-4" />
            Fila e Logs
          </TabsTrigger>
        </TabsList>

        <TabsContent value="config" className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Canais globais</CardTitle>
              <CardDescription>
                Habilite os canais disponíveis e defina os padrões da plataforma.
              </CardDescription>
            </CardHeader>
            <CardContent className="grid gap-6 md:grid-cols-2">
              <div className="space-y-4">
                <div className="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 p-4">
                  <div>
                    <Label>Sistema de notificações ativo</Label>
                    <p className="text-xs text-muted-foreground">Pausa ou reativa geração, fila e entregas do módulo.</p>
                  </div>
                  <Switch
                    checked={settings.is_active}
                    onCheckedChange={(checked) => setSettings((current) => current ? { ...current, is_active: checked } : current)}
                  />
                </div>
                <div className="flex items-center justify-between">
                  <div>
                    <Label>Toast na plataforma</Label>
                    <p className="text-xs text-muted-foreground">Exibe alertas dentro da aplicação.</p>
                  </div>
                  <Switch
                    checked={settings.toast_enabled}
                    onCheckedChange={(checked) => setSettings((current) => current ? { ...current, toast_enabled: checked } : current)}
                  />
                </div>
                <div className="flex items-center justify-between">
                  <div>
                    <Label>Banco de dados</Label>
                    <p className="text-xs text-muted-foreground">Mantém histórico e central de notificações.</p>
                  </div>
                  <Switch
                    checked={settings.database_enabled}
                    onCheckedChange={(checked) => setSettings((current) => current ? { ...current, database_enabled: checked } : current)}
                  />
                </div>
                <div className="flex items-center justify-between">
                  <div>
                    <Label>E-mail</Label>
                    <p className="text-xs text-muted-foreground">Enfileira entregas por SMTP.</p>
                  </div>
                  <Switch
                    checked={settings.email_enabled}
                    onCheckedChange={(checked) => setSettings((current) => current ? { ...current, email_enabled: checked } : current)}
                  />
                </div>
                <div className="flex items-center justify-between">
                  <div>
                    <Label>WhatsApp</Label>
                    <p className="text-xs text-muted-foreground">Enfileira entregas pelo adapter configurado.</p>
                  </div>
                  <Switch
                    checked={settings.whatsapp_enabled}
                    onCheckedChange={(checked) => setSettings((current) => current ? { ...current, whatsapp_enabled: checked } : current)}
                  />
                </div>
                <div className="flex items-center justify-between">
                  <div>
                    <Label>Permitir override do usuário</Label>
                    <p className="text-xs text-muted-foreground">Usuários podem ajustar seus próprios canais.</p>
                  </div>
                  <Switch
                    checked={settings.allow_user_channel_overrides}
                    onCheckedChange={(checked) => setSettings((current) => current ? { ...current, allow_user_channel_overrides: checked } : current)}
                  />
                </div>
              </div>

              <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                  <Label htmlFor="days-before-due-default">Dias antes do vencimento</Label>
                  <Input
                    id="days-before-due-default"
                    type="number"
                    value={settings.days_before_due_default}
                    onChange={(event) => setSettings((current) => current ? { ...current, days_before_due_default: Number(event.target.value) || 0 } : current)}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="days-before-overdue-default">Dias para vencido</Label>
                  <Input
                    id="days-before-overdue-default"
                    type="number"
                    value={settings.days_before_overdue_default}
                    onChange={(event) => setSettings((current) => current ? { ...current, days_before_overdue_default: Number(event.target.value) || 0 } : current)}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="quiet-hours-start-default">Silêncio início</Label>
                  <Input
                    id="quiet-hours-start-default"
                    type="time"
                    value={settings.quiet_hours_start_default}
                    onChange={(event) => setSettings((current) => current ? { ...current, quiet_hours_start_default: event.target.value } : current)}
                  />
                </div>
              <div className="space-y-2">
                <Label htmlFor="quiet-hours-end-default">Silêncio fim</Label>
                <Input
                  id="quiet-hours-end-default"
                  type="time"
                    value={settings.quiet_hours_end_default}
                    onChange={(event) => setSettings((current) => current ? { ...current, quiet_hours_end_default: event.target.value } : current)}
                  />
                </div>
                <div className="flex items-center justify-between rounded-xl border p-4 sm:col-span-2">
                  <div>
                    <Label>Alertas em fim de semana por padrão</Label>
                    <p className="text-xs text-muted-foreground">Define o comportamento inicial para sábados e domingos.</p>
                  </div>
                  <Switch
                    checked={settings.weekend_notifications_default}
                    onCheckedChange={(checked) => setSettings((current) => current ? { ...current, weekend_notifications_default: checked } : current)}
                  />
                </div>
                <div className="space-y-2 sm:col-span-2">
                  <Label htmlFor="email-from-name">Nome remetente e-mail</Label>
                  <Input
                    id="email-from-name"
                    value={settings.email_from_name || ''}
                    onChange={(event) => setSettings((current) => current ? { ...current, email_from_name: event.target.value } : current)}
                  />
                </div>
                <div className="space-y-2 sm:col-span-2">
                  <Label htmlFor="email-from-address">E-mail remetente</Label>
                  <Input
                    id="email-from-address"
                    value={settings.email_from_address || ''}
                    onChange={(event) => setSettings((current) => current ? { ...current, email_from_address: event.target.value } : current)}
                  />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Padrões globais por evento</CardTitle>
              <CardDescription>
                Defina quais tipos de evento já nascem habilitados para os usuários e para o gerador automático.
              </CardDescription>
            </CardHeader>
            <CardContent className="grid gap-4 md:grid-cols-2">
              <div className="flex items-center justify-between rounded-xl border p-4">
                <div>
                  <Label>Contas vencendo em breve</Label>
                  <p className="text-xs text-muted-foreground">Padrão inicial para avisos prévios de contas a pagar.</p>
                </div>
                <Switch
                  checked={settings.alert_due_soon_default}
                  onCheckedChange={(checked) => setSettings((current) => current ? { ...current, alert_due_soon_default: checked } : current)}
                />
              </div>

              <div className="flex items-center justify-between rounded-xl border p-4">
                <div>
                  <Label>Contas vencidas</Label>
                  <p className="text-xs text-muted-foreground">Padrão inicial para alertas de atraso.</p>
                </div>
                <Switch
                  checked={settings.alert_overdue_default}
                  onCheckedChange={(checked) => setSettings((current) => current ? { ...current, alert_overdue_default: checked } : current)}
                />
              </div>

              <div className="flex items-center justify-between rounded-xl border p-4">
                <div>
                  <Label>Pagamentos confirmados</Label>
                  <p className="text-xs text-muted-foreground">Padrão inicial para confirmações de contas pagas.</p>
                </div>
                <Switch
                  checked={settings.alert_paid_default}
                  onCheckedChange={(checked) => setSettings((current) => current ? { ...current, alert_paid_default: checked } : current)}
                />
              </div>

              <div className="flex items-center justify-between rounded-xl border p-4">
                <div>
                  <Label>Recebimentos</Label>
                  <p className="text-xs text-muted-foreground">Padrão inicial para recebimentos próximos e confirmados.</p>
                </div>
                <Switch
                  checked={settings.alert_received_default}
                  onCheckedChange={(checked) => setSettings((current) => current ? { ...current, alert_received_default: checked } : current)}
                />
              </div>

              <div className="flex items-center justify-between rounded-xl border p-4 md:col-span-2">
                <div>
                  <Label>Eventos críticos do sistema</Label>
                  <p className="text-xs text-muted-foreground">Mantém o padrão ativo para alertas operacionais sensíveis.</p>
                </div>
                <Switch
                  checked={settings.system_critical_default}
                  onCheckedChange={(checked) => setSettings((current) => current ? { ...current, system_critical_default: checked } : current)}
                />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Testes de canal</CardTitle>
              <CardDescription>
                Dispare testes administrativos usando os destinos informados abaixo.
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {!settings.is_active && (
                <div className="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
                  Os testes administrativos continuam disponíveis mesmo com o sistema global pausado.
                </div>
              )}
              <Textarea
                value={testMessage}
                onChange={(event) => setTestMessage(event.target.value)}
                rows={4}
                placeholder="Mensagem de teste"
              />
              <div className="grid gap-4 md:grid-cols-2">
                <div className="space-y-2">
                  <Label htmlFor="test-email">E-mail de teste</Label>
                  <Input
                    id="test-email"
                    value={testTargets.email}
                    onChange={(event) => setTestTargets((current) => ({ ...current, email: event.target.value }))}
                    placeholder="financeiro@empresa.com"
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="test-whatsapp">WhatsApp de teste</Label>
                  <Input
                    id="test-whatsapp"
                    value={testTargets.whatsapp}
                    onChange={(event) => setTestTargets((current) => ({ ...current, whatsapp: event.target.value }))}
                    placeholder="+5511999999999"
                  />
                </div>
              </div>
              <div className="flex flex-wrap gap-3">
                <Button variant="outline" onClick={() => void sendTest('toast')} disabled={sendingTestChannel !== null}>
                  {sendingTestChannel === 'toast' ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <BellRing className="mr-2 h-4 w-4" />}
                  Testar toast
                </Button>
                <Button variant="outline" onClick={() => void sendTest('email')} disabled={sendingTestChannel !== null}>
                  {sendingTestChannel === 'email' ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Mail className="mr-2 h-4 w-4" />}
                  Testar e-mail
                </Button>
                <Button variant="outline" onClick={() => void sendTest('whatsapp')} disabled={sendingTestChannel !== null}>
                  {sendingTestChannel === 'whatsapp' ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Smartphone className="mr-2 h-4 w-4" />}
                  Testar WhatsApp
                </Button>
              </div>
            </CardContent>
          </Card>

          <div className="flex justify-end">
            <Button onClick={() => void handleSaveSettings()} disabled={savingSettings}>
              {savingSettings ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Save className="mr-2 h-4 w-4" />}
              Salvar configurações
            </Button>
          </div>
        </TabsContent>

        <TabsContent value="templates" className="space-y-4">
          {groupedTemplates.map(([eventKey, eventTemplates]) => (
            <Card key={eventKey}>
              <CardHeader>
                <CardTitle>{EVENT_LABELS[eventKey] || eventKey}</CardTitle>
                <CardDescription>{eventKey}</CardDescription>
              </CardHeader>
              <CardContent className="grid gap-4 lg:grid-cols-3">
                {eventTemplates.map((template) => (
                  <div key={template.id} className="rounded-xl border p-4 space-y-3">
                    <div className="flex items-center justify-between">
                      <div className="flex items-center gap-2 font-bold capitalize">
                        {template.channel === 'toast' ? <BellRing className="h-4 w-4" /> : template.channel === 'email' ? <Mail className="h-4 w-4" /> : <Smartphone className="h-4 w-4" />}
                        {template.channel}
                      </div>
                      <div className="flex items-center gap-2">
                        {!template.isSeeded && (
                          <Badge variant="outline">Padrão local</Badge>
                        )}
                        <Switch
                          checked={template.is_active}
                          onCheckedChange={(checked) => handleTemplateChange(template.id, 'is_active', checked)}
                        />
                      </div>
                    </div>
                    <div className="space-y-2">
                      <Label>Título</Label>
                      <Input
                        value={template.title_template}
                        onChange={(event) => handleTemplateChange(template.id, 'title_template', event.target.value)}
                      />
                    </div>
                    {template.channel === 'email' && (
                      <div className="space-y-2">
                        <Label>Assunto</Label>
                        <Input
                          value={template.subject_template || ''}
                          onChange={(event) => handleTemplateChange(template.id, 'subject_template', event.target.value)}
                        />
                      </div>
                    )}
                    <div className="space-y-2">
                      <Label>Corpo</Label>
                      <Textarea
                        rows={5}
                        value={template.body_template}
                        onChange={(event) => handleTemplateChange(template.id, 'body_template', event.target.value)}
                      />
                    </div>
                    <Button
                      className="w-full"
                      variant="outline"
                      onClick={() => void handleSaveTemplate(template)}
                      disabled={savingTemplateId === `${template.event_key}:${template.channel}`}
                    >
                      {savingTemplateId === `${template.event_key}:${template.channel}` ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Save className="mr-2 h-4 w-4" />}
                      Salvar template
                    </Button>
                  </div>
                ))}
              </CardContent>
            </Card>
          ))}
        </TabsContent>

        <TabsContent value="deliveries" className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Notificações recentes</CardTitle>
              <CardDescription>Eventos gravados no banco antes do envio por canal ou leitura pelo usuário.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-3">
              {notifications.length === 0 ? (
                <div className="rounded-xl border border-dashed p-6 text-sm text-muted-foreground">
                  Nenhuma notificação recente encontrada.
                </div>
              ) : notifications.map((notification) => (
                <div key={notification.id} className="space-y-2 rounded-xl border p-4">
                  <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                      <p className="font-semibold">{notification.title}</p>
                      <p className="text-xs text-muted-foreground">{notification.message}</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                      <Badge variant={notification.is_read ? 'outline' : 'default'}>
                        {notification.is_read ? 'lida' : 'não lida'}
                      </Badge>
                      <Badge variant="outline">{EVENT_LABELS[notification.event_key] || notification.event_key}</Badge>
                      <Badge variant={notification.type === 'warning' ? 'destructive' : 'outline'}>
                        {notification.type}
                      </Badge>
                    </div>
                  </div>
                  <div className="grid gap-2 text-xs text-muted-foreground md:grid-cols-3">
                    <span>Usuário: {profileNames[notification.user_id] || notification.user_id}</span>
                    <span>Criada em: {new Date(notification.created_at).toLocaleString('pt-BR')}</span>
                    <span>Ação: {notification.action_url || 'n/d'}</span>
                  </div>
                </div>
              ))}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                  <CardTitle>Fila recente</CardTitle>
                  <CardDescription>Itens aguardando, processando ou finalizados recentemente.</CardDescription>
                </div>
                <Button
                  variant="outline"
                  onClick={() => void handleRetryFailedQueue()}
                  disabled={retryingAllFailed || queueCounts.failed === 0}
                >
                  {retryingAllFailed ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <RotateCcw className="mr-2 h-4 w-4" />}
                  Reenfileirar falhas ({queueCounts.failed})
                </Button>
              </div>
            </CardHeader>
            <CardContent className="space-y-3">
              <div className="flex flex-wrap gap-2">
                <Button variant={queueFilter === 'all' ? 'default' : 'outline'} size="sm" onClick={() => setQueueFilter('all')}>
                  Todas ({queueItems.length})
                </Button>
                <Button variant={queueFilter === 'pending' ? 'default' : 'outline'} size="sm" onClick={() => setQueueFilter('pending')}>
                  Pendentes ({queueCounts.pending})
                </Button>
                <Button variant={queueFilter === 'processing' ? 'default' : 'outline'} size="sm" onClick={() => setQueueFilter('processing')}>
                  Processando ({queueCounts.processing})
                </Button>
                <Button variant={queueFilter === 'retrying' ? 'default' : 'outline'} size="sm" onClick={() => setQueueFilter('retrying')}>
                  Reagendadas ({queueCounts.retrying})
                </Button>
                <Button variant={queueFilter === 'failed' ? 'default' : 'outline'} size="sm" onClick={() => setQueueFilter('failed')}>
                  Falhas ({queueCounts.failed})
                </Button>
                <Button variant={queueFilter === 'sent' ? 'default' : 'outline'} size="sm" onClick={() => setQueueFilter('sent')}>
                  Enviadas ({queueCounts.sent})
                </Button>
              </div>

              {filteredQueueItems.length === 0 ? (
                <div className="rounded-xl border border-dashed p-6 text-sm text-muted-foreground">
                  Nenhum item encontrado para o filtro atual.
                </div>
              ) : filteredQueueItems.map((item) => (
                <div key={item.id} className="space-y-3 rounded-xl border p-4">
                  <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="space-y-1">
                      <p className="font-semibold">{item.channel.toUpperCase()} • {item.destination}</p>
                      <p className="text-xs text-muted-foreground">{item.content}</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                      <Badge variant={item.status === 'failed' ? 'destructive' : item.status === 'sent' ? 'default' : 'outline'}>
                        {item.status}
                      </Badge>
                      <span className="text-xs text-muted-foreground">Tentativas: {item.attempts}/{item.max_attempts}</span>
                      {item.status === 'failed' && (
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => void handleRetryQueueItem(item.id)}
                          disabled={retryingQueueId === item.id}
                        >
                          {retryingQueueId === item.id ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <RotateCcw className="mr-2 h-4 w-4" />}
                          Reenfileirar
                        </Button>
                      )}
                    </div>
                  </div>
                  <div className="grid gap-2 text-xs text-muted-foreground md:grid-cols-3">
                    <span>Criado em: {new Date(item.created_at).toLocaleString('pt-BR')}</span>
                    <span>Próxima tentativa: {new Date(item.next_attempt_at).toLocaleString('pt-BR')}</span>
                    <span>Enviado em: {item.sent_at ? new Date(item.sent_at).toLocaleString('pt-BR') : 'n/d'}</span>
                  </div>
                  {item.last_error && (
                    <div className="rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-700">
                      {item.last_error}
                    </div>
                  )}
                </div>
              ))}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Entregas recentes</CardTitle>
              <CardDescription>Resultado final das tentativas de envio.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-3">
              <div className="flex flex-wrap gap-2">
                <Button variant={deliveryFilter === 'all' ? 'default' : 'outline'} size="sm" onClick={() => setDeliveryFilter('all')}>
                  Todas ({deliveries.length})
                </Button>
                <Button variant={deliveryFilter === 'sent' ? 'default' : 'outline'} size="sm" onClick={() => setDeliveryFilter('sent')}>
                  Enviadas ({deliveryCounts.sent})
                </Button>
                <Button variant={deliveryFilter === 'failed' ? 'default' : 'outline'} size="sm" onClick={() => setDeliveryFilter('failed')}>
                  Falhas ({deliveryCounts.failed})
                </Button>
              </div>

              {filteredDeliveries.length === 0 ? (
                <div className="rounded-xl border border-dashed p-6 text-sm text-muted-foreground">
                  Nenhuma entrega encontrada para o filtro atual.
                </div>
              ) : filteredDeliveries.map((delivery) => (
                <div key={delivery.id} className="flex flex-wrap items-center justify-between gap-3 rounded-xl border p-3">
                  <div>
                    <p className="font-semibold">{delivery.provider} • {delivery.destination}</p>
                    <p className="text-xs text-muted-foreground">{new Date(delivery.attempted_at).toLocaleString('pt-BR')}</p>
                  </div>
                  <div className="flex items-center gap-2">
                    <Badge variant={delivery.status === 'failed' ? 'destructive' : 'default'}>{delivery.status}</Badge>
                    {delivery.error_message && (
                      <span className="max-w-[480px] truncate text-xs text-red-600">{delivery.error_message}</span>
                    )}
                  </div>
                </div>
              ))}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}
