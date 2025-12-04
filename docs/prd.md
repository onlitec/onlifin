# Documento de Requisitos da Plataforma de Gestão Financeira Pessoal

## 1. Visão Geral

### 1.1 Nome da Plataforma
Plataforma de Gestão Financeira Pessoal com Assistente de IA

### 1.2 Descrição
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
  - Exibição clara do saldo atual na página de contas (https://onlifin.onlitec.com.br/accounts)
  - Atualização automática do saldo sempre que uma transação for registrada, editada ou excluída
- Visualização de limites disponíveis de cartões de crédito
\n### 2.2 Importação e Conciliação\n- **Importação de extratos bancários nos formatos CSV, OFX e QIF:**
  - **Suporte completo ao formato OFX (Open Financial Exchange):**
    - Parsing de arquivos OFX versões 1.x (SGML) e 2.x (XML)
    - Extração automática de dados de transações: data, descrição, valor, tipo (débito/crédito), saldo
    - Identificação automática de conta bancária associada através de informações do arquivo OFX
    - Validação de integridade do arquivo OFX antesdo processamento
    - Tratamento de erros e feedback claro em caso de arquivo corrompido ou formato inválido
  - **Suporte ao formato CSV:**
    - Mapeamento flexível de colunas (data, descrição, valor, tipo)\n    - Detecção automática de delimitadores (vírgula, ponto e vírgula, tabulação)
  - **Suporte ao formato QIF (Quicken Interchange Format):**
    - Parsing de arquivos QIF com extração de transações
    - Mapeamento de campos QIF para estrutura interna da plataforma
- **Importação de extratos diretamente na interface da plataforma**
- **Importação de extratos via chatbot flutuante com novo fluxo:**
  - **Passo 1: Upload do extrato**
    - Botão de upload de arquivo visível no chatbot flutuante (ícone de clipe ou upload)\n    - Suporte aos formatos CSV, OFX e QIF
    - Após seleção do arquivo, sistema salva o extrato na plataforma
    - Feedback visual de upload concluído (barra de progresso e mensagem de confirmação)
  - **Passo 2: Comando de análise**
    - Após salvamento do extrato, surge botão 'Analisar Extrato' no chatbot\n    - Usuário clica no botão para acionar análise do modelo de IA
    - Modelo de IA analisa o arquivo já salvo na plataforma
  - **Passo 3: Exibição de resultados em popup**
    - Após análise, sistema exibe janela popup com resultados\n    - **Estrutura do popup:**
      - Título:'Resultado da Análise do Extrato'
      - Lista de transações ordenadas por data (da mais antiga para a mais recente)
      - Cada transação exibida com:
        - Data da transação
        - Descrição/títulodo estabelecimento
        - Valor da transação
        - Dropdown de seleção de categoria ao lado de cada transação
        - Categoria sugerida pelo modelo de IA pré-selecionada no dropdown
        - Possibilidade de alterar categoria manualmente via dropdown
      - Botão 'Cadastrar Transações' na parte inferior do popup
    - **Ação do botão 'Cadastrar Transações':**
      - Cadastra todas as transações listadas no popup
      - Cada transação é registrada na categoria selecionada no dropdown correspondente
      - Após cadastro, popup é fechado e chatbot exibe mensagem de confirmação
      - Saldo das contas é atualizado automaticamente\n- Mapeamento automático de transações importadas\n- Ferramenta de conciliação manual de lançamentos
- Classificação automática de transações\n\n### 2.3 Análise e Categorização Automática de Transações com IA
- **Análise automática de transações importadas:**
  - **Trigger:acionado manualmente pelo usuário através do botão 'Analisar Extrato' no chatbot após upload e salvamento do arquivo**
  - O modelo de IA analisa cada transação do extrato salvo utilizando:
    - Descrição da transação
    - Título ou nome do estabelecimento
    - Valor e data da transação (opcional)
  - Com base nessas informações, o modelo sugere a categoria apropriada para cada transação, escolhendo entre categorias já existentes no sistema
- **Sugestão de novas categorias:**
  - Quando o modelo identificar padrões ou estabelecimentos que não se encaixam nas categorias existentes, ele sugere a criação de uma nova categoria
  - As categorias sugeridas são incluídas no dropdown de seleção de categorias no popup
- **Cadastro de transações via popup:**
  - Popup exibe lista ordenada de transações por data
  - Cada transação possui dropdown de categoria com sugestãodo modelo de IA pré-selecionada
  - Usuário pode alterar categoria manualmente antesdo cadastro
  - Ao clicar em 'Cadastrar Transações', sistema:\n    - Cria automaticamente as categorias novas selecionadas pelo usuário (se houver)
    - Registra as transações nas categorias correspondentes (existentes ou recém-criadas)
    - Fecha o popup e exibe mensagem de confirmação no chatbot
- **Aprendizado contínuo:**
  - Histórico de aprendizado do modelo: quanto mais o usuário confirmar ou corrigir categorias, mais preciso o modelo se torna
  - Sugestão de categoria automática baseada em padrões frequentes do usuário
\n### 2.4 Movimentações Financeiras
- Cadastro de receitas e despesas (valor, data, categoria, conta, tag, nota)
- **Edição de transações existentes: permite alterar valor, data, descrição, título e categoria de transações já cadastradas**
- **Atualização automática do saldo da conta ao cadastrar, editar ou excluir transações**
- Suporte a pagamentos recorrentes\n- Suporte a receitas recorrentes
- Controle de parcelamentos com acompanhamento de parcelas
- Agendamento de compromissos e pagamentos
- Sistema de alertas para vencimentos\n- **Cadastro de transações pelo modelo de IA: o assistente de IA pode criar transações na plataforma mediante solicitação do usuário, incluindo receitas, despesas, pagamentos recorrentes e parcelamentos**\n
### 2.5 Controle Financeiro
- Gestão de contas a pagar e receber
- Visualização de fluxo de caixa
- Previsões financeiras simples
- Relatórios e dashboards:\n  - Saldo por conta (com cálculo automático baseado em receitas e despesas)
  - Despesas por categoria
  - Histórico mensal\n  - Projeção de fluxo de caixa
- Exportação de relatórios em CSV, Excel e PDF
\n### 2.6 Assistente de IA Contextual\n- Elemento visível em todas as páginas (botão flutuante ou ícone de chat)
- Chat contextual com acesso total aos dados da plataforma
- **Funcionalidade de upload de extrato bancário diretamente no chatbot:**
  - **Botão de upload de arquivo visível no chatbot flutuante (ícone de clipe ou upload)**
  - **Suporte aos formatos CSV, OFX e QIF**
  - **Fluxo de importação e análise:**
    1. Usuário clica no botão de upload e seleciona arquivo
    2. Sistema salva o extrato na plataforma e exibe confirmação
    3. Botão 'Analisar Extrato' surge no chatbot
    4. Usuário clica no botão para acionar análise do modelo de IA
    5. Modelo de IA processa o arquivo salvo e identifica categorias
    6. Sistema exibe popup com lista de transações ordenadas por data\n    7. Cada transação possui dropdown de categoria com sugestão pré-selecionada
    8. Usuário revisa e ajusta categorias conforme necessário
    9. Usuário clica em 'Cadastrar Transações' no popup
    10. Sistema cadastra todas as transações nas categorias selecionadas
    11. Popup é fechado e chatbot exibe mensagem de confirmação
- Funcionalidadesdo assistente:\n  - Categorização automática de transações
  - Recomendações de economia\n  - Previsão de fluxo de caixa
  - Alertas de vencimentos
  - Simulações de parcelamento
  - Sugestões de orçamento
  - **Cadastro de transações mediante solicitação do usuário (receitas, despesas, pagamentos recorrentes, parcelamentos)**
  - **Análise e categorização automática de extratos bancários importados via chatbot**
  - **Sugestão de novas categorias baseadas em padrões identificados**
- Análise completa de contas cadastradas
- Consulta detalhada de extratos de transações
- Análise de pagamentos e recebimentos
- Geração e interpretação de relatórios
- Consulta de saldos em tempo real
- **Integração com plugins configurados para funcionalidades estendidas**

### 2.7 Painel de Administração deIA
- Indicador visual de status de configuração:\n  - Badge ou ícone na página de configurações indicando se há modelo de IA configurado
  - Mensagem clara exibindo 'Modelo Configurado' (com ícone de check verde) ou 'Nenhum Modelo Configurado' (com ícone de alerta laranja)
  - Exibição do nome do modelo ativo quando configurado
- Configuração de modelos de IA (seleção de modelo, endpoint, chave de API)
- Ajuste de prompts-padrão e templates de resposta
- Controles de permissão de acesso total:\n  - Acesso completo a todas as contas cadastradas
  - Acesso completo a todas as transações (receitas e despesas)
  - Acesso completo a pagamentos e recebimentos
  - Acesso completo a relatórios financeiros
  - Acesso completo a saldos das contas
  - Acesso completo a extratos de transações
  - **Permissão para cadastro de transações: toggle paraativar/desativar a capacidade do modelo de IA criar transações na plataforma**
  - **Permissão para análise e categorização automática de extratos bancários**
  - Toggle para ativar/desativar acesso total com confirmação e consentimento explícito do usuário
- Logs e histórico de conversas com IA
- Registro de auditoria detalhado:\n  - Quem ativou o acesso total
  - Quando foi ativado
  - Finalidade declarada
  - Histórico de consultas realizadas pelo modelo de IA
  - Dados acessados em cada interação
  - **Registro de todas as transações criadas pelo modelo de IA (data, hora, tipo, valor, usuário solicitante)**
  - **Registro de análises e categorizações automáticas realizadas pelo modelo de IA**
- Opções de apagar ou exportar histórico conforme políticas de retenção\n
### 2.8 Gestão de Plugins
- **Cadastro e configuração de plugins na plataforma**
- **Interface de gerenciamento de plugins com as seguintes funcionalidades:**
  - Lista de plugins disponíveis e instalados
  - Ativação/desativação de plugins\n  - Configuração de parâmetros específicos de cada plugin (chaves de API, endpoints, credenciais)
  - Indicador visual de status (ativo/inativo) comícone de check verde ou alerta laranja
- **Permissões de acesso para plugins:**
  - Controle granular de quais dados da plataforma cada plugin pode acessar
  - Toggle individual para cada tipo de acesso (contas, transações, relatórios, etc.)
  - Termo de consentimento específico para cada plugin
- **Integração de plugins com o assistente de IA:**
  - Plugins podem estender funcionalidades do assistente de IA
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

## 3. Segurança e Privacidade\n
### 3.1 Proteção de Dados
- TLS/HTTPS em todas as comunicações
- Criptografia em repouso para dados sensíveis
- Mascaramento e criptografia de números de conta e cartão
- Transmissão segura de dados completos ao modelo de IA quando acesso total estiver ativado
- **Transmissão segura de dados a plugins conforme permissões configuradas**

### 3.2 Autenticação e Autorização
- Autenticação por email/senha com MFA (autenticação multifator)
- RBAC (controle de acesso baseado em papéis): admin, financeiro, usuário
- Consentimento explícito e informado do usuário para conceder acesso totaldo modelo de IA a todos os dados financeiros:\n  - Contas cadastradas
  - Transações completas
  - Pagamentos e recebimentos
  - Relatórios financeiros\n  - Saldos das contas
  - Extratos de transações
  - **Permissão para cadastro de transações pelo modelo de IA**
  - **Permissão para análise e categorização automática de extratos**
- **Consentimento explícito para cada plugin com detalhamento de dados acessados**
- Termo de consentimento detalhado explicando o escopo do acesso total e da permissão de cadastro
- Opção de revogar acesso total e permissão de cadastro a qualquer momento
- **Opção de revogar permissões de plugins individualmente**

### 3.3 Auditoria\n- Registro completo e detalhado de todos os acessos realizados pelo modelo de IA
- Auditoria de ações de usuários humanos
- Log de todas as consultas do modelo de IA aos dados da plataforma
- **Log detalhado de todas as transações criadas pelo modelo de IA, incluindo timestamp, usuário solicitante, dados da transação e confirmação de execução**
- **Log de análises e categorizações automáticas realizadas pelo modelo de IA**
- **Log completo de todas as ações executadas por plugins (acessos, modificações, chamadas de API)**
- Middleware de validação e registro antes de enviar dados ao conector de IA
- **Middleware de validação e registro para comunicação com plugins**
- Relatório de auditoria acessível ao usuário para transparência

## 4. Extensibilidade\n
### 4.1 API\n- API REST/GraphQL bemdocumentada\n- Suporte para integrações (apps móveis, plugins, serviços de contabilidade)
- **Endpoints específicos para integração de plugins externos**
\n### 4.2 Webhooks
- Eventos disponíveis:\n  - Nova transação\n  - Vencimento próximo
  - Sugestão gerada pelo assistente\n  - **Transação criada pelo modelo de IA**
  - **Categorização automática concluída**
  - **Ação executada por plugin**
\n## 5. Observabilidade e Confiabilidade

### 5.1 Monitoramento
- Sistema de logs estruturados
- Métricas de usodo assistente de IA
- Alertas para erros e falhas de importação
- Monitoramento de acessos do modelo de IA aos dados\n- **Monitoramento de transações criadas pelo modelo de IA**
- **Monitoramento de análises e categorizações automáticas**
- **Monitoramento de performance e erros de plugins**
- **Alertas para falhas de comunicação com plugins**

### 5.2 Backup e Recuperação
- Backup automático do banco de dados
- Procedimentos documentados de recuperação de desastres
\n## 6. Arquitetura Técnica

### 6.1 Stack Tecnológica
- Backend: Flask ou FastAPI
- Banco de dados: PostgreSQL\n- Frontend: React\n- Containerização: Docker / docker-compose
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
  - **Interface de análise e categorização automática de extratos**
- **Camada de acesso controlado para plugins conforme permissões configuradas**
- **Módulo de cálculo de saldo em tempo real:**
  - Calcula saldo atual baseado em saldo inicial, receitas recebidas e despesas pagas
  - Atualiza saldo automaticamente ao registrar, editar ou excluir transações
  - Fornece saldo atualizado para exibição na interface e para consultas do assistente de IA
- **Módulo de análise e categorização automática:**
  - **Trigger manual:acionado pelo usuário através do botão 'Analisar Extrato' no chatbot após upload e salvamento do arquivo**
  - Processa extratos salvos e identifica padrões\n  - Sugere categorias existentes ou novas categorias
  - Exibe resultados em popup com lista ordenada de transações por data
  - Permite revisão e edição de categorias via dropdown antes do cadastro
  - Aprende com confirmações e correções do usuário
- **Módulo de importação de arquivos OFX:**
  - Parser OFX para versões1.x (SGML) e 2.x (XML)
  - Extração de dados de transações: data, descrição, valor, tipo, saldo
  - Validação de integridade e tratamento de erros
  - Mapeamento automático de conta bancária
- **Módulo de upload e salvamento de extratos no chatbot:**
  - Interface de upload de arquivo no chatbot flutuante
  - Salvamento seguro do arquivo na plataforma
  - Feedback visual de progresso e confirmação de upload
  - Geração de botão 'Analisar Extrato' após salvamento
- **Módulo de popup de resultados de análise:**
  - Exibição de janela popup após análise do modelo de IA
  - Lista ordenada de transações por data\n  - Dropdown de seleção de categoria para cada transação
  - Categoria sugerida pelo modelo de IA pré-selecionada
  - Botão 'Cadastrar Transações' na parte inferior\n  - Cadastro em lote de todas as transações nas categorias selecionadas
- Credenciais gerenciadas via vault/segredos (nunca na UI)
- Design modular: módulo de importação, processamento, API, UI, conector IA, **gerenciador de plugins**
- **Módulo de validação e confirmação para transações criadas pelo modelo de IA**\n- **Módulo de validação e sandbox para execução segura de plugins**
\n### 6.3 Testes
- Cobertura de testes unitários e de integração
- Testes de segurança (SAST/DAST)
- Testes de acessodo modelo de IA aos dados
- **Testes de cadastro de transações pelo modelo de IA, incluindo validação de dados e auditoria**
- **Testes de análise e categorização automática:**
  - Validação de trigger manual após upload e salvamento
  - Validação de sugestões de categorias existentes
  - Validação de sugestões de novas categorias
  - Validação de exibição de popup com lista ordenada de transações
  - Validação de dropdown de categorias com pré-seleção
  - Validação de cadastro em lote de transações
  - Validação de aprendizado contínuo do modelo\n- **Testes de importação de arquivos OFX:**
  - Validação de parsing de OFX versões 1.x e 2.x
  - Validação de extração correta de transações
  - Validação de tratamento de erros para arquivos corrompidos
  - Validação de mapeamento automático de conta bancária
- **Testes de cálculo de saldo em tempo real:**
  - Validação de cálculo correto ao adicionar receitas (aumento de saldo)
  - Validação de cálculo correto ao adicionar despesas (diminuição de saldo)
  - Validação de atualização de saldo ao editar ou excluir transações
- **Testes de upload e salvamento de extrato no chatbot:**
  - Validação de upload de arquivo via chatbot
  - Validação de salvamento seguro na plataforma
  - Validação de feedback visual de progresso
  - Validação de geração de botão 'Analisar Extrato'
- **Testes de popup de resultados:**
  - Validação de exibição de popup após análise\n  - Validação de ordenação de transações por data
  - Validação de dropdown de categorias\n  - Validação de pré-seleção de categoria sugerida
  - Validação de cadastro em lote ao clicar em 'Cadastrar Transações'
- **Testes de integração com plugins**\n- **Testes de segurança e isolamento de plugins**

## 7. Escopodo MVP

### 7.1 Funcionalidades Iniciais
- Autenticação com MFA
- Cadastro de contas e cartões
- **Exibição de saldo atual da conta com cálculo automático baseado em receitas recebidas e despesas pagas**
- **Importação de extratos CSV, OFX e QIF com suporte completo ao formato OFX (versões 1.x e 2.x)**
- **Importação de extratos diretamente na interface**
- **Upload de extrato bancário via chatbot flutuante (CSV, OFX e QIF) com novo fluxo:**
  - Botão de upload de arquivo no chatbot
  - Salvamento do extrato na plataforma
  - Botão 'Analisar Extrato' após salvamento
  - Análise do arquivo salvo pelo modelo de IA
  - Exibição de popup com lista ordenada de transações por data
  - Dropdown de categoria para cada transação com sugestão pré-selecionada
  - Botão 'Cadastrar Transações' no popup para cadastro em lote
- **Análise e categorização automática de transações comIA:**
  - Trigger manual via botão 'Analisar Extrato' no chatbot
  - Sugestão de categorias existentes\n  - Sugestão de novas categorias incluídas no dropdown
  - Interface de revisão em popup antes do cadastro
  - Cadastro em lote de transações categorizadas
  - Aprendizado contínuo baseado em confirmações do usuário
- CRUD de transações (incluindo edição de valor, data, descrição, título e categoria)
- **Atualização automática do saldo da conta ao cadastrar, editar ou excluir transações**
- Conciliação manual\n- Dashboard básico com visualizações principais
- Assistente de IA com acesso total configurável a:\n  - Todas as contas cadastradas
  - Todas as transações (receitas e despesas)
  - Todos os pagamentos e recebimentos
  - Todos os relatórios financeiros
  - Saldos de todas as contas
  - Extratos completos de transações
  - **Permissão para cadastro de transações mediante solicitação do usuário**
  - **Permissão para análise e categorização automática de extratos**
- Botão de chat com IA nas telas principais
- Painel de administração com:
  - Indicador visual de status de configuração
  - Configuração de modelo de IA
  - Toggle de acesso total com termo de consentimento
  - **Toggle de permissão para cadastro de transações pelo modelo de IA**
  - **Toggle de permissão para análise e categorização automática**
  - **Interface de gerenciamento de plugins**
  - **Configuração de permissões para plugins**
  - Logs detalhados de chat e acessos do modelo de IA
  - **Logs de transações criadas pelo modelo de IA**
  - **Logs de análises e categorizações automáticas**
  - **Logs de ações executadas por plugins**
- Relatório de auditoria de acessos
\n### 7.2 Versões Futuras (1.1 / 1.2)\n- Conciliação automática por Machine Learning
- Integração com APIs bancárias (Open Banking)
- Importação automatizada OFX\n- Permissões granulares avançadas para modelos de IA com níveis intermediários de acesso
- **Marketplace de plugins com plugins pré-aprovados**
- **SDK para desenvolvimento de plugins personalizados**
- Aplicativo móvel\n\n## 8. Estilo de Design

### 8.1 Paleta de Cores
- Cores principais: azul profissional (#2C3E50) e verde financeiro (#27AE60) para transmitir confiança e estabilidade
- Cores de apoio: cinza claro (#ECF0F1) para fundos e branco (#FFFFFF) para cards
\n### 8.2 Layout\n- Layout em cards para organização modular de informações financeiras
- Sidebar fixa com navegação principal
- Dashboard com grid responsivo para visualização de métricas
- **Popup centralizado para exibição de resultados de análise de extrato**
\n### 8.3 Elementos Visuais
-Ícones minimalistas para categorias e ações
- Gráficos limpos e legíveis (linhas para fluxo de caixa, pizza para categorias)
- Botão flutuante do assistente de IA comícone de chat, posicionado no canto inferior direito
- **Botão de upload de arquivo no chatbot (ícone de clipe ou upload)**
- **Botão 'Analisar Extrato' no chatbot após upload (ícone de lupa ou análise)**
- **Popup de resultados com:**
  - Lista ordenada de transações por data\n  - Dropdown de categoria ao lado de cada transação
  - Categoria sugerida pré-selecionada no dropdown
  - Botão 'Cadastrar Transações' em destaque na parte inferior
- Bordas suaves com raio de 8px para cards, botões e popup
- Sombras sutis para criar hierarquia visual
- Badge de status comícone de check verde para modelo configurado ou ícone de alerta laranja para ausência de configuração
- Indicador visual de acesso total ativo (ícone de cadeado aberto verde) quando o modelo de IA tiver permissão completa
- **Indicador visual de permissão de cadastro ativa (ícone de lápis verde) quando o modelo de IA tiver permissão para criar transações**
- **Indicador visual de permissão de análise ativa (ícone de lupa verde) quando o modelo de IA tiver permissão para analisar e categorizar extratos**
- **Badge de status para plugins (ícone de check verde paraativo, ícone cinza para inativo)**
- **Ícone de engrenagem para acesso às configurações de plugins**
- **Exibição destacada do saldo atual da conta na página de contas com formatação clara (valor em negrito, cor verde para saldo positivo, cor vermelha para saldo negativo)**

## 9. Referências de Interface

### 9.1 Imagens de Referência
- Interfacedo Assistente Financeiro IA: {7FFF7068-4DCD-48E0-8714-FF810EA6906F}.png
- Exemplo de mensagem de erro do sistema: {9C49DCD9-E33F-45CE-86FB-3A635D5A7630}.png
- Exemplo de mensagem de erro de importação OFX: {2C7B1F61-7FE3-4148-B737-A544FBDEEF2D}.png

---

## 10. Planejamento Detalhado de Implementação

### 10.1 Fase 1: Preparação do Ambiente (Sprint 1)
- Configuração do ambiente de desenvolvimento
- Setup do repositório Git e CI/CD
- Configuração do Docker e docker-compose
- Setup do banco de dados PostgreSQL
- Configuração do backend (Flask/FastAPI)
- Configuração do frontend (React)
\n### 10.2 Fase 2: Implementação do Módulo de Upload no Chatbot (Sprint 2)\n- **Backend:**
  - Criar endpoint para upload de arquivo via chatbot
  - Implementar validação de formato de arquivo (CSV, OFX, QIF)\n  - Implementar salvamento seguro do arquivo na plataforma
  - Criar endpoint para retornar status de upload
- **Frontend:**
  - Adicionar botão de upload de arquivo no chatbot flutuante (ícone de clipe ou upload)
  - Implementar interface de seleção de arquivo
  - Implementar barra de progresso de upload
  - Exibir mensagem de confirmação após salvamento
  - Gerar botão 'Analisar Extrato' após upload bem-sucedido
- **Testes:**
  - Testes unitários de validação de formato
  - Testes de integração de upload e salvamento
  - Testes de interface de upload no chatbot
\n### 10.3 Fase 3: Implementação do Módulo de Análise de Extrato (Sprint 3)\n- **Backend:**
  - Criar endpoint para acionar análise do modelo de IA
  - Implementar integração com modelo de IA para análise de extrato salvo
  - Implementar lógica de sugestão de categorias existentes
  - Implementar lógica de sugestão de novas categorias
  - Criar endpoint para retornar resultados de análise
- **Frontend:**
  - Implementar açãodo botão 'Analisar Extrato' no chatbot\n  - Exibir feedback visual durante análise (spinner ou mensagem de carregamento)
- **Testes:**
  - Testes unitários de análise de transações
  - Testes de integração com modelo de IA
  - Testes de sugestão de categorias
\n### 10.4 Fase 4: Implementação do Popup de Resultados (Sprint 4)
- **Backend:**
  - Criar endpoint para fornecer dados formatados para o popup
  - Implementar ordenação de transações por data
  - Implementar lógica de pré-seleção de categoria sugerida
- **Frontend:**
  - Criar componente de popup centralizado
  - Implementar lista ordenada de transações por data
  - Implementar dropdown de categoria para cada transação
  - Pré-selecionar categoria sugerida pelo modelo de IA no dropdown
  - Adicionar botão 'Cadastrar Transações' na parte inferior do popup
  - Implementar lógica de fechamento do popup após cadastro
- **Testes:**
  - Testes de renderização do popup
  - Testes de ordenação de transações
  - Testes de dropdown de categorias
  - Testes de pré-seleção de categoria
\n### 10.5 Fase 5: Implementação do Cadastro em Lote de Transações (Sprint 5)\n- **Backend:**
  - Criar endpoint para cadastro em lote de transações
  - Implementar lógica de criação de novas categorias (se selecionadas)
  - Implementar lógica de cadastro de transações nas categorias selecionadas
  - Implementar atualização automática de saldo das contas
  - Implementar registro de auditoria de transações cadastradas
- **Frontend:**\n  - Implementar ação do botão 'Cadastrar Transações' no popup\n  - Exibir feedback visual durante cadastro (spinner ou mensagem de carregamento)
  - Exibir mensagem de confirmação no chatbot após cadastro
  - Fechar popup após cadastro bem-sucedido
- **Testes:**
  - Testes unitários de cadastro em lote
  - Testes de criação de novas categorias
  - Testes de atualização de saldo
  - Testes de auditoria\n
### 10.6 Fase 6: Integração e Testes End-to-End (Sprint 6)
- **Integração:**
  - Integrar todos os módulos (upload, análise, popup, cadastro)\n  - Validar fluxo completo de ponta a ponta
- **Testes:**
  - Testes end-to-end do fluxo completo:\n    1. Upload de extrato via chatbot
    2. Salvamento do arquivo\n    3. Acionamento de análise\n    4. Exibição de popup com resultados
    5. Revisão e ajuste de categorias
    6. Cadastro em lote de transações\n    7. Confirmação e fechamento do popup
  - Testes de usabilidade
  - Testes de performance
  - Testes de segurança

### 10.7 Fase 7: Ajustes Finais e Deploy (Sprint 7)
- Correção de bugs identificados nos testes
- Ajustes de interface e experiência do usuário
- Documentação técnica e de usuário
- Deploy em ambiente de produção
- Monitoramento pós-deploy

### 10.8 Cronograma Resumido
- **Sprint 1:** Preparação do Ambiente (1 semana)
- **Sprint 2:** Módulo de Upload no Chatbot (1 semana)
- **Sprint 3:** Módulo de Análise de Extrato (1 semana)
- **Sprint 4:** Popup de Resultados (1 semana)
- **Sprint 5:** Cadastro em Lote de Transações (1 semana)
- **Sprint 6:** Integração e Testes End-to-End (1 semana)\n- **Sprint 7:** Ajustes Finais e Deploy (1 semana)
- **Total:** 7 semanas

### 10.9 Recursos Necessários
- 1 Desenvolvedor Backend (Flask/FastAPI, PostgreSQL)
- 1 Desenvolvedor Frontend (React)\n- 1 Engenheiro de IA (integração com modelo de IA)
- 1 QA Engineer (testes)\n- 1 DevOps Engineer (CI/CD, deploy)
- 1 Product Owner (gestão de requisitos)
\n### 10.10 Riscos e Mitigações\n- **Risco:** Complexidade de parsing de arquivos OFX
- **Mitigação:** Utilizar bibliotecas especializadas e realizar testes extensivos com diferentes versões de OFX
- **Risco:** Performance de análise de IA para extratos grandes
  - **Mitigação:** Implementar processamento assíncrono e feedback visual de progresso
- **Risco:** Usabilidade do popup em dispositivos móveis
  - **Mitigação:** Design responsivo e testes em diferentes tamanhos de tela\n- **Risco:** Segurança de dados durante upload e análise
  - **Mitigação:** Criptografia de dados em trânsito e em repouso, validação rigorosa de arquivos