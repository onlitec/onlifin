# TODO: Implementação de Importação e Categorização Automática de Extratos

## 📋 Requisitos

### 1. Importação de Extratos Bancários
- [ ] Criar página/componente para importação
- [ ] Suporte para upload de arquivo (CSV, OFX, QIF)
- [ ] Suporte para colar texto do extrato
- [ ] Parser para diferentes formatos de extrato

### 2. Análise e Categorização Automática
- [ ] Integração com AI Assistant
- [ ] Análise de cada transação:
  - Descrição da transação
  - Nome do estabelecimento
  - Valor e data
- [ ] Sugestão de categoria apropriada
- [ ] Matching com categorias existentes

### 3. Sugestão de Novas Categorias
- [ ] Identificar padrões que não se encaixam
- [ ] Sugerir criação de novas categorias
- [ ] Interface com checkboxes para aprovação
- [ ] Criar categorias aprovadas pelo usuário

### 4. Cadastro de Transações
- [ ] Botão "Cadastrar Transações"
- [ ] Criar categorias novas selecionadas
- [ ] Registrar transações em lote
- [ ] Atualizar saldos das contas

### 5. Interface Amigável
- [ ] Exibir extrato com categorias sugeridas
- [ ] Permitir edição manual antes do cadastro
- [ ] Visualização lado a lado
- [ ] Feedback visual claro

### 6. Extras (Opcional)
- [ ] Histórico de aprendizado
- [ ] Sugestões baseadas em padrões do usuário
- [ ] Melhorar precisão com feedback

## 🎯 Plano de Implementação

### Fase 1: Estrutura Básica
1. Criar página ImportStatements.tsx
2. Adicionar rota /import-statements
3. Criar componente de upload de arquivo
4. Criar componente de área de texto para colar

### Fase 2: Parser de Extratos
1. Implementar parser CSV
2. Implementar parser OFX (opcional)
3. Implementar parser QIF (opcional)
4. Normalizar dados para formato padrão

### Fase 3: Integração com IA
1. Atualizar Edge Function ai-assistant
2. Adicionar prompt para categorização
3. Enviar transações para análise
4. Receber sugestões de categorias

### Fase 4: Interface de Revisão
1. Criar tabela de transações com categorias sugeridas
2. Adicionar dropdowns para editar categorias
3. Adicionar checkboxes para novas categorias
4. Adicionar botão de cadastro

### Fase 5: Cadastro em Lote
1. Criar função de bulk insert
2. Criar categorias aprovadas
3. Inserir transações
4. Atualizar saldos

### Fase 6: Melhorias
1. Adicionar histórico de aprendizado
2. Melhorar sugestões com base em padrões
3. Adicionar feedback visual
4. Otimizar performance

## 📝 Notas Técnicas

### Formato de Dados

```typescript
interface ParsedTransaction {
  date: string;
  description: string;
  amount: number;
  type: 'income' | 'expense';
  merchant?: string;
}

interface CategorizedTransaction extends ParsedTransaction {
  suggestedCategory: string;
  suggestedCategoryId?: string;
  isNewCategory: boolean;
  confidence: number;
}
```

### API Endpoints Necessários

1. POST /functions/v1/ai-assistant
   - Payload: { action: 'categorize_transactions', transactions: ParsedTransaction[] }
   - Response: CategorizedTransaction[]

2. POST /rest/v1/transactions (bulk)
   - Payload: { transactions: Transaction[] }
   - Response: { success: boolean, count: number }

3. POST /rest/v1/categories (bulk)
   - Payload: { categories: Category[] }
   - Response: { success: boolean, categories: Category[] }

## 🚀 Próximos Passos

1. Começar com Fase 1: Estrutura Básica
2. Implementar upload de arquivo CSV
3. Criar parser básico
4. Integrar com IA para categorização
5. Criar interface de revisão
6. Implementar cadastro em lote
