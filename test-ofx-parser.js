// Test OFX Parser
import { readFileSync } from 'fs';
import { parseOFX, isValidOFX } from './src/utils/ofxParser.ts';

console.log('=== Teste do Parser OFX ===\n');

try {
  // Lê o arquivo de teste
  const content = readFileSync('./test-ofx-sample.ofx', 'utf-8');
  
  console.log('1. Validando arquivo OFX...');
  const isValid = isValidOFX(content);
  console.log(`   Válido: ${isValid}\n`);
  
  if (isValid) {
    console.log('2. Fazendo parse do arquivo...');
    const transactions = parseOFX(content);
    
    console.log(`\n3. Resultado:`);
    console.log(`   Total de transações: ${transactions.length}\n`);
    
    transactions.forEach((trn, index) => {
      console.log(`   Transação ${index + 1}:`);
      console.log(`     Data: ${trn.date}`);
      console.log(`     Descrição: ${trn.description}`);
      console.log(`     Valor: R$ ${trn.amount.toFixed(2)}`);
      console.log(`     Tipo: ${trn.type === 'income' ? 'Receita' : 'Despesa'}`);
      console.log(`     Merchant: ${trn.merchant || 'N/A'}`);
      console.log('');
    });
    
    console.log('✅ Teste concluído com sucesso!');
  } else {
    console.log('❌ Arquivo OFX inválido');
  }
} catch (error) {
  console.error('❌ Erro no teste:', error.message);
  console.error(error);
}
