# Sistema de Transferências entre Contas

## Visão Geral
O sistema de transferências permite que usuários transfiram valores entre contas cadastradas na plataforma. Cada transferência cria automaticamente duas transações vinculadas: uma despesa na conta de origem e uma receita na conta de destino.

## Funcionalidades Implementadas

### 1. Banco de Dados
- **Tipo de Transação**: Adicionado 'transfer' ao ENUM `transaction_type`
- **Campos Novos**:
  - `is_transfer`: Booleano que marca transações de transferência
  - `transfer_destination_account_id`: UUID da conta de destino
- **Índices**: Criados para otimizar consultas de transferências
- **Funções RPC**:
  - `create_transfer()`: Cria transferência atômica com validação
  - `get_transfer_pair()`: Recupera par de transações vinculadas

### 2. API
- `createTransfer()`: Cria transferência entre contas
- `getTransferPair()`: Obtém detalhes da transferência vinculada

### 3. Interface do Usuário

#### Formulário de Transação
- **Tipo "Transferência"**: Nova opção no seletor de tipo
- **Campos Condicionais**:
  - "Conta de Origem": Exibido quando tipo é transferência
  - "Conta de Destino": Exibido apenas para transferências
  - "Categoria": Oculto para transferências
  - "Cartão": Oculto para transferências
  - "Recorrente": Oculto para transferências
  - "Parcelamento": Oculto para transferências

#### Validações
- Conta de origem é obrigatória
- Conta de destino é obrigatória
- Contas de origem e destino devem ser diferentes
- Transferências não podem ser editadas (devem ser excluídas e recriadas)

#### Lista de Transações
- **Ícone**: ArrowRightLeft (⇄) para transferências
- **Cor**: Azul primário (diferente de receitas e despesas)
- **Descrição**: Mostra "Transferência: [Origem] → [Destino]"
- **Valor**: Exibido sem sinal de + ou -
- **Ações**: Botão de edição oculto (apenas exclusão disponível)

#### Filtros
- **Novo Filtro**: "Transferências" adicionado ao filtro de tipo
- **Lógica**: Filtra apenas transações marcadas como transferência
- **Exclusão Mútua**: Transferências não aparecem em filtros de receitas/despesas

## Fluxo de Criação de Transferência

1. Usuário seleciona tipo "Transferência"
2. Interface exibe campos de origem e destino
3. Usuário preenche:
   - Conta de origem
   - Conta de destino
   - Valor
   - Data
   - Descrição (opcional)
4. Sistema valida:
   - Contas são diferentes
   - Ambas as contas existem
   - Valor é positivo
5. RPC `create_transfer()` cria duas transações:
   - Transação 1 (despesa): Debita da conta de origem
   - Transação 2 (receita): Credita na conta de destino
6. Ambas as transações são vinculadas via `parent_transaction_id`
7. Ambas são marcadas com `is_transfer = true`

## Estrutura de Dados

### Transação de Transferência (Origem)
```typescript
{
  id: "uuid-1",
  type: "expense",
  is_transfer: true,
  account_id: "conta-origem-id",
  transfer_destination_account_id: "conta-destino-id",
  amount: 100.00,
  parent_transaction_id: null,
  // ... outros campos
}
```

### Transação de Transferência (Destino)
```typescript
{
  id: "uuid-2",
  type: "income",
  is_transfer: true,
  account_id: "conta-destino-id",
  transfer_destination_account_id: null,
  amount: 100.00,
  parent_transaction_id: "uuid-1",
  // ... outros campos
}
```

## Regras de Negócio

1. **Atomicidade**: Transferências são criadas em uma única transação de banco de dados
2. **Integridade**: Se uma transação falhar, ambas são revertidas
3. **Vinculação**: Transações de transferência são sempre vinculadas
4. **Imutabilidade**: Transferências não podem ser editadas, apenas excluídas
5. **Exclusão em Cascata**: Ao excluir uma transferência, ambas as transações são removidas
6. **Saldo**: Transferências afetam o saldo de ambas as contas automaticamente

## Tipos TypeScript

```typescript
export type TransactionType = 'income' | 'expense' | 'transfer';
export type CategoryType = 'income' | 'expense'; // Categorias não incluem transfer

export interface Transaction {
  // ... campos existentes
  is_transfer: boolean;
  transfer_destination_account_id: string | null;
}
```

## Arquivos Modificados

1. `supabase/migrations/00008_add_transfer_support.sql` - Schema e funções
2. `src/types/types.ts` - Tipos TypeScript
3. `src/db/api.ts` - Funções de API
4. `src/pages/Transactions.tsx` - Interface do usuário
5. `src/pages/Import.tsx` - Suporte a importação
6. `src/pages/Categories.tsx` - Correção de tipos

## Testes Recomendados

1. **Criar Transferência**:
   - Criar transferência entre duas contas
   - Verificar que duas transações foram criadas
   - Verificar que saldos foram atualizados corretamente

2. **Validações**:
   - Tentar criar transferência com mesma conta origem/destino
   - Tentar criar transferência sem conta de destino
   - Verificar mensagens de erro

3. **Visualização**:
   - Verificar ícone de transferência na lista
   - Verificar descrição "Origem → Destino"
   - Verificar que botão de edição está oculto

4. **Filtros**:
   - Filtrar apenas transferências
   - Verificar que transferências não aparecem em receitas/despesas
   - Verificar filtro "Todos" inclui transferências

5. **Exclusão**:
   - Excluir transferência
   - Verificar que ambas as transações foram removidas
   - Verificar que saldos foram atualizados

## Melhorias Futuras

1. **Edição de Transferências**: Permitir edição com validação adicional
2. **Histórico de Transferências**: Página dedicada para visualizar transferências
3. **Transferências Recorrentes**: Suporte a transferências automáticas
4. **Transferências Agendadas**: Agendar transferências futuras
5. **Notificações**: Alertar usuário sobre transferências realizadas
6. **Relatórios**: Incluir transferências em relatórios financeiros
7. **Exportação**: Incluir transferências em exportações CSV/PDF
