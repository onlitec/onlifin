// Parser Excel (XLSX) utilizando SheetJS
import * as XLSX from 'xlsx';
import { normalizeDate, normalizeAmount, normalizeDescription } from './dataValidator';

export interface ParsedTransaction {
  date: string;
  description: string;
  amount: number;
  type: 'income' | 'expense';
}

export function parseXLSX(buffer: ArrayBuffer): ParsedTransaction[] {
  const workbook = XLSX.read(buffer, { type: 'array' });
  const sheetName = workbook.SheetNames[0];
  const sheet = workbook.Sheets[sheetName];
  const json: any[] = XLSX.utils.sheet_to_json(sheet, { header: 1 });

  if (json.length === 0) throw new Error('Planilha vazia');

  // Detectar cabeçalho
  const headers: string[] = (json[0] as any[]).map(h => (h || '').toString().toLowerCase());
  const dateIdx = headers.findIndex(h => h.includes('data') || h.includes('date'));
  const descIdx = headers.findIndex(h => h.includes('descr') || h.includes('historic') || h.includes('memo') || h.includes('desc'));
  const amountIdx = headers.findIndex(h => h.includes('valor') || h.includes('amount') || h.includes('total'));

  if (dateIdx === -1 || descIdx === -1 || amountIdx === -1) {
    throw new Error('Não foi possível detectar colunas de Data, Descrição ou Valor na planilha');
  }

  const transactions: ParsedTransaction[] = [];

  for (let i = 1; i < json.length; i++) {
    const row = json[i] as any[];
    if (!row || row.length === 0) continue;

    try {
      const dateStr = row[dateIdx]?.toString() || '';
      const descStr = row[descIdx]?.toString() || '';
      const amountStr = row[amountIdx]?.toString() || '';

      if (!dateStr || !descStr || !amountStr) continue;

      const date = normalizeDate(dateStr);
      const description = normalizeDescription(descStr);
      const amount = Math.abs(normalizeAmount(amountStr));

      const type: 'income' | 'expense' = amountStr.toString().includes('-') ? 'expense' : 'income';

      transactions.push({ date, description, amount, type });
    } catch (error) {
      // Ignorar linha com erro
    }
  }

  if (transactions.length === 0) throw new Error('Nenhuma transação válida encontrada na planilha');

  return transactions;
}
