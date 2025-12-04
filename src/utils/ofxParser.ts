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
 * 
 * Nova abordagem: processa o conteúdo inteiro, não linha por linha
 */
function sgmlToXml(sgml: string): string {
  try {
    console.log('=== INÍCIO DA CONVERSÃO SGML -> XML ===');
    
    // Se já é XML válido, retorna como está
    if (sgml.includes('<?xml')) {
      console.log('Arquivo já é XML válido, retornando sem conversão');
      return sgml;
    }

    // Remove headers OFX e pega apenas o conteúdo
    let content = sgml.replace(/^[\s\S]*?<OFX>/i, '<OFX>');
    console.log('Conteúdo após remover headers (primeiros 300 chars):', content.substring(0, 300));
    
    // Remove quebras de linha para processar como stream contínuo
    // Mas mantém espaços para preservar estrutura
    content = content.replace(/\r\n/g, '\n');
    
    // Estratégia: processar tag por tag
    // Regex para encontrar todas as tags: <TAG> ou </TAG> ou <TAG>valor
    const tagPattern = /<\/?([A-Z0-9_.]+)>([^<]*)/gi;
    const result: string[] = [];
    const stack: string[] = [];
    let lastIndex = 0;
    let match;
    let matchCount = 0;
    
    // Reset regex
    tagPattern.lastIndex = 0;
    
    console.log('Iniciando processamento de tags...');
    
    while ((match = tagPattern.exec(content)) !== null) {
      const fullMatch = match[0];
      const tagName = match[1];
      const afterTag = match[2];
      const isClosing = fullMatch.startsWith('</');
      
      matchCount++;
      
      // Log das primeiras 10 tags para debug
      if (matchCount <= 10) {
        console.log(`Tag ${matchCount}: ${isClosing ? 'CLOSE' : 'OPEN'} <${tagName}>, afterTag: "${afterTag.substring(0, 30)}${afterTag.length > 30 ? '...' : ''}"`);
      }
      
      if (isClosing) {
        // Tag de fechamento - apenas adiciona
        result.push(`</${tagName}>`);
        // Remove do stack se estiver lá
        const stackIndex = stack.lastIndexOf(tagName);
        if (stackIndex !== -1) {
          stack.splice(stackIndex, 1);
        }
      } else {
        // Tag de abertura
        const value = afterTag.trim();
        
        if (value && !value.startsWith('<')) {
          // Tem valor inline - é uma tag leaf
          // Adiciona com fechamento
          result.push(`<${tagName}>${value}</${tagName}>`);
        } else {
          // Sem valor ou próximo caractere é <
          // É uma tag container
          result.push(`<${tagName}>`);
          stack.push(tagName);
        }
      }
      
      lastIndex = tagPattern.lastIndex;
    }
    
    console.log(`Total de tags processadas: ${matchCount}`);
    console.log(`Tags ainda abertas no stack: ${stack.length > 0 ? stack.join(', ') : 'nenhuma'}`);
    
    // Fecha tags que ficaram abertas (não deveria acontecer em OFX válido)
    while (stack.length > 0) {
      const tag = stack.pop();
      console.log(`Fechando tag que ficou aberta: ${tag}`);
      result.push(`</${tag}>`);
    }
    
    const xmlResult = result.join('\n');
    console.log('XML gerado (primeiros 500 chars):', xmlResult.substring(0, 500));
    console.log('=== FIM DA CONVERSÃO ===');
    
    return xmlResult;
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
    console.log('=== INÍCIO DO PARSE XML ===');
    console.log('Tamanho do XML:', xml.length, 'caracteres');
    
    const parser = new DOMParser();
    const doc = parser.parseFromString(xml, 'text/xml');
    
    // Verifica se houve erro no parsing
    const parserError = doc.querySelector('parsererror');
    if (parserError) {
      console.error('❌ ERRO NO PARSE XML:');
      console.error('Mensagem de erro:', parserError.textContent);
      console.error('XML que causou o erro (primeiros 1000 chars):');
      console.error(xml.substring(0, 1000));
      return null;
    }
    
    console.log('✅ Parse XML bem-sucedido');
    console.log('Root element:', doc.documentElement?.tagName);
    console.log('=== FIM DO PARSE XML ===');
    
    return doc;
  } catch (error) {
    console.error('❌ Exceção ao fazer parse do XML:', error);
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
      throw new Error(
        'Não foi possível processar o arquivo OFX. ' +
        'O arquivo pode estar corrompido ou em um formato não suportado. ' +
        'Tente exportar novamente do banco ou use o formato CSV. ' +
        'Consulte o guia SOLUCAO_PROBLEMAS_OFX.md para mais ajuda.'
      );
    }
    
    // Extrai transações
    const transactions = extractTransactions(doc);
    
    console.log(`${transactions.length} transações extraídas do arquivo OFX`);
    
    if (transactions.length === 0) {
      console.warn('Nenhuma transação encontrada. Estrutura do documento:', doc.documentElement?.tagName);
      throw new Error(
        'Nenhuma transação encontrada no arquivo OFX. ' +
        'Verifique se o arquivo contém transações ou tente um período diferente.'
      );
    }
    
    return transactions;
  } catch (error: any) {
    console.error('Erro ao fazer parse do OFX:', error);
    console.error('Erro completo:', error);
    
    // Se já é uma mensagem de erro nossa, repassa
    if (error.message && error.message.includes('Não foi possível processar')) {
      throw error;
    }
    
    // Caso contrário, cria mensagem mais amigável
    throw new Error(
      'Erro ao processar arquivo OFX: ' + (error.message || 'Formato inválido') + '. ' +
      'Tente exportar o arquivo novamente do banco ou use o formato CSV como alternativa.'
    );
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
