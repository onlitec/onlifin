// Parser CSV avançado com detecção automática de colunas

import { normalizeDate, normalizeAmount, normalizeDescription } from './dataValidator';

export interface CSVColumnMapping {
  date: number;
  description: number;
  amount: number;
  type?: number;
  balance?: number;
}

export interface ParsedTransaction {
  date: string;
  description: string;
  amount: number;
  type: 'income' | 'expense';
  balance?: number;
}

/**
 * Detecta automaticamente as colunas de um CSV
 */
export function detectCSVColumns(headers: string[]): CSVColumnMapping | null {
  const mapping: Partial<CSVColumnMapping> = {};
  
  // Palavras-chave para cada tipo de coluna
  const dateKeywords = ['data', 'date', 'dt', 'dia', 'when', 'fecha'];
  const descriptionKeywords = ['descri', 'description', 'memo', 'historic', 'detail', 'name', 'estabelecimento'];
  const amountKeywords = ['valor', 'amount', 'value', 'montante', 'quantia', 'total'];
  const typeKeywords = ['tipo', 'type', 'natureza', 'categoria', 'cat'];
  const balanceKeywords = ['saldo', 'balance', 'bal'];
  
  for (let i = 0; i < headers.length; i++) {
    const header = headers[i].toLowerCase().trim();
    
    // Detectar data
    if (mapping.date === undefined && dateKeywords.some(kw => header.includes(kw))) {
      mapping.date = i;
      continue;
    }
    
    // Detectar descrição
    if (mapping.description === undefined && descriptionKeywords.some(kw => header.includes(kw))) {
      mapping.description = i;
      continue;
    }
    
    // Detectar valor
    if (mapping.amount === undefined && amountKeywords.some(kw => header.includes(kw))) {
      mapping.amount = i;
      continue;
    }
    
    // Detectar tipo
    if (mapping.type === undefined && typeKeywords.some(kw => header.includes(kw))) {
      mapping.type = i;
      continue;
    }
    
    // Detectar saldo
    if (mapping.balance === undefined && balanceKeywords.some(kw => header.includes(kw))) {
      mapping.balance = i;
      continue;
    }
  }
  
  // Validar se encontrou os campos obrigatórios
  if (mapping.date !== undefined && mapping.description !== undefined && mapping.amount !== undefined) {
    return mapping as CSVColumnMapping;
  }
  
  return null;
}

/**
 * Detecta o delimitador de um CSV
 */
export function detectCSVDelimiter(content: string): string {
  const firstLine = content.split('\n')[0];
  
  const delimiters = [',', ';', '\t', '|'];
  let maxCount = 0;
  let bestDelimiter = ',';
  
  for (const delimiter of delimiters) {
    const count = (firstLine.match(new RegExp(`\\${delimiter}`, 'g')) || []).length;
    if (count > maxCount) {
      maxCount = count;
      bestDelimiter = delimiter;
    }
  }
  
  return bestDelimiter;
}

/**
 * Parse CSV com detecção automática de colunas
 */
export function parseCSV(content: string, customMapping?: CSVColumnMapping): ParsedTransaction[] {
  const transactions: ParsedTransaction[] = [];
  
  // Detectar delimitador
  const delimiter = detectCSVDelimiter(content);
  
  // Dividir em linhas
  const lines = content.split('\n').filter(line => line.trim());
  
  if (lines.length === 0) {
    throw new Error('Arquivo CSV vazio');
  }
  
  // Primeira linha como cabeçalho
  const headers = lines[0].split(delimiter).map(h => h.trim().replace(/^["']|["']$/g, ''));
  
  // Detectar colunas automaticamente ou usar mapeamento customizado
  const mapping = customMapping || detectCSVColumns(headers);
  
  if (!mapping) {
    throw new Error('Não foi possível detectar as colunas do CSV. Verifique se o arquivo contém cabeçalhos com "Data", "Descrição" e "Valor".');
  }
  
  // Processar linhas de dados
  for (let i = 1; i < lines.length; i++) {
    const line = lines[i];
    
    // Parse CSV respeitando aspas
    const parts = parseCSVLine(line, delimiter);
    
    if (parts.length < Math.max(mapping.date, mapping.description, mapping.amount) + 1) {
      console.warn(`Linha ${i + 1} ignorada: número insuficiente de colunas`);
      continue;
    }
    
    try {
      const dateStr = parts[mapping.date]?.trim();
      const descriptionStr = parts[mapping.description]?.trim();
      const amountStr = parts[mapping.amount]?.trim();
      
      if (!dateStr || !descriptionStr || !amountStr) {
        console.warn(`Linha ${i + 1} ignorada: campos vazios`);
        continue;
      }
      
      const date = normalizeDate(dateStr);
      const description = normalizeDescription(descriptionStr);
      const amount = normalizeAmount(amountStr);
      
      // Detectar tipo (receita/despesa)
      let type: 'income' | 'expense' = 'expense';
      
      if (mapping.type !== undefined && parts[mapping.type]) {
        const typeStr = parts[mapping.type].toLowerCase();
        if (typeStr.includes('credit') || typeStr.includes('receita') || typeStr.includes('entrada')) {
          type = 'income';
        }
      } else {
        // Detectar pelo sinal do valor
        type = amount >= 0 ? 'income' : 'expense';
      }
      
      // Saldo (opcional)
      let balance: number | undefined;
      if (mapping.balance !== undefined && parts[mapping.balance]) {
        balance = normalizeAmount(parts[mapping.balance]);
      }
      
      transactions.push({
        date,
        description,
        amount: Math.abs(amount),
        type,
        balance
      });
    } catch (error) {
      console.error(`Erro ao processar linha ${i + 1}:`, error);
    }
  }
  
  if (transactions.length === 0) {
    throw new Error('Nenhuma transação válida encontrada no arquivo CSV');
  }
  
  return transactions;
}

/**
 * Parse de uma linha CSV respeitando aspas
 */
function parseCSVLine(line: string, delimiter: string): string[] {
  const result: string[] = [];
  let current = '';
  let inQuotes = false;
  
  for (let i = 0; i < line.length; i++) {
    const char = line[i];
    const nextChar = line[i + 1];
    
    if (char === '"' || char === "'") {
      if (inQuotes && nextChar === char) {
        // Aspas escapadas
        current += char;
        i++; // Pular próximo caractere
      } else {
        // Início ou fim de aspas
        inQuotes = !inQuotes;
      }
    } else if (char === delimiter && !inQuotes) {
      // Delimitador fora de aspas
      result.push(current.trim());
      current = '';
    } else {
      current += char;
    }
  }
  
  // Adicionar último campo
  result.push(current.trim());
  
  return result.map(field => field.replace(/^["']|["']$/g, ''));
}

/**
 * Gera preview do CSV para validação do usuário
 */
export function generateCSVPreview(content: string, maxRows: number = 5): {
  headers: string[];
  rows: string[][];
  detectedMapping: CSVColumnMapping | null;
} {
  const delimiter = detectCSVDelimiter(content);
  const lines = content.split('\n').filter(line => line.trim()).slice(0, maxRows + 1);
  
  if (lines.length === 0) {
    return { headers: [], rows: [], detectedMapping: null };
  }
  
  const headers = parseCSVLine(lines[0], delimiter);
  const rows = lines.slice(1).map(line => parseCSVLine(line, delimiter));
  const detectedMapping = detectCSVColumns(headers);
  
  return { headers, rows, detectedMapping };
}
