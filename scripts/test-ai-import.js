#!/usr/bin/env node
/**
 * Teste Prático de Importação com IA
 * 
 * Este script:
 * 1. Lê os extratos da pasta docs (CSV e OFX)
 * 2. Envia as transações para o modelo Ollama categorizar
 * 3. Exibe os resultados para validação do treinamento
 * 
 * Uso: node scripts/test-ai-import.js
 */

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Configurações
// Usando IP interno da rede Docker (onlifin-network)
const OLLAMA_URL = 'http://172.19.0.2:11434/api/chat';
const OLLAMA_MODEL = 'qwen2.5:0.5b'; // Modelo mais leve e rápido
const DOCS_DIR = path.join(__dirname, '..', 'docs');
const BATCH_SIZE = 3; // Processar 3 transações por vez para melhor performance
const TIMEOUT_MS = 180000; // 3 minutos de timeout

// Cores para console
const colors = {
    reset: '\x1b[0m',
    bright: '\x1b[1m',
    green: '\x1b[32m',
    yellow: '\x1b[33m',
    blue: '\x1b[34m',
    red: '\x1b[31m',
    cyan: '\x1b[36m',
    magenta: '\x1b[35m'
};

function log(msg, color = 'reset') {
    console.log(`${colors[color]}${msg}${colors.reset}`);
}

function logSection(title) {
    console.log('\n' + '═'.repeat(60));
    log(`  ${title}`, 'bright');
    console.log('═'.repeat(60));
}

// Categorias padrão para simular banco de dados
const defaultCategories = [
    { id: 'cat-salario', name: 'Salário', type: 'income' },
    { id: 'cat-transferencia-entrada', name: 'Transferência Recebida', type: 'income' },
    { id: 'cat-outros-receitas', name: 'Outras Receitas', type: 'income' },
    { id: 'cat-alimentacao', name: 'Alimentação', type: 'expense' },
    { id: 'cat-transporte', name: 'Transporte', type: 'expense' },
    { id: 'cat-mercado', name: 'Supermercado', type: 'expense' },
    { id: 'cat-gas', name: 'Gás e Combustível', type: 'expense' },
    { id: 'cat-transferencia-saida', name: 'Transferência Enviada', type: 'expense' },
    { id: 'cat-compras', name: 'Compras Gerais', type: 'expense' },
    { id: 'cat-pagamentos', name: 'Pagamentos', type: 'expense' },
    { id: 'cat-assinaturas', name: 'Assinaturas e Serviços', type: 'expense' },
];

// Parser de CSV (adaptado do código existente)
function parseCSV(content) {
    const lines = content.split(/\r?\n/).filter(l => l.trim());
    if (lines.length < 2) return [];

    const header = lines[0].toLowerCase();
    const separator = header.includes(';') ? ';' : ',';

    const headerParts = lines[0].split(separator).map(h => h.trim().toLowerCase());
    const dateIdx = headerParts.findIndex(h => h.includes('data') || h.includes('date'));
    const valueIdx = headerParts.findIndex(h => h.includes('valor') || h.includes('value') || h.includes('amount'));
    const descIdx = headerParts.findIndex(h => h.includes('desc') || h.includes('memo'));

    const transactions = [];
    for (let i = 1; i < lines.length; i++) {
        const parts = lines[i].split(separator);
        if (parts.length < 3) continue;

        const dateRaw = parts[dateIdx]?.trim() || '';
        const valueRaw = parts[valueIdx]?.trim().replace(',', '.') || '0';
        const description = parts[descIdx]?.trim() || '';

        const amount = parseFloat(valueRaw);
        if (isNaN(amount)) continue;

        transactions.push({
            date: dateRaw,
            description,
            amount,
            type: amount >= 0 ? 'income' : 'expense'
        });
    }

    return transactions;
}

// Parser de OFX
function parseOFX(content) {
    const transactions = [];
    const regex = /<STMTTRN>([\s\S]*?)<\/STMTTRN>/gi;
    let match;

    while ((match = regex.exec(content)) !== null) {
        const block = match[1];

        const amountMatch = block.match(/<TRNAMT>([^<]+)/i);
        const memoMatch = block.match(/<MEMO>([^<]+)/i);
        const dateMatch = block.match(/<DTPOSTED>([^<\[]+)/i);
        const fitidMatch = block.match(/<FITID>([^<]+)/i);

        if (amountMatch) {
            const amount = parseFloat(amountMatch[1]);
            const description = memoMatch ? memoMatch[1].trim() : '';
            const dateRaw = dateMatch ? dateMatch[1].trim() : '';

            // Converter data YYYYMMDD para DD/MM/YYYY
            let formattedDate = dateRaw;
            if (dateRaw.length >= 8) {
                const y = dateRaw.substring(0, 4);
                const m = dateRaw.substring(4, 6);
                const d = dateRaw.substring(6, 8);
                formattedDate = `${d}/${m}/${y}`;
            }

            transactions.push({
                date: formattedDate,
                description,
                amount,
                type: amount >= 0 ? 'income' : 'expense',
                id: fitidMatch ? fitidMatch[1].trim() : undefined
            });
        }
    }

    return transactions;
}

// Chamar Ollama API com AbortController
async function categorizeWithAI(transactions, categories) {
    const incomeCategories = categories
        .filter(c => c.type === 'income')
        .map(c => `- "${c.name}" (ID: ${c.id})`)
        .join('\n');

    const expenseCategories = categories
        .filter(c => c.type === 'expense')
        .map(c => `- "${c.name}" (ID: ${c.id})`)
        .join('\n');

    const formattedTransactions = transactions.map((t, i) =>
        `${i + 1}. ${t.date} | ${t.description.substring(0, 60)} | R$ ${Math.abs(t.amount).toFixed(2)} | ${t.amount >= 0 ? 'RECEITA' : 'DESPESA'}`
    ).join('\n');

    const prompt = `Categorize estas transações bancárias brasileiras.

CATEGORIAS DISPONÍVEIS:

RECEITA:
${incomeCategories}

DESPESA:
${expenseCategories}

TRANSAÇÕES:
${formattedTransactions}

REGRAS:
- Use categorias existentes quando possível
- "Transferência Recebida/enviada" = Transferência
- "BRASIL GAS" = Gás e Combustível  
- "MERCADO" = Supermercado
- "PAGAR.ME" = Pagamentos

Responda APENAS com JSON:
{
  "categorizedTransactions": [
    {
      "index": 1,
      "suggestedCategory": "Nome da Categoria",
      "suggestedCategoryId": "id-da-categoria",
      "confidence": 0.9
    }
  ]
}`;

    const requestBody = {
        model: OLLAMA_MODEL,
        messages: [
            {
                role: 'system',
                content: 'Você categoriza transações financeiras. Responda APENAS em JSON válido, sem explicações.'
            },
            {
                role: 'user',
                content: prompt
            }
        ],
        stream: false,
        options: {
            temperature: 0.2,
            num_predict: 2000
        }
    };

    log(`\n📤 Enviando ${transactions.length} transações para IA...`, 'blue');
    log(`   Modelo: ${OLLAMA_MODEL}`, 'cyan');

    const startTime = Date.now();

    // Criar AbortController com timeout
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), TIMEOUT_MS);

    try {
        const response = await fetch(OLLAMA_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(requestBody),
            signal: controller.signal
        });

        clearTimeout(timeoutId);

        if (!response.ok) {
            throw new Error(`Erro na API Ollama: ${response.status} - ${await response.text()}`);
        }

        const data = await response.json();
        const elapsed = ((Date.now() - startTime) / 1000).toFixed(2);

        log(`   ⏱️  Tempo: ${elapsed}s`, 'green');

        const content = data.message?.content || '';

        // Extrair JSON da resposta
        let jsonMatch = content.match(/\{[\s\S]*\}/);
        if (!jsonMatch) {
            const codeBlockMatch = content.match(/```(?:json)?\s*([\s\S]*?)```/);
            if (codeBlockMatch) {
                jsonMatch = codeBlockMatch[1].match(/\{[\s\S]*\}/);
            }
        }

        if (!jsonMatch) {
            log('\n⚠️  Resposta bruta da IA:', 'yellow');
            console.log(content.substring(0, 500));
            throw new Error('Resposta não contém JSON válido');
        }

        return JSON.parse(jsonMatch[0]);
    } catch (err) {
        clearTimeout(timeoutId);
        if (err.name === 'AbortError') {
            throw new Error(`Timeout após ${TIMEOUT_MS / 1000}s aguardando resposta da IA`);
        }
        throw err;
    }
}

// Processar em lotes
async function categorizeInBatches(transactions, categories) {
    const allCategorized = [];
    const totalBatches = Math.ceil(transactions.length / BATCH_SIZE);

    log(`\n📦 Processando em ${totalBatches} lotes de ${BATCH_SIZE} transações`, 'blue');

    for (let i = 0; i < transactions.length; i += BATCH_SIZE) {
        const batch = transactions.slice(i, i + BATCH_SIZE);
        const batchNum = Math.floor(i / BATCH_SIZE) + 1;

        log(`\n🔄 Lote ${batchNum}/${totalBatches}`, 'cyan');

        try {
            const result = await categorizeWithAI(batch, categories);

            // Mesclar resultados com transações originais
            const categorizedBatch = result.categorizedTransactions || [];

            for (let j = 0; j < batch.length; j++) {
                const originalTx = batch[j];
                const aiResult = categorizedBatch.find(r => r.index === j + 1) || categorizedBatch[j];

                allCategorized.push({
                    ...originalTx,
                    suggestedCategory: aiResult?.suggestedCategory || 'Sem categoria',
                    suggestedCategoryId: aiResult?.suggestedCategoryId || null,
                    confidence: aiResult?.confidence || 0.5,
                    isNewCategory: !categories.find(c => c.id === aiResult?.suggestedCategoryId)
                });
            }

            log(`   ✅ ${batch.length} transações categorizadas`, 'green');

        } catch (err) {
            log(`   ❌ Erro no lote ${batchNum}: ${err.message}`, 'red');

            // Fallback: adicionar transações sem categorização
            for (const tx of batch) {
                allCategorized.push({
                    ...tx,
                    suggestedCategory: 'Não categorizado',
                    suggestedCategoryId: null,
                    confidence: 0,
                    isNewCategory: false,
                    error: err.message
                });
            }
        }

        // Pequena pausa entre lotes
        if (i + BATCH_SIZE < transactions.length) {
            await new Promise(resolve => setTimeout(resolve, 1000));
        }
    }

    return {
        categorizedTransactions: allCategorized,
        newCategories: []
    };
}

// Exibir resultados
function displayResults(result, originalTransactions) {
    logSection('📊 RESULTADOS DA CATEGORIZAÇÃO');

    const categorized = result.categorizedTransactions || [];

    if (categorized.length === 0) {
        log('⚠️  Nenhuma transação categorizada!', 'yellow');
        return;
    }

    // Estatísticas
    const incomeCount = categorized.filter(t => t.type === 'income').length;
    const expenseCount = categorized.filter(t => t.type === 'expense').length;
    const successCount = categorized.filter(t => t.suggestedCategory !== 'Não categorizado').length;
    const avgConfidence = categorized.reduce((sum, t) => sum + (t.confidence || 0), 0) / categorized.length;

    console.log('\n📈 Estatísticas:');
    log(`   Total: ${categorized.length} transações`, 'cyan');
    log(`   Receitas: ${incomeCount}`, 'green');
    log(`   Despesas: ${expenseCount}`, 'red');
    log(`   Categorizadas com sucesso: ${successCount}/${categorized.length}`, 'yellow');
    log(`   Confiança média: ${(avgConfidence * 100).toFixed(1)}%`, 'magenta');

    console.log('\n📋 Detalhamento por Transação:\n');

    categorized.forEach((t, i) => {
        const typeIcon = t.type === 'income' ? '💰' : '💸';
        const amountColor = t.type === 'income' ? 'green' : 'red';
        const confidence = ((t.confidence || 0) * 100).toFixed(0);

        console.log(`${typeIcon} ${i + 1}. ${t.date} - ${(t.description || '').substring(0, 50)}...`);
        log(`   Valor: R$ ${Math.abs(t.amount).toFixed(2)}`, amountColor);
        log(`   Categoria: ${t.suggestedCategory}`, t.suggestedCategory === 'Não categorizado' ? 'red' : 'cyan');
        log(`   Confiança: ${confidence}%`, 'magenta');
        console.log('');
    });

    // Resumo por categoria
    logSection('📈 RESUMO POR CATEGORIA');
    const byCategory = {};
    categorized.forEach(t => {
        const cat = t.suggestedCategory || 'Sem categoria';
        if (!byCategory[cat]) {
            byCategory[cat] = { count: 0, total: 0, type: t.type };
        }
        byCategory[cat].count++;
        byCategory[cat].total += Math.abs(t.amount);
    });

    Object.entries(byCategory)
        .sort((a, b) => b[1].total - a[1].total)
        .forEach(([cat, data]) => {
            const icon = data.type === 'income' ? '📥' : '📤';
            const color = data.type === 'income' ? 'green' : 'red';
            log(`${icon} ${cat}: ${data.count}x - R$ ${data.total.toFixed(2)}`, color);
        });
}

// Função principal
async function main() {
    logSection('🚀 TESTE DE IMPORTAÇÃO COM IA');
    log(`   Data: ${new Date().toLocaleDateString('pt-BR')}`, 'cyan');
    log(`   Pasta de extratos: ${DOCS_DIR}`, 'cyan');

    // Verificar se Ollama está disponível
    try {
        const healthCheck = await fetch('http://172.19.0.2:11434/api/tags');
        if (!healthCheck.ok) throw new Error('Ollama não responde');
        const models = await healthCheck.json();
        log('   ✅ Ollama conectado', 'green');
        log(`   Modelos: ${models.models?.map(m => m.name).join(', ') || 'nenhum'}`, 'cyan');
    } catch (err) {
        log('   ❌ Ollama não está disponível!', 'red');
        log('   Execute: docker start onlifin-ollama', 'yellow');
        process.exit(1);
    }

    // Listar arquivos na pasta docs - usar apenas OFX (tem IDs únicos)
    logSection('📁 ARQUIVOS ENCONTRADOS');

    const files = fs.readdirSync(DOCS_DIR).filter(f => f.endsWith('.ofx'));

    if (files.length === 0) {
        log('⚠️  Nenhum arquivo OFX encontrado, tentando CSV...', 'yellow');
        const csvFiles = fs.readdirSync(DOCS_DIR).filter(f => f.endsWith('.csv'));
        if (csvFiles.length > 0) {
            files.push(...csvFiles);
        }
    }

    if (files.length === 0) {
        log('❌ Nenhum arquivo CSV ou OFX encontrado em ' + DOCS_DIR, 'red');
        process.exit(1);
    }

    files.forEach(f => log(`   📄 ${f}`, 'cyan'));

    // Processar cada arquivo
    let allTransactions = [];

    for (const file of files) {
        const filePath = path.join(DOCS_DIR, file);
        const content = fs.readFileSync(filePath, 'utf-8');

        logSection(`📄 PROCESSANDO: ${file}`);

        let transactions = [];
        if (file.endsWith('.csv')) {
            transactions = parseCSV(content);
            log(`   Parser: CSV`, 'blue');
        } else if (file.endsWith('.ofx')) {
            transactions = parseOFX(content);
            log(`   Parser: OFX`, 'blue');
        }

        log(`   Transações encontradas: ${transactions.length}`, 'green');

        if (transactions.length > 0) {
            console.log('\n   Prévia das transações:');
            transactions.slice(0, 5).forEach((t, i) => {
                const typeIcon = t.type === 'income' ? '💰' : '💸';
                const amountColor = t.type === 'income' ? 'green' : 'red';
                log(`   ${typeIcon} ${t.date} | ${t.description.substring(0, 40)}... | R$ ${Math.abs(t.amount).toFixed(2)}`, amountColor);
            });
            if (transactions.length > 5) {
                log(`   ... e mais ${transactions.length - 5} transações`, 'cyan');
            }
        }

        allTransactions = allTransactions.concat(transactions);
    }

    // Remover duplicatas (pelo ID ou descrição+data+valor)
    const uniqueTransactions = [];
    const seen = new Set();

    for (const t of allTransactions) {
        const key = t.id || `${t.date}|${t.description}|${t.amount}`;
        if (!seen.has(key)) {
            seen.add(key);
            uniqueTransactions.push(t);
        }
    }

    logSection('🔄 CONSOLIDAÇÃO');
    log(`   Total de transações: ${allTransactions.length}`, 'cyan');
    log(`   Transações únicas: ${uniqueTransactions.length}`, 'green');
    if (allTransactions.length !== uniqueTransactions.length) {
        log(`   Duplicatas removidas: ${allTransactions.length - uniqueTransactions.length}`, 'yellow');
    }

    // Categorizar com IA em lotes
    logSection('🤖 CATEGORIZAÇÃO COM IA');

    try {
        const result = await categorizeInBatches(uniqueTransactions, defaultCategories);
        displayResults(result, uniqueTransactions);

        // Salvar resultado em arquivo JSON
        const outputPath = path.join(DOCS_DIR, 'resultado_categorizacao.json');
        fs.writeFileSync(outputPath, JSON.stringify(result, null, 2), 'utf-8');

        logSection('✅ TESTE CONCLUÍDO');
        log(`   Resultado salvo em: ${outputPath}`, 'green');

    } catch (error) {
        log(`\n❌ Erro na categorização: ${error.message}`, 'red');
        console.error(error);
        process.exit(1);
    }
}

// Executar
main().catch(err => {
    console.error('Erro fatal:', err);
    process.exit(1);
});
