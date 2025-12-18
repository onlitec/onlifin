# Desenvolvimento Local - Onlifin

Este guia explica como configurar e executar a plataforma Onlifin em ambiente local.

## 📋 Estado Atual do Setup

### ✅ Implementado com Sucesso

| Item | Status | Detalhes |
|------|--------|----------|
| Node.js | ✅ Instalado | v20.19.6 |
| pnpm | ✅ Instalado | Gerenciador de pacotes |
| Docker | ✅ Instalado | v29.1.2 |
| Supabase CLI | ✅ Instalado | v2.65.5 |
| PostgreSQL | ✅ Instalado | v16.11 (nativo) |
| Banco de Dados | ✅ Criado | `onlifin` com schema completo |
| Usuário Admin | ✅ Criado | admin@financeiro.com / admin123 |
| Dependências do Projeto | ✅ Instaladas | pnpm install concluído |
| Scripts de Automação | ✅ Criados | setup_server.sh, start_local.sh, backup_db.sh |

### ⚠️ Limitação Identificada

Este servidor roda em um **container LXC (Proxmox)**, que tem limitações de segurança que impedem o uso de `sysctls` pelo Docker. Por isso:

- **Supabase Local via Docker**: ❌ Não funciona neste ambiente
- **Alternativa**: PostgreSQL nativo instalado diretamente

## 🔧 Configuração do Ambiente

### Opção 1: Usar Supabase Cloud (Recomendado para este servidor)

A configuração atual no arquivo `.env` já aponta para o Supabase Cloud:

```env
VITE_SUPABASE_URL=https://twbzhscoyasetrstrofl.supabase.co
VITE_SUPABASE_ANON_KEY=eyJhb...
```

Para iniciar o servidor de desenvolvimento:
```bash
cd /opt/onlifin
pnpm dev
```

### Opção 2: Usar PostgreSQL Local (Para Backend Independente)

O PostgreSQL local está configurado e pronto:

**Conexão:**
```
Host: localhost
Porta: 5432
Banco: onlifin
Usuário: onlifin_user
Senha: onlifin_password
```

**String de conexão:**
```
postgresql://onlifin_user:onlifin_password@localhost:5432/onlifin
```

**Usuário Admin da Aplicação:**
- Email: `admin@financeiro.com`
- Senha: `admin123`

## 🗄️ Estrutura do Banco de Dados Local

O banco de dados `onlifin` contém:

### Tabelas Criadas
- `auth.users` - Usuários autenticados
- `profiles` - Perfis de usuários
- `accounts` - Contas bancárias
- `cards` - Cartões de crédito
- `categories` - Categorias (13 padrão já inseridas)
- `transactions` - Transações financeiras
- `ai_configurations` - Configurações da IA
- `ai_chat_logs` - Logs de conversas com IA
- `import_history` - Histórico de importações

### Funções e Triggers
- `auth.register_user()` - Registrar novo usuário
- `auth.authenticate_user()` - Autenticar usuário
- `handle_new_user()` - Trigger para criar perfil
- `update_account_balance_on_transaction()` - Atualizar saldos automaticamente
- `recalculate_account_balance()` - Recalcular saldo de conta

## 📁 Scripts Disponíveis

### `/opt/onlifin/scripts/setup_server.sh`
Instala todas as dependências necessárias (Node, pnpm, Docker, Supabase CLI).

### `/opt/onlifin/scripts/start_local.sh`
Inicia o Supabase local (requer ambiente com suporte a Docker sysctls).

### `/opt/onlifin/scripts/backup_db.sh`
Cria backup do banco de dados local.

### `/opt/onlifin/scripts/init_local_db.sql`
Script SQL para inicializar o banco PostgreSQL nativo.

## 🚀 Comandos Úteis

### Iniciar Desenvolvimento
```bash
cd /opt/onlifin
pnpm dev
```

### Verificar PostgreSQL
```bash
sudo systemctl status postgresql
sudo -u postgres psql -d onlifin
```

### Acessar Banco de Dados
```bash
psql -h localhost -U onlifin_user -d onlifin
# Senha: onlifin_password
```

### Ver Logs
```bash
sudo journalctl -u postgresql -f
```

## 🔒 Segurança

### Credenciais Locais
- **PostgreSQL User**: `onlifin_user` / `onlifin_password`
- **Admin da App**: `admin@financeiro.com` / `admin123`

### Recomendações para Produção
1. Alterar todas as senhas padrão
2. Configurar SSL para conexões
3. Habilitar RLS (Row Level Security)
4. Configurar backup automático
5. Monitorar logs de acesso

## 🔄 Próximos Passos para Ambiente LXC

Para rodar o Supabase completo localmente neste servidor LXC, é necessário:

1. **No Host Proxmox**, habilitar "nesting" para o container:
   ```bash
   pct set <VMID> -features nesting=1
   ```

2. **Reiniciar o container LXC**

3. **Testar novamente**:
   ```bash
   sudo supabase start
   ```

Alternativamente, use uma VM completa ao invés de container LXC para desenvolvimento local completo.

## 📊 Resumo da Análise da Plataforma

### Tecnologias Utilizadas
- **Frontend**: React 18, TypeScript, Vite
- **UI**: Tailwind CSS, Radix UI, shadcn/ui
- **Backend**: Supabase (PostgreSQL + Auth + Edge Functions)
- **Estado**: React Query, React Hook Form
- **Gráficos**: Recharts

### Funcionalidades Implementadas
- ✅ Gestão de contas bancárias
- ✅ Gestão de cartões de crédito
- ✅ Transações (CRUD completo)
- ✅ Categorias
- ✅ Dashboard com gráficos
- ✅ Relatórios
- ✅ Assistente de IA
- ✅ Painel de administração
- ✅ Autenticação e autorização

### Qualidade do Código
- Estrutura organizada em componentes
- TypeScript para tipagem
- ESLint/Biome para linting
- Tailwind para estilos consistentes

---

**Última atualização**: 12/12/2024
