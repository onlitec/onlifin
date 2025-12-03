# Documento de Requisitos da Plataforma de Gestão Financeira Pessoal\n
## 1. Visão Geral
\n### 1.1 Nome da Plataforma
Plataforma de Gestão Financeira Pessoal com Assistente de IA

### 1.2 Descrição
Plataforma web (MVP) para gestão de finanças pessoais que permite importar extratos bancários, gerenciar contas e cartões, cadastrar receitas e despesas, e oferece um assistente de IA contextual acessível em toda a interface. Inclui painel de administração para configurar modelos de IA, controlar permissões de acesso aos dados e registrar auditoria de interações.
\n## 2. Funcionalidades Principais

### 2.1 Gestão de Contas e Cartões
- Cadastro de contas bancárias (nome, banco, agência, conta, moeda)
- Cadastro de cartões de crédito (limite, data de fechamento, data de vencimento)
- Visualização de saldos e limites disponíveis

### 2.2 Importação e Conciliação
- Importação de extratos bancários nos formatos CSV, OFX e QIF
- Mapeamento automático de transações importadas
- Ferramenta de conciliação manual de lançamentos
- Classificação automática de transações

### 2.3 Movimentações Financeiras
- Cadastro de receitas e despesas (valor, data, categoria, conta, tag, nota)
- Suporte a pagamentos recorrentes
- Suporte a receitas recorrentes
- Controle de parcelamentos com acompanhamento de parcelas
- Agendamento de compromissos e pagamentos
- Sistema de alertas para vencimentos\n
### 2.4 Controle Financeiro\n- Gestão de contas a pagar e receber
- Visualização de fluxo de caixa
- Previsões financeiras simples\n- Relatórios e dashboards:
  - Saldo por conta
  - Despesas por categoria
  - Histórico mensal
  - Projeção de fluxo de caixa
- Exportação de relatórios em CSV, Excel e PDF

### 2.5 Assistente de IA Contextual\n- Elemento visível em todas as páginas (botão flutuante ou ícone de chat)\n- Chat contextual com acesso total aos dados da plataforma\n- Funcionalidades do assistente:
  - Categorização automática de transações
  - Recomendações de economia
  - Previsão de fluxo de caixa
  - Alertas de vencimentos
  - Simulações de parcelamento
  - Sugestões de orçamento
- Análise completa de contas cadastradas\n  - Consulta detalhada de extratos de transações\n  - Análise de pagamentos e recebimentos
  - Geração e interpretação de relatórios
  - Consulta de saldos em tempo real

### 2.6 Painel de Administração de IA
- Indicador visual de status de configuração:
  - Badge ou ícone na página de configurações indicando se há modelo de IA configurado
  - Mensagem clara exibindo 'Modelo Configurado' (com ícone de check verde) ou 'Nenhum Modelo Configurado' (com ícone de alerta laranja)
  - Exibição do nome do modelo ativo quando configurado
- Configuração de modelos de IA (seleção de modelo, endpoint, chave de API)
- Ajuste de prompts-padrão e templates de resposta
- Controles de permissão de acesso total:
  - Acesso completo a todas as contas cadastradas
  - Acesso completo a todas as transações (receitas e despesas)
  - Acesso completo a pagamentos e recebimentos
  - Acesso completo a relatórios financeiros
  - Acesso completo a saldos das contas
  - Acesso completo a extratos de transações
  - Toggle para ativar/desativar acesso total com confirmação e consentimento explícito do usuário
- Logs e histórico de conversas com IA
- Registro de auditoria detalhado:\n  - Quem ativou o acesso total
  - Quando foi ativado
  - Finalidade declarada
  - Histórico de consultas realizadas pelo modelo de IA
  - Dados acessados em cada interação
- Opções de apagar ou exportar histórico conforme políticas de retenção

## 3. Segurança e Privacidade

### 3.1 Proteção de Dados
- TLS/HTTPS em todas as comunicações
- Criptografia em repouso para dados sensíveis
- Mascaramento e criptografia de números de conta e cartão
- Transmissão segura de dados completos ao modelo de IA quando acesso total estiver ativado

### 3.2 Autenticação e Autorização
- Autenticação por email/senha com MFA (autenticação multifator)
- RBAC (controle de acesso baseado em papéis): admin, financeiro, usuário
- Consentimento explícito e informado do usuário para conceder acesso totaldo modelo de IA a todos os dados financeiros:
  - Contas cadastradas
  - Transações completas
  - Pagamentos e recebimentos\n  - Relatórios financeiros
  - Saldos das contas
  - Extratos de transações
- Termo de consentimento detalhado explicando o escopo do acesso total
- Opção de revogar acesso total a qualquer momento

### 3.3 Auditoria
- Registro completo e detalhado de todos os acessos realizados pelo modelo de IA
- Auditoria de ações de usuários humanos
- Log de todas as consultas do modelo de IA aos dados da plataforma
- Middleware de validação e registro antes de enviar dados ao conector de IA
- Relatório de auditoria acessível ao usuário para transparência
\n## 4. Extensibilidade

### 4.1 API
- API REST/GraphQL bem documentada\n- Suporte para integrações (apps móveis, plugins, serviços de contabilidade)\n
### 4.2 Webhooks
- Eventos disponíveis:
  - Nova transação
  - Vencimento próximo
  - Sugestão gerada pelo assistente

## 5. Observabilidade e Confiabilidade

### 5.1 Monitoramento
- Sistema de logs estruturados
- Métricas de uso do assistente de IA
- Alertas para erros e falhas de importação
- Monitoramento de acessos do modelo de IA aos dados

### 5.2 Backup e Recuperação
- Backup automático do banco de dados
- Procedimentos documentados de recuperação de desastres
\n## 6. Arquitetura Técnica

### 6.1 Stack Tecnológica
- Backend: Flask ou FastAPI
- Banco de dados: PostgreSQL
- Frontend: React
- Containerização: Docker / docker-compose
- CI/CD: GitHub Actions

### 6.2 Componentes
- Serviço de IA como componente externo configurável\n- Conector que chama endpoints de modelos (OpenAI/compatíveis, instâncias privadas)
- Camada de acesso a dados que fornece ao modelo de IA:
  - Dados completos de contas cadastradas
  - Histórico completo de transações
  - Registros de pagamentos e recebimentos
  - Relatórios financeiros gerados
  - Saldos atualizados das contas\n  - Extratos detalhados de transações
- Credenciais gerenciadas via vault/segredos (nunca na UI)
- Design modular: módulo de importação, processamento, API, UI, conector IA

### 6.3 Testes
- Cobertura de testes unitários e de integração
- Testes de segurança (SAST/DAST)
- Testes de acesso do modelo de IA aos dados

## 7. Escopo do MVP
\n### 7.1 Funcionalidades Iniciais
- Autenticação com MFA
- Cadastro de contas e cartões
- Importação de extratos CSV/OFX
- CRUD de transações
- Conciliação manual
- Dashboard básico com visualizações principais
- Assistente de IA com acesso total configurável a:
  - Todas as contas cadastradas
  - Todas as transações (receitas e despesas)\n  - Todos os pagamentos e recebimentos
  - Todos os relatórios financeiros
  - Saldos de todas as contas
  - Extratos completos de transações
- Botão de chat com IA nas telas principais
- Painel de administração com:
  - Indicador visual de status de configuração\n  - Configuração de modelo de IA
  - Toggle de acesso total com termo de consentimento
  - Logs detalhados de chat e acessos do modelo de IA
  - Relatório de auditoria de acessos
\n### 7.2 Versões Futuras (1.1 / 1.2)\n- Conciliação automática por Machine Learning
- Integração com APIs bancárias (Open Banking)
- Importação automatizada OFX
- Permissões granulares avançadas para modelos de IA com níveis intermediários de acesso\n- Aplicativo móvel\n
## 8. Estilo de Design

### 8.1 Paleta de Cores
- Cores principais: azul profissional (#2C3E50) e verde financeiro (#27AE60) para transmitir confiança e estabilidade
- Cores de apoio: cinza claro (#ECF0F1) para fundos e branco (#FFFFFF) para cards

### 8.2 Layout\n- Layout em cards para organização modular de informações financeiras\n- Sidebar fixa com navegação principal
- Dashboard com grid responsivo para visualização de métricas

### 8.3 Elementos Visuais
- Ícones minimalistas para categorias e ações
- Gráficos limpos e legíveis (linhas para fluxo de caixa, pizza para categorias)\n- Botão flutuante do assistente de IA com ícone de chat, posicionado no canto inferior direito
- Bordas suaves com raio de 8px para cards e botões
- Sombras sutis para criar hierarquia visual
- Badge de status com ícone de check verde para modelo configurado ou ícone de alerta laranja para ausência de configuração
- Indicador visual de acesso total ativo (ícone de cadeado aberto verde) quando o modelo de IA tiver permissão completa\n
## 9. Referências de Interface

### 9.1 Imagens de Referência
- Interfacedo Assistente Financeiro IA: {7FFF7068-4DCD-48E0-8714-FF810EA6906F}.png
- Exemplo de mensagem de erro do sistema: {9C49DCD9-E33F-45CE-86FB-3A635D5A7630}.png