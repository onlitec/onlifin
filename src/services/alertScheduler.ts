import { supabase } from '@/db/client';
import { AlertService } from './alertService';
import type { BillToPay, BillToReceive } from '@/types/types';

export class AlertScheduler {
  // Verificar contas a pagar próximas do vencimento
  static async checkBillsToPayDueSoon(): Promise<void> {
    try {
      const today = new Date();
      const threeDaysFromNow = new Date(today);
      threeDaysFromNow.setDate(today.getDate() + 3);

      const { data: bills, error } = await supabase
        .from('bills_to_pay')
        .select('*')
        .eq('status', 'pending')
        .lte('due_date', threeDaysFromNow.toISOString().split('T')[0])
        .gte('due_date', today.toISOString().split('T')[0]);

      if (error) throw error;
      if (!bills) return;

      for (const bill of bills) {
        const daysUntilDue = Math.ceil((new Date(bill.due_date).getTime() - today.getTime()) / (1000 * 60 * 60 * 24));
        
        // Verificar se já existe alerta para esta conta hoje
        const hasAlertToday = await this.hasAlertToday(bill.id, bill.user_id);
        
        if (!hasAlertToday) {
          if (daysUntilDue === 0) {
            // Vence hoje
            await AlertService.createBillDueSoonAlert(bill.id, bill.user_id, bill.description, bill.due_date, bill.amount);
          } else if (daysUntilDue <= 3) {
            // Vence em até 3 dias
            await AlertService.createBillDueSoonAlert(bill.id, bill.user_id, bill.description, bill.due_date, bill.amount);
          }
        }
      }
    } catch (error) {
      console.error('Erro ao verificar contas a pagar vencendo em breve:', error);
    }
  }

  // Verificar contas a pagar vencidas
  static async checkOverdueBillsToPay(): Promise<void> {
    try {
      const today = new Date().toISOString().split('T')[0];

      const { data: bills, error } = await supabase
        .from('bills_to_pay')
        .select('*')
        .eq('status', 'pending')
        .lt('due_date', today);

      if (error) throw error;
      if (!bills) return;

      for (const bill of bills) {
        // Verificar se já existe alerta de vencido hoje
        const hasOverdueAlertToday = await this.hasOverdueAlertToday(bill.id, bill.user_id);
        
        if (!hasOverdueAlertToday) {
          await AlertService.createBillOverdueAlert(bill.id, bill.user_id, bill.description, bill.due_date, bill.amount);
        }
      }
    } catch (error) {
      console.error('Erro ao verificar contas a pagar vencidas:', error);
    }
  }

  // Verificar contas a receber próximas do vencimento
  static async checkBillsToReceiveDueSoon(): Promise<void> {
    try {
      const today = new Date();
      const threeDaysFromNow = new Date(today);
      threeDaysFromNow.setDate(today.getDate() + 3);

      const { data: bills, error } = await supabase
        .from('bills_to_receive')
        .select('*')
        .eq('status', 'pending')
        .lte('due_date', threeDaysFromNow.toISOString().split('T')[0])
        .gte('due_date', today.toISOString().split('T')[0]);

      if (error) throw error;
      if (!bills) return;

      for (const bill of bills) {
        const daysUntilDue = Math.ceil((new Date(bill.due_date).getTime() - today.getTime()) / (1000 * 60 * 60 * 24));
        
        // Verificar se já existe alerta para esta conta hoje
        const hasAlertToday = await this.hasAlertToday(bill.id, bill.user_id, true);
        
        if (!hasAlertToday && daysUntilDue <= 3) {
          await AlertService.createBillToReceiveDueSoonAlert(bill.id, bill.user_id, bill.description, bill.due_date, bill.amount);
        }
      }
    } catch (error) {
      console.error('Erro ao verificar contas a receber vencendo em breve:', error);
    }
  }

  // Verificar se já existe alerta hoje para esta conta
  private static async hasAlertToday(billId: string, userId: string, isReceive = false): Promise<boolean> {
    try {
      const today = new Date().toISOString().split('T')[0];
      
      const { data, error } = await supabase
        .from('notifications')
        .select('*')
        .eq('user_id', userId)
        .eq(isReceive ? 'related_bill_to_receive_id' : 'related_bill_id', billId)
        .gte('created_at', `${today}T00:00:00.000Z`)
        .lte('created_at', `${today}T23:59:59.999Z`);

      if (error) throw error;
      return (data && data.length > 0) || false;
    } catch (error) {
      console.error('Erro ao verificar alerta existente:', error);
      return false;
    }
  }

  // Verificar se já existe alerta de vencido hoje
  private static async hasOverdueAlertToday(billId: string, userId: string): Promise<boolean> {
    try {
      const today = new Date().toISOString().split('T')[0];
      
      const { data, error } = await supabase
        .from('notifications')
        .select('*')
        .eq('user_id', userId)
        .eq('related_bill_id', billId)
        .eq('type', 'warning')
        .gte('created_at', `${today}T00:00:00.000Z`)
        .lte('created_at', `${today}T23:59:59.999Z`);

      if (error) throw error;
      return (data && data.length > 0) || false;
    } catch (error) {
      console.error('Erro ao verificar alerta de vencido existente:', error);
      return false;
    }
  }

  // Executar todas as verificações
  static async runAllChecks(): Promise<void> {
    console.log('Iniciando verificação de alertas...');
    
    await Promise.all([
      this.checkBillsToPayDueSoon(),
      this.checkOverdueBillsToPay(),
      this.checkBillsToReceiveDueSoon()
    ]);
    
    console.log('Verificação de alertas concluída.');
  }

  // Agendar execução diária (para ser chamado por um job scheduler)
  static scheduleDailyCheck(): void {
    // Executar a cada hora durante o dia
    const scheduleNextRun = () => {
      const now = new Date();
      const nextHour = new Date(now);
      nextHour.setHours(now.getHours() + 1, 0, 0, 0);
      
      const delay = nextHour.getTime() - now.getTime();
      
      setTimeout(async () => {
        await this.runAllChecks();
        scheduleNextRun(); // Agendar próxima execução
      }, delay);
    };

    // Iniciar agendamento
    scheduleNextRun();
  }

  // Verificar contas pagas recentemente (para alertas de confirmação)
  static async checkRecentlyPaidBills(): Promise<void> {
    try {
      const oneHourAgo = new Date(Date.now() - 60 * 60 * 1000).toISOString();

      const { data: bills, error } = await supabase
        .from('bills_to_pay')
        .select('*')
        .eq('status', 'paid')
        .gte('paid_date', oneHourAgo);

      if (error) throw error;
      if (!bills) return;

      for (const bill of bills) {
        // Verificar se já existe alerta de pagamento
        const hasPaidAlert = await this.hasPaidAlertToday(bill.id, bill.user_id);
        
        if (!hasPaidAlert) {
          await AlertService.createBillPaidAlert(bill.id, bill.user_id, bill.description, bill.amount);
        }
      }
    } catch (error) {
      console.error('Erro ao verificar contas pagas recentemente:', error);
    }
  }

  // Verificar contas recebidas recentemente
  static async checkRecentlyReceivedBills(): Promise<void> {
    try {
      const oneHourAgo = new Date(Date.now() - 60 * 60 * 1000).toISOString();

      const { data: bills, error } = await supabase
        .from('bills_to_receive')
        .select('*')
        .eq('status', 'received')
        .gte('received_date', oneHourAgo);

      if (error) throw error;
      if (!bills) return;

      for (const bill of bills) {
        // Verificar se já existe alerta de recebimento
        const hasReceivedAlert = await this.hasReceivedAlertToday(bill.id, bill.user_id);
        
        if (!hasReceivedAlert) {
          await AlertService.createBillToReceiveReceivedAlert(bill.id, bill.user_id, bill.description, bill.amount);
        }
      }
    } catch (error) {
      console.error('Erro ao verificar contas recebidas recentemente:', error);
    }
  }

  // Verificar se já existe alerta de pagamento hoje
  private static async hasPaidAlertToday(billId: string, userId: string): Promise<boolean> {
    try {
      const today = new Date().toISOString().split('T')[0];
      
      const { data, error } = await supabase
        .from('notifications')
        .select('*')
        .eq('user_id', userId)
        .eq('related_bill_id', billId)
        .eq('type', 'success')
        .like('message', '%paga%')
        .gte('created_at', `${today}T00:00:00.000Z`)
        .lte('created_at', `${today}T23:59:59.999Z`);

      if (error) throw error;
      return (data && data.length > 0) || false;
    } catch (error) {
      console.error('Erro ao verificar alerta de pagamento existente:', error);
      return false;
    }
  }

  // Verificar se já existe alerta de recebimento hoje
  private static async hasReceivedAlertToday(billId: string, userId: string): Promise<boolean> {
    try {
      const today = new Date().toISOString().split('T')[0];
      
      const { data, error } = await supabase
        .from('notifications')
        .select('*')
        .eq('user_id', userId)
        .eq('related_bill_to_receive_id', billId)
        .eq('type', 'success')
        .like('message', '%recebido%')
        .gte('created_at', `${today}T00:00:00.000Z`)
        .lte('created_at', `${today}T23:59:59.999Z`);

      if (error) throw error;
      return (data && data.length > 0) || false;
    } catch (error) {
      console.error('Erro ao verificar alerta de recebimento existente:', error);
      return false;
    }
  }

  // Executar verificação completa (incluindo pagamentos/recebimentos)
  static async runFullCheck(): Promise<void> {
    console.log('Iniciando verificação completa de alertas...');
    
    await Promise.all([
      this.checkBillsToPayDueSoon(),
      this.checkOverdueBillsToPay(),
      this.checkBillsToReceiveDueSoon(),
      this.checkRecentlyPaidBills(),
      this.checkRecentlyReceivedBills()
    ]);
    
    console.log('Verificação completa de alertas concluída.');
  }
}
