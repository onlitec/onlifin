// Utilitário para detecção avançada de duplicatas usando fuzzy matching

interface Transaction {
  date: string;
  description: string;
  amount: number;
  type: 'income' | 'expense';
}

interface DuplicateMatch {
  existingTransaction: Transaction;
  similarity: number; // 0-1
  matchFields: ('date' | 'amount' | 'description')[];
  matchReason: string;
}

interface DuplicateDetectionOptions {
  dateToleranceDays?: number;
  amountTolerancePercent?: number;
  descriptionSimilarityThreshold?: number;
}

/**
 * Calcula similaridade entre duas strings usando algoritmo de Jaro-Winkler
 */
function stringSimilarity(str1: string, str2: string): number {
  if (str1 === str2) return 1;
  if (!str1 || !str2) return 0;

  const s1 = str1.toLowerCase().trim();
  const s2 = str2.toLowerCase().trim();

  if (s1 === s2) return 1;

  if (s1.length === 0 || s2.length === 0) return 0;

  // Distância de Levenshtein simplificada
  const matrix: number[][] = [];
  for (let i = 0; i <= s1.length; i++) {
    matrix[i] = [];
    for (let j = 0; j <= s2.length; j++) {
      if (i === 0) matrix[i][j] = j;
      else if (j === 0) matrix[i][j] = i;
      else if (s1.charAt(i - 1) === s2.charAt(j - 1)) {
        matrix[i][j] = matrix[i - 1][j - 1];
      } else {
        matrix[i][j] = Math.min(
          matrix[i - 1][j - 1] + 1,
          matrix[i][j - 1] + 1,
          matrix[i + 1][j] + 1
        );
      }
    }
  }

  const distance = matrix[s1.length][s2.length];
  const maxLen = Math.max(s1.length, s2.length);
  
  return 1 - distance / maxLen;
}

/**
 * Normaliza uma string para comparação
 */
function normalizeString(str: string): string {
  return str
    .toLowerCase()
    .replace(/[^\w\s]/g, '') // Remove caracteres especiais
    .replace(/\s+/g, ' ') // Normaliza espaços
    .trim();
}

/**
 * Calcula similaridade de duas datas
 * Retorna 1 se iguais, 0 se muito diferentes
 */
function dateSimilarity(date1: string, date2: string, toleranceDays: number): number {
  const d1 = new Date(date1);
  const d2 = new Date(date2);

  if (isNaN(d1.getTime()) || isNaN(d2.getTime())) return 0;

  const diffTime = Math.abs(d1.getTime() - d2.getTime());
  const diffDays = diffTime / (1000 * 60 * 60 * 24);

  if (diffDays <= toleranceDays) {
    return 1 - (diffDays / toleranceDays);
  }

  return 0;
}

/**
 * Calcula similaridade de valores monetários
 */
function amountSimilarity(amount1: number, amount2: number, tolerancePercent: number): number {
  if (amount1 === 0 && amount2 === 0) return 1;
  if (amount1 === 0 || amount2 === 0) return 0;

  const diff = Math.abs(amount1 - amount2);
  const maxAmount = Math.max(amount1, amount2);
  const tolerance = maxAmount * (tolerancePercent / 100);

  if (diff <= tolerance) {
    return 1 - (diff / tolerance);
  }

  return 0;
}

/**
 * Encontra transações duplicatas usando fuzzy matching
 * Filtra por mês/ano para evitar falsos positivos
 */
export function findDuplicates(
  newTransaction: Transaction,
  existingTransactions: Transaction[],
  options: DuplicateDetectionOptions = {}
): DuplicateMatch[] {
  const {
    dateToleranceDays = 3,
    amountTolerancePercent = 5,
    descriptionSimilarityThreshold = 0.7
  } = options;

  const matches: DuplicateMatch[] = [];
  const newDate = new Date(newTransaction.date);
  const newMonth = newDate.getMonth();
  const newYear = newDate.getFullYear();

  // Filtrar transações do mesmo mês/ano
  const sameMonthTransactions = existingTransactions.filter(existing => {
    const existingDate = new Date(existing.date);
    return existingDate.getMonth() === newMonth && existingDate.getFullYear() === newYear;
  });

  for (const existing of sameMonthTransactions) {
    // Verificar se é do mesmo tipo
    if (existing.type !== newTransaction.type) continue;

    const matchFields: ('date' | 'amount' | 'description')[] = [];
    let totalSimilarity = 0;
    let fieldCount = 0;

    // Comparar valor (mais importante)
    const amountSim = amountSimilarity(newTransaction.amount, existing.amount, amountTolerancePercent);
    if (amountSim >= 0.9) { // Exigir alta similaridade de valor
      matchFields.push('amount');
      totalSimilarity += amountSim * 2; // Peso dobrado para valor
      fieldCount += 2;
    }

    // Comparar descrição
    const descSim = stringSimilarity(newTransaction.description, existing.description);
    if (descSim >= descriptionSimilarityThreshold) {
      matchFields.push('description');
      totalSimilarity += descSim;
      fieldCount++;
    }

    // Comparar data
    const dateSim = dateSimilarity(newTransaction.date, existing.date, dateToleranceDays);
    if (dateSim > 0.5) {
      matchFields.push('date');
      totalSimilarity += dateSim;
      fieldCount++;
    }

    // Calcular similaridade média ponderada
    if (fieldCount >= 2) {
      const avgSimilarity = totalSimilarity / fieldCount;

      if (avgSimilarity >= 0.6) { // Threshold mais baixo para detectar mais
        matches.push({
          existingTransaction: existing,
          similarity: avgSimilarity,
          matchFields,
          matchReason: generateMatchReason(matchFields, avgSimilarity)
        });
      }
    }
  }

  // Ordenar por similaridade (maior primeiro)
  matches.sort((a, b) => b.similarity - a.similarity);

  return matches;
}

/**
 * Gera uma descrição do motivo do match
 */
function generateMatchReason(fields: string[], similarity: number): string {
  const fieldNames = {
    date: 'Data',
    amount: 'Valor',
    description: 'Descrição'
  };

  const fieldList = fields.map(f => fieldNames[f as keyof typeof fieldNames]).join(', ');
  const percent = (similarity * 100).toFixed(0);

  return `Similaridade ${percent}% em: ${fieldList}`;
}

/**
 * Classifica o nível de confiança da duplicata
 */
export function getDuplicateConfidence(matches: DuplicateMatch[]): 'high' | 'medium' | 'low' | 'none' {
  if (matches.length === 0) return 'none';
  
  const bestMatch = matches[0];
  
  if (bestMatch.similarity >= 0.95) return 'high';
  if (bestMatch.similarity >= 0.75) return 'medium';
  return 'low';
}

/**
 * Verifica se uma transação é provável duplicata (threshold alto)
 */
export function isLikelyDuplicate(matches: DuplicateMatch[]): boolean {
  return matches.length > 0 && matches[0].similarity >= 0.95;
}

/**
 * Verifica se deve mostrar interface de decisão para o usuário
 */
export function shouldShowDuplicateResolver(matches: DuplicateMatch[]): boolean {
  return matches.length > 0 && matches[0].similarity >= 0.75 && matches[0].similarity < 0.95;
}

/**
 * Encontra a melhor correspondência (match mais similar)
 */
export function findBestMatch(matches: DuplicateMatch[]): DuplicateMatch | null {
  if (matches.length === 0) return null;
  return matches[0];
}

/**
 * Agrupa transações por similaridade para facilitar visualização
 */
export function groupTransactionsBySimilarity(
  transactions: Transaction[],
  threshold: number = 0.85
): Array<{ representative: Transaction; similar: Transaction[] }> {
  const groups: Array<{ representative: Transaction; similar: Transaction[] }> = [];
  const processed = new Set<number>();

  for (let i = 0; i < transactions.length; i++) {
    if (processed.has(i)) continue;

    const current = transactions[i];
    const similar: Transaction[] = [];

    for (let j = i + 1; j < transactions.length; j++) {
      if (processed.has(j)) continue;

      const other = transactions[j];
      const matches = findDuplicates(current, [other], { dateToleranceDays: 1, amountTolerancePercent: 2 });

      if (matches.length > 0 && matches[0].similarity >= threshold) {
        similar.push(other);
        processed.add(j);
      }
    }

    if (similar.length > 0) {
      groups.push({
        representative: current,
        similar
      });
      processed.add(i);
    }
  }

  return groups;
}
