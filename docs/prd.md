# Guia de Implementação da Plataforma de Gestão Financeira Pessoal

## 1. Visão Geral da Implementação

Este guia fornece um roteiro estruturado para implementar a plataforma OnliFin conforme adocumentação de requisitos completa, comênfase especial na implementação do Progressive Web App (PWA).

## 2. Fases de Implementação
\n### Fase 1: Configuração do Ambiente e Infraestrutura (Semana 1-2)\n
#### 1.1 Configuração do Repositório
- Criar repositório Git no GitHub
- Configurar estrutura de branches (main, develop, feature/*)
- Configurar GitHub Actions para CI/CD
- Definir padrões de commit e pull requests

#### 1.2 Setupdo Backend
- Instalar Python 3.9+
- Configurar ambiente virtual
- Instalar Flask/FastAPI
- Configurar PostgreSQL
- Configurar Redis para cache e filas
- Instalar Celery/APScheduler para tarefas agendadas
- Configurar Docker e docker-compose

#### 1.3 Setup do Frontend\n- Instalar Node.js16+
- Criar projeto React com Create React App ou Vite
- Configurar Material-UI ou biblioteca de componentes
- **Configurar Workbox para PWA desde o início**
- Configurar estrutura de pastas (components, pages, services, utils)

#### 1.4 Configuração de Segurança
- Configurar certificado SSL/TLS
- Implementar HTTPS obrigatório
- Configurar Content Security Policy (CSP)\n- Configurar variáveis de ambiente para credenciais
\n### Fase 2: Autenticação e Gestão de Usuários (Semana 3)\n
#### 2.1 Backend
- Implementar modelo de usuário no banco de dados
- Criar endpoints de registro e login
- Implementar autenticação JWT
- Implementar MFA (autenticação multifator)
- Criar sistema RBAC (admin, financeiro, usuário)
\n#### 2.2 Frontend
- Criar telas de login e registro
- Implementar fluxo de autenticação
- Criar componente de MFA
- Implementar proteção de rotas
- Criar gerenciamento de sessão

### Fase 3: Gestão de Contas e Cartões (Semana 4)

#### 3.1 Backend
- Criar modelo de contas bancárias (id, nome, banco, agência, conta, moeda, tipo_conta, instituição, saldo_inicial, saldo_atual, atualizado_em)
- Criar modelo de cartões de crédito\n- Implementar CRUD de contas
- Implementar CRUD de cartões
- Implementar cálculo automático de saldo
\n#### 3.2 Frontend
- Criar interface de cadastro de contas
- Criar interface de cadastro de cartões
- Implementar notificação toast ao cadastrar conta
- Criar visualização de lista de contas com saldo atual
- Criar visualização de limites de cartões

### Fase 4: Sistema de Transferências (Semana 5)

#### 4.1 Backend
- Criar modelo de transferências (id, conta_origem_id, conta_destino_id, valor, data, descrição, user_id)\n- Implementar validação de saldo suficiente
- Implementar criação automática de duas movimentações vinculadas
- Implementar atualização automática de saldos
- Criar endpoints de CRUD de transferências

#### 4.2 Frontend\n- Criar interface de cadastro de transferências
- Implementar seleção de conta origem e destino
- Implementar validação de saldo
- Criar visualização de transferências na lista de transações
- Implementar filtro específico para transferências
- Adicionar ícone visual de transferência

### Fase 5: Gestão de Categorias (Semana 6)
\n#### 5.1 Backend
- Criar modelo de categorias (id, nome, cor, ícone, user_id)
- Implementar CRUD de categorias
- Criar categoria padrão 'Transferência'
\n#### 5.2 Frontend
- Criar interface de cadastro de categorias
- Implementar notificação toast ao cadastrar categoria
- Criar seletor de cores e ícones
- Criar visualização de lista de categorias\n\n### Fase 6: Transações Financeiras (Semana 7-8)

#### 6.1 Backend
- Criar modelo de transações (id, valor, data, categoria_id, conta_id, tipo, origem, descrição, título, data_criacao, user_id)
- Implementar CRUD de transações
- Implementar atualização automática de saldo ao criar/editar/excluir transação
- Criar endpoints de consulta com filtros

#### 6.2 Frontend
- Criar interface de cadastro de transações
- Implementar notificação toast ao cadastrar transação
- Criar página de transações (https://onlifin.onlitec.com.br/transactions)
- Implementar campo de busca em tempo real
- Implementar filtros (conta, categoria, tipo, data, origem)
- Implementar ordenação (data, categoria, valor)
- Implementar notificação toast fixa ao aplicar filtros
- Criar exportação em CSV e Excel

### Fase 7: **Implementação Completa do Progressive Web App (PWA)** (Semana 9-12)

#### 7.1 Configuração Básica do PWA (Semana 9)
\n**7.1.1 Criar e Configurar manifest.json**
- Definir nome da aplicação:'OnliFin'
- Configurar nome curto: 'OnliFin'
- Definir descrição da aplicação
- Configurar tema de cores (theme_color e background_color)
- Definir modo de exibição: 'standalone'\n- Configurar orientação: 'portrait'
- Adicionar ícones em múltiplos tamanhos:\n  - 72x72, 96x96, 128x128, 144x144, 152x152, 192x192, 384x384, 512x512\n- Configurar start_url: '/'
- Definir scope: '/'
- Adicionar screenshots para app stores

**7.1.2 Configurar Service Worker com Workbox**
- Instalar Workbox: `npm install workbox-webpack-plugin workbox-window`
- Criar arquivo sw.js na pasta src/
- Configurar estratégias de cache:
  - **Cache First**: para assets estáticos (CSS, JS, imagens, fontes)
  - **Network First**: para dados da API com fallback para cache
  - **Stale While Revalidate**: para dados que podem ser levemente desatualizados
- Configurar precaching de assets críticos
- Implementar versionamento de cache
- Configurar limpeza automática de caches antigos

**7.1.3 Configurar IndexedDB para Armazenamento Local**
- Instalar biblioteca: `npm install idb`\n- Criar estrutura de banco de dados local:\n  - Store'transactions': para transações pendentes
  - Store 'accounts': para cache de contas
  - Store 'categories': para cache de categorias
  - Store 'sync_queue': para fila de sincronização
- Implementar funções de CRUD no IndexedDB
- Configurar índices para consultas eficientes

**7.1.4 Implementar Criptografia de Dados Locais**
- Instalar biblioteca de criptografia: `npm install crypto-js`
- Implementar criptografia AES-256 para dados sensíveis
- Criar sistema de chaves baseado em sessão do usuário
- Criptografar dados antes de salvar no IndexedDB
- Descriptografar dados ao recuperar do IndexedDB

#### 7.2 Funcionalidades Offline (Semana 10)
\n**7.2.1 Implementar Cache de Dados Essenciais**
- Cachear lista de contas ao fazer login
- Cachear lista de categorias\n- Cachear últimas 100 transações
- Cachear configurações do usuário
- Implementar atualização automática de cache quando online

**7.2.2 Criar Interface de Cadastro Offline**
- Adaptar formulários para funcionar offline
- Implementar validação local de dados
- Salvar transações no IndexedDB quando offline
- Adicionar timestamp de criação offline
- Marcar transações como 'pendente de sincronização'

**7.2.3 Implementar Indicadores de Status de Conexão**
- Criar componente de status de rede no header
- Exibir ícone verde quando online
- Exibir ícone vermelho quando offline\n- Mostrar toast ao perder/recuperar conexão
- Adicionar badge'Offline' em formulários

**7.2.4 Criar Contador de Transações Pendentes**
- Exibir número de transações não sincronizadas
- Criar badge noícone de sincronização
- Mostrar lista de transações pendentes
- Implementar botão 'Sincronizar Agora'\n
#### 7.3 Sincronização em Segundo Plano (Semana 11)

**7.3.1 Implementar Background Sync API**
- Registrar service worker para background sync
- Configurar tag de sincronização: 'sync-transactions'
- Implementar evento 'sync' no service worker
- Configurar retry automático em caso de falha
- Limitar número de tentativas de sincronização (máximo 3)

**7.3.2 Criar Fila de Operações Pendentes**
- Implementar fila FIFO no IndexedDB
- Adicionar operações à fila quando offline:\n  - Criar transação\n  - Editar transação
  - Excluir transação
  - Criar conta
  - Criar categoria
- Processar fila ao recuperar conexão
- Remover operações da fila após sucesso

**7.3.3 Implementar Resolução de Conflitos**
- Detectar conflitos ao sincronizar:\n  - Transação editada localmente e no servidor
  - Transação excluída localmente mas editada no servidor
  - Conta excluída mas com transações pendentes
- Implementar estratégias de resolução:
  - **Last Write Wins**: para edições simples
  - **Manual Resolution**: para conflitos complexos
- Criar interface de resolução de conflitos
- Notificar usuário sobre conflitos detectados

**7.3.4 Configurar Sincronização Periódica**
- Implementar Periodic Background Sync API
- Configurar intervalo de sincronização: a cada 12 horas
- Sincronizar dados essenciais em segundo plano
- Atualizar cache de contas e categorias
- Buscar novas transações do servidor
- Respeitar configurações de economia de bateria

#### 7.4 Notificações Push (Semana 12)

**7.4.1 Implementar Push Notifications API**
- Solicitar permissão de notificações ao usuário
- Registrar service worker para push notifications
- Implementar evento 'push' no service worker\n- Configurar exibição de notificações com:\n  - Título
  - Corpo da mensagem
  - Ícone da aplicação
  - Badge de contador\n  - Ações (botões de ação rápida)
\n**7.4.2 Configurar Registro de Subscriptions**
- Criar endpoint no backend: POST /api/push/subscribe
- Gerar chaves VAPID no servidor
- Salvar subscription no banco de dados
- Associar subscription ao usuário
- Implementar renovação automática de subscriptions

**7.4.3 Criar Sistema de Envio de Notificações**\n- Implementar envio de notificações no backend
- Configurar tipos de notificações:
  - Alerta de vencimento de conta a pagar
  - Alerta de limite de cartão próximo
  - Alerta de saldo baixo
  - Alerta de risco financeiro (do Agente de Previsão)
  - Confirmação de sincronização concluída
- Implementar agendamento de notificações
- Configurar retry em caso de falha

**7.4.4 Implementar Deep Linking**
- Configurar abertura de páginas específicas ao clicar na notificação
- Implementar navegação para:\n  - Página de transações ao clicar em alerta de vencimento
  - Página de contas ao clicar em alerta de saldo\n  - Página de previsão ao clicar em alerta de risco
- Passar parâmetros via URL (ex: /transactions?id=123)
- Focar janela existente se app já estiver aberto

#### 7.5 Instalação e Experiência Nativa (Semana 12)

**7.5.1 Implementar Prompt de Instalação**
- Capturar evento 'beforeinstallprompt'\n- Armazenar evento para uso posterior
- Exibir prompt customizado após3 interações do usuário
- Criar banner de instalação no topo da página
- Adicionar botão 'Instalar App' no menu\n
**7.5.2 Criar Botão 'Instalar App'**
- Adicionar botão no header da aplicação
- Exibir apenas se app não estiver instalado
- Implementar ação de instalação ao clicar
- Mostrar feedback visual durante instalação
- Ocultar botão após instalação bem-sucedida

**7.5.3 Configurar Splash Screen**
- Criar imagem de splash screen (1125x2436px)
- Configurar cores de fundo e tema
- Adicionar logo da OnliFin centralizado
- Configurar fade-in suave ao carregar app
\n**7.5.4 Otimizar para Experiência Standalone**
- Ocultar elementos de navegação do navegador
- Ajustar layout para modo fullscreen
- Implementar barra de status customizada
- Configurar gestos de navegação (swipe back)
- Adicionar animações de transição entre páginas
- Implementar pull-to-refresh customizado

### Fase 8: Importação de Extratos (Semana 13-14)\n
#### 8.1 Backend
- Implementar parser CSV\n- Implementar parser OFX (versões 1.x e 2.x)
- Implementar parser QIF
- Criar endpoint de upload de arquivo
- Implementar salvamento de extrato

#### 8.2 Frontend
- Criar interface de importação na página principal
- Criar botão de upload no chatbot flutuante
- Implementar feedback visual de progresso
- Criar botão 'Analisar Extrato' após upload
\n### Fase 9: Análise e Categorização Automática comIA (Semana 15-16)

#### 9.1 Backend
- Configurar integração com modelo de IA (OpenAI ou compatível)
- Criar Agente de Análise de Transações
- Implementar análise de descrição e título
- Implementar sugestão de categorias
- Criar endpoint de análise de extrato

#### 9.2 Frontend
- Criar popup de resultados de análise
- Implementar lista ordenada de transações por data
- Criar dropdown de categoria para cada transação
- Implementar pré-seleção de categoria sugerida
- Criar botão 'Cadastrar Transações'\n- Implementar cadastro em lote apenas de transações com categoria selecionada

### Fase 10: Contas a Pagar e Receber (Semana 17)\n
#### 10.1 Backend
- Criar modelo de contas a pagar (id, descrição, valor, vencimento, categoria_id, status, recorrência, user_id)
- Criar modelo de contas a receber (id, descrição, valor, vencimento, categoria_id, status, recorrente, user_id)
- Implementar atualização automática de status
- Criar endpoints de CRUD\n
#### 10.2 Frontend
- Criar interface de cadastro de contas a pagar
- Criar interface de cadastro de contas a receber
- Criar visualização consolidada\n- Implementar indicadores visuais de urgência
\n### Fase 11: Dashboard com Indicadores e Gráficos (Semana 18-19)

#### 11.1 Backend
- Criar endpoints de agregação de dados
- Implementar cálculo de indicadores financeiros
- Implementar consulta de dados históricos por mês
- Criar cache de dados agregados

#### 11.2 Frontend
- Criar seletor de mês no dashboard
- Implementar indicadores principais (saldo total, receitas, despesas, transferências, saldo líquido)
- Criar gráfico de linha (evolução de saldo)
- Criar gráfico de pizza (distribuição de despesas por categoria)
- Criar gráfico de barras (receitas vs despesas)
- Criar gráfico de área (projeção de fluxo de caixa)
- Criar gráfico de fluxo (transferências entre contas)
- Implementar atualização automática ao selecionar mês

### Fase 12: Agente de Previsão Financeira (Semana 20-21)

#### 12.1 Backend
- Criar modelo de Previsões no banco de dados
- Implementar Agente de Previsão Financeira:\n  - Componente de coleta de dados
  - Componente de análise de padrões
  - Componente de geração de previsões (diária, semanal, mensal)
  - Componente de detecção de riscos
  - Componente de geração de insights
- Configurar rotina automática diária às 02:00\n- Implementar salvamento de resultados
- Criar endpoints de consulta de previsões

#### 12.2 Frontend
- Criar página de Previsão Financeira Inteligente (https://onlifin.onlitec.com.br/forecast)
- Implementar cartão de status de risco
- Criar gráfico de previsão de saldo diário
- Criar gráfico de distribuição de gastos por categoria
- Criar lista de alertas ordenados por gravidade
- Criar seção de insights gerados pelaIA
- Criar tabelas de previsão semanal e mensal
- Implementar botão 'Atualizar Previsão Agora'

### Fase 13: Sistema de Notificações Automáticas (Semana 22)\n
#### 13.1 Backend
- Implementar sistema de notificações in-app
- Configurar envio de emails (opcional)
- Criar endpoints de gerenciamento de notificações
\n#### 13.2 Frontend
- Implementar notificações toast para alertas críticos
- Criar badge com número de alertas não lidos
- Implementar modal de alerta para riscos de alta gravidade
- Criar painel de configuração de notificações
\n### Fase 14: Assistente de IA com Memória Persistente (Semana 23-24)

#### 14.1 Backend
- Criar modelo de conversas no banco de dados
- Criar modelo de solicitações\n- Implementar sistema de armazenamento de histórico
- Implementar recuperação de contexto
- Configurar integração com modelo de IA
- Implementar permissões de leitura e escrita
- Criar endpoints de chat\n
#### 14.2 Frontend
- Criar botão flutuante de chat
- Implementar interface de chat\n- Criar botão de upload de arquivo no chat
- Implementar visualização de histórico de conversas
- Criar interface de busca no histórico
\n### Fase 15: Painel de Administração deIA (Semana 25)\n
#### 15.1 Backend
- Criar endpoints de configuração de modelo de IA
- Implementar sistema de auditoria\n- Criar logs detalhados de acessos e operações
\n#### 15.2 Frontend
- Criar painel de configuração de modelo de IA
- Implementar toggles de permissões (leitura e escrita)
- Criar visualização de logs de auditoria
- Implementar indicadores visuais de status
- Criar interface de gerenciamento de memória

### Fase 16: Gestão de Plugins (Semana 26)
\n#### 16.1 Backend
- Criar modelo de plugins no banco de dados
- Implementar sistema de gerenciamento de plugins
- Criar endpoints de configuração de plugins
- Implementar controle de permissões de plugins

#### 16.2 Frontend
- Criar interface de gerenciamento de plugins
- Implementar ativação/desativação de plugins\n- Criar configuração de parâmetros de plugins
- Implementar visualização de logs de plugins
\n### Fase 17: Funcionalidade OCR para Cupons Fiscais (Semana 27-28)

#### 17.1 Backend
- Integrar biblioteca OCR (Tesseract ou Google Cloud Vision API)
- Criar endpoint de processamento de QR Code
- Implementar extração de dadosdo cupom fiscal
- Implementar validação de autenticidade
- Criar armazenamento de imagens de comprovantes

#### 17.2 Frontend
- Criar botão 'Escanear Cupom Fiscal'\n- Implementar acesso à câmera do dispositivo
- Criar interface de captura de QR Code
- Implementar pré-preenchimento de transação
- Criar visualização de comprovante
- Adicionar ícone de cupom fiscal na lista de transações

### Fase 18: Testes e Otimizações (Semana 29-31)

#### 18.1 Testes\n- Implementar testes unitários (backend e frontend)
- Implementar testes de integração
- Realizar testes de segurança (SAST/DAST)
- **Testar funcionalidades offlinedo PWA em diferentes dispositivos**
- **Testar sincronização e resolução de conflitos**
- **Testar notificações push em Android e iOS**
- **Testar instalação do PWA em diferentes navegadores**\n- **Validar Lighthouse Score (Performance: 90+, PWA: 100)**
- **Validar Core Web Vitals (LCP < 2.5s, FID < 100ms, CLS < 0.1)**

#### 18.2 Otimizações\n- Implementar lazy loading\n- Configurar compressão de assets (Gzip/Brotli)
- Otimizar consultas ao banco de dados
- Implementar cache de dados agregados
- Otimizar tamanho de bundledo frontend
- **Otimizar tamanho do Service Worker**
- **Reduzir tamanho de ícones e splash screens**
- **Implementar code splitting para reduzir bundle inicial**
\n### Fase 19: Documentação e Deploy (Semana 32)\n
#### 19.1 Documentação
- Documentar API REST/GraphQL
- Criar guia de usuário\n- Documentar arquitetura técnica
- Criar documentação de plugins
- Documentar procedimentos de backup e recuperação
- **Documentar funcionalidades offline do PWA**\n- **Criar guia de instalação do PWA para usuários**

#### 19.2 Deploy
- Configurar ambiente de produção
- Configurar CI/CD completo
- Realizar deploy inicial
- Configurar monitoramento e logs
- Configurar alertas de erro
- **Configurar HTTPS obrigatório para PWA**
- **Registrar app em app stores (opcional)**

##3. Stack Tecnológica Recomendada

### Backend\n- **Framework**: FastAPI (recomendado) ou Flask\n- **Banco de dados**: PostgreSQL 13+
- **Cache**: Redis 6+
- **Filas**: Celery com Redis como broker
- **Agendador**: APScheduler ou Celery Beat
- **OCR**: Tesseract OCR ou Google Cloud Vision API
- **Push Notifications**: Web Push (biblioteca web-push para Node.js ou pywebpush para Python)
- **Containerização**: Docker e docker-compose
\n### Frontend
- **Framework**: React 18+
- **UI Library**: Material-UI (MUI) ou Ant Design
- **Gráficos**: Recharts ou Chart.js
- **PWA**: Workbox 6+
- **State Management**: Redux Toolkit ou Zustand
- **HTTP Client**: Axios\n- **IndexedDB**: idb (wrapper para IndexedDB)
- **Criptografia**: crypto-js

### DevOps
- **CI/CD**: GitHub Actions\n- **Monitoramento**: Sentry (erros) + Prometheus/Grafana (métricas)
- **Logs**: ELK Stack ou Loki
\n## 4. Estrutura de Pastas Recomendada

### Backend
```
backend/
├── app/
│   ├── models/          # Modelos do banco de dados
│   ├── routes/          # Endpoints da API
│   ├── services/        # Lógica de negócio
│   ├── agents/          # Agentes de IA (Previsão, Análise)\n│   ├── utils/           # Funções auxiliares
│   ├── middleware/      # Middleware de autenticação, logs
│   └── config.py        # Configurações\n├── tests/               # Testes unitários e de integração
├── migrations/          # Migrações do banco de dados
├── docker-compose.yml\n├── Dockerfile
└── requirements.txt
```

### Frontend
```
frontend/
├── public/
│   ├── manifest.json    # Configuração do PWA
│   ├── sw.js            # Service Worker
│   └── icons/           # Ícones do PWA (72x72 até 512x512)
├── src/
│   ├── components/      # Componentes reutilizáveis
│   ├── pages/           # Páginas da aplicação
│   ├── services/        # Chamadas à API
│   ├── utils/           # Funções auxiliares
│   ├── store/           # State management
│   ├── db/# Configuração do IndexedDB
│   ├── sw-register.js   # Registro do Service Worker
│   └── App.js\n├── package.json
└── vite.config.js       # Configuração do Vite
```

## 5. Prioridades de Implementação

### Alta Prioridade (MVP)
1. Autenticação e gestão de usuários
2. Gestão de contas e cartões
3. Sistema de transferências
4. Transações financeiras (CRUD)
5. **PWA completo (instalação, offline, sincronização, notificações push)**
6. Dashboard com indicadores básicos
7. Assistente de IA com acesso de leitura
\n### Média Prioridade\n1. Importação de extratos (CSV, OFX, QIF)\n2. Análise e categorização automática com IA
3. Agente de Previsão Financeira
4. Contas a pagar e receber\n5. Sistema de notificações automáticas
6. Painel de administração de IA
7. Funcionalidade OCR para cupons fiscais

### Baixa Prioridade (Versões Futuras)
1. Gestão de plugins
2. Marketplace de plugins
3. Integração com APIs bancárias (Open Banking)
4. Análise preditiva avançada
5. Aplicativo móvel nativo
\n## 6. Considerações de Segurança

- Implementar HTTPS obrigatório desde o início (requisito para PWA)
- Configurar Content Security Policy (CSP)
- Criptografar dados sensíveis em repouso (especialmente no IndexedDB)
- Implementar rate limiting em endpoints críticos
- Validar e sanitizar todas as entradas de usuário
- Implementar auditoria completa de acessos
- Configurar backup automático do banco de dados
- Implementar MFA para todos os usuários
- Proteger chaves VAPID para push notifications

## 7. Próximos Passos Imediatos para Implementação do PWA

1. **Configurar manifest.json com todos os metadados necessários**
2. **Criar e configurar Service Worker com Workbox**
3. **Implementar estratégias de cache para assets e dados da API**
4. **Configurar IndexedDB para armazenamento local**
5. **Implementar funcionalidades offline (cadastro de transações)**
6. **Configurar Background Sync API para sincronização automática**
7. **Implementar Push Notifications API**
8. **Criar prompt de instalação customizado**
9. **Testar PWA em diferentes dispositivos e navegadores**
10. **Validar Lighthouse Score e Core Web Vitals**

## 8. Recursos Adicionais

- **Documentação FastAPI**: https://fastapi.tiangolo.com/
- **Documentação React**: https://react.dev/
- **Documentação Workbox (PWA)**: https://developers.google.com/web/tools/workbox
- **Documentação PWA**: https://web.dev/progressive-web-apps/
- **Documentação Push API**: https://developer.mozilla.org/en-US/docs/Web/API/Push_API
- **Documentação Background Sync**: https://developer.mozilla.org/en-US/docs/Web/API/Background_Synchronization_API
- **Documentação IndexedDB**: https://developer.mozilla.org/en-US/docs/Web/API/IndexedDB_API
- **Documentação PostgreSQL**: https://www.postgresql.org/docs/\n- **Documentação Celery**: https://docs.celeryproject.org/
- **Lighthouse CI**: https://github.com/GoogleChrome/lighthouse-ci
\n---

**Observação**: Este guia fornece um roteiro completo para implementação com foco especial no PWA. A implementação do PWA foi priorizada e detalhada nas Fases 7(Semanas 9-12), garantindo que a plataforma OnliFin ofereça uma experiência nativa em dispositivos móveis e desktop, com funcionalidades offline robustas, sincronização automática e notificações push. A implementação completa está estimada em 32 semanas (aproximadamente 8 meses) com uma equipe de 3-4 desenvolvedores.