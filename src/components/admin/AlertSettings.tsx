import * as React from 'react';
import { alertPreferencesApi } from '@/db/api';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { useToast } from '@/hooks/use-toast';
import { 
  Bell, 
  Clock, 
  Calendar, 
  AlertTriangle, 
  CheckCircle, 
  Settings,
  Volume2,
  Mail,
  Smartphone,
  Monitor,
  Loader2,
  TestTube,
  Database
} from 'lucide-react';
import type { AlertPreferences } from '@/types/types';

interface AlertSettingsProps {
  userId: string;
}

export function AlertSettings({ userId }: AlertSettingsProps) {
  const { toast } = useToast();
  const [preferences, setPreferences] = React.useState<AlertPreferences | null>(null);
  const [isLoading, setIsLoading] = React.useState(true);
  const [isSaving, setIsSaving] = React.useState(false);
  const [isTesting, setIsTesting] = React.useState(false);

  React.useEffect(() => {
    loadPreferences();
  }, [userId]);

  const loadPreferences = async () => {
    try {
      console.log('Carregando preferências para userId:', userId);
      
      // Verificar se userId é válido
      if (!userId || userId === 'null' || userId === 'undefined') {
        console.error('UserId inválido:', userId);
        throw new Error('ID de usuário inválido');
      }
      
      const data = await alertPreferencesApi.getPreferences(userId);
      console.log('Dados recebidos:', data);
      
      if (data) {
        setPreferences(data);
      } else {
        // Criar preferências padrão
        console.log('Criando preferências padrão...');
        const defaultPrefs = await alertPreferencesApi.createDefaultPreferences(userId);
        console.log('Preferências padrão criadas:', defaultPrefs);
        if (defaultPrefs) {
          setPreferences(defaultPrefs);
        }
      }
    } catch (error) {
      console.error('Erro ao carregar preferências:', error);
      toast({
        title: 'Erro',
        description: 'Não foi possível carregar as preferências de alerta',
        variant: 'destructive'
      });
    } finally {
      setIsLoading(false);
    }
  };

  const savePreferences = async () => {
    if (!preferences) return;

    setIsSaving(true);
    try {
      const updated = await alertPreferencesApi.updatePreferences(userId, preferences);
      if (updated) {
        setPreferences(updated);
        toast({
          title: 'Sucesso',
          description: 'Preferências de alerta salvas com sucesso',
          variant: 'default'
        });
      }
    } catch (error) {
      console.error('Erro ao salvar preferências:', error);
      toast({
        title: 'Erro',
        description: 'Não foi possível salvar as preferências',
        variant: 'destructive'
      });
    } finally {
      setIsSaving(false);
    }
  };

  const testNotification = async (type: 'due-soon' | 'overdue' | 'paid' | 'received') => {
    setIsTesting(true);
    try {
      // Importar dinamicamente para evitar circular dependency
      const { AlertService } = await import('@/services/alertService');
      
      switch (type) {
        case 'due-soon':
          await AlertService.createNotification({
            title: 'Teste - Conta Vencendo em Breve',
            message: 'Esta é uma notificação de teste para conta vencendo em breve',
            type: 'alert',
            severity: 'medium',
            userId
          });
          break;
        case 'overdue':
          await AlertService.createNotification({
            title: 'Teste - Conta Vencida',
            message: 'Esta é uma notificação de teste para conta vencida',
            type: 'warning',
            severity: 'high',
            userId
          });
          break;
        case 'paid':
          await AlertService.createNotification({
            title: 'Teste - Conta Paga',
            message: 'Esta é uma notificação de teste para conta paga',
            type: 'success',
            severity: 'low',
            userId
          });
          break;
        case 'received':
          await AlertService.createNotification({
            title: 'Teste - Valor Recebido',
            message: 'Esta é uma notificação de teste para valor recebido',
            type: 'success',
            severity: 'low',
            userId
          });
          break;
      }

      toast({
        title: 'Teste Enviado',
        description: 'Notificação de teste enviada com sucesso',
        variant: 'default'
      });
    } catch (error) {
      console.error('Erro ao enviar teste:', error);
      toast({
        title: 'Erro',
        description: 'Não foi possível enviar a notificação de teste',
        variant: 'destructive'
      });
    } finally {
      setIsTesting(false);
    }
  };

  if (isLoading) {
    return (
      <Card>
        <CardHeader>
          <div className="flex items-center gap-2">
            <Loader2 className="h-5 w-5 animate-spin" />
            <CardTitle>Carregando configurações...</CardTitle>
          </div>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {[1, 2, 3, 4].map(i => (
              <div key={i} className="h-4 bg-gray-200 rounded animate-pulse"></div>
            ))}
          </div>
        </CardContent>
      </Card>
    );
  }

  if (!preferences) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <AlertTriangle className="h-5 w-5 text-red-500" />
            Erro nas Configurações
          </CardTitle>
        </CardHeader>
        <CardContent>
          <Alert>
            <AlertTriangle className="h-4 w-4" />
            <AlertDescription>
              Não foi possível carregar as configurações de alerta. Tente recarregar a página.
            </AlertDescription>
          </Alert>
        </CardContent>
      </Card>
    );
  }

  return (
    <div className="space-y-6">
      {/* Configurações de Tempo */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Clock className="h-5 w-5" />
            Configurações de Tempo
          </CardTitle>
          <CardDescription>
            Defina quando e com que antecedência receber os alertas
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="days-before-due">Dias antes do vencimento</Label>
              <Select
                value={preferences.days_before_due.toString()}
                onValueChange={(value) => setPreferences(prev => prev ? {...prev, days_before_due: parseInt(value)} : null)}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Selecione os dias" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="1">1 dia antes</SelectItem>
                  <SelectItem value="3">3 dias antes</SelectItem>
                  <SelectItem value="5">5 dias antes</SelectItem>
                  <SelectItem value="7">7 dias antes</SelectItem>
                  <SelectItem value="15">15 dias antes</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label htmlFor="days-before-overdue">Dias antes de considerar vencido</Label>
              <Select
                value={preferences.days_before_overdue.toString()}
                onValueChange={(value) => setPreferences(prev => prev ? {...prev, days_before_overdue: parseInt(value)} : null)}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Selecione os dias" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="0">No mesmo dia</SelectItem>
                  <SelectItem value="1">1 dia depois</SelectItem>
                  <SelectItem value="2">2 dias depois</SelectItem>
                  <SelectItem value="3">3 dias depois</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="quiet-hours-start">Início do horário de silêncio</Label>
              <Input
                id="quiet-hours-start"
                type="time"
                value={preferences.quiet_hours_start}
                onChange={(e) => setPreferences(prev => prev ? {...prev, quiet_hours_start: e.target.value} : null)}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="quiet-hours-end">Fim do horário de silêncio</Label>
              <Input
                id="quiet-hours-end"
                type="time"
                value={preferences.quiet_hours_end}
                onChange={(e) => setPreferences(prev => prev ? {...prev, quiet_hours_end: e.target.value} : null)}
              />
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Tipos de Alerta */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Bell className="h-5 w-5" />
            Tipos de Alerta
          </CardTitle>
          <CardDescription>
            Escolha quais tipos de notificações deseja receber
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-3">
            <div className="flex items-center justify-between">
              <div className="space-y-0.5">
                <Label className="text-base">Contas vencendo em breve</Label>
                <p className="text-sm text-muted-foreground">Alertar quando contas estiverem próximas do vencimento</p>
              </div>
              <Switch
                checked={preferences.alert_due_soon}
                onCheckedChange={(checked) => setPreferences(prev => prev ? {...prev, alert_due_soon: checked} : null)}
              />
            </div>

            <div className="flex items-center justify-between">
              <div className="space-y-0.5">
                <Label className="text-base">Contas vencidas</Label>
                <p className="text-sm text-muted-foreground">Alertar quando contas estiverem vencidas</p>
              </div>
              <Switch
                checked={preferences.alert_overdue}
                onCheckedChange={(checked) => setPreferences(prev => prev ? {...prev, alert_overdue: checked} : null)}
              />
            </div>

            <div className="flex items-center justify-between">
              <div className="space-y-0.5">
                <Label className="text-base">Contas pagas</Label>
                <p className="text-sm text-muted-foreground">Confirmar quando contas forem pagas</p>
              </div>
              <Switch
                checked={preferences.alert_paid}
                onCheckedChange={(checked) => setPreferences(prev => prev ? {...prev, alert_paid: checked} : null)}
              />
            </div>

            <div className="flex items-center justify-between">
              <div className="space-y-0.5">
                <Label className="text-base">Valores recebidos</Label>
                <p className="text-sm text-muted-foreground">Confirmar quando valores forem recebidos</p>
              </div>
              <Switch
                checked={preferences.alert_received}
                onCheckedChange={(checked) => setPreferences(prev => prev ? {...prev, alert_received: checked} : null)}
              />
            </div>

            <div className="flex items-center justify-between">
              <div className="space-y-0.5">
                <Label className="text-base">Alertas de fim de semana</Label>
                <p className="text-sm text-muted-foreground">Receber alertas também nos fins de semana</p>
              </div>
              <Switch
                checked={preferences.weekend_notifications}
                onCheckedChange={(checked) => setPreferences(prev => prev ? {...prev, weekend_notifications: checked} : null)}
              />
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Canais de Notificação */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Monitor className="h-5 w-5" />
            Canais de Notificação
          </CardTitle>
          <CardDescription>
            Escolha como deseja receber as notificações
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-3">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-3">
                <Monitor className="h-5 w-5 text-blue-500" />
                <div>
                  <Label className="text-base">Notificações na plataforma</Label>
                  <p className="text-sm text-muted-foreground">Alertas toast e painel de notificações</p>
                </div>
              </div>
              <Switch
                checked={preferences.toast_notifications}
                onCheckedChange={(checked) => setPreferences(prev => prev ? {...prev, toast_notifications: checked} : null)}
              />
            </div>

            <div className="flex items-center justify-between">
              <div className="flex items-center gap-3">
                <Database className="h-5 w-5 text-green-500" />
                <div>
                  <Label className="text-base">Banco de dados</Label>
                  <p className="text-sm text-muted-foreground">Salvar histórico de notificações</p>
                </div>
              </div>
              <Switch
                checked={preferences.database_notifications}
                onCheckedChange={(checked) => setPreferences(prev => prev ? {...prev, database_notifications: checked} : null)}
              />
            </div>

            <div className="flex items-center justify-between">
              <div className="flex items-center gap-3">
                <Mail className="h-5 w-5 text-purple-500" />
                <div>
                  <Label className="text-base">E-mail</Label>
                  <p className="text-sm text-muted-foreground">Enviar alertas por e-mail (em breve)</p>
                </div>
              </div>
              <Switch
                checked={preferences.email_notifications}
                onCheckedChange={(checked) => setPreferences(prev => prev ? {...prev, email_notifications: checked} : null)}
                disabled
              />
            </div>

            <div className="flex items-center justify-between">
              <div className="flex items-center gap-3">
                <Smartphone className="h-5 w-5 text-orange-500" />
                <div>
                  <Label className="text-base">Push notification</Label>
                  <p className="text-sm text-muted-foreground">Alertas no celular (em breve)</p>
                </div>
              </div>
              <Switch
                checked={preferences.push_notifications}
                onCheckedChange={(checked) => setPreferences(prev => prev ? {...prev, push_notifications: checked} : null)}
                disabled
              />
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Teste de Notificações */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <TestTube className="h-5 w-5" />
            Testar Notificações
          </CardTitle>
          <CardDescription>
            Envie notificações de teste para verificar se estão funcionando corretamente
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
            <Button
              variant="outline"
              onClick={() => testNotification('due-soon')}
              disabled={isTesting || !preferences.toast_notifications}
              className="flex items-center gap-2"
            >
              <Calendar className="h-4 w-4" />
              Vencendo
            </Button>
            <Button
              variant="outline"
              onClick={() => testNotification('overdue')}
              disabled={isTesting || !preferences.toast_notifications}
              className="flex items-center gap-2"
            >
              <AlertTriangle className="h-4 w-4" />
              Vencida
            </Button>
            <Button
              variant="outline"
              onClick={() => testNotification('paid')}
              disabled={isTesting || !preferences.toast_notifications}
              className="flex items-center gap-2"
            >
              <CheckCircle className="h-4 w-4" />
              Paga
            </Button>
            <Button
              variant="outline"
              onClick={() => testNotification('received')}
              disabled={isTesting || !preferences.toast_notifications}
              className="flex items-center gap-2"
            >
              <Volume2 className="h-4 w-4" />
              Recebido
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Botões de Ação */}
      <div className="flex justify-end gap-3">
        <Button
          variant="outline"
          onClick={loadPreferences}
          disabled={isSaving}
        >
          <Settings className="mr-2 h-4 w-4" />
          Restaurar Padrão
        </Button>
        <Button
          onClick={savePreferences}
          disabled={isSaving}
        >
          {isSaving ? (
            <>
              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              Salvando...
            </>
          ) : (
            <>
              <CheckCircle className="mr-2 h-4 w-4" />
              Salvar Configurações
            </>
          )}
        </Button>
      </div>
    </div>
  );
}
