import {
  alertPreferencesApi,
  notificationQueueApi,
  notificationSettingsApi,
  notificationTemplatesApi,
  notificationsApi,
  profilesApi
} from '@/db/api';
import type {
  AlertPreferences,
  Notification,
  NotificationSettings,
  NotificationTemplate,
  NotificationType
} from '@/types/types';

type NotificationSeverity = 'low' | 'medium' | 'high';
type NotificationChannel = 'toast' | 'email' | 'whatsapp';
type NotificationEventKey =
  | 'bill_due_soon'
  | 'bill_overdue'
  | 'bill_paid'
  | 'bill_to_receive_due_soon'
  | 'bill_to_receive_received'
  | 'system_critical'
  | 'custom';

const DEFAULT_SETTINGS: NotificationSettings = {
  id: 'global',
  settings_key: 'global',
  is_active: true,
  toast_enabled: true,
  database_enabled: true,
  email_enabled: false,
  whatsapp_enabled: false,
  allow_user_channel_overrides: true,
  days_before_due_default: 3,
  days_before_overdue_default: 1,
  quiet_hours_start_default: '22:00:00',
  quiet_hours_end_default: '08:00:00',
  weekend_notifications_default: true,
  alert_due_soon_default: true,
  alert_overdue_default: true,
  alert_paid_default: true,
  alert_received_default: true,
  system_critical_default: true,
  email_from_name: 'OnliFin',
  email_from_address: null,
  email_test_destination: null,
  whatsapp_test_destination: null,
  created_at: new Date().toISOString(),
  updated_at: new Date().toISOString()
};

const DEFAULT_TEMPLATE_MAP: Record<
  NotificationEventKey,
  Record<NotificationChannel, Omit<NotificationTemplate, 'id' | 'created_at' | 'updated_at' | 'event_key' | 'channel'>>
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

export interface AlertOptions {
  userId: string;
  title: string;
  message: string;
  type: NotificationType;
  severity?: NotificationSeverity;
  eventKey?: NotificationEventKey;
  actionUrl?: string;
  personId?: string | null;
  relatedBillId?: string;
  relatedBillToReceiveId?: string;
  relatedTransactionId?: string;
  relatedForecastId?: string;
  metadata?: Record<string, unknown>;
  overrideChannels?: Partial<Record<NotificationChannel, boolean>>;
  destinations?: Partial<Record<'email' | 'whatsapp', string>>;
  ignoreUserChannelPreferences?: boolean;
  ignoreUserEventPreferences?: boolean;
  ignoreGlobalSystemState?: boolean;
}

function normalizeMoney(value: number): string {
  return value.toFixed(2).replace('.', ',');
}

function renderTemplate(template: string | null | undefined, payload: Record<string, unknown>): string {
  if (!template) return '';

  return template.replace(/\{\{\s*([\w.]+)\s*\}\}/g, (_, key: string) => {
    const value = payload[key];
    if (value === undefined || value === null) return '';
    return String(value);
  });
}

function getDefaultPreferences(userId: string, settings: NotificationSettings): AlertPreferences {
  return {
    id: '',
    user_id: userId,
    days_before_due: settings.days_before_due_default,
    days_before_overdue: settings.days_before_overdue_default,
    alert_due_soon: settings.alert_due_soon_default,
    alert_overdue: settings.alert_overdue_default,
    alert_paid: settings.alert_paid_default,
    alert_received: settings.alert_received_default,
    system_critical_notifications: settings.system_critical_default,
    toast_notifications: true,
    database_notifications: true,
    email_notifications: false,
    whatsapp_notifications: false,
    push_notifications: false,
    quiet_hours_start: settings.quiet_hours_start_default,
    quiet_hours_end: settings.quiet_hours_end_default,
    weekend_notifications: settings.weekend_notifications_default,
    created_at: new Date().toISOString(),
    updated_at: new Date().toISOString()
  };
}

export class AlertService {
  static async getGlobalSettings(): Promise<NotificationSettings> {
    try {
      const settings = await notificationSettingsApi.getGlobal();
      return settings || DEFAULT_SETTINGS;
    } catch (error) {
      console.error('Erro ao carregar configuracoes globais de notificacao:', error);
      return DEFAULT_SETTINGS;
    }
  }

  static async getUserPreferences(userId: string): Promise<AlertPreferences> {
    const settings = await this.getGlobalSettings();

    try {
      const preferences = await alertPreferencesApi.getPreferences(userId);
      if (preferences) {
        return preferences;
      }

      const created = await alertPreferencesApi.createDefaultPreferences(userId);
      return created || getDefaultPreferences(userId, settings);
    } catch (error) {
      console.error('Erro ao buscar preferencias de notificacao:', error);
      return getDefaultPreferences(userId, settings);
    }
  }

  static isQuietHours(preferences: Pick<AlertPreferences, 'quiet_hours_start' | 'quiet_hours_end'>): boolean {
    const now = new Date();
    const currentTime = now.toTimeString().slice(0, 5);
    const [startHour, startMinute] = preferences.quiet_hours_start.split(':').map(Number);
    const [endHour, endMinute] = preferences.quiet_hours_end.split(':').map(Number);
    const [currentHour, currentMinute] = currentTime.split(':').map(Number);

    const currentTotal = currentHour * 60 + currentMinute;
    const startTotal = startHour * 60 + startMinute;
    const endTotal = endHour * 60 + endMinute;

    if (startTotal > endTotal) {
      return currentTotal >= startTotal || currentTotal <= endTotal;
    }

    return currentTotal >= startTotal && currentTotal <= endTotal;
  }

  static isWeekend(): boolean {
    const day = new Date().getDay();
    return day === 0 || day === 6;
  }

  private static isEventEnabled(eventKey: NotificationEventKey, preferences: AlertPreferences): boolean {
    switch (eventKey) {
      case 'bill_due_soon':
        return preferences.alert_due_soon;
      case 'bill_overdue':
        return preferences.alert_overdue;
      case 'bill_paid':
        return preferences.alert_paid;
      case 'bill_to_receive_due_soon':
      case 'bill_to_receive_received':
        return preferences.alert_received;
      case 'system_critical':
        return preferences.system_critical_notifications;
      default:
        return true;
    }
  }

  private static canDeliverExternal(preferences: AlertPreferences): boolean {
    if (this.isQuietHours(preferences)) {
      return false;
    }

    if (!preferences.weekend_notifications && this.isWeekend()) {
      return false;
    }

    return true;
  }

  private static async getTemplate(
    eventKey: NotificationEventKey,
    channel: NotificationChannel
  ): Promise<NotificationTemplate | null> {
    try {
      const templates = await notificationTemplatesApi.getAll();
      const template = templates.find((item) => item.event_key === eventKey && item.channel === channel && item.is_active);
      if (template) {
        return template;
      }
    } catch (error) {
      console.error('Erro ao buscar template de notificacao:', error);
    }

    const fallback = DEFAULT_TEMPLATE_MAP[eventKey]?.[channel] || DEFAULT_TEMPLATE_MAP.custom[channel];

    return {
      id: `default-${eventKey}-${channel}`,
      event_key: eventKey,
      channel,
      title_template: fallback.title_template,
      subject_template: fallback.subject_template,
      body_template: fallback.body_template,
      is_active: true,
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString()
    };
  }

  private static async queueExternalDelivery(
    notificationId: string | null,
    userId: string,
    channel: 'email' | 'whatsapp',
    destination: string,
    template: NotificationTemplate,
    payload: Record<string, unknown>
  ) {
    const subject = renderTemplate(template.subject_template, payload) || null;
    const content = renderTemplate(template.body_template, payload);

    if (!content.trim()) {
      return;
    }

    await notificationQueueApi.enqueue({
      notification_id: notificationId,
      user_id: userId,
      channel,
      destination,
      subject,
      content,
      template_id: template.id,
      payload,
      status: 'pending',
      max_attempts: 5,
      next_attempt_at: new Date().toISOString(),
      last_error: null,
      provider_response: {}
    });
  }

  private static async sendToastNotification(options: AlertOptions, payload: Record<string, unknown>) {
    if (typeof window === 'undefined') {
      return;
    }

    const template = await this.getTemplate(options.eventKey || 'custom', 'toast');
    if (!template) {
      return;
    }

    const title = renderTemplate(template.title_template, payload) || options.title;
    const description = renderTemplate(template.body_template, payload) || options.message;

    const { toast } = await import('@/hooks/use-toast');
    toast({
      title,
      description,
      variant: options.type === 'warning' ? 'destructive' : 'default',
      duration: options.severity === 'high' ? 8000 : 5000
    });
  }

  static async createNotification(options: AlertOptions): Promise<Notification | null> {
    try {
      const eventKey = options.eventKey || 'custom';
      const [settings, preferences, profile] = await Promise.all([
        this.getGlobalSettings(),
        this.getUserPreferences(options.userId),
        profilesApi.getProfile(options.userId)
      ]);

      if (!settings.is_active && !options.ignoreGlobalSystemState) {
        return null;
      }

      if (!options.ignoreUserEventPreferences && !this.isEventEnabled(eventKey, preferences)) {
        return null;
      }

      const basePayload: Record<string, unknown> = {
        title: options.title,
        message: options.message,
        ...(options.metadata || {})
      };

      const toastTemplate = await this.getTemplate(eventKey, 'toast');
      const renderedTitle = renderTemplate(toastTemplate?.title_template, basePayload) || options.title;
      const renderedMessage = renderTemplate(toastTemplate?.body_template, basePayload) || options.message;

      const notification = settings.database_enabled && preferences.database_notifications
        ? await notificationsApi.create({
            user_id: options.userId,
            title: renderedTitle,
            message: renderedMessage,
            event_key: eventKey,
            type: options.type,
            severity: options.severity || 'medium',
            is_read: false,
            related_forecast_id: options.relatedForecastId || null,
            related_bill_id: options.relatedBillId || null,
            related_bill_to_receive_id: options.relatedBillToReceiveId || null,
            related_transaction_id: options.relatedTransactionId || null,
            action_url: options.actionUrl || null,
            person_id: options.personId || null,
            metadata: options.metadata || {}
          })
        : null;

      const canDeliverNow = this.canDeliverExternal(preferences);
      const channelOverride = options.overrideChannels || {};
      const canUseEmail = options.ignoreUserChannelPreferences ? true : preferences.email_notifications;
      const canUseWhatsapp = options.ignoreUserChannelPreferences ? true : preferences.whatsapp_notifications;
      const canUseToast = options.ignoreUserChannelPreferences ? true : preferences.toast_notifications;

      if ((channelOverride.toast ?? true) && settings.toast_enabled && canUseToast && canDeliverNow) {
        await this.sendToastNotification(options, basePayload);
      }

      const emailDestination = options.destinations?.email || profile?.email || undefined;
      const whatsappDestination = options.destinations?.whatsapp || profile?.whatsapp || undefined;

      if ((channelOverride.email ?? true) && settings.email_enabled && canUseEmail && canDeliverNow && emailDestination) {
        const template = await this.getTemplate(eventKey, 'email');
        if (template) {
          await this.queueExternalDelivery(notification?.id || null, options.userId, 'email', emailDestination, template, basePayload);
        }
      }

      if ((channelOverride.whatsapp ?? true) && settings.whatsapp_enabled && canUseWhatsapp && canDeliverNow && whatsappDestination) {
        const template = await this.getTemplate(eventKey, 'whatsapp');
        if (template) {
          await this.queueExternalDelivery(notification?.id || null, options.userId, 'whatsapp', whatsappDestination, template, basePayload);
        }
      }

      return notification;
    } catch (error) {
      console.error('Erro ao criar notificacao:', error);
      return null;
    }
  }

  static async createBillDueSoonAlert(billId: string, userId: string, description: string, dueDate: string, amount: number): Promise<void> {
    const daysUntilDue = Math.ceil((new Date(dueDate).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24));

    await this.createNotification({
      userId,
      title: 'Conta vencendo em breve',
      message: `${description} vence em ${daysUntilDue} dia(s) no valor de R$ ${normalizeMoney(amount)}.`,
      type: 'alert',
      severity: daysUntilDue <= 1 ? 'high' : daysUntilDue <= 3 ? 'medium' : 'low',
      eventKey: 'bill_due_soon',
      actionUrl: '/pf/bills-to-pay',
      relatedBillId: billId,
      metadata: {
        description,
        amount: normalizeMoney(amount),
        due_date: dueDate,
        days_until_due: daysUntilDue,
        bill_id: billId
      }
    });
  }

  static async createBillOverdueAlert(billId: string, userId: string, description: string, dueDate: string, amount: number): Promise<void> {
    const daysOverdue = Math.ceil((new Date().getTime() - new Date(dueDate).getTime()) / (1000 * 60 * 60 * 24));

    await this.createNotification({
      userId,
      title: 'Conta vencida',
      message: `${description} está vencida há ${daysOverdue} dia(s) no valor de R$ ${normalizeMoney(amount)}.`,
      type: 'warning',
      severity: daysOverdue >= 7 ? 'high' : 'medium',
      eventKey: 'bill_overdue',
      actionUrl: '/pf/bills-to-pay',
      relatedBillId: billId,
      metadata: {
        description,
        amount: normalizeMoney(amount),
        due_date: dueDate,
        days_overdue: daysOverdue,
        bill_id: billId
      }
    });
  }

  static async createBillPaidAlert(billId: string, userId: string, description: string, amount: number): Promise<void> {
    await this.createNotification({
      userId,
      title: 'Conta paga',
      message: `${description} foi paga no valor de R$ ${normalizeMoney(amount)}.`,
      type: 'success',
      severity: 'low',
      eventKey: 'bill_paid',
      actionUrl: '/pf/bills-to-pay',
      relatedBillId: billId,
      metadata: {
        description,
        amount: normalizeMoney(amount),
        bill_id: billId
      }
    });
  }

  static async createBillToReceiveDueSoonAlert(billId: string, userId: string, description: string, dueDate: string, amount: number): Promise<void> {
    const daysUntilDue = Math.ceil((new Date(dueDate).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24));

    await this.createNotification({
      userId,
      title: 'Recebimento em breve',
      message: `${description} será recebido em ${daysUntilDue} dia(s) no valor de R$ ${normalizeMoney(amount)}.`,
      type: 'info',
      severity: 'low',
      eventKey: 'bill_to_receive_due_soon',
      actionUrl: '/pf/bills-to-receive',
      relatedBillToReceiveId: billId,
      metadata: {
        description,
        amount: normalizeMoney(amount),
        due_date: dueDate,
        days_until_due: daysUntilDue,
        bill_id: billId
      }
    });
  }

  static async createBillToReceiveReceivedAlert(billId: string, userId: string, description: string, amount: number): Promise<void> {
    await this.createNotification({
      userId,
      title: 'Valor recebido',
      message: `${description} foi recebido no valor de R$ ${normalizeMoney(amount)}.`,
      type: 'success',
      severity: 'low',
      eventKey: 'bill_to_receive_received',
      actionUrl: '/pf/bills-to-receive',
      relatedBillToReceiveId: billId,
      metadata: {
        description,
        amount: normalizeMoney(amount),
        bill_id: billId
      }
    });
  }

  static async createSystemCriticalAlert(userId: string, title: string, message: string, metadata?: Record<string, unknown>): Promise<void> {
    await this.createNotification({
      userId,
      title,
      message,
      type: 'warning',
      severity: 'high',
      eventKey: 'system_critical',
      metadata
    });
  }

  static async getUnreadNotifications(userId: string): Promise<Notification[]> {
    return notificationsApi.getUnread(userId);
  }

  static async markAsRead(notificationId: string): Promise<void> {
    await notificationsApi.markAsRead(notificationId);
  }

  static async markAllAsRead(userId: string): Promise<void> {
    await notificationsApi.markAllAsRead(userId);
  }

  static async deleteNotification(notificationId: string): Promise<void> {
    await notificationsApi.delete(notificationId);
  }
}
