// Utilitários para validação e normalização de dados de importação

/**
 * Detecta o encoding de um arquivo
 */
export function detectEncoding(buffer: ArrayBuffer): string {
  const bytes = new Uint8Array(buffer);
  
  // Verifica BOM UTF-8
  if (bytes.length >= 3 && bytes[0] === 0xEF && bytes[1] === 0xBB && bytes[2] === 0xBF) {
    return 'UTF-8';
  }
  
  // Verifica BOM UTF-16 LE
  if (bytes.length >= 2 && bytes[0] === 0xFF && bytes[1] === 0xFE) {
    return 'UTF-16LE';
  }
  
  // Verifica BOM UTF-16 BE
  if (bytes.length >= 2 && bytes[0] === 0xFE && bytes[1] === 0xFF) {
    return 'UTF-16BE';
  }
  
  // Heurística: verifica se parece UTF-8
  let isUtf8 = true;
  for (let i = 0; i < Math.min(bytes.length, 1000); i++) {
    if (bytes[i] > 127) {
      // Caractere não-ASCII, verificar sequência UTF-8
      if ((bytes[i] & 0xE0) === 0xC0) {
        // 2 bytes
        if (i + 1 >= bytes.length || (bytes[i + 1] & 0xC0) !== 0x80) {
          isUtf8 = false;
          break;
        }
        i += 1;
      } else if ((bytes[i] & 0xF0) === 0xE0) {
        // 3 bytes
        if (i + 2 >= bytes.length || (bytes[i + 1] & 0xC0) !== 0x80 || (bytes[i + 2] & 0xC0) !== 0x80) {
          isUtf8 = false;
          break;
        }
        i += 2;
      } else if ((bytes[i] & 0xF8) === 0xF0) {
        // 4 bytes
        if (i + 3 >= bytes.length || (bytes[i + 1] & 0xC0) !== 0x80 || (bytes[i + 2] & 0xC0) !== 0x80 || (bytes[i + 3] & 0xC0) !== 0x80) {
          isUtf8 = false;
          break;
        }
        i += 3;
      } else {
        isUtf8 = false;
        break;
      }
    }
  }
  
  if (isUtf8) return 'UTF-8';
  
  // Fallback: assumir ISO-8859-1 (Latin-1) ou Windows-1252
  return 'ISO-8859-1';
}

/**
 * Normaliza formato de data para YYYY-MM-DD
 */
export function normalizeDate(dateStr: string): string {
  if (!dateStr) return new Date().toISOString().split('T')[0];
  
  // Remove espaços extras
  const cleaned = dateStr.trim();
  
  // Formatos suportados
  const formats = [
    // YYYY-MM-DD (ISO)
    /^(\d{4})-(\d{2})-(\d{2})$/,
    // DD/MM/YYYY
    /^(\d{2})\/(\d{2})\/(\d{4})$/,
    // DD-MM-YYYY
    /^(\d{2})-(\d{2})-(\d{4})$/,
    // MM/DD/YYYY (formato americano)
    /^(\d{2})\/(\d{2})\/(\d{4})$/,
    // YYYYMMDD (sem separadores)
    /^(\d{4})(\d{2})(\d{2})$/,
    // DD.MM.YYYY
    /^(\d{2})\.(\d{2})\.(\d{4})$/,
  ];
  
  for (let i = 0; i < formats.length; i++) {
    const match = cleaned.match(formats[i]);
    if (match) {
      if (i === 0 || i === 4) {
        // YYYY-MM-DD ou YYYYMMDD
        const year = match[1];
        const month = match[2];
        const day = match[3];
        return `${year}-${month}-${day}`;
      } else if (i === 3) {
        // MM/DD/YYYY (americano) - assumir que mês vem primeiro
        const month = match[1];
        const day = match[2];
        const year = match[3];
        // Validar se faz sentido (mês <= 12)
        if (parseInt(month) <= 12) {
          return `${year}-${month}-${day}`;
        }
        // Senão, assumir DD/MM/YYYY
        return `${year}-${day}-${month}`;
      } else {
        // DD/MM/YYYY, DD-MM-YYYY, DD.MM.YYYY
        const day = match[1];
        const month = match[2];
        const year = match[3];
        return `${year}-${month}-${day}`;
      }
    }
  }
  
  // Tentar parse nativo como fallback
  try {
    const date = new Date(cleaned);
    if (!isNaN(date.getTime())) {
      return date.toISOString().split('T')[0];
    }
  } catch (e) {
    // Ignorar erro
  }
  
  // Fallback: data atual
  console.warn(`Data inválida: "${dateStr}", usando data atual`);
  return new Date().toISOString().split('T')[0];
}

/**
 * Normaliza valor monetário para número
 */
export function normalizeAmount(amountStr: string): number {
  if (typeof amountStr === 'number') return amountStr;
  if (!amountStr) return 0;
  
  // Remove espaços e símbolos de moeda
  let cleaned = amountStr
    .toString()
    .trim()
    .replace(/[R$\s€£¥]/g, '')
    .replace(/\u00A0/g, ''); // Remove non-breaking space
  
  // Detectar separador decimal
  // Padrão brasileiro: 1.234,56
  // Padrão internacional: 1,234.56
  
  const lastComma = cleaned.lastIndexOf(',');
  const lastDot = cleaned.lastIndexOf('.');
  
  if (lastComma > lastDot) {
    // Vírgula é decimal (padrão brasileiro)
    cleaned = cleaned.replace(/\./g, '').replace(',', '.');
  } else if (lastDot > lastComma) {
    // Ponto é decimal (padrão internacional)
    cleaned = cleaned.replace(/,/g, '');
  } else if (lastComma !== -1 || lastDot !== -1) {
    // Apenas um separador
    cleaned = cleaned.replace(',', '.');
  }
  
  // Remove caracteres não numéricos (exceto . e -)
  cleaned = cleaned.replace(/[^\d.-]/g, '');
  
  const value = parseFloat(cleaned);
  return isNaN(value) ? 0 : value;
}

/**
 * Limpa e normaliza descrição de transação
 */
export function normalizeDescription(description: string): string {
  if (!description) return 'Transação sem descrição';
  
  return description
    .trim()
    // Remove tags XML/HTML
    .replace(/<[^>]*>/g, '')
    // Remove múltiplos espaços
    .replace(/\s+/g, ' ')
    // Remove caracteres de controle
    .replace(/[\x00-\x1F\x7F]/g, '')
    // Limita tamanho
    .substring(0, 255)
    .trim() || 'Transação sem descrição';
}

/**
 * Valida se uma transação tem dados mínimos necessários
 */
export function validateTransaction(transaction: any): { valid: boolean; errors: string[] } {
  const errors: string[] = [];
  
  if (!transaction.date) {
    errors.push('Data é obrigatória');
  } else {
    const normalized = normalizeDate(transaction.date);
    if (normalized === new Date().toISOString().split('T')[0] && transaction.date !== normalized) {
      errors.push(`Data inválida: "${transaction.date}"`);
    }
  }
  
  if (transaction.amount === undefined || transaction.amount === null) {
    errors.push('Valor é obrigatório');
  } else if (typeof transaction.amount !== 'number' && isNaN(normalizeAmount(transaction.amount))) {
    errors.push(`Valor inválido: "${transaction.amount}"`);
  }
  
  if (!transaction.description || transaction.description.trim() === '') {
    errors.push('Descrição é obrigatória');
  }
  
  if (transaction.type && !['income', 'expense'].includes(transaction.type)) {
    errors.push(`Tipo inválido: "${transaction.type}". Use "income" ou "expense"`);
  }
  
  return {
    valid: errors.length === 0,
    errors
  };
}

/**
 * Sanitiza descrição para prevenir XSS
 */
export function sanitizeDescription(description: string): string {
  return description
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#x27;')
    .replace(/\//g, '&#x2F;');
}

/**
 * Valida tamanho de arquivo
 */
export function validateFileSize(file: File, maxSizeMB: number = 10): { valid: boolean; error?: string } {
  const maxBytes = maxSizeMB * 1024 * 1024;
  
  if (file.size > maxBytes) {
    return {
      valid: false,
      error: `Arquivo muito grande. Tamanho máximo: ${maxSizeMB}MB`
    };
  }
  
  return { valid: true };
}

/**
 * Valida extensão de arquivo
 */
export function validateFileExtension(filename: string, allowedExtensions: string[]): { valid: boolean; error?: string } {
  const extension = filename.split('.').pop()?.toLowerCase();
  
  if (!extension || !allowedExtensions.includes(extension)) {
    return {
      valid: false,
      error: `Formato não suportado. Use: ${allowedExtensions.join(', ')}`
    };
  }
  
  return { valid: true };
}
