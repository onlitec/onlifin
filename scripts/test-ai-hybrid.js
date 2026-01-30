#!/usr/bin/env node
/**
 * Teste de Categorização Híbrida: Regras + IA
 * 
 * Este script demonstra o fluxo ideal:
 * 1. Primeiro aplica regras de palavras-chave (rápido e preciso)
 * 2. Depois usa IA apenas para transações não identificadas
 * 
 * Uso: node scripts/test-ai-hybrid.js
 */

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Configurações
const OLLAMA_URL = 'http://172.19.0.2:11434/api/chat';
const OLLAMA_MODEL = 'qwen2.5:0.5b';
const DOCS_DIR = path.join(__dirname, '..', 'docs');
const TIMEOUT_MS = 120000;

// Cores
const c = {
    reset: '\x1b[0m', bold: '\x1b[1m', green: '\x1b[32m',
    yellow: '\x1b[33m', blue: '\x1b[34m', red: '\x1b[31m',
    cyan: '\x1b[36m', magenta: '\x1b[35m'
};
const log = (msg, color = 'reset') => console.log(`${c[color]}${msg}${c.reset}`);
const section = (title) => {
    console.log('\n' + '═'.repeat(60));
    log(`  ${title}`, 'bold');
    console.log('═'.repeat(60));
};

// ==========================================
// REGRAS DE PALAVRAS-CHAVE (baseadas nos extratos)
// ==========================================
const keywordRules = [
    // Gás e Combustível
    { keyword: 'BRASIL GAS', categoryId: 'cat-gas', categoryName: 'Gás e Combustível', type: 'expense', matchType: 'contains' },
    { keyword: 'POSTO', categoryId: 'cat-gas', categoryName: 'Gás e Combustível', type: 'expense', matchType: 'contains' },
    { keyword: 'SHELL', categoryId: 'cat-gas', categoryName: 'Gás e Combustível', type: 'expense', matchType: 'contains' },
    { keyword: 'IPIRANGA', categoryId: 'cat-gas', categoryName: 'Gás e Combustível', type: 'expense', matchType: 'contains' },

    // Supermercado
    { keyword: 'MERCADO', categoryId: 'cat-mercado', categoryName: 'Supermercado', type: 'expense', matchType: 'contains' },
    { keyword: 'SUPERMERCADO', categoryId: 'cat-mercado', categoryName: 'Supermercado', type: 'expense', matchType: 'contains' },
    { keyword: 'CARREFOUR', categoryId: 'cat-mercado', categoryName: 'Supermercado', type: 'expense', matchType: 'contains' },
    { keyword: 'ATACADAO', categoryId: 'cat-mercado', categoryName: 'Supermercado', type: 'expense', matchType: 'contains' },

    // Pagamentos Online
    { keyword: 'PAGAR.ME', categoryId: 'cat-pagamentos', categoryName: 'Pagamentos', type: 'expense', matchType: 'contains' },
    { keyword: 'PAGSEGURO', categoryId: 'cat-pagamentos', categoryName: 'Pagamentos', type: 'expense', matchType: 'contains' },
    { keyword: 'MERCADOPAGO', categoryId: 'cat-pagamentos', categoryName: 'Pagamentos', type: 'expense', matchType: 'contains' },
    { keyword: 'PICPAY', categoryId: 'cat-pagamentos', categoryName: 'Pagamentos', type: 'expense', matchType: 'contains' },

    // Delivery/Alimentação
    { keyword: 'IFOOD', categoryId: 'cat-alimentacao', categoryName: 'Alimentação', type: 'expense', matchType: 'contains' },
    { keyword: 'UBER EATS', categoryId: 'cat-alimentacao', categoryName: 'Alimentação', type: 'expense', matchType: 'contains' },
    { keyword: 'RAPPI', categoryId: 'cat-alimentacao', categoryName: 'Alimentação', type: 'expense', matchType: 'contains' },

    // Transporte
    { keyword: 'UBER ', categoryId: 'cat-transporte', categoryName: 'Transporte', type: 'expense', matchType: 'contains' },
    { keyword: '99 APP', categoryId: 'cat-transporte', categoryName: 'Transporte', type: 'expense', matchType: 'contains' },
    { keyword: '99POP', categoryId: 'cat-transporte', categoryName: 'Transporte', type: 'expense', matchType: 'contains' },

    // Assinaturas
    { keyword: 'NETFLIX', categoryId: 'cat-assinaturas', categoryName: 'Assinaturas e Serviços', type: 'expense', matchType: 'contains' },
    { keyword: 'SPOTIFY', categoryId: 'cat-assinaturas', categoryName: 'Assinaturas e Serviços', type: 'expense', matchType: 'contains' },
    { keyword: 'AMAZON PRIME', categoryId: 'cat-assinaturas', categoryName: 'Assinaturas e Serviços', type: 'expense', matchType: 'contains' },
    { keyword: 'DISNEY', categoryId: 'cat-assinaturas', categoryName: 'Assinaturas e Serviços', type: 'expense', matchType: 'contains' },

    // Transferências - ordem importa! Mais específico primeiro
    { keyword: 'Transferência Recebida', categoryId: 'cat-transferencia-entrada', categoryName: 'Transferência Recebida', type: 'income', matchType: 'starts_with' },
    { keyword: 'Transferência recebida', categoryId: 'cat-transferencia-entrada', categoryName: 'Transferência Recebida', type: 'income', matchType: 'contains' },
    { keyword: 'Transferência enviada', categoryId: 'cat-transferencia-saida', categoryName: 'Transferência Enviada', type: 'expense', matchType: 'contains' },
    { keyword: 'PIX recebido', categoryId: 'cat-transferencia-entrada', categoryName: 'Transferência Recebida', type: 'income', matchType: 'contains' },
    { keyword: 'PIX enviado', categoryId: 'cat-transferencia-saida', categoryName: 'Transferência Enviada', type: 'expense', matchType: 'contains' },

    // Compras genéricas
    { keyword: 'Compra no débito', categoryId: 'cat-compras', categoryName: 'Compras Gerais', type: 'expense', matchType: 'starts_with' },
    { keyword: 'Compra no crédito', categoryId: 'cat-compras', categoryName: 'Compras Gerais', type: 'expense', matchType: 'starts_with' },
];

// Categorias disponíveis
const categories = [
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

            let formattedDate = dateRaw;
            if (dateRaw.length >= 8) {
                formattedDate = `${dateRaw.substring(6, 8)}/${dateRaw.substring(4, 6)}/${dateRaw.substring(0, 4)}`;
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

// Aplicar regras de palavras-chave
function applyKeywordRules(transactions) {
    return transactions.map(t => {
        const descUpper = (t.description || '').toUpperCase();

        for (const rule of keywordRules) {
            const keyword = rule.keyword.toUpperCase();
            let matches = false;

            if (rule.matchType === 'exact') {
                matches = descUpper === keyword;
            } else if (rule.matchType === 'starts_with') {
                matches = descUpper.startsWith(keyword);
            } else { // contains
                matches = descUpper.includes(keyword);
            }

            if (matches) {
                return {
                    ...t,
                    suggestedCategory: rule.categoryName,
                    suggestedCategoryId: rule.categoryId,
                    confidence: 1.0, // 100% para regras
                    matchedByRule: true,
                    matchedKeyword: rule.keyword
                };
            }
        }

        return { ...t, matchedByRule: false };
    });
}

// Categorizar com IA (apenas transações não identificadas)
async function categorizeWithAI(transactions) {
    if (transactions.length === 0) return [];

    const formattedTransactions = transactions.map((t, i) =>
        `${i + 1}. ${t.description.substring(0, 50)} | R$ ${Math.abs(t.amount).toFixed(2)} | ${t.type === 'income' ? 'RECEITA' : 'DESPESA'}`
    ).join('\n');

    const catList = categories.map(c => `- ${c.name} (${c.type})`).join('\n');

    const prompt = `Categorize estas transações usando APENAS estas categorias:

CATEGORIAS:
${catList}

TRANSAÇÕES:
${formattedTransactions}

Responda em JSON: {"results": [{"index": 1, "category": "Nome da Categoria", "confidence": 0.9}]}`;

    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), TIMEOUT_MS);

    try {
        const response = await fetch(OLLAMA_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                model: OLLAMA_MODEL,
                messages: [
                    { role: 'system', content: 'Categorize transações financeiras. Responda apenas em JSON.' },
                    { role: 'user', content: prompt }
                ],
                stream: false,
                options: { temperature: 0.2, num_predict: 1000 }
            }),
            signal: controller.signal
        });

        clearTimeout(timeoutId);

        const data = await response.json();
        const content = data.message?.content || '';
        const jsonMatch = content.match(/\{[\s\S]*\}/);

        if (jsonMatch) {
            const result = JSON.parse(jsonMatch[0]);
            return result.results || [];
        }
    } catch (err) {
        clearTimeout(timeoutId);
        log(`   ⚠️ Erro na IA: ${err.message}`, 'yellow');
    }

    return [];
}

// Função principal
async function main() {
    section('🧪 TESTE DE CATEGORIZAÇÃO HÍBRIDA');
    log('   Estratégia: Regras de Palavras-Chave + IA', 'cyan');

    // Carregar OFX
    const ofxFile = fs.readdirSync(DOCS_DIR).find(f => f.endsWith('.ofx'));
    if (!ofxFile) {
        log('❌ Nenhum arquivo OFX encontrado', 'red');
        process.exit(1);
    }

    const content = fs.readFileSync(path.join(DOCS_DIR, ofxFile), 'utf-8');
    const transactions = parseOFX(content);

    log(`\n📄 Arquivo: ${ofxFile}`, 'cyan');
    log(`📊 Transações: ${transactions.length}`, 'cyan');

    // Etapa 1: Aplicar regras
    section('🔑 ETAPA 1: REGRAS DE PALAVRAS-CHAVE');

    const processed = applyKeywordRules(transactions);
    const byRules = processed.filter(t => t.matchedByRule);
    const needsAI = processed.filter(t => !t.matchedByRule);

    log(`   ✅ Categorizadas por regras: ${byRules.length}`, 'green');
    log(`   🤖 Precisam de IA: ${needsAI.length}`, 'yellow');

    console.log('\n   Transações categorizadas por regras:');
    byRules.forEach(t => {
        const icon = t.type === 'income' ? '💰' : '💸';
        const color = t.type === 'income' ? 'green' : 'red';
        log(`   ${icon} "${t.description.substring(0, 40)}..." → ${t.suggestedCategory}`, color);
        log(`      Keyword: "${t.matchedKeyword}"`, 'blue');
    });

    // Etapa 2: IA para transações não identificadas
    if (needsAI.length > 0) {
        section('🤖 ETAPA 2: CATEGORIZAÇÃO COM IA');
        log(`   Enviando ${needsAI.length} transações...`, 'blue');

        const startTime = Date.now();
        const aiResults = await categorizeWithAI(needsAI);
        const elapsed = ((Date.now() - startTime) / 1000).toFixed(2);

        log(`   ⏱️ Tempo: ${elapsed}s`, 'green');

        // Mesclar resultados da IA
        for (let i = 0; i < needsAI.length; i++) {
            const aiResult = aiResults.find(r => r.index === i + 1) || aiResults[i];
            if (aiResult) {
                const cat = categories.find(c => c.name === aiResult.category);
                needsAI[i].suggestedCategory = aiResult.category || 'Não categorizado';
                needsAI[i].suggestedCategoryId = cat?.id || null;
                needsAI[i].confidence = aiResult.confidence || 0.5;
            } else {
                needsAI[i].suggestedCategory = 'Não categorizado';
                needsAI[i].confidence = 0;
            }
        }

        console.log('\n   Transações categorizadas pela IA:');
        needsAI.forEach(t => {
            const icon = t.type === 'income' ? '💰' : '💸';
            const color = t.suggestedCategory === 'Não categorizado' ? 'yellow' : (t.type === 'income' ? 'green' : 'red');
            log(`   ${icon} "${t.description.substring(0, 40)}..." → ${t.suggestedCategory}`, color);
        });
    }

    // Resultado final
    section('📊 RESULTADO FINAL');

    const allCategorized = [...byRules, ...needsAI];
    const successCount = allCategorized.filter(t => t.suggestedCategory !== 'Não categorizado').length;

    console.log('\n📈 Estatísticas:');
    log(`   Total: ${allCategorized.length} transações`, 'cyan');
    log(`   Por regras: ${byRules.length} (${(byRules.length / allCategorized.length * 100).toFixed(0)}%)`, 'green');
    log(`   Por IA: ${needsAI.length - allCategorized.filter(t => t.suggestedCategory === 'Não categorizado').length}`, 'blue');
    log(`   Não categorizadas: ${allCategorized.length - successCount}`, 'yellow');

    // Resumo por categoria
    console.log('\n📈 Por Categoria:');
    const byCategory = {};
    allCategorized.forEach(t => {
        const cat = t.suggestedCategory || 'Sem categoria';
        if (!byCategory[cat]) byCategory[cat] = { count: 0, total: 0 };
        byCategory[cat].count++;
        byCategory[cat].total += Math.abs(t.amount);
    });

    Object.entries(byCategory)
        .sort((a, b) => b[1].total - a[1].total)
        .forEach(([cat, data]) => {
            log(`   • ${cat}: ${data.count}x - R$ ${data.total.toFixed(2)}`, 'cyan');
        });

    // Salvar resultado
    const outputPath = path.join(DOCS_DIR, 'resultado_hibrido.json');
    fs.writeFileSync(outputPath, JSON.stringify({
        metadata: {
            date: new Date().toISOString(),
            totalTransactions: allCategorized.length,
            categorizedByRules: byRules.length,
            categorizedByAI: needsAI.filter(t => t.suggestedCategory !== 'Não categorizado').length,
            uncategorized: allCategorized.filter(t => t.suggestedCategory === 'Não categorizado').length
        },
        transactions: allCategorized,
        rulesUsed: keywordRules.length
    }, null, 2), 'utf-8');

    section('✅ TESTE CONCLUÍDO');
    log(`   Resultado: ${outputPath}`, 'green');
}

main().catch(err => {
    console.error('Erro:', err);
    process.exit(1);
});
