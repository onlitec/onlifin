# Documento de Requisitos da Plataforma de Gestão Financeira Pessoal

## 1. Visão Geral\n
### 1.1 Nome da Plataforma
Plataforma de Gestão Financeira Pessoal com Assistente de IA
\n### 1.2 Descrição
Plataforma web (MVP) para gestão de finanças pessoais que permite importar extratos bancários, gerenciar contas e cartões, cadastrar receitas e despesas, e oferece um assistente de IA contextual acessível em toda a interface. Inclui painel de administração para configurar modelos de IA, controlar permissões de acesso aos dados, configurar plugins e registrar auditoria de interações.

## 2. Funcionalidades Principais

### 2.1 Gestão de Contas e Cartões
- Cadastro de contas bancárias (nome, banco, agência, conta, moeda)
- Cadastro de cartões de crédito (limite, data de fechamento, data de vencimento)
- **Visualização de saldo atual da conta com cálculo automático:**
  - Saldo inicial da conta
  - Saldo atualizado em tempo real considerando:\n    - Despesas pagas: diminuem o saldo da conta
    - Receitas recebidas: aumentam o saldo da conta
  - Fórmula: Saldo Atual = Saldo Inicial + Receitas Recebidas - Despesas Pagas
  - Exibição clara do saldo atual na página de contas (https://onlifin.onlitec.com.br/accounts)\n  - Atualização automática do saldo sempre que uma transação for registrada, editada ou excluída
- Visualização de limites disponíveis de cartões de crédito
\n### 2.2 Importação e Conciliação\n- Importação de extratos bancários nos formatos CSV, OFX e QIF
- Mapeamento automático de transações importadas\n- Ferramenta de conciliação manual de lançamentos
- Classificação automática de transações\n\n### 2.3 Movimentações Financeiras
- Cadastro de receitas e despesas (valor, data, categoria, conta, tag, nota)
- **Edição de transações existentes: permite alterar valor, data, descrição, título e categoria de transações já cadastradas**
- **Atualização automática do saldo da conta ao cadastrar, editar ou excluir transações**
- Suporte a pagamentos recorrentes\n- Suporte a receitas recorrentes
- Controle de parcelamentos com acompanhamento de parcelas
- Agendamento de compromissos e pagamentos
- Sistema de alertas para vencimentos\n- **Cadastro de transações pelo modelo de IA: o assistente de IA pode criar transações na plataforma mediante solicitação do usuário, incluindo receitas, despesas, pagamentos recorrentes e parcelamentos**
\n### 2.4 Controle Financeiro
- Gestão de contas a pagar e receber
- Visualização de fluxo de caixa
- Previsões financeiras simples
- Relatórios e dashboards:\n  - Saldo por conta (com cálculo automático baseado em receitas e despesas)
  - Despesas por categoria
  - Histórico mensal\n  - Projeção de fluxo de caixa
- Exportação de relatórios em CSV, Excel e PDF

### 2.5 Assistente de IA Contextual\n- Elemento visível em todas as páginas (botão flutuante ou ícone de chat)
- Chat contextual com acesso total aos dados da plataforma
- Funcionalidades do assistente:
  - Categorização automática de transações
  - Recomendações de economia\n  - Previsão de fluxo de caixa
  - Alertas de vencimentos
  - Simulações de parcelamento
  - Sugestões de orçamento
  - **Cadastro de transações mediante solicitação do usuário (receitas, despesas, pagamentos recorrentes, parcelamentos)**
- Análise completa de contas cadastradas
- Consulta detalhada de extratos de transações
- Análise de pagamentos e recebimentos
- Geração e interpretação de relatórios\n- Consulta de saldos em tempo real
- **Integração com plugins configurados para funcionalidades estendidas**

### 2.6 Painel de Administração deIA
- Indicador visual de status de configuração:\n  - Badge ou ícone na página de configurações indicando se há modelo de IA configurado
  - Mensagem clara exibindo 'Modelo Configurado' (com ícone de check verde) ou 'Nenhum Modelo Configurado' (com ícone de alerta laranja)
  - Exibição do nome do modelo ativo quando configurado
- Configuração de modelos de IA (seleção de modelo, endpoint, chave de API)
- Ajuste de prompts-padrão e templates de resposta\n- Controles de permissão de acesso total:\n  - Acesso completo a todas as contas cadastradas
  - Acesso completo a todas as transações (receitas e despesas)
  - Acesso completo a pagamentos e recebimentos
  - Acesso completo a relatórios financeiros
  - Acesso completo a saldos das contas
  - Acesso completo a extratos de transações
  - **Permissão para cadastro de transações: toggle paraativar/desativar a capacidadedo modelo de IA criar transações na plataforma**
  - Toggle para ativar/desativar acesso total com confirmação e consentimento explícito do usuário
- Logs e histórico de conversas com IA
- Registro de auditoria detalhado:\n  - Quem ativou o acesso total
  - Quando foi ativado
  - Finalidade declarada
  - Histórico de consultas realizadas pelo modelo de IA
  - Dados acessados em cada interação
  - **Registro de todas as transações criadas pelo modelo de IA (data, hora, tipo, valor, usuário solicitante)**
- Opções de apagar ou exportar histórico conforme políticas de retenção\n
### 2.7 Gestão de Plugins
- **Cadastro e configuração de plugins na plataforma**
- **Interface de gerenciamento de plugins com as seguintes funcionalidades:**
  - Lista de plugins disponíveis e instalados
  - Ativação/desativação de plugins
  - Configuração de parâmetros específicos de cada plugin (chaves de API, endpoints, credenciais)
  - Indicador visual de status (ativo/inativo) com ícone de check verde ou alerta laranja
- **Permissões de acesso para plugins:**
  - Controle granular de quais dados da plataforma cada plugin pode acessar
  - Toggle individual para cada tipo de acesso (contas, transações, relatórios, etc.)
  - Termo de consentimento específico para cada plugin
- **Integração de plugins com o assistente de IA:**\n  - Plugins podem estender funcionalidades do assistente de IA
  - Assistente pode invocar funções de plugins mediante solicitação do usuário
  - Registro de auditoria de todas as chamadas a plugins
- **Logs e auditoria de plugins:**
  - Registro detalhado de todas as ações executadas por plugins
  - Histórico de acessos aos dados da plataforma
  - Monitoramento de erros e falhas de plugins
- **Segurança de plugins:**
  - Validação de credenciais e autenticação de plugins
  - Criptografia de dados sensíveis transmitidos a plugins
  - Isolamento de execução para prevenir interferências entre plugins

## 3. Segurança e Privacidade

### 3.1 Proteção de Dados
- TLS/HTTPS em todas as comunicações
- Criptografia em repouso para dados sensíveis
- Mascaramento e criptografia de números de conta e cartão
- Transmissão segura de dados completos ao modelo de IA quando acesso total estiver ativado
- **Transmissão segura de dados a plugins conforme permissões configuradas**\n
### 3.2 Autenticação e Autorização
- Autenticação por email/senha com MFA (autenticação multifator)
- RBAC (controle de acesso baseado em papéis): admin, financeiro, usuário
- Consentimento explícito e informado do usuário para conceder acesso totaldo modelo de IA a todos os dados financeiros:\n  - Contas cadastradas
  - Transações completas
  - Pagamentos e recebimentos
  - Relatórios financeiros
  - Saldos das contas
  - Extratos de transações
  - **Permissão para cadastro de transações pelo modelo de IA**
- **Consentimento explícito para cada plugin com detalhamento de dados acessados**
- Termo de consentimento detalhado explicando o escopo do acesso total e da permissão de cadastro
- Opção de revogar acesso total e permissão de cadastro a qualquer momento
- **Opção de revogar permissões de plugins individualmente**

### 3.3 Auditoria\n- Registro completo e detalhado de todos os acessos realizados pelo modelo de IA
- Auditoria de ações de usuários humanos
- Log de todas as consultas do modelo de IA aos dados da plataforma
- **Log detalhado de todas as transações criadas pelo modelo de IA, incluindo timestamp, usuário solicitante, dados da transação e confirmação de execução**
- **Log completo de todas as ações executadas por plugins (acessos, modificações, chamadas de API)**
- Middleware de validação e registro antes de enviar dados ao conector de IA
- **Middleware de validação e registro para comunicação com plugins**
- Relatório de auditoria acessível ao usuário para transparência

## 4. Extensibilidade\n
### 4.1 API\n- API REST/GraphQL bemdocumentada\n- Suporte para integrações (apps móveis, plugins, serviços de contabilidade)
- **Endpoints específicos para integração de plugins externos**

### 4.2 Webhooks
- Eventos disponíveis:
  - Nova transação\n  - Vencimento próximo
  - Sugestão gerada pelo assistente\n  - **Transação criada pelo modelo de IA**
  - **Ação executada por plugin**
\n## 5. Observabilidade e Confiabilidade

### 5.1 Monitoramento
- Sistema de logs estruturados
- Métricas de usodo assistente de IA
- Alertas para erros e falhas de importação
- Monitoramento de acessos do modelo de IA aos dados
- **Monitoramento de transações criadas pelo modelo de IA**
- **Monitoramento de performance e erros de plugins**
- **Alertas para falhas de comunicação com plugins**\n
### 5.2 Backup e Recuperação
- Backup automático do banco de dados
- Procedimentos documentados de recuperação de desastres
\n## 6. Arquitetura Técnica
\n### 6.1 Stack Tecnológica
- Backend: Flask ou FastAPI
- Banco de dados: PostgreSQL
- Frontend: React\n- Containerização: Docker / docker-compose
- CI/CD: GitHub Actions
\n### 6.2 Componentes\n- Serviço de IA como componente externo configurável
- Conector que chama endpoints de modelos (OpenAI/compatíveis, instâncias privadas)
- **Sistema de gerenciamento de plugins com arquitetura modular**
- **Conector de plugins para comunicação segura com serviços externos**
- Camada de acesso a dados que fornece ao modelo de IA:\n  - Dados completos de contas cadastradas
  - Histórico completo de transações
  - Registros de pagamentos e recebimentos
  - Relatórios financeiros gerados\n  - Saldos atualizados das contas
  - Extratos detalhados de transações
  - **Interface de escrita para cadastro de transações pelo modelo de IA**
- **Camada de acesso controlado para plugins conforme permissões configuradas**
- **Módulo de cálculo de saldo em tempo real:**
  - Calcula saldo atual baseado em saldo inicial, receitas recebidas e despesas pagas
  - Atualiza saldo automaticamente ao registrar, editar ou excluir transações
  - Fornece saldo atualizado para exibição na interface e para consultas do assistente de IA
- Credenciais gerenciadas via vault/segredos (nunca na UI)
- Design modular: módulo de importação, processamento, API, UI, conector IA, **gerenciador de plugins**
- **Módulo de validação e confirmação para transações criadas pelo modelo de IA**\n- **Módulo de validação e sandbox para execução segura de plugins**

### 6.3 Testes
- Cobertura de testes unitários e de integração
- Testes de segurança (SAST/DAST)
- Testes de acessodo modelo de IA aos dados
- **Testes de cadastro de transações pelo modelo de IA, incluindo validação de dados e auditoria**
- **Testes de cálculo de saldo em tempo real:**
  - Validação de cálculo correto ao adicionar receitas (aumento de saldo)
  - Validação de cálculo correto ao adicionar despesas (diminuição de saldo)
  - Validação de atualização de saldo ao editar ou excluir transações
- **Testes de integração com plugins**
- **Testes de segurança e isolamento de plugins**
\n## 7. Escopo do MVP

### 7.1 Funcionalidades Iniciais
- Autenticação com MFA
- Cadastro de contas e cartões
- **Exibição de saldo atual da conta com cálculo automático baseado em receitas recebidas e despesas pagas**
- Importação de extratos CSV/OFX\n- CRUD de transações (incluindo edição de valor, data, descrição, título e categoria)
- **Atualização automática do saldo da conta ao cadastrar, editar ou excluir transações**
- Conciliação manual\n- Dashboard básico com visualizações principais
- Assistente de IA com acesso total configurável a:\n  - Todas as contas cadastradas
  - Todas as transações (receitas e despesas)
  - Todos os pagamentos e recebimentos\n  - Todos os relatórios financeiros
  - Saldos de todas as contas
  - Extratos completos de transações
  - **Permissão para cadastro de transações mediante solicitação do usuário**\n- Botão de chat comIA nas telas principais
- Painel de administração com:\n  - Indicador visual de status de configuração
  - Configuração de modelo de IA
  - Toggle de acesso total com termo de consentimento
  - **Toggle de permissão para cadastro de transações pelo modelo de IA**
  - **Interface de gerenciamento de plugins**
  - **Configuração de permissões para plugins**
  - Logs detalhados de chat e acessos do modelo de IA
  - **Logs de transações criadas pelo modelo de IA**
  - **Logs de ações executadas por plugins**
- Relatório de auditoria de acessos
\n### 7.2 Versões Futuras (1.1 / 1.2)\n- Conciliação automática por Machine Learning
- Integração com APIs bancárias (Open Banking)
- Importação automatizada OFX\n- Permissões granulares avançadas para modelos de IA com níveis intermediários de acesso\n- **Marketplace de plugins com plugins pré-aprovados**
- **SDK para desenvolvimento de plugins personalizados**
- Aplicativo móvel\n\n## 8. Estilo de Design

### 8.1 Paleta de Cores
- Cores principais: azul profissional (#2C3E50) e verde financeiro (#27AE60) para transmitir confiança e estabilidade
- Cores de apoio: cinza claro (#ECF0F1) para fundos e branco (#FFFFFF) para cards
\n### 8.2 Layout
- Layout em cards para organização modular de informações financeiras
- Sidebar fixa com navegação principal
- Dashboard com grid responsivo para visualização de métricas
\n### 8.3 Elementos Visuais
- Ícones minimalistas para categorias eações
- Gráficos limpos e legíveis (linhas para fluxo de caixa, pizza para categorias)
- Botão flutuante do assistente de IA com ícone de chat, posicionado no canto inferior direito
- Bordas suaves com raio de 8px para cards e botões
- Sombras sutis para criar hierarquia visual
- Badge de status com ícone de check verde para modelo configurado ouícone de alerta laranja para ausência de configuração
- Indicador visual de acesso total ativo (ícone de cadeado aberto verde) quando o modelo de IA tiver permissão completa
- **Indicador visual de permissão de cadastro ativa (ícone de lápis verde) quando o modelo de IA tiver permissão para criar transações**
- **Badge de status para plugins (ícone de check verde paraativo, ícone cinza para inativo)**
- **Ícone de engrenagem para acesso às configurações de plugins**
- **Exibição destacada do saldo atual da conta na página de contas com formatação clara (valor em negrito, cor verde para saldo positivo, cor vermelha para saldo negativo)**

## 9. Referências de Interface

### 9.1 Imagens de Referência
- Interfacedo Assistente Financeiro IA: {7FFF7068-4DCD-48E0-8714-FF810EA6906F}.png
- Exemplo de mensagem de erro do sistema: {9C49DCD9-E33F-45CE-86FB-3A635D5A7630}.png