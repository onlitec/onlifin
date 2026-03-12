import * as React from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { 
  AlertTriangle, 
  AlertCircle, 
  CheckCircle, 
  Info, 
  X,
  Bell,
  BellOff
} from 'lucide-react';
import { format } from 'date-fns';
import { ptBR } from 'date-fns/locale';
import { cn } from '@/lib/utils';
import type { Notification } from '@/types/types';

interface AlertsPanelProps {
  alerts: Notification[];
  onMarkAsRead?: (alertId: string) => void;
  onMarkAllAsRead?: () => void;
  onDismiss?: (alertId: string) => void;
  isLoading?: boolean;
  maxItems?: number;
}

export function AlertsPanel({ 
  alerts, 
  onMarkAsRead, 
  onMarkAllAsRead, 
  onDismiss,
  isLoading = false,
  maxItems = 5 
}: AlertsPanelProps) {
  const [showAll, setShowAll] = React.useState(false);
  
  const displayAlerts = showAll ? alerts : alerts.slice(0, maxItems);
  const unreadCount = alerts.filter(alert => !alert.is_read).length;

  const getAlertIcon = (type: string, severity?: string) => {
    if (type === 'warning' || severity === 'high') {
      return <AlertTriangle className="h-4 w-4 text-red-500" />;
    }
    if (type === 'alert' || severity === 'medium') {
      return <AlertCircle className="h-4 w-4 text-orange-500" />;
    }
    if (type === 'success') {
      return <CheckCircle className="h-4 w-4 text-green-500" />;
    }
    return <Info className="h-4 w-4 text-blue-500" />;
  };

  const getAlertVariant = (type: string, severity?: string) => {
    if (type === 'warning' || severity === 'high') return 'destructive';
    if (type === 'alert' || severity === 'medium') return 'default';
    if (type === 'success') return 'default';
    return 'default';
  };

  const getSeverityColor = (severity?: string) => {
    switch (severity) {
      case 'high': return 'border-red-200 bg-red-50';
      case 'medium': return 'border-orange-200 bg-orange-50';
      case 'low': return 'border-blue-200 bg-blue-50';
      default: return 'border-gray-200 bg-gray-50';
    }
  };

  const formatTime = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffInMinutes = Math.floor((now.getTime() - date.getTime()) / (1000 * 60));

    if (diffInMinutes < 1) return 'Agora';
    if (diffInMinutes < 60) return `${diffInMinutes} min atrás`;
    
    const diffInHours = Math.floor(diffInMinutes / 60);
    if (diffInHours < 24) return `${diffInHours}h atrás`;
    
    const diffInDays = Math.floor(diffInHours / 24);
    if (diffInDays <= 7) return `${diffInDays}d atrás`;
    
    return format(date, 'dd/MM/yyyy', { locale: ptBR });
  };

  if (isLoading) {
    return (
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <Bell className="h-5 w-5 animate-pulse" />
              <div className="h-5 bg-gray-200 rounded w-24"></div>
            </div>
            <div className="h-6 bg-gray-200 rounded w-16"></div>
          </div>
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            {[1, 2, 3].map(i => (
              <div key={i} className="flex items-start gap-3 p-3 bg-gray-50 rounded-lg animate-pulse">
                <div className="h-4 w-4 bg-gray-200 rounded"></div>
                <div className="flex-1 space-y-2">
                  <div className="h-4 bg-gray-200 rounded w-3/4"></div>
                  <div className="h-3 bg-gray-200 rounded w-1/2"></div>
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card>
      <CardHeader className="pb-3">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <Bell className="h-5 w-5" />
            <CardTitle className="text-lg">Alertas</CardTitle>
            {unreadCount > 0 && (
              <Badge variant="destructive" className="text-xs">
                {unreadCount} nova{unreadCount > 1 ? 's' : ''}
              </Badge>
            )}
          </div>
          {unreadCount > 0 && onMarkAllAsRead && (
            <Button
              variant="ghost"
              size="sm"
              onClick={onMarkAllAsRead}
              className="text-xs"
            >
              Marcar todas como lidas
            </Button>
          )}
        </div>
        <CardDescription>
          Notificações e alertas do sistema
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-3">
        {alerts.length === 0 ? (
          <div className="flex flex-col items-center justify-center py-8 text-center">
            <BellOff className="h-8 w-8 text-gray-400 mb-2" />
            <p className="text-sm text-gray-500">Nenhum alerta no momento</p>
            <p className="text-xs text-gray-400">Você será notificado quando houver novidades</p>
          </div>
        ) : (
          <>
            <ScrollArea className="h-[300px] pr-3">
              <div className="space-y-2">
                {displayAlerts.map((alert) => (
                  <Alert
                    key={alert.id}
                    variant={getAlertVariant(alert.type, alert.severity)}
                    className={cn(
                      "relative transition-all duration-200 hover:shadow-sm",
                      !alert.is_read && "border-l-4 border-l-orange-400",
                      getSeverityColor(alert.severity)
                    )}
                  >
                    <div className="flex items-start gap-3">
                      <div className="mt-0.5">
                        {getAlertIcon(alert.type, alert.severity)}
                      </div>
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center justify-between mb-1">
                          <h4 className="text-sm font-medium truncate">
                            {alert.title}
                          </h4>
                          {!alert.is_read && (
                            <div className="h-2 w-2 bg-orange-400 rounded-full flex-shrink-0"></div>
                          )}
                        </div>
                        <AlertDescription className="text-xs mb-2">
                          {alert.message}
                        </AlertDescription>
                        <div className="flex items-center justify-between">
                          <span className="text-xs text-gray-500">
                            {formatTime(alert.created_at)}
                          </span>
                          <div className="flex items-center gap-1">
                            {alert.severity && (
                              <Badge variant="outline" className="text-xs h-5">
                                {alert.severity === 'high' ? 'Alta' : 
                                 alert.severity === 'medium' ? 'Média' : 'Baixa'}
                              </Badge>
                            )}
                            {!alert.is_read && onMarkAsRead && (
                              <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => onMarkAsRead(alert.id)}
                                className="h-6 px-2 text-xs"
                              >
                                Marcar lida
                              </Button>
                            )}
                            {onDismiss && (
                              <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => onDismiss(alert.id)}
                                className="h-6 px-2 text-xs"
                              >
                                <X className="h-3 w-3" />
                              </Button>
                            )}
                          </div>
                        </div>
                      </div>
                    </div>
                  </Alert>
                ))}
              </div>
            </ScrollArea>

            {alerts.length > maxItems && (
              <div className="pt-2 border-t">
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => setShowAll(!showAll)}
                  className="w-full"
                >
                  {showAll ? 'Mostrar menos' : `Ver todos (${alerts.length})`}
                </Button>
              </div>
            )}
          </>
        )}
      </CardContent>
    </Card>
  );
}
