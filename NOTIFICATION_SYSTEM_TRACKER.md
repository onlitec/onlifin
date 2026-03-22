# Notification System Tracker

Ultima atualizacao: 2026-03-22 00:20 UTC

Legenda:
- `[x]` Concluido
- `[-]` Em andamento
- `[ ]` Pendente

## Status Geral

Implementacao de codigo estimada: `99%`

Passos grandes restantes para finalizar no ambiente: `2`

Restante:
1. Configurar credenciais reais de entrega externa
2. Validar envio externo fim a fim no ambiente publicado

## Concluido

### Banco e modelo
- [x] Estrutura base de notificacoes criada (`notifications`, `alert_preferences`, settings, templates, fila, entregas)
- [x] Campos e defaults globais de notificacao adicionados
- [x] Suporte a comandos assíncronos do worker via banco
- [x] Politica para retry administrativo da fila adicionada
- [x] Politica de leitura administrativa das notificacoes adicionada

### Backend e worker
- [x] `AlertService` centralizado para criacao de notificacoes
- [x] Respeito a preferencias do usuario por evento/canal
- [x] Respeito ao switch mestre `is_active`
- [x] Bypass controlado para testes administrativos mesmo com sistema pausado
- [x] Resolucao correta de papel admin via claim JWT `app_role`
- [x] Separacao entre admin da conta e admin da plataforma nas claims JWT (`account_admin`, `tenant_id`)
- [x] Health do worker detalha chaves de ambiente faltantes por canal
- [x] Destinos pessoais de notificacao resolvidos por `profiles.settings` com fallback para email/whatsapp legados
- [x] Credenciais globais SMTP/WhatsApp persistidas em tabela admin-only separada
- [x] Worker de notificacoes criado
- [x] Geracao automatica de notificacoes para contas a pagar/receber
- [x] Fila de entrega externa com retry/backoff
- [x] Health do worker com status operacional e prontidao de canais
- [x] Consumo de comandos manuais (`process_queue`, `generate_notifications`)

### Frontend admin
- [x] Tela `AdminNotifications` criada e roteada
- [x] Guardas de rota separadas para `Administração` x `Configurações`
- [x] Sidebar diferencia `Admin da Conta` de `Admin da Plataforma`
- [x] Pagina pessoal `Preferências` criada para qualquer usuario autenticado
- [x] `Administração > Geral` adaptada para admin da conta sem expor controles globais
- [x] `Configurações` restrita a admins da plataforma
- [x] Usuario final informa apenas seus destinos pessoais (`notification_email`, `notification_whatsapp`) na UI
- [x] Admin da plataforma informa credenciais globais SMTP/WhatsApp na UI de `Configurações`
- [x] Gestao global de canais e defaults
- [x] Estado efetivo dos canais externos visivel na UI (ligado x credencial real)
- [x] UI publicada mostra variaveis faltantes do worker para SMTP/WhatsApp
- [x] Templates por evento/canal com edicao completa
- [x] Matriz completa de templates exibida mesmo sem seed previo
- [x] Salvamento de templates por `upsert`
- [x] Testes administrativos de toast/e-mail/WhatsApp
- [x] Visualizacao de comandos recentes do worker
- [x] Retry individual e em lote para falhas da fila
- [x] Visualizacao de notificacoes gravadas, fila e entregas recentes
- [x] Refresh em background sem spinner global
- [x] Polling mais leve e sem toast repetitivo em falhas transitórias
- [x] Aba ativa preservada durante o polling em background

### Bootstrap e deploy
- [x] `Dockerfile.postgres` atualizado para incluir migrations de notificacao em banco novo
- [x] `init-coolify-db.sh` atualizado para aplicar o pacote de migrations de notificacao
- [x] Script unico para aplicar o pacote de migrations manualmente (`scripts/apply-notification-migrations.sh`)
- [x] Script manual de migrations atualizado para incluir a separacao `account_admin`
- [x] Script de diagnostico do deploy criado (`scripts/check-notification-deploy-readiness.sh`)
- [x] Checker ajustado para considerar credenciais vindas do banco via health do worker
- [x] Script de configuracao assistida de credenciais criado (`scripts/configure-notification-channel-env.sh`)
- [x] Script de smoke test externo criado (`scripts/run-notification-channel-smoke-test.sh`)
- [x] Script de smoke test da segregacao administrativa criado (`scripts/run-admin-access-smoke-test.sh`)
- [x] Script orquestrador final criado (`scripts/finalize-notification-system.sh`)
- [x] Migrations aplicadas no banco real com sucesso
- [x] Frontend publicado no `onlifin-app` com bundle atualizado
- [x] `notification-worker` publicado e reiniciado no ambiente atual
- [x] Correcao de claims JWT administrativas aplicada no banco real
- [x] Frontend republicado com preservacao da aba ativa no polling
- [x] Migration de separacao `admin da conta` x `admin da plataforma` aplicada no banco real
- [x] Frontend republicado com as novas guardas de acesso e menu por perfil
- [x] Migration de credenciais globais dos canais aplicada no banco real
- [x] Frontend republicado com formulario de integracoes globais da plataforma
- [x] Worker republicado lendo credenciais do banco com fallback para env

## Pendente

### Integracao no ambiente
- [x] Aplicar as migrations:
  - `migrations/20260321_notification_management_system.sql`
  - `migrations/20260321_notification_queue_admin_actions.sql`
  - `migrations/20260321_notification_worker_commands.sql`
  - `migrations/20260321_notification_admin_read_access.sql`

- [x] Rebuild/redeploy dos servicos impactados para o ambiente atual:
  - app/frontend publicado via `dist`
  - `notification-worker` atualizado e reiniciado
  - schema PostgREST recarregado apos migrations

- [ ] Configurar credenciais reais:
  - `SMTP_HOST`
  - `SMTP_USER`
  - `SMTP_PASS`
  - `SMTP_FROM_ADDRESS`
  - `WHATSAPP_API_BASE_URL`
  - `WHATSAPP_API_TOKEN`

- [-] Validacao fim a fim:
  - [x] health do worker atualizado e respondendo
  - [x] comando manual `process_queue` consumido e concluido
  - [x] comando manual `generate_notifications` consumido e concluido
  - [x] ciclo de falha da fila validado sem SMTP configurado
  - [x] sistema ativo/inativo validado pela UI publicada
  - [x] teste administrativo por toast na UI publicada
  - [x] segregacao de acesso validada na UI publicada:
    - `Admin da Conta` ve `Administracao` e nao acessa `Configuracoes`
    - `Admin da Plataforma` ve `Administracao` e `Configuracoes`
  - [x] smoke test reutilizavel da segregacao administrativa validado no ambiente publicado
  - [x] bloqueio direto de rotas globais (`/settings`, `/admin-notifications`, `/user-management`, `/ai-admin`) validado para `Admin da Conta`
  - [x] tela `Preferências` validada publicada para `Admin da Conta` e `Admin da Plataforma`
  - [x] salvamento administrativo de credenciais SMTP/WhatsApp validado pela UI publicada com reflexo no banco e no health do worker
  - [ ] teste administrativo por e-mail com SMTP real
  - [ ] teste administrativo por WhatsApp com provider real
  - [x] reenfileiramento de falhas validado pela UI publicada
  - [x] visualizacao de notificacoes, fila e entregas validada pela UI publicada

## Observacoes

- O principal bloco restante nao e mais de implementacao local; agora depende de credenciais reais de entrega e validacao externa dos canais.
- Nesta sessao foi possivel usar `sudo docker`, aplicar migrations, publicar frontend/worker e validar o fluxo pelo banco, pelo health do worker e pela UI autenticada publicada.
- A separacao entre `admin da conta` e `admin da plataforma` foi aplicada no banco, propagada no JWT e validada em runtime na UI publicada.
- Os usuarios temporarios de validacao desta rodada foram removidos do ambiente ao final dos testes.
- Durante a validacao publicada apareceram dois bugs reais e ambos foram corrigidos:
  - policy admin quebrada por `current_app_role()` ignorar a claim JWT `app_role`;
  - aba ativa de `AdminNotifications` sendo perdida durante o polling em background.
- Este arquivo deve ser atualizado a cada bloco novo de trabalho no modulo de notificacoes.
