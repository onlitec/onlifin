// OFX Parser - Suporte para arquivos OFX (Open Financial Exchange)

interface OFXTransaction {
  date: string;
  description: string;
  amount: number;
  type: 'income' | 'expense';
  merchant?: string;
}

/**
 * Converte data OFX (YYYYMMDD ou YYYYMMDDHHMMSS) para formato DD/MM/YYYY
 */
function parseOFXDate(dateStr: string): string {
  // Remove timezone e hora se existir
  const cleanDate = dateStr.replace(/\[.*\]/, '').substring(0, 8);
  
  if (cleanDate.length === 8) {
    const year = cleanDate.substring(0, 4);
    const month = cleanDate.substring(4, 6);
    const day = cleanDate.substring(6, 8);
    return `${day}/${month}/${year}`;
  }
  
  return dateStr;
}

/**
 * Extrai valor numérico de string OFX
 */
function parseOFXAmount(amountStr: string): number {
  return parseFloat(amountStr.replace(',', '.'));
}

/**
 * Converte OFX SGML para XML
 * OFX pode vir em formato SGML (sem tags de fechamento)
 */
function sgmlToXml(sgml: string): string {
  try {
    // Se já é XML válido, retorna como está
    if (sgml.includes('<?xml')) {
      return sgml;
    }

    // Remove headers OFX e pega apenas o conteúdo
    let content = sgml.replace(/^[\s\S]*?<OFX>/i, '<OFX>');
    
    // Regex para encontrar tags SGML: <TAG>valor ou <TAG> (sem fechamento)
    // Substitui por XML válido: <TAG>valor</TAG>
    content = content.replace(/<([A-Z0-9_.]+)>([^<\n]+)/gi, (match, tag, value) => {
      // Se o valor já termina com tag de fechamento, não adiciona
      if (value.trim().endsWith(`</${tag}>`)) {
        return match;
      }
      // Adiciona tag de fechamento
      return `<${tag}>${value.trim()}</${tag}>`;
    });
    
    return content;
  } catch (error) {
    console.error('Erro na conversão SGML para XML:', error);
    return sgml;
  }
}

/**
 * Parse XML simples (sem dependências externas)
 */
function parseXML(xml: string): Document | null {
  try {
    const parser = new DOMParser();
    const doc = parser.parseFromString(xml, 'text/xml');
    
    // Verifica se houve erro no parsing
    const parserError = doc.querySelector('parsererror');
    if (parserError) {
      console.error('Erro ao fazer parse do XML:', parserError.textContent);
      return null;
    }
    
    return doc;
  } catch (error) {
    console.error('Erro ao fazer parse do XML:', error);
    return null;
  }
}

/**
 * Extrai transações de um documento OFX
 */
function extractTransactions(doc: Document): OFXTransaction[] {
  const transactions: OFXTransaction[] = [];
  
  // Busca por transações bancárias (STMTTRN)
  const stmtTrns = doc.querySelectorAll('STMTTRN');
  
  stmtTrns.forEach((trn) => {
    try {
      // Tipo de transação
      const trnTypeEl = trn.querySelector('TRNTYPE');
      const trnType = trnTypeEl?.textContent?.trim().toUpperCase() || '';
      
      // Data
      const dtPostedEl = trn.querySelector('DTPOSTED');
      const dtPosted = dtPostedEl?.textContent?.trim() || '';
      
      // Valor
      const trnAmtEl = trn.querySelector('TRNAMT');
      const trnAmt = trnAmtEl?.textContent?.trim() || '0';
      
      // Nome/Descrição
      const nameEl = trn.querySelector('NAME');
      const memoEl = trn.querySelector('MEMO');
      const name = nameEl?.textContent?.trim() || '';
      const memo = memoEl?.textContent?.trim() || '';
      
      // Monta descrição
      let description = name;
      if (memo && memo !== name) {
        description = description ? `${description} - ${memo}` : memo;
      }
      if (!description) {
        description = 'Transação sem descrição';
      }
      
      // Parse valores
      const amount = Math.abs(parseOFXAmount(trnAmt));
      const date = parseOFXDate(dtPosted);
      
      // Determina tipo (receita ou despesa)
      let type: 'income' | 'expense' = 'expense';
      
      // Verifica pelo tipo da transação
      if (trnType === 'CREDIT' || trnType === 'DEP' || trnType === 'DEPOSIT') {
        type = 'income';
      } else if (trnType === 'DEBIT' || trnType === 'PAYMENT' || trnType === 'CHECK') {
        type = 'expense';
      }
      // Verifica pelo sinal do valor
      else if (parseFloat(trnAmt) > 0) {
        type = 'income';
      } else if (parseFloat(trnAmt) < 0) {
        type = 'expense';
      }
      
      // Extrai merchant (primeira palavra da descrição)
      const merchant = description.split(/[\s-]/)[0];
      
      transactions.push({
        date,
        description,
        amount,
        type,
        merchant,
      });
    } catch (error) {
      console.error('Erro ao processar transação OFX:', error);
    }
  });
  
  // Busca por transações de cartão de crédito (CCSTMTTRN)
  const ccStmtTrns = doc.querySelectorAll('CCSTMTTRN');
  
  ccStmtTrns.forEach((trn) => {
    try {
      const trnTypeEl = trn.querySelector('TRNTYPE');
      const trnType = trnTypeEl?.textContent?.trim().toUpperCase() || '';
      
      const dtPostedEl = trn.querySelector('DTPOSTED');
      const dtPosted = dtPostedEl?.textContent?.trim() || '';
      
      const trnAmtEl = trn.querySelector('TRNAMT');
      const trnAmt = trnAmtEl?.textContent?.trim() || '0';
      
      const nameEl = trn.querySelector('NAME');
      const memoEl = trn.querySelector('MEMO');
      const name = nameEl?.textContent?.trim() || '';
      const memo = memoEl?.textContent?.trim() || '';
      
      let description = name;
      if (memo && memo !== name) {
        description = description ? `${description} - ${memo}` : memo;
      }
      if (!description) {
        description = 'Transação sem descrição';
      }
      
      const amount = Math.abs(parseOFXAmount(trnAmt));
      const date = parseOFXDate(dtPosted);
      
      let type: 'income' | 'expense' = 'expense';
      
      if (trnType === 'CREDIT') {
        type = 'income';
      } else if (parseFloat(trnAmt) > 0) {
        type = 'income';
      }
      
      const merchant = description.split(/[\s-]/)[0];
      
      transactions.push({
        date,
        description,
        amount,
        type,
        merchant,
      });
    } catch (error) {
      console.error('Erro ao processar transação de cartão OFX:', error);
    }
  });
  
  return transactions;
}

/**
 * Função principal para fazer parse de arquivo OFX
 */
export function parseOFX(content: string): OFXTransaction[] {
  try {
    console.log('Iniciando parse de arquivo OFX...');
    console.log('Tamanho do arquivo:', content.length, 'bytes');
    
    // Remove BOM se existir
    content = content.replace(/^\uFEFF/, '');
    
    // Log das primeiras linhas para debug
    const firstLines = content.substring(0, 500);
    console.log('Primeiras linhas do arquivo:', firstLines);
    
    // Converte SGML para XML se necessário
    const xml = sgmlToXml(content);
    
    // Log do XML convertido (primeiras linhas)
    const xmlPreview = xml.substring(0, 500);
    console.log('XML após conversão:', xmlPreview);
    
    // Parse XML
    const doc = parseXML(xml);
    if (!doc) {
      console.error('Falha no parse XML. Conteúdo:', xml.substring(0, 1000));
      throw new Error('Não foi possível fazer parse do arquivo OFX. Verifique se o arquivo está correto.');
    }
    
    // Extrai transações
    const transactions = extractTransactions(doc);
    
    console.log(`${transactions.length} transações extraídas do arquivo OFX`);
    
    if (transactions.length === 0) {
      console.warn('Nenhuma transação encontrada. Estrutura do documento:', doc.documentElement?.tagName);
      throw new Error('Nenhuma transação encontrada no arquivo OFX');
    }
    
    return transactions;
  } catch (error: any) {
    console.error('Erro ao fazer parse do OFX:', error);
    console.error('Erro completo:', error);
    throw new Error(error.message || 'Erro ao processar arquivo OFX');
  }
}

/**
 * Valida se o conteúdo é um arquivo OFX válido
 */
export function isValidOFX(content: string): boolean {
  const upperContent = content.toUpperCase();
  return (
    upperContent.includes('<OFX>') ||
    upperContent.includes('OFXHEADER:') ||
    (upperContent.includes('<STMTTRN>') || upperContent.includes('<CCSTMTTRN>'))
  );
}

export type { OFXTransaction };
