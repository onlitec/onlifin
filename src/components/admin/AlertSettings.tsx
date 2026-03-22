import * as React from 'react';
import { Bell, Clock, Database, Loader2, Mail, ShieldAlert, Smartphone, TestTube } from 'lucide-react';
import { alertPreferencesApi, notificationSettingsApi } from '@/db/api';
import { AlertService } from '@/services/alertService';
import type { AlertPreferences, NotificationSettings } from '@/types/types';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { useToast } from '@/hooks/use-toast';

interface AlertSettingsProps {
  userId: string;
  emailDestination?: string;
  whatsappDestination?: string;
}

export function AlertSettings({ userId, emailDestination = '', whatsappDestination = '' }: AlertSettingsProps) {
  const { toast } = useToast();
  const [preferences, setPreferences] = React.useState<AlertPreferences | null>(null);
  const [globalSettings, setGlobalSettings] = React.useState<NotificationSettings | null>(null);
  const [isLoading, setIsLoading] = React.useState(true);
  const [isSaving, setIsSaving] = React.useState(false);
  const [isTesting, setIsTesting] = React.useState(false);

  const loadPreferences = React.useCallback(async () => {
    try {
      const [prefs, settings] = await Promise.all([
        alertPreferencesApi.getPreferences(userId),
        notificationSettingsApi.getGlobal()
      ]);

      setGlobalSettings(settings);

      if (prefs) {
        setPreferences(prefs);
      } else {
        const defaultPrefs = await alertPreferencesApi.createDefaultPreferences(userId);
        setPreferences(defaultPrefs);
      }
    } catch (error) {
      console.error(error);
      toast({
        title: 'Erro',
        description: 'Nao foi possivel carregar as preferencias de notificacao.',
        variant: 'destructive'
      });
    } finally {
      setIsLoading(false);
    }
  }, [toast, userId]);

  React.useEffect(() => {
    void loadPreferences();
  }, [loadPreferences]);

  const savePreferences = async () => {
    if (!preferences) return;
    setIsSaving(true);
    try {
      const updated = await alertPreferencesApi.updatePreferences(userId, preferences);
      setPreferences(updated);
      toast({
        title: 'Preferencias salvas',
        description: 'Suas configuracoes de notificacao foram atualizadas.'
      });
    } catch (error) {
      console.error(error);
      toast({
        title: 'Erro',
        description: 'Nao foi possivel salvar suas preferencias.',
        variant: 'destructive'
      });
    } finally {
      setIsSaving(false);
    }
  };

  const sendTest = async () => {
    setIsTesting(true);
    try {
      await AlertService.createNotification({
        userId,
        title: 'Teste pessoal',
        message: 'Esta e uma notificacao de teste das suas preferencias individuais.',
        type: 'info',
        severity: 'medium',
        eventKey: 'system_critical',
        ignoreUserEventPreferences: true
      });
      toast({
        title: 'Teste enviado',
        description: 'A notificacao de teste foi disparada.'
      });
    } catch (error) {
      console.error(error);
      toast({
        title: 'Erro',
        description: 'Nao foi possivel disparar o teste.',
        variant: 'destructive'
      });
    } finally {
      setIsTesting(false);
    }
  };

  if (isLoading || !preferences) {
    return (
      <div className="flex items-center gap-2 text-sm text-muted-foreground">
        <Loader2 className="h-4 w-4 animate-spin" />
        Carregando preferencias...
      </div>
    );
  }

  const channelOverrideLocked = globalSettings ? !globalSettings.allow_user_channel_overrides : false;
  const emailAvailable = Boolean(globalSettings?.email_enabled);
  const whatsappAvailable = Boolean(globalSettings?.whatsapp_enabled);
  const hasEmailDestination = emailDestination.trim().length > 0;
  const hasWhatsappDestination = whatsappDestination.trim().length > 0;

  return (
    <div className="space-y-6">
      {globalSettings && (
        <Alert>
          <ShieldAlert className="h-4 w-4" />
          <AlertDescription>
            Os canais disponiveis seguem a configuracao global da plataforma.
            {channelOverrideLocked ? ' O administrador bloqueou alteracoes individuais de canal.' : ' Voce pode personalizar seus canais dentro do que estiver habilitado.'}
          </AlertDescription>
        </Alert>
      )}

      <div className="grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Clock className="h-5 w-5" />
              Regras pessoais
            </CardTitle>
            <CardDescription>
              Antecedência, janela silenciosa e comportamento geral dos alertas.
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid gap-4 md:grid-cols-2">
              <div className="space-y-2">
                <Label>Dias antes do vencimento</Label>
                <Select
                  value={String(preferences.days_before_due)}
                  onValueChange={(value) => setPreferences((current) => current ? { ...current, days_before_due: Number(value) } : current)}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="1">1 dia</SelectItem>
                    <SelectItem value="3">3 dias</SelectItem>
                    <SelectItem value="5">5 dias</SelectItem>
                    <SelectItem value="7">7 dias</SelectItem>
                    <SelectItem value="15">15 dias</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label>Dias para considerar vencido</Label>
                <Select
                  value={String(preferences.days_before_overdue)}
                  onValueChange={(value) => setPreferences((current) => current ? { ...current, days_before_overdue: Number(value) } : current)}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="0">Mesmo dia</SelectItem>
                    <SelectItem value="1">1 dia</SelectItem>
                    <SelectItem value="2">2 dias</SelectItem>
                    <SelectItem value="3">3 dias</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label>Silêncio início</Label>
                <Input
                  type="time"
                  value={preferences.quiet_hours_start}
                  onChange={(event) => setPreferences((current) => current ? { ...current, quiet_hours_start: event.target.value } : current)}
                />
              </div>

              <div className="space-y-2">
                <Label>Silêncio fim</Label>
                <Input
                  type="time"
                  value={preferences.quiet_hours_end}
                  onChange={(event) => setPreferences((current) => current ? { ...current, quiet_hours_end: event.target.value } : current)}
                />
              </div>
            </div>

            <div className="flex items-center justify-between">
              <div>
                <Label>Alertas em fim de semana</Label>
                <p className="text-xs text-muted-foreground">Receber alertas também aos sábados e domingos.</p>
              </div>
              <Switch
                checked={preferences.weekend_notifications}
                onCheckedChange={(checked) => setPreferences((current) => current ? { ...current, weekend_notifications: checked } : current)}
              />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Bell className="h-5 w-5" />
              Tipos e canais
            </CardTitle>
            <CardDescription>
              Escolha o que deseja receber e como prefere receber.
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-5">
            <div className="grid gap-3">
              <div className="flex items-center justify-between">
                <div>
                  <Label>Contas vencendo</Label>
                  <p className="text-xs text-muted-foreground">Avisos antes do vencimento.</p>
                </div>
                <Switch checked={preferences.alert_due_soon} onCheckedChange={(checked) => setPreferences((current) => current ? { ...current, alert_due_soon: checked } : current)} />
              </div>
              <div className="flex items-center justify-between">
                <div>
                  <Label>Contas vencidas</Label>
                  <p className="text-xs text-muted-foreground">Notificações para contas em atraso.</p>
                </div>
                <Switch checked={preferences.alert_overdue} onCheckedChange={(checked) => setPreferences((current) => current ? { ...current, alert_overdue: checked } : current)} />
              </div>
              <div className="flex items-center justify-between">
                <div>
                  <Label>Pagamentos confirmados</Label>
                  <p className="text-xs text-muted-foreground">Confirmação de contas pagas.</p>
                </div>
                <Switch checked={preferences.alert_paid} onCheckedChange={(checked) => setPreferences((current) => current ? { ...current, alert_paid: checked } : current)} />
              </div>
              <div className="flex items-center justify-between">
                <div>
                  <Label>Recebimentos confirmados</Label>
                  <p className="text-xs text-muted-foreground">Confirmação de valores recebidos.</p>
                </div>
                <Switch checked={preferences.alert_received} onCheckedChange={(checked) => setPreferences((current) => current ? { ...current, alert_received: checked } : current)} />
              </div>
              <div className="flex items-center justify-between">
                <div>
                  <Label>Alertas críticos do sistema</Label>
                  <p className="text-xs text-muted-foreground">Erros operacionais e eventos críticos.</p>
                </div>
                <Switch checked={preferences.system_critical_notifications} onCheckedChange={(checked) => setPreferences((current) => current ? { ...current, system_critical_notifications: checked } : current)} />
              </div>
            </div>

            <div className="space-y-3 rounded-xl border p-4">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <Bell className="h-4 w-4 text-blue-500" />
                  <div>
                    <Label>Toast na plataforma</Label>
                    <p className="text-xs text-muted-foreground">Mensagens rápidas dentro do sistema.</p>
                  </div>
                </div>
                <Switch
                  disabled={channelOverrideLocked || !globalSettings?.toast_enabled}
                  checked={preferences.toast_notifications}
                  onCheckedChange={(checked) => setPreferences((current) => current ? { ...current, toast_notifications: checked } : current)}
                />
              </div>

              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <Database className="h-4 w-4 text-emerald-500" />
                  <div>
                    <Label>Central interna</Label>
                    <p className="text-xs text-muted-foreground">Salva histórico na central de notificações.</p>
                  </div>
                </div>
                <Switch
                  disabled
                  checked={preferences.database_notifications}
                  onCheckedChange={() => null}
                />
              </div>

              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <Mail className="h-4 w-4 text-purple-500" />
                  <div>
                    <Label>E-mail</Label>
                    <p className="text-xs text-muted-foreground">
                      {!emailAvailable
                        ? 'Canal desabilitado pela administração.'
                        : hasEmailDestination
                          ? 'Receber alertas por e-mail.'
                          : 'Informe antes um e-mail de recebimento nas suas preferências.'}
                    </p>
                  </div>
                </div>
                <Switch
                  disabled={channelOverrideLocked || !emailAvailable || !hasEmailDestination}
                  checked={preferences.email_notifications}
                  onCheckedChange={(checked) => setPreferences((current) => current ? { ...current, email_notifications: checked } : current)}
                />
              </div>

              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <Smartphone className="h-4 w-4 text-orange-500" />
                  <div>
                    <Label>WhatsApp</Label>
                    <p className="text-xs text-muted-foreground">
                      {!whatsappAvailable
                        ? 'Canal desabilitado pela administração.'
                        : hasWhatsappDestination
                          ? 'Receber alertas transacionais por WhatsApp.'
                          : 'Informe antes um WhatsApp de recebimento nas suas preferências.'}
                    </p>
                  </div>
                </div>
                <Switch
                  disabled={channelOverrideLocked || !whatsappAvailable || !hasWhatsappDestination}
                  checked={preferences.whatsapp_notifications}
                  onCheckedChange={(checked) => setPreferences((current) => current ? { ...current, whatsapp_notifications: checked } : current)}
                />
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <div className="flex flex-wrap justify-end gap-3">
        <Button variant="outline" onClick={() => void sendTest()} disabled={isTesting}>
          {isTesting ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <TestTube className="mr-2 h-4 w-4" />}
          Testar notificação
        </Button>
        <Button onClick={() => void savePreferences()} disabled={isSaving}>
          {isSaving ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : null}
          Salvar preferências
        </Button>
      </div>
    </div>
  );
}
