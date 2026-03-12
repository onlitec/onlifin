import { supabase } from '@/db/client';
import type { Notification, AlertPreferences } from '@/types/types';

export interface AlertOptions {
  title: string;
  message: string;
  type: 'alert' | 'info' | 'warning' | 'success';
  severity?: 'low' | 'medium' | 'high';
  userId: string;
  relatedBillId?: string;
  relatedBillToReceiveId?: string;
  relatedTransactionId?: string;
  metadata?: Record<string, any>;
}

export class AlertService {
  // Criar notificação
  static async createNotification(options: AlertOptions): Promise<Notification | null> {
    try {
      // Buscar preferências do usuário
      const preferences = await this.getUserPreferences(options.userId);
      
      // Verificar se deve criar notificação
      if (!this.shouldCreateNotification(options, preferences)) {
        return null;
      }

      const { data, error } = await supabase
        .from('notifications')
        .insert({
          user_id: options.userId,
          title: options.title,
          message: options.message,
          type: options.type,
          severity: options.severity || 'medium',
          related_bill_id: options.relatedBillId,
          related_bill_to_receive_id: options.relatedBillToReceiveId,
          related_transaction_id: options.relatedTransactionId,
          metadata: options.metadata || {}
        })
        .select()
        .single();

      if (error) throw error;

      // Enviar toast se habilitado
      if (preferences.toast_notifications) {
        this.sendToastNotification(options);
      }

      return data;
    } catch (error) {
      console.error('Erro ao criar notificação:', error);
      return null;
    }
  }

  // Buscar preferências do usuário
  static async getUserPreferences(userId: string): Promise<AlertPreferences> {
    try {
      const { data, error } = await supabase
        .from('alert_preferences')
        .select('*')
        .eq('user_id', userId)
        .single();

      if (error || !data) {
        // Retornar preferências padrão
        return {
          id: '',
          user_id: userId,
          days_before_due: 3,
          days_before_overdue: 1,
          alert_due_soon: true,
          alert_overdue: true,
          alert_paid: true,
          alert_received: true,
          toast_notifications: true,
          database_notifications: true,
          email_notifications: false,
          push_notifications: false,
          quiet_hours_start: '22:00:00',
          quiet_hours_end: '08:00:00',
          weekend_notifications: true,
          created_at: new Date().toISOString(),
          updated_at: new Date().toISOString()
        };
      }

      return data;
    } catch (error) {
      console.error('Erro ao buscar preferências:', error);
      // Retornar preferências padrão
      return {
        id: '',
        user_id: userId,
        days_before_due: 3,
        days_before_overdue: 1,
        alert_due_soon: true,
        alert_overdue: true,
        alert_paid: true,
        alert_received: true,
        toast_notifications: true,
        database_notifications: true,
        email_notifications: false,
        push_notifications: false,
        quiet_hours_start: '22:00:00',
        quiet_hours_end: '08:00:00',
        weekend_notifications: true,
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString()
      };
    }
  }

  // Verificar se deve criar notificação
  private static shouldCreateNotification(options: AlertOptions, preferences: AlertPreferences): boolean {
    // Verificar horário de silêncio
    if (this.isQuietHours(preferences)) {
      return false;
    }

    // Verificar fim de semana
    if (!preferences.weekend_notifications && this.isWeekend()) {
      return false;
    }

    // Verificar tipo de alerta
    switch (options.type) {
      case 'alert':
        return preferences.alert_due_soon || preferences.alert_overdue;
      case 'warning':
        return preferences.alert_overdue;
      case 'success':
        return options.message.toLowerCase().includes('paga') ? preferences.alert_paid : preferences.alert_received;
      default:
        return true;
    }
  }

  // Verificar horário de silêncio
  private static isQuietHours(preferences: AlertPreferences): boolean {
    const now = new Date();
    const currentTime = now.toTimeString().slice(0, 5); // HH:MM
    
    const [startHour, startMin] = preferences.quiet_hours_start.split(':').map(Number);
    const [endHour, endMin] = preferences.quiet_hours_end.split(':').map(Number);
    const [currentHour, currentMin] = currentTime.split(':').map(Number);

    const currentMinutes = currentHour * 60 + currentMin;
    const startMinutes = startHour * 60 + startMin;
    const endMinutes = endHour * 60 + endMin;

    // Se o horário de silêncio atravessa a meia-noite
    if (startMinutes > endMinutes) {
      return currentMinutes >= startMinutes || currentMinutes <= endMinutes;
    }

    // Caso normal
    return currentMinutes >= startMinutes && currentMinutes <= endMinutes;
  }

  // Verificar se é fim de semana
  private static isWeekend(): boolean {
    const day = new Date().getDay();
    return day === 0 || day === 6; // Domingo ou Sábado
  }

  // Enviar toast notification
  private static sendToastNotification(options: AlertOptions) {
    // Importar dinamicamente para evitar circular dependency
    import('@/hooks/use-toast').then(({ toast }) => {
      toast({
        title: options.title,
        description: options.message,
        variant: options.type === 'warning' ? 'destructive' : 'default',
        duration: options.severity === 'high' ? 8000 : 5000
      });
    });
  }

  // Alertas específicos para contas a pagar
  static async createBillDueSoonAlert(billId: string, userId: string, description: string, dueDate: string, amount: number): Promise<void> {
    const daysUntilDue = Math.ceil((new Date(dueDate).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24));
    
    await this.createNotification({
      title: 'Conta Vencendo em Breve',
      message: `${description} vence em ${daysUntilDue} dias (R$ ${amount.toFixed(2)})`,
      type: 'alert',
      severity: daysUntilDue <= 1 ? 'high' : daysUntilDue <= 3 ? 'medium' : 'low',
      userId,
      relatedBillId: billId,
      metadata: {
        billId,
        dueDate,
        amount,
        daysUntilDue
      }
    });
  }

  static async createBillOverdueAlert(billId: string, userId: string, description: string, dueDate: string, amount: number): Promise<void> {
    const daysOverdue = Math.ceil((new Date().getTime() - new Date(dueDate).getTime()) / (1000 * 60 * 60 * 24));
    
    await this.createNotification({
      title: 'Conta Vencida',
      message: `${description} está vencida há ${daysOverdue} dias (R$ ${amount.toFixed(2)})`,
      type: 'warning',
      severity: daysOverdue >= 7 ? 'high' : 'medium',
      userId,
      relatedBillId: billId,
      metadata: {
        billId,
        dueDate,
        amount,
        daysOverdue
      }
    });
  }

  static async createBillPaidAlert(billId: string, userId: string, description: string, amount: number): Promise<void> {
    await this.createNotification({
      title: 'Conta Paga',
      message: `${description} foi paga no valor de R$ ${amount.toFixed(2)}`,
      type: 'success',
      severity: 'low',
      userId,
      relatedBillId: billId,
      metadata: {
        billId,
        amount
      }
    });
  }

  // Alertas específicos para contas a receber
  static async createBillToReceiveDueSoonAlert(billId: string, userId: string, description: string, dueDate: string, amount: number): Promise<void> {
    const daysUntilDue = Math.ceil((new Date(dueDate).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24));
    
    await this.createNotification({
      title: 'Recebimento em Breve',
      message: `${description} será recebido em ${daysUntilDue} dias (R$ ${amount.toFixed(2)})`,
      type: 'info',
      severity: 'low',
      userId,
      relatedBillToReceiveId: billId,
      metadata: {
        billId,
        dueDate,
        amount,
        daysUntilDue
      }
    });
  }

  static async createBillToReceiveReceivedAlert(billId: string, userId: string, description: string, amount: number): Promise<void> {
    await this.createNotification({
      title: 'Valor Recebido',
      message: `${description} foi recebido no valor de R$ ${amount.toFixed(2)}`,
      type: 'success',
      severity: 'low',
      userId,
      relatedBillToReceiveId: billId,
      metadata: {
        billId,
        amount
      }
    });
  }

  // Buscar notificações não lidas
  static async getUnreadNotifications(userId: string): Promise<Notification[]> {
    try {
      const { data, error } = await supabase
        .from('notifications')
        .select('*')
        .eq('user_id', userId)
        .eq('is_read', false)
        .order('created_at', { ascending: false });

      if (error) throw error;
      return data || [];
    } catch (error) {
      console.error('Erro ao buscar notificações não lidas:', error);
      return [];
    }
  }

  // Marcar notificação como lida
  static async markAsRead(notificationId: string): Promise<void> {
    try {
      const { error } = await supabase
        .from('notifications')
        .update({ is_read: true })
        .eq('id', notificationId);

      if (error) throw error;
    } catch (error) {
      console.error('Erro ao marcar notificação como lida:', error);
    }
  }

  // Marcar todas como lidas
  static async markAllAsRead(userId: string): Promise<void> {
    try {
      const { error } = await supabase
        .from('notifications')
        .update({ is_read: true })
        .eq('user_id', userId)
        .eq('is_read', false);

      if (error) throw error;
    } catch (error) {
      console.error('Erro ao marcar todas as notificações como lidas:', error);
    }
  }

  // Excluir notificação
  static async deleteNotification(notificationId: string): Promise<void> {
    try {
      const { error } = await supabase
        .from('notifications')
        .delete()
        .eq('id', notificationId);

      if (error) throw error;
    } catch (error) {
      console.error('Erro ao excluir notificação:', error);
    }
  }
}
