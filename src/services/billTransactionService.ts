import { supabase } from '@/db/client';
import { AlertService } from './alertService';
import type { BillToPay, BillToReceive, Transaction } from '@/types/types';

export interface CreateTransactionFromBillOptions {
  billId: string;
  userId: string;
  accountId?: string;
  categoryId?: string;
  description?: string;
  notes?: string;
}

export class BillTransactionService {
  // Criar transação a partir de conta a pagar
  static async createTransactionFromBillToPay(options: CreateTransactionFromBillOptions): Promise<Transaction | null> {
    try {
      // Buscar dados da conta a pagar
      const { data: bill, error: billError } = await supabase
        .from('bills_to_pay')
        .select('*')
        .eq('id', options.billId)
        .single();

      if (billError || !bill) {
        throw new Error('Conta a pagar não encontrada');
      }

      // Verificar se já existe transação vinculada
      if (bill.transaction_id) {
        const { data: existingTransaction } = await supabase
          .from('transactions')
          .select('*')
          .eq('id', bill.transaction_id)
          .single();

        if (existingTransaction) {
          return existingTransaction;
        }
      }

      // Criar transação
      const transactionData = {
        user_id: options.userId,
        account_id: options.accountId || bill.account_id,
        category_id: options.categoryId || bill.category_id,
        type: 'expense' as const,
        amount: bill.amount,
        description: options.description || bill.description,
        date: bill.paid_date || new Date().toISOString().split('T')[0],
        is_recurring: bill.is_recurring,
        recurrence_pattern: bill.recurrence_pattern,
        is_installment: bill.is_installment || false,
        installment_number: bill.installment_number || null,
        total_installments: bill.total_installments || null,
        parent_transaction_id: null, // Pode ser vinculado posteriormente se necessário
        is_reconciled: true, // Marcada como reconciliada pois vem de uma conta
        is_transfer: false,
        transfer_destination_account_id: null,
        company_id: bill.company_id,
        person_id: bill.person_id,
        tags: ['conta-a-pagar'],
        notes: options.notes || bill.notes
      };

      const { data: transaction, error: transactionError } = await supabase
        .from('transactions')
        .insert(transactionData)
        .select()
        .single();

      if (transactionError) throw transactionError;

      // Vincular transação à conta
      await supabase
        .from('bills_to_pay')
        .update({ 
          transaction_id: transaction.id,
          status: 'paid',
          paid_date: bill.paid_date || new Date().toISOString().split('T')[0]
        })
        .eq('id', options.billId);

      // Gerar próxima ocorrência se for recorrente
      if (bill.is_recurring && !bill.is_installment) {
        await this.generateNextRecurringBill(bill, 'bills_to_pay');
      }

      return transaction;
    } catch (error) {
      console.error('Erro ao criar transação a partir de conta a pagar:', error);
      return null;
    }
  }

  // Criar transação a partir de conta a receber
  static async createTransactionFromBillToReceive(options: CreateTransactionFromBillOptions): Promise<Transaction | null> {
    try {
      // Buscar dados da conta a receber
      const { data: bill, error: billError } = await supabase
        .from('bills_to_receive')
        .select('*')
        .eq('id', options.billId)
        .single();

      if (billError || !bill) {
        throw new Error('Conta a receber não encontrada');
      }

      // Verificar se já existe transação vinculada
      if (bill.transaction_id) {
        const { data: existingTransaction } = await supabase
          .from('transactions')
          .select('*')
          .eq('id', bill.transaction_id)
          .single();

        if (existingTransaction) {
          return existingTransaction;
        }
      }

      // Criar transação
      const transactionData = {
        user_id: options.userId,
        account_id: options.accountId || bill.account_id,
        category_id: options.categoryId || bill.category_id,
        type: 'income' as const,
        amount: bill.amount,
        description: options.description || bill.description,
        date: bill.received_date || new Date().toISOString().split('T')[0],
        is_recurring: bill.is_recurring,
        recurrence_pattern: bill.recurrence_pattern,
        is_installment: bill.is_installment || false,
        installment_number: bill.installment_number || null,
        total_installments: bill.total_installments || null,
        parent_transaction_id: null,
        is_reconciled: true, // Marcada como reconciliada pois vem de uma conta
        is_transfer: false,
        transfer_destination_account_id: null,
        company_id: bill.company_id,
        person_id: bill.person_id,
        tags: ['conta-a-receber'],
        notes: options.notes || bill.notes
      };

      const { data: transaction, error: transactionError } = await supabase
        .from('transactions')
        .insert(transactionData)
        .select()
        .single();

      if (transactionError) throw transactionError;

      // Vincular transação à conta
      await supabase
        .from('bills_to_receive')
        .update({ 
          transaction_id: transaction.id,
          status: 'received',
          received_date: bill.received_date || new Date().toISOString().split('T')[0]
        })
        .eq('id', options.billId);

      // Gerar próxima ocorrência se for recorrente
      if (bill.is_recurring && !bill.is_installment) {
        await this.generateNextRecurringBill(bill, 'bills_to_receive');
      }

      return transaction;
    } catch (error) {
      console.error('Erro ao criar transação a partir de conta a receber:', error);
      return null;
    }
  }

  // Vincular transação existente a uma conta a pagar
  static async linkTransactionToBillToPay(transactionId: string, billId: string): Promise<boolean> {
    try {
      // Buscar dados da transação
      const { data: transaction, error: transactionError } = await supabase
        .from('transactions')
        .select('*')
        .eq('id', transactionId)
        .single();

      if (transactionError || !transaction) {
        throw new Error('Transação não encontrada');
      }

      // Buscar dados da conta
      const { data: bill, error: billError } = await supabase
        .from('bills_to_pay')
        .select('*')
        .eq('id', billId)
        .single();

      if (billError || !bill) {
        throw new Error('Conta a pagar não encontrada');
      }

      // Validar compatibilidade
      if (transaction.type !== 'expense') {
        throw new Error('Transação deve ser uma despesa');
      }

      if (Math.abs(transaction.amount - bill.amount) > 0.01) {
        throw new Error('Valores não correspondem');
      }

      // Vincular transação à conta
      const { error: updateError } = await supabase
        .from('bills_to_pay')
        .update({ 
          transaction_id: transactionId,
          status: 'paid',
          paid_date: transaction.date
        })
        .eq('id', billId);

      if (updateError) throw updateError;

      // Atualizar transação como reconciliada
      await supabase
        .from('transactions')
        .update({ 
          is_reconciled: true,
          tags: [...(transaction.tags || []), 'conta-a-pagar']
        })
        .eq('id', transactionId);

      // Enviar alerta de confirmação
      await AlertService.createBillPaidAlert(
        billId,
        bill.user_id,
        bill.description,
        bill.amount
      );

      return true;
    } catch (error) {
      console.error('Erro ao vincular transação à conta a pagar:', error);
      return false;
    }
  }

  // Vincular transação existente a uma conta a receber
  static async linkTransactionToBillToReceive(transactionId: string, billId: string): Promise<boolean> {
    try {
      // Buscar dados da transação
      const { data: transaction, error: transactionError } = await supabase
        .from('transactions')
        .select('*')
        .eq('id', transactionId)
        .single();

      if (transactionError || !transaction) {
        throw new Error('Transação não encontrada');
      }

      // Buscar dados da conta
      const { data: bill, error: billError } = await supabase
        .from('bills_to_receive')
        .select('*')
        .eq('id', billId)
        .single();

      if (billError || !bill) {
        throw new Error('Conta a receber não encontrada');
      }

      // Validar compatibilidade
      if (transaction.type !== 'income') {
        throw new Error('Transação deve ser uma receita');
      }

      if (Math.abs(transaction.amount - bill.amount) > 0.01) {
        throw new Error('Valores não correspondem');
      }

      // Vincular transação à conta
      const { error: updateError } = await supabase
        .from('bills_to_receive')
        .update({ 
          transaction_id: transactionId,
          status: 'received',
          received_date: transaction.date
        })
        .eq('id', billId);

      if (updateError) throw updateError;

      // Atualizar transação como reconciliada
      await supabase
        .from('transactions')
        .update({ 
          is_reconciled: true,
          tags: [...(transaction.tags || []), 'conta-a-receber']
        })
        .eq('id', transactionId);

      // Enviar alerta de confirmação
      await AlertService.createBillToReceiveReceivedAlert(
        billId,
        bill.user_id,
        bill.description,
        bill.amount
      );

      return true;
    } catch (error) {
      console.error('Erro ao vincular transação à conta a receber:', error);
      return false;
    }
  }

  // Desvincular transação de uma conta
  static async unlinkTransactionFromBill(billId: string, isPay: boolean): Promise<boolean> {
    try {
      const tableName = isPay ? 'bills_to_pay' : 'bills_to_receive';
      const statusField = isPay ? 'paid_date' : 'received_date';
      const newStatus = isPay ? 'pending' : 'pending';

      // Buscar dados da conta
      const { data: bill, error: billError } = await supabase
        .from(tableName)
        .select('*')
        .eq('id', billId)
        .single();

      if (billError || !bill) {
        throw new Error('Conta não encontrada');
      }

      // Desvincular transação
      const { error: updateError } = await supabase
        .from(tableName)
        .update({ 
          transaction_id: null,
          status: newStatus,
          [statusField]: null
        })
        .eq('id', billId);

      if (updateError) throw updateError;

      // Remover tag da transação
      if (bill.transaction_id) {
        await supabase
          .from('transactions')
          .update({ 
            is_reconciled: false
          })
          .eq('id', bill.transaction_id);
      }

      return true;
    } catch (error) {
      console.error('Erro ao desvincular transação da conta:', error);
      return false;
    }
  }

  // Buscar transações não vinculadas que poderiam corresponder a contas
  static async findUnlinkedTransactions(userId: string, companyId?: string, personId?: string): Promise<{
    billsToPay: Array<{ bill: BillToPay; transactions: Transaction[] }>;
    billsToReceive: Array<{ bill: BillToReceive; transactions: Transaction[] }>;
  }> {
    try {
      // Buscar contas a pagar sem transação vinculada
      const { data: billsToPay, error: payError } = await supabase
        .from('bills_to_pay')
        .select('*')
        .eq('user_id', userId)
        .is('transaction_id', null)
        .eq('status', 'pending');

      if (payError) throw payError;

      // Buscar contas a receber sem transação vinculada
      const { data: billsToReceive, error: receiveError } = await supabase
        .from('bills_to_receive')
        .select('*')
        .eq('user_id', userId)
        .is('transaction_id', null)
        .eq('status', 'pending');

      if (receiveError) throw receiveError;

      // Buscar transações não reconciliadas do mesmo período
      const thirtyDaysAgo = new Date();
      thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);

      const { data: transactions, error: transactionError } = await supabase
        .from('transactions')
        .select('*')
        .eq('user_id', userId)
        .eq('is_reconciled', false)
        .gte('date', thirtyDaysAgo.toISOString().split('T')[0]);

      if (transactionError) throw transactionError;

      // Encontrar correspondências potenciais
      const result = {
        billsToPay: (billsToPay || []).map((bill: BillToPay) => ({
          bill,
          transactions: (transactions || []).filter((tx: Transaction) => 
            tx.type === 'expense' &&
            Math.abs(tx.amount - bill.amount) < 0.01 &&
            (!companyId || tx.company_id === companyId) &&
            (!personId || tx.person_id === personId)
          )
        })).filter((item: { bill: BillToPay; transactions: Transaction[] }) => item.transactions.length > 0),
        billsToReceive: (billsToReceive || []).map((bill: BillToReceive) => ({
          bill,
          transactions: (transactions || []).filter((tx: Transaction) => 
            tx.type === 'income' &&
            Math.abs(tx.amount - bill.amount) < 0.01 &&
            (!companyId || tx.company_id === companyId) &&
            (!personId || tx.person_id === personId)
          )
        })).filter((item: { bill: BillToReceive; transactions: Transaction[] }) => item.transactions.length > 0)
      };

      return result;
    } catch (error) {
      console.error('Erro ao buscar transações não vinculadas:', error);
      return { billsToPay: [], billsToReceive: [] };
    }
  }

  // Gerar próxima conta recorrente
  static async generateNextRecurringBill(bill: any, tableName: string) {
    try {
      if (!bill.is_recurring || !bill.recurrence_pattern || bill.next_occurrence_generated) return;

      const nextDueDate = new Date(bill.due_date + 'T12:00:00'); // Evitar problemas de fuso
      
      if (bill.recurrence_pattern === 'monthly') {
        nextDueDate.setMonth(nextDueDate.getMonth() + 1);
      } else if (bill.recurrence_pattern === 'weekly') {
        nextDueDate.setDate(nextDueDate.getDate() + 7);
      } else if (bill.recurrence_pattern === 'fortnightly') {
        nextDueDate.setDate(nextDueDate.getDate() + 14);
      } else if (bill.recurrence_pattern === 'yearly') {
        nextDueDate.setFullYear(nextDueDate.getFullYear() + 1);
      } else {
        return;
      }

      const { error: insertError } = await supabase
        .from(tableName)
        .insert({
          user_id: bill.user_id,
          company_id: bill.company_id,
          person_id: bill.person_id,
          description: bill.description,
          amount: bill.amount,
          due_date: nextDueDate.toISOString().split('T')[0],
          category_id: bill.category_id,
          status: 'pending',
          is_recurring: true,
          recurrence_pattern: bill.recurrence_pattern,
          is_installment: false,
          installment_number: null,
          total_installments: null,
          parent_bill_id: bill.parent_bill_id || bill.id,
          account_id: bill.account_id,
          notes: bill.notes,
          notification_mode: bill.notification_mode,
          notification_frequency: bill.notification_frequency,
          custom_days_before: bill.custom_days_before
        });

      if (insertError) throw insertError;

      // Marcar conta atual como geradora da próxima
      await supabase
        .from(tableName)
        .update({ next_occurrence_generated: true })
        .eq('id', bill.id);

    } catch (error) {
      console.error('Erro ao gerar próxima conta recorrente:', error);
    }
  }
}
