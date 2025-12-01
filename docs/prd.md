# Documento de Requisitos da Plataforma de Gestão Financeira Pessoal\n
## 1. Visão Geral
\n### 1.1 Nome da Plataforma
Plataforma de Gestão Financeira Pessoal com Assistente de IA

### 1.2 Descrição\nPlataforma web (MVP) para gestão de finanças pessoais que permite importar extratos bancários, gerenciar contas e cartões, cadastrar receitas e despesas, e oferece um assistente de IA contextual acessível em toda a interface. Inclui painel de administração para configurar modelos de IA, controlar permissões de acesso aos dados e registrar auditoria de interações.

## 2. Funcionalidades Principais

### 2.1 Gestão de Contas e Cartões
- Cadastro de contas bancárias (nome, banco, agência, conta, moeda)
- Cadastro de cartões de crédito (limite, data de fechamento, data de vencimento)
- Visualização de saldos e limites disponíveis
\n### 2.2 Importação e Conciliação\n- Importação de extratos bancários nos formatos CSV, OFX e QIF
- Mapeamento automático de transações importadas
- Ferramenta de conciliação manual de lançamentos
- Classificação automática de transações

### 2.3 Movimentações Financeiras\n- Cadastro de receitas e despesas (valor, data, categoria, conta, tag, nota)\n- Suporte a pagamentos recorrentes
- Suporte a receitas recorrentes\n- Controle de parcelamentos com acompanhamento de parcelas
- Agendamento de compromissos e pagamentos\n- Sistema de alertas para vencimentos

### 2.4 Controle Financeiro
- Gestão de contas a pagar e receber
- Visualização de fluxo de caixa\n- Previsões financeiras simples
- Relatórios e dashboards:
  - Saldo por conta
  - Despesas por categoria
  - Histórico mensal\n  - Projeção de fluxo de caixa\n- Exportação de relatórios em CSV, Excel e PDF\n
### 2.5 Assistente de IA Contextual
- Elemento visível em todas as páginas (botão flutuante ou ícone de chat)
- Chat contextual com acesso controlado aos dados autorizados
- Funcionalidades do assistente:
  - Categorização automática de transações
  - Recomendações de economia
  - Previsão de fluxo de caixa
  - Alertas de vencimentos
  - Simulações de parcelamento
  - Sugestões de orçamento
\n### 2.6 Painel de Administração de IA
- Indicador visual de status de configuração:\n  - Badge ou ícone na página de configurações indicando se há modelo de IA configurado
  - Mensagem clara exibindo 'Modelo Configurado' (com ícone de check verde) ou 'Nenhum Modelo Configurado' (com ícone de alerta laranja)
  - Exibição do nome do modelo ativo quando configurado
- Configuração de modelos de IA (seleção de modelo, endpoint, chave de API)
- Ajuste de prompts-padrão e templates de resposta
- Controles de permissão granulares:
  - Acesso restrito por padrão (leitura somente de campos necessários)
  - Níveis configuráveis: leitura agregada, leitura transacional, leitura completa
  - Toggle para 'acesso total' com confirmação e consentimento explícito
- Logs e histórico de conversas com IA
- Registro de auditoria (quem ativou, quando, finalidade)
- Opções de apagar ou exportar histórico conforme políticas de retenção

## 3. Segurança e Privacidade
\n### 3.1 Proteção de Dados
- TLS/HTTPS em todas as comunicações
- Criptografia em repouso para dados sensíveis
- Mascaramento e criptografia de números de conta e cartão
- Anonymization/aggregation de dados antes de enviar ao modelo de IA

### 3.2 Autenticação e Autorização
- Autenticação por email/senha com MFA (autenticação multifator)\n- RBAC (controle de acesso baseado em papéis): admin, financeiro, usuário
- Consentimento explícito do usuário para acesso de IA a dados sensíveis\n
### 3.3 Auditoria
- Registro completo de acesso a dados por modelos de IA\n- Auditoria de ações de usuários humanos
- Middleware de validação de escopos antes de enviar dados ao conector de IA
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
\n### 5.2 Backup e Recuperação
- Backup automático do banco de dados
- Procedimentos documentados de recuperação de desastres\n
## 6. Arquitetura Técnica

### 6.1 Stack Tecnológica
- Backend: Flask ou FastAPI
- Banco de dados: PostgreSQL
- Frontend: React
- Containerização: Docker / docker-compose
- CI/CD: GitHub Actions

### 6.2 Componentes
- Serviço de IA como componente externo configurável
- Conector que chama endpoints de modelos (OpenAI/compatíveis, instâncias privadas)
- Credenciais gerenciadas via vault/segredos (nunca na UI)
- Design modular: módulo de importação, processamento, API, UI, conector IA

### 6.3 Testes
- Cobertura de testes unitários e de integração
- Testes de segurança (SAST/DAST)

## 7. Escopo do MVP
\n### 7.1 Funcionalidades Iniciais
- Autenticação com MFA
- Cadastro de contas e cartões
- Importação de extratos CSV/OFX
- CRUD de transações
- Conciliação manual
- Dashboard básico com visualizações principais
- Assistente de IA em modo leitura-agregada (acesso apenas a somatórios e categorias)
- Botão de chat com IA nas telas principais
- Painel de administração com indicador visual de status de configuração, configuração de modelo e logs de chat

### 7.2 Versões Futuras (1.1 / 1.2)
- Conciliação automática por Machine Learning
- Integração com APIs bancárias (Open Banking)
- Importação automatizada OFX
- Permissões granulares avançadas para modelos de IA
- Opção de 'acesso total' sob consentimento com auditoria completa
- Aplicativo móvel
\n## 8. Estilo de Design

### 8.1 Paleta de Cores
- Cores principais: azul profissional (#2C3E50) e verde financeiro (#27AE60) para transmitir confiança e estabilidade
- Cores de apoio: cinza claro (#ECF0F1) para fundos e branco (#FFFFFF) para cards

### 8.2 Layout
- Layout em cards para organização modular de informações financeiras
- Sidebar fixa com navegação principal
- Dashboard com grid responsivo para visualização de métricas\n
### 8.3 Elementos Visuais\n- Ícones minimalistas para categorias e ações
- Gráficos limpos e legíveis (linhas para fluxo de caixa, pizza para categorias)
- Botão flutuante do assistente de IA com ícone de chat, posicionado no canto inferior direito
- Bordas suaves com raio de 8px para cards e botões
- Sombras sutis para criar hierarquia visual
- Badge de status com ícone de check verde para modelo configurado ouícone de alerta laranja para ausência de configuração