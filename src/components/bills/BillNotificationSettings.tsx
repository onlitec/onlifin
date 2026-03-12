import * as React from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Bell, BellOff, Settings, Clock, Calendar, AlertTriangle } from 'lucide-react';

export interface BillNotificationSettings {
  notification_mode: 'default' | 'custom' | 'disabled';
  notification_frequency: 'once' | 'daily' | 'weekly' | 'standard';
  custom_days_before: number;
}

interface BillNotificationSettingsProps {
  settings: BillNotificationSettings;
  onChange: (settings: BillNotificationSettings) => void;
  disabled?: boolean;
}

export function BillNotificationSettings({ 
  settings, 
  onChange, 
  disabled = false 
}: BillNotificationSettingsProps) {
  
  const updateSetting = <K extends keyof BillNotificationSettings>(
    key: K, 
    value: BillNotificationSettings[K]
  ) => {
    onChange({
      ...settings,
      [key]: value
    });
  };

  const getModeDescription = (mode: string) => {
    switch (mode) {
      case 'default':
        return 'Usa as configurações globais de alerta';
      case 'custom':
        return 'Configurações personalizadas para esta conta';
      case 'disabled':
        return 'Nenhuma notificação será enviada';
      default:
        return '';
    }
  };

  const getFrequencyDescription = (frequency: string) => {
    switch (frequency) {
      case 'once':
        return 'Apenas uma notificação';
      case 'daily':
        return 'Notificações diárias até o vencimento';
      case 'weekly':
        return 'Notificações semanais';
      case 'standard':
        return 'Frequência padrão do sistema';
      default:
        return '';
    }
  };

  const getModeIcon = (mode: string) => {
    switch (mode) {
      case 'default':
        return <Settings className="h-4 w-4 text-blue-500" />;
      case 'custom':
        return <Bell className="h-4 w-4 text-green-500" />;
      case 'disabled':
        return <BellOff className="h-4 w-4 text-gray-500" />;
      default:
        return <Bell className="h-4 w-4" />;
    }
  };

  const getFrequencyIcon = (frequency: string) => {
    switch (frequency) {
      case 'once':
        return <Calendar className="h-4 w-4 text-purple-500" />;
      case 'daily':
        return <Clock className="h-4 w-4 text-orange-500" />;
      case 'weekly':
        return <Calendar className="h-4 w-4 text-blue-500" />;
      case 'standard':
        return <Settings className="h-4 w-4 text-gray-500" />;
      default:
        return <Clock className="h-4 w-4" />;
    }
  };

  const isCustomMode = settings.notification_mode === 'custom';
  const isDisabled = settings.notification_mode === 'disabled';

  return (
    <Card className={`${disabled ? 'opacity-50' : ''}`}>
      <CardHeader className="pb-3">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            {getModeIcon(settings.notification_mode)}
            <CardTitle className="text-sm">Configurações de Notificação</CardTitle>
          </div>
          {isDisabled && (
            <Badge variant="secondary" className="text-xs">
              Desabilitado
            </Badge>
          )}
        </div>
        <CardDescription className="text-xs">
          {getModeDescription(settings.notification_mode)}
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        {/* Modo de Notificação */}
        <div className="space-y-2">
          <Label className="text-xs font-medium">Modo de Notificação</Label>
          <Select
            value={settings.notification_mode}
            onValueChange={(value: 'default' | 'custom' | 'disabled') => 
              updateSetting('notification_mode', value)
            }
            disabled={disabled}
          >
            <SelectTrigger className="h-8">
              <SelectValue placeholder="Selecione o modo" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="default">
                <div className="flex items-center gap-2">
                  <Settings className="h-4 w-4" />
                  <span>Padrão do Sistema</span>
                </div>
              </SelectItem>
              <SelectItem value="custom">
                <div className="flex items-center gap-2">
                  <Bell className="h-4 w-4" />
                  <span>Personalizado</span>
                </div>
              </SelectItem>
              <SelectItem value="disabled">
                <div className="flex items-center gap-2">
                  <BellOff className="h-4 w-4" />
                  <span>Desabilitado</span>
                </div>
              </SelectItem>
            </SelectContent>
          </Select>
        </div>

        {/* Configurações Personalizadas */}
        {isCustomMode && (
          <div className="space-y-3 p-3 bg-blue-50 rounded-lg border border-blue-200">
            <div className="flex items-center gap-2 text-sm font-medium text-blue-900">
              <AlertTriangle className="h-4 w-4" />
              Configurações Personalizadas
            </div>

            {/* Frequência */}
            <div className="space-y-2">
              <Label className="text-xs">Frequência</Label>
              <Select
                value={settings.notification_frequency}
                onValueChange={(value: 'once' | 'daily' | 'weekly' | 'standard') => 
                  updateSetting('notification_frequency', value)
                }
                disabled={disabled}
              >
                <SelectTrigger className="h-8">
                  <SelectValue placeholder="Selecione a frequência" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="once">
                    <div className="flex items-center gap-2">
                      <Calendar className="h-4 w-4" />
                      <span>Uma vez</span>
                    </div>
                  </SelectItem>
                  <SelectItem value="daily">
                    <div className="flex items-center gap-2">
                      <Clock className="h-4 w-4" />
                      <span>Diário</span>
                    </div>
                  </SelectItem>
                  <SelectItem value="weekly">
                    <div className="flex items-center gap-2">
                      <Calendar className="h-4 w-4" />
                      <span>Semanal</span>
                    </div>
                  </SelectItem>
                  <SelectItem value="standard">
                    <div className="flex items-center gap-2">
                      <Settings className="h-4 w-4" />
                      <span>Padrão</span>
                    </div>
                  </SelectItem>
                </SelectContent>
              </Select>
              <p className="text-xs text-muted-foreground">
                {getFrequencyDescription(settings.notification_frequency)}
              </p>
            </div>

            {/* Dias Antes do Vencimento */}
            <div className="space-y-2">
              <Label className="text-xs">Dias antes do vencimento</Label>
              <div className="flex items-center gap-2">
                <Input
                  type="number"
                  min="1"
                  max="30"
                  value={settings.custom_days_before}
                  onChange={(e) => updateSetting('custom_days_before', parseInt(e.target.value) || 3)}
                  className="h-8 w-20"
                  disabled={disabled}
                />
                <span className="text-xs text-muted-foreground">dias antes</span>
              </div>
              <p className="text-xs text-muted-foreground">
                Quantos dias antes do vencimento enviar o primeiro alerta
              </p>
            </div>
          </div>
        )}

        {/* Resumo */}
        <div className="flex items-center justify-between p-2 bg-gray-50 rounded text-xs">
          <div className="flex items-center gap-2">
            {getFrequencyIcon(settings.notification_frequency)}
            <span className="text-muted-foreground">
              {isDisabled 
                ? 'Notificações desabilitadas' 
                : isCustomMode 
                  ? `Personalizado: ${settings.custom_days_before} dias antes`
                  : 'Usando configurações globais'
              }
            </span>
          </div>
          {settings.notification_frequency !== 'standard' && (
            <Badge variant="outline" className="text-xs h-5">
              {settings.notification_frequency === 'once' ? 'Única' :
               settings.notification_frequency === 'daily' ? 'Diária' :
               settings.notification_frequency === 'weekly' ? 'Semanal' : 'Padrão'}
            </Badge>
          )}
        </div>
      </CardContent>
    </Card>
  );
}
