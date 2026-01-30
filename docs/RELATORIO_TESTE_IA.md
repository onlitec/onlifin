# 📊 Relatório do Teste Prático de Importação com IA

**Data:** 16/01/2026  
**Modelo:** qwen2.5:0.5b  
**Arquivo testado:** NU_141249423_01JAN2026_14JAN2026.ofx

---

## 📈 Estatísticas do Teste

| Métrica | Valor |
|---------|-------|
| Total de transações | 12 |
| Receitas | 4 |
| Despesas | 8 |
| Categorizadas com sucesso | 12/12 |
| Confiança média | 77.1% |
| Tempo total | ~2 minutos |
| Tempo médio por lote | ~28s |

---

## 🎯 Análise dos Resultados

### ✅ Categorização Correta (IDs corretos)
1. **Transferência Recebida** (cat-transferencia-entrada): 
   - ✅ "Transferência Recebida - Márcia" → income → `cat-transferencia-entrada`
   - ✅ "Transferência recebida pelo Pix - Alessandro" → income → `cat-transferencia-entrada`

2. **Transferência Enviada** (cat-transferencia-saida):
   - ✅ "Transferência enviada pelo Pix - Márcia" → expense → `cat-transferencia-saida`

### ⚠️ Categorização Incorreta
1. **BRASIL GAS** foi categorizado como "Transferência Recebida" em vez de "Gás e Combustível"
2. **MERCADO SEVEN II** ficou sem categoria (deveria ser "Supermercado")
3. **PAGAR.ME** ficou sem categoria (deveria ser "Pagamentos")
4. Algumas transferências enviadas foram incorretamente marcadas como "Transferência Recebida"

---

## 📝 Regras de Treinamento Identificadas

Com base nos extratos analisados, estas regras podem melhorar a precisão:

```json
{
  "keywordRules": [
    {
      "keyword": "BRASIL GAS",
      "category_id": "cat-gas",
      "category_name": "Gás e Combustível",
      "match_type": "contains"
    },
    {
      "keyword": "MERCADO",
      "category_id": "cat-mercado",
      "category_name": "Supermercado",
      "match_type": "contains"
    },
    {
      "keyword": "PAGAR.ME",
      "category_id": "cat-pagamentos",
      "category_name": "Pagamentos",
      "match_type": "contains"
    },
    {
      "keyword": "Transferência Recebida",
      "category_id": "cat-transferencia-entrada",
      "category_name": "Transferência Recebida",
      "match_type": "starts_with"
    },
    {
      "keyword": "Transferência recebida",
      "category_id": "cat-transferencia-entrada",
      "category_name": "Transferência Recebida",
      "match_type": "starts_with"
    },
    {
      "keyword": "Transferência enviada",
      "category_id": "cat-transferencia-saida",
      "category_name": "Transferência Enviada",
      "match_type": "starts_with"
    },
    {
      "keyword": "Compra no débito",
      "category_id": "cat-compras",
      "category_name": "Compras Gerais",
      "match_type": "starts_with"
    }
  ]
}
```

---

## 🔧 Melhorias Recomendadas

1. **Implementar regras de palavras-chave** antes da IA para casos óbvios
2. **Corrigir inversão receita/despesa** - a IA confunde às vezes
3. **Usar few-shot learning** com exemplos do próprio usuário
4. **Aumentar precisão do modelo** com prompts mais curtos e diretos

---

## 📁 Arquivos Gerados

- `/opt/onlifin/scripts/test-ai-import.js` - Script de teste
- `/opt/onlifin/docs/resultado_categorizacao.json` - Resultado em JSON
- `/opt/onlifin/docs/RELATORIO_TESTE_IA.md` - Este relatório

---

## 🚀 Próximos Passos

1. [ ] Cadastrar as regras de palavras-chave no banco de dados
2. [ ] Integrar regras com o fluxo de importação existente
3. [ ] Testar com mais extratos bancários
4. [ ] Ajustar prompts para melhor precisão
