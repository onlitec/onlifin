/**
 * Teste do conversor SGML para XML
 * Para executar: node test-sgml-converter.js
 */

// Implementação do conversor
function sgmlToXml(sgml) {
  try {
    if (sgml.includes('<?xml')) {
      return sgml;
    }

    let content = sgml.replace(/^[\s\S]*?<OFX>/i, '<OFX>');
    content = content.replace(/\r\n/g, '\n');
    
    const tagPattern = /<\/?([A-Z0-9_.]+)>([^<]*)/gi;
    const result = [];
    const stack = [];
    let match;
    
    while ((match = tagPattern.exec(content)) !== null) {
      const fullMatch = match[0];
      const tagName = match[1];
      const afterTag = match[2];
      const isClosing = fullMatch.startsWith('</');
      
      if (isClosing) {
        result.push(`</${tagName}>`);
        const stackIndex = stack.lastIndexOf(tagName);
        if (stackIndex !== -1) {
          stack.splice(stackIndex, 1);
        }
      } else {
        const value = afterTag.trim();
        
        if (value && !value.startsWith('<')) {
          result.push(`<${tagName}>${value}</${tagName}>`);
        } else {
          result.push(`<${tagName}>`);
          stack.push(tagName);
        }
      }
    }
    
    while (stack.length > 0) {
      const tag = stack.pop();
      result.push(`</${tag}>`);
    }
    
    return result.join('\n');
  } catch (error) {
    console.error('Erro na conversão:', error);
    return sgml;
  }
}

// Teste 1: Estrutura simples
console.log('=== TESTE 1: Estrutura Simples ===');
const test1 = `<OFX>
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
</OFX>`;

const result1 = sgmlToXml(test1);
console.log('Input:');
console.log(test1);
console.log('\nOutput:');
console.log(result1);
console.log('\n');

// Teste 2: Múltiplas tags na mesma linha
console.log('=== TESTE 2: Múltiplas Tags na Mesma Linha ===');
const test2 = `<OFX>
<STATUS><CODE>0<SEVERITY>INFO</STATUS>
</OFX>`;

const result2 = sgmlToXml(test2);
console.log('Input:');
console.log(test2);
console.log('\nOutput:');
console.log(result2);
console.log('\n');

// Teste 3: Transação completa
console.log('=== TESTE 3: Transação Completa ===');
const test3 = `<OFX>
<BANKTRANLIST>
<STMTTRN>
<TRNTYPE>DEBIT
<DTPOSTED>20240115
<TRNAMT>-50.00
<FITID>12345
<MEMO>Compra Supermercado
</STMTTRN>
</BANKTRANLIST>
</OFX>`;

const result3 = sgmlToXml(test3);
console.log('Input:');
console.log(test3);
console.log('\nOutput:');
console.log(result3);
console.log('\n');

// Teste 4: Validação
console.log('=== TESTE 4: Validação XML ===');
try {
  const lines = result3.split('\n');
  const openTags = [];
  let valid = true;
  
  for (const line of lines) {
    const openMatch = line.match(/<([A-Z0-9_.]+)>(?!.*<\/\1>)/i);
    const closeMatch = line.match(/<\/([A-Z0-9_.]+)>/i);
    const selfClosedMatch = line.match(/<([A-Z0-9_.]+)>.*<\/\1>/i);
    
    if (selfClosedMatch) {
      continue;
    }
    
    if (openMatch && !selfClosedMatch) {
      openTags.push(openMatch[1]);
    }
    
    if (closeMatch) {
      const expectedTag = openTags.pop();
      if (expectedTag !== closeMatch[1]) {
        console.error(`❌ Erro: Esperava fechar <${expectedTag}> mas encontrou </${closeMatch[1]}>`);
        valid = false;
      }
    }
  }
  
  if (openTags.length > 0) {
    console.error(`❌ Erro: Tags não fechadas: ${openTags.join(', ')}`);
    valid = false;
  }
  
  if (valid) {
    console.log('✅ XML válido! Todas as tags estão corretamente fechadas.');
  }
} catch (error) {
  console.error('❌ Erro na validação:', error.message);
}

console.log('\n=== FIM DOS TESTES ===');
