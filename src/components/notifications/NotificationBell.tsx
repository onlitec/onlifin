import * as React from 'react';
import { useNavigate } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Badge } from '@/components/ui/badge';
import { notificationsApi } from '@/db/api';
import { AlertService } from '@/services/alertService';
import type { Notification } from '@/types/types';
import { useToast } from '@/hooks/use-toast';
import { Bell, CheckCheck, ExternalLink, Loader2, Trash2 } from 'lucide-react';

interface NotificationBellProps {
  userId: string;
}

export function NotificationBell({ userId }: NotificationBellProps) {
  const navigate = useNavigate();
  const { toast } = useToast();
  const [notifications, setNotifications] = React.useState<Notification[]>([]);
  const [loading, setLoading] = React.useState(true);
  const globalSettingsRef = React.useRef<Awaited<ReturnType<typeof AlertService.getGlobalSettings>> | null>(null);
  const seenIdsRef = React.useRef<Set<string>>(new Set());
  const initializedRef = React.useRef(false);

  const loadNotifications = React.useCallback(async () => {
    try {
      const items = await notificationsApi.getAll(userId, 10);
      setNotifications(items);

      if (!initializedRef.current) {
        seenIdsRef.current = new Set(items.map((item) => item.id));
        initializedRef.current = true;
        return;
      }

      const [preferences, globalSettings] = await Promise.all([
        AlertService.getUserPreferences(userId),
        globalSettingsRef.current ? Promise.resolve(globalSettingsRef.current) : AlertService.getGlobalSettings()
      ]);
      globalSettingsRef.current = globalSettings;

      const canToast = globalSettings.toast_enabled
        && preferences.toast_notifications
        && !AlertService.isQuietHours(preferences)
        && (preferences.weekend_notifications || !AlertService.isWeekend());

      if (!canToast) {
        items.forEach((item) => seenIdsRef.current.add(item.id));
        return;
      }

      const newUnreadItems = items.filter((item) => !item.is_read && !seenIdsRef.current.has(item.id));
      newUnreadItems.forEach((item) => {
        toast({
          title: item.title,
          description: item.message
        });
        seenIdsRef.current.add(item.id);
      });
    } catch (error) {
      console.error('Erro ao carregar notificacoes:', error);
    } finally {
      setLoading(false);
    }
  }, [toast, userId]);

  React.useEffect(() => {
    void loadNotifications();
    const intervalId = window.setInterval(() => {
      void loadNotifications();
    }, 30000);

    return () => {
      window.clearInterval(intervalId);
    };
  }, [loadNotifications]);

  const unreadCount = notifications.filter((item) => !item.is_read).length;

  const handleOpen = async (notification: Notification) => {
    try {
      if (!notification.is_read) {
        await notificationsApi.markAsRead(notification.id);
      }

      if (notification.action_url) {
        navigate(notification.action_url);
      }

      await loadNotifications();
    } catch (error) {
      console.error(error);
    }
  };

  const handleDelete = async (notificationId: string) => {
    try {
      await notificationsApi.delete(notificationId);
      await loadNotifications();
    } catch (error) {
      console.error(error);
    }
  };

  const handleMarkAll = async () => {
    try {
      await notificationsApi.markAllAsRead(userId);
      await loadNotifications();
    } catch (error) {
      console.error(error);
    }
  };

  return (
    <Popover>
      <PopoverTrigger asChild>
        <Button variant="ghost" size="icon" className="text-slate-500 rounded-xl relative">
          <Bell className="h-5 w-5" />
          {unreadCount > 0 && (
            <span className="absolute top-2 right-2 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-black text-white">
              {unreadCount > 9 ? '9+' : unreadCount}
            </span>
          )}
        </Button>
      </PopoverTrigger>
      <PopoverContent align="end" className="w-[420px] p-0 overflow-hidden">
        <div className="flex items-center justify-between border-b px-4 py-3">
          <div>
            <p className="font-bold text-slate-900">Notificações</p>
            <p className="text-xs text-muted-foreground">Últimos eventos e alertas do sistema</p>
          </div>
          {unreadCount > 0 && (
            <Button variant="ghost" size="sm" onClick={() => void handleMarkAll()}>
              <CheckCheck className="mr-2 h-4 w-4" />
              Ler todas
            </Button>
          )}
        </div>

        <ScrollArea className="max-h-[420px]">
          {loading ? (
            <div className="flex items-center justify-center gap-2 px-4 py-6 text-sm text-muted-foreground">
              <Loader2 className="h-4 w-4 animate-spin" />
              Carregando notificações...
            </div>
          ) : notifications.length === 0 ? (
            <div className="px-4 py-8 text-center text-sm text-muted-foreground">
              Nenhuma notificação no momento.
            </div>
          ) : (
            <div className="divide-y">
              {notifications.map((notification) => (
                <div
                  key={notification.id}
                  className={`px-4 py-3 transition-colors ${notification.is_read ? 'bg-white' : 'bg-blue-50/40'}`}
                >
                  <div className="flex items-start justify-between gap-3">
                    <button
                      type="button"
                      onClick={() => void handleOpen(notification)}
                      className="flex-1 text-left"
                    >
                      <div className="mb-1 flex items-center gap-2">
                        <p className="font-semibold text-slate-900">{notification.title}</p>
                        {!notification.is_read && <Badge className="h-5">Nova</Badge>}
                        {notification.action_url && <ExternalLink className="h-3.5 w-3.5 text-slate-400" />}
                      </div>
                      <p className="text-sm text-slate-600">{notification.message}</p>
                      <p className="mt-1 text-[11px] text-slate-400">
                        {new Date(notification.created_at).toLocaleString('pt-BR')}
                      </p>
                    </button>

                    <Button
                      type="button"
                      variant="ghost"
                      size="icon"
                      className="h-8 w-8 shrink-0"
                      onClick={() => void handleDelete(notification.id)}
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </ScrollArea>
      </PopoverContent>
    </Popover>
  );
}
