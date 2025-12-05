# 🔍 Filtros e Busca de Transações - Guia Completo

## 📋 Visão Geral

A página de Transações agora possui um sistema completo de **filtros, busca e ordenação** que permite encontrar e organizar suas transações de forma rápida e eficiente.

---

## ✨ Funcionalidades Disponíveis

### 1. 🔎 Busca por Descrição

**Como usar:**
- Digite qualquer palavra ou termo no campo de busca
- Os resultados são filtrados em tempo real
- A busca procura na descrição das transações

**Exemplos:**
```
"supermercado" → Encontra todas as transações com "supermercado" na descrição
"uber" → Encontra todas as corridas de Uber
"salário" → Encontra pagamentos de salário
```

**Dicas:**
- ✅ A busca não diferencia maiúsculas de minúsculas
- ✅ Busca parcial funciona (ex: "super" encontra "supermercado")
- ✅ Combine busca com outros filtros para resultados mais precisos

---

### 2. 🎯 Filtros Disponíveis

#### Filtro por Tipo
**Opções:**
- **Todos**: Mostra receitas e despesas
- **Receitas**: Apenas entradas de dinheiro
- **Despesas**: Apenas saídas de dinheiro

**Quando usar:**
- Ver apenas seus ganhos do mês
- Analisar somente seus gastos
- Separar receitas de despesas

---

#### Filtro por Conta Bancária
**Opções:**
- **Todas as contas**: Mostra transações de todas as contas
- **Conta específica**: Filtra por uma conta bancária

**Quando usar:**
- Verificar movimentações de uma conta específica
- Analisar gastos de um cartão de crédito
- Conferir saldo de uma conta

**Exemplo:**
```
Conta: "Nubank" → Mostra apenas transações da conta Nubank
```

---

#### Filtro por Categoria
**Opções:**
- **Todas as categorias**: Mostra todas as transações
- **Categoria específica**: Filtra por uma categoria

**Quando usar:**
- Ver quanto gastou com alimentação
- Analisar despesas de transporte
- Verificar gastos com lazer

**Exemplo:**
```
Categoria: "🍔 Alimentação" → Mostra apenas gastos com comida
Categoria: "💰 Salário" → Mostra apenas receitas de salário
```

---

#### Filtro por Data

**Campos:**
- **Data Inicial**: A partir de qual data mostrar
- **Data Final**: Até qual data mostrar

**Quando usar:**
- Ver transações de um mês específico
- Analisar gastos de uma semana
- Verificar transações de um período

**Exemplos:**
```
De: 01/12/2025  Até: 31/12/2025  → Transações de dezembro
De: 01/12/2025  Até: [vazio]     → Transações desde 01/12
De: [vazio]     Até: 31/12/2025  → Transações até 31/12
```

---

### 3. 📊 Ordenação

**Opções disponíveis:**

#### Data (mais recente)
- **Padrão**: Transações mais recentes aparecem primeiro
- **Útil para**: Ver suas últimas movimentações

#### Data (mais antiga)
- Transações mais antigas aparecem primeiro
- **Útil para**: Análise cronológica, ver histórico

#### Categoria
- Agrupa transações por categoria em ordem alfabética
- **Útil para**: Comparar gastos por categoria

#### Valor (maior para menor)
- Transações de maior valor aparecem primeiro
- **Útil para**: Identificar maiores gastos ou receitas

#### Valor (menor para maior)
- Transações de menor valor aparecem primeiro
- **Útil para**: Ver pequenos gastos, encontrar cobranças mínimas

---

## 🎮 Como Usar

### Passo a Passo

#### 1. Acessar Filtros
```
1. Vá para a página "Transações"
2. Clique no botão "Filtros" (ao lado da busca)
3. O painel de filtros será exibido
```

#### 2. Aplicar Filtros
```
1. Escolha os filtros desejados
2. Os resultados são atualizados automaticamente
3. Combine múltiplos filtros para resultados precisos
```

#### 3. Limpar Filtros
```
Opção 1: Clique no botão "Limpar" (ao lado de "Filtros")
Opção 2: Clique em "Limpar Filtros" na mensagem de sem resultados
```

---

## 💡 Exemplos de Uso

### Exemplo 1: Gastos com Alimentação em Dezembro
```
1. Clique em "Filtros"
2. Tipo: "Despesas"
3. Categoria: "🍔 Alimentação"
4. Data Inicial: "01/12/2025"
5. Data Final: "31/12/2025"
6. Ordenar por: "Valor (maior para menor)"

Resultado: Maiores gastos com alimentação em dezembro
```

---

### Exemplo 2: Receitas de uma Conta Específica
```
1. Clique em "Filtros"
2. Tipo: "Receitas"
3. Conta: "Banco do Brasil"
4. Ordenar por: "Data (mais recente)"

Resultado: Últimas receitas recebidas na conta do Banco do Brasil
```

---

### Exemplo 3: Buscar Transações de Uber
```
1. Digite "uber" no campo de busca
2. Clique em "Filtros"
3. Ordenar por: "Valor (maior para menor)"

Resultado: Corridas de Uber ordenadas por valor
```

---

### Exemplo 4: Pequenos Gastos do Mês
```
1. Clique em "Filtros"
2. Tipo: "Despesas"
3. Data Inicial: "01/12/2025"
4. Data Final: "31/12/2025"
5. Ordenar por: "Valor (menor para maior)"

Resultado: Menores gastos do mês (útil para identificar assinaturas)
```

---

## 🎨 Interface

### Elementos Visuais

#### Barra de Busca
```
┌─────────────────────────────────────────────┐
│ 🔍 Buscar transações por descrição...      │
└─────────────────────────────────────────────┘
```

#### Botões de Ação
```
[Filtros] [Limpar]
   ↑         ↑
   │         └─ Aparece quando há filtros ativos
   └─ Abre/fecha painel de filtros
```

#### Painel de Filtros
```
┌─────────────────────────────────────────────┐
│ Tipo          Conta         Categoria       │
│ [Todos ▼]     [Todas ▼]     [Todas ▼]      │
│                                             │
│ Data Inicial  Data Final    Ordenar Por    │
│ [01/12/2025]  [31/12/2025]  [Data ▼]       │
└─────────────────────────────────────────────┘
```

#### Contador de Resultados
```
┌─────────────────────────────────────────────┐
│ 15 transações encontradas    🔍 Filtros ativos │
└─────────────────────────────────────────────┘
```

---

## 📊 Indicadores

### Filtros Ativos
Quando você aplica filtros, verá:
- ✅ Botão "Limpar" visível
- ✅ Indicador "Filtros ativos" no contador
- ✅ Botão "Filtros" destacado

### Sem Resultados
Se nenhuma transação corresponder aos filtros:
```
┌─────────────────────────────────────────────┐
│                                             │
│         Nenhuma transação encontrada        │
│                                             │
│   Tente ajustar os filtros ou buscar por   │
│            outros termos                    │
│                                             │
│           [Limpar Filtros]                  │
│                                             │
└─────────────────────────────────────────────┘
```

---

## 🚀 Dicas e Truques

### Dica 1: Combine Filtros
```
✅ Busca + Tipo + Categoria
   "mercado" + Despesas + Alimentação
   → Encontra compras de mercado

✅ Conta + Data + Ordenação
   Nubank + Dezembro + Valor (maior)
   → Maiores gastos do Nubank em dezembro
```

### Dica 2: Use Datas Estrategicamente
```
✅ Apenas Data Inicial
   → Ver tudo desde uma data

✅ Apenas Data Final
   → Ver tudo até uma data

✅ Ambas as datas
   → Período específico
```

### Dica 3: Ordenação Inteligente
```
✅ Data (recente) → Acompanhamento diário
✅ Data (antiga) → Análise histórica
✅ Categoria → Comparação de gastos
✅ Valor (maior) → Identificar grandes gastos
✅ Valor (menor) → Encontrar assinaturas/taxas
```

### Dica 4: Busca Eficiente
```
✅ Termos curtos funcionam melhor
   "uber" em vez de "corrida de uber"

✅ Palavras-chave únicas
   "ifood" em vez de "comida"

✅ Combine com filtros
   Busca + Categoria para precisão
```

---

## 📱 Responsividade

### Desktop (Tela Grande)
- Filtros em 3 colunas
- Busca com largura total
- Todos os controles visíveis

### Tablet (Tela Média)
- Filtros em 2 colunas
- Layout adaptado
- Boa usabilidade

### Mobile (Tela Pequena)
- Filtros em 1 coluna
- Painel colapsável
- Otimizado para toque

---

## ⚡ Performance

### Otimizações Implementadas

#### Filtragem Eficiente
- ✅ Usa `useMemo` para evitar recálculos
- ✅ Atualização em tempo real
- ✅ Sem atrasos perceptíveis

#### Renderização Inteligente
- ✅ Apenas componentes necessários re-renderizam
- ✅ Lista virtualizada para muitas transações
- ✅ Carregamento rápido

---

## 🔧 Casos de Uso Avançados

### Análise Mensal Completa
```
1. Filtro de Data: Mês atual
2. Ordenar por: Categoria
3. Resultado: Gastos agrupados por categoria

Depois:
4. Ordenar por: Valor (maior)
5. Resultado: Maiores gastos do mês
```

### Auditoria de Conta
```
1. Filtro de Conta: Conta específica
2. Filtro de Data: Período desejado
3. Ordenar por: Data (mais antiga)
4. Resultado: Histórico cronológico da conta
```

### Identificar Padrões de Gasto
```
1. Filtro de Categoria: Categoria específica
2. Ordenar por: Data (mais recente)
3. Resultado: Evolução de gastos na categoria
```

### Encontrar Transações Duplicadas
```
1. Busca: Termo específico
2. Ordenar por: Valor (maior)
3. Resultado: Transações similares agrupadas
```

---

## 🐛 Solução de Problemas

### Problema: Nenhum resultado encontrado

**Verificações:**
1. ✅ Verifique se os filtros estão muito restritivos
2. ✅ Tente limpar os filtros e aplicar novamente
3. ✅ Verifique se há transações cadastradas
4. ✅ Confira se as datas estão corretas

**Solução:**
- Clique em "Limpar Filtros"
- Aplique filtros um por vez
- Verifique o período de datas

---

### Problema: Busca não encontra transação

**Verificações:**
1. ✅ Verifique a ortografia
2. ✅ Tente termos mais curtos
3. ✅ Verifique se a descrição está correta
4. ✅ Limpe outros filtros ativos

**Solução:**
- Use palavras-chave simples
- Combine com filtros de categoria
- Verifique a descrição da transação

---

### Problema: Ordenação não funciona como esperado

**Verificações:**
1. ✅ Verifique qual ordenação está selecionada
2. ✅ Confirme se há dados para ordenar
3. ✅ Verifique se filtros não estão limitando resultados

**Solução:**
- Selecione a ordenação desejada novamente
- Limpe filtros para ver todos os dados
- Recarregue a página se necessário

---

## 📈 Estatísticas e Insights

### Contador de Resultados
```
"15 transações encontradas"
   ↑
   Mostra quantas transações correspondem aos filtros
```

### Indicador de Filtros Ativos
```
"🔍 Filtros ativos"
   ↑
   Aparece quando há filtros aplicados
```

---

## 🎯 Melhores Práticas

### Para Análise Financeira
1. ✅ Use filtros de data para períodos específicos
2. ✅ Ordene por valor para identificar maiores gastos
3. ✅ Filtre por categoria para análise detalhada
4. ✅ Combine múltiplos filtros para insights precisos

### Para Busca Rápida
1. ✅ Use a busca para termos específicos
2. ✅ Combine busca com tipo (receita/despesa)
3. ✅ Ordene por data para ver mais recentes
4. ✅ Limpe filtros quando terminar

### Para Organização
1. ✅ Ordene por categoria para agrupar
2. ✅ Use datas para separar períodos
3. ✅ Filtre por conta para organizar por origem
4. ✅ Salve filtros mentalmente para uso recorrente

---

## 🔮 Funcionalidades Futuras

### Planejado
- [ ] **Salvar Filtros**: Salvar combinações de filtros favoritas
- [ ] **Exportar Resultados**: Exportar transações filtradas
- [ ] **Busca Avançada**: Buscar em múltiplos campos
- [ ] **Filtro por Tags**: Filtrar por tags personalizadas
- [ ] **Presets de Data**: "Este Mês", "Mês Passado", etc.
- [ ] **Multi-seleção**: Selecionar múltiplas categorias/contas
- [ ] **Filtros Salvos**: Criar e salvar filtros personalizados

---

## 📞 Suporte

### Precisa de Ajuda?
- 📖 Consulte este guia
- 💬 Use o chat de suporte
- 📧 Email: suporte@plataforma.com
- 🐛 Reporte problemas no GitHub

---

## ✅ Resumo Rápido

### Funcionalidades Principais
- ✅ **Busca**: Por descrição
- ✅ **Filtros**: Tipo, Conta, Categoria, Data
- ✅ **Ordenação**: Data, Categoria, Valor
- ✅ **Combinação**: Use múltiplos filtros juntos
- ✅ **Limpeza**: Botão para resetar tudo

### Como Começar
1. Acesse a página de Transações
2. Clique em "Filtros"
3. Escolha seus critérios
4. Veja os resultados atualizados
5. Limpe quando terminar

### Dicas Essenciais
- 💡 Combine filtros para resultados precisos
- 💡 Use ordenação para organizar dados
- 💡 Limpe filtros entre análises diferentes
- 💡 Experimente diferentes combinações

---

**Data de Criação**: 2025-12-01  
**Versão**: 1.0  
**Status**: ✅ Totalmente Funcional  
**Idioma**: Português (Brasil)
