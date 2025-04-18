# Changelog

## Versão 5.0.0 - 2025-04-18

### Correção de Valores Monetários

#### Problema Identificado
O sistema estava apresentando um problema no processamento de valores monetários onde:
- Ao digitar um valor (ex: 300)
- O valor era transformado incorretamente em R$ 3.000,00 ao salvar no banco de dados
- Isso ocorria devido a múltiplas conversões desnecessárias entre reais e centavos

#### Solução Implementada

1. **Frontend (JavaScript)**
   - Removida a conversão desnecessária de reais para centavos
   - O valor digitado é tratado diretamente como reais
   - Exemplo: Se o usuário digita 300, é tratado como R$ 300,00

2. **Backend (PHP)**
   - Removida a conversão duplicada de centavos
   - O valor recebido do frontend é salvo diretamente no banco
   - Não há mais multiplicação por 100 no backend

3. **Fluxo Corrigido**
   - Usuário digita: 300
   - Exibido como: R$ 300,00
   - Salvo no banco como: 30000 centavos (R$ 300,00)

#### Arquivos Modificados
- `app/Http/Controllers/TransactionController.php`
- `resources/views/transactions/create.blade.php`
- `app/Http/Requests/StoreTransactionRequest.php`

#### Impacto
- Melhor precisão no processamento de valores monetários
- Eliminação de conversões desnecessárias
- Valores salvos corretamente no banco de dados

### Outras Alterações
- Atualização do sistema de backup
- Adição de scripts de atualização
- Melhorias na interface de categorias e contas
- Correção de validações de formulário

---

**Nota:** Esta versão resolve um problema crítico de processamento de valores monetários que afetava a precisão dos dados financeiros no sistema.
