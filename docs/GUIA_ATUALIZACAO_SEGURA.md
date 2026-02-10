# 📘 Guia: Atualização Segura com Suporte Multi-Empresa

## 🎯 Problema Identificado

Após a atualização que implementou suporte multi-empresa (PJ), os dados antigos não apareciam porque:

1. **Dados legados** tinham `company_id = NULL` (sem empresa associada)
2. **Frontend** filtra por empresa e não mostra dados sem `company_id`
3. **Políticas RLS** foram atualizadas para multi-tenant

## ✅ Solução Aplicada

### 1. Verificação dos Dados

```sql
-- Confirmar que dados existem
SELECT COUNT(*) FROM accounts;        -- Contas existentes
SELECT COUNT(*) FROM transactions;    -- Transações existentes

-- Verificar dados órfãos
SELECT COUNT(*) FROM accounts WHERE company_id IS NULL;
SELECT COUNT(*) FROM transactions WHERE company_id IS NULL;
```

### 2. Migração Automática

Criada migração `005_fix_pf_data_visibility.sql` que:

- ✅ Associa dados legados à **empresa padrão** do usuário
- ✅ Migra contas, transações, cartões e contas a pagar/receber
- ✅ Mantém integridade referencial
- ✅ Gera relatório de migração

## 🔄 Como Fazer Atualizações Seguras no Futuro

### Passo 1: Backup Antes da Atualização

```bash
# Criar backup completo
docker exec onlifin-database pg_dump -U onlifin onlifin > backups/onlifin_backup_$(date +%Y%m%d_%H%M%S).sql
```

### Passo 2: Testar Migrações Localmente

Sempre teste migrações estruturais em um banco de teste antes de aplicar em produção.

### Passo 3: Migração com Dados de Transição

Ao adicionar colunas obrigatórias, sempre:
1. Adicione como NULLABLE.
2. Migre os dados.
3. Torne obrigatório (NOT NULL).

## 🔧 Comandos Úteis

### Verificar Status dos Dados

```sql
SELECT 
    'accounts' as table, COUNT(*) as total,
    COUNT(*) FILTER (WHERE company_id IS NULL) as orphans
FROM accounts
UNION ALL
SELECT 'transactions', COUNT(*), COUNT(*) FILTER (WHERE company_id IS NULL)
FROM transactions;
```

### Restaurar Backup

```bash
cat backups/nome_do_backup.sql | docker exec -i onlifin-database psql -U onlifin -d onlifin
```
