# Documento de Requisitos e Implementação da Plataforma de Gestão Financeira Pessoal

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
    - Parsing de arquivos OFX versões 1.x (SGML) e 2.x (XML)\n    - Extração automática de dados de transações: data, descrição, valor, tipo (débito/crédito), saldo
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
    - Botão de upload de arquivo visível no chatbot flutuante (ícone de clipe ou upload)\n    - Suporte aos formatos CSV, OFX e QIF\n    - Após seleção do arquivo, sistema salva o extrato na plataforma
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
- **Dashboard expandido com indicadores financeiros e gráficos:**
  - **Indicadores principais:**
    - Saldo total consolidado de todas as contas (com cálculo automático baseado em receitas e despesas)
    - Receitas totais do mês atual
    - Despesas totais do mês atual
    - Saldo líquido do mês (receitas - despesas)
    - Variação percentual em relação ao mês anterior
    - Limite total disponível em cartões de crédito
- Valor total de contas a pagar no mês
    - Valor total de contas a receber no mês
  - **Gráficos e visualizações:**
    - Gráfico de linha: evolução do saldo ao longo dos últimos 6 meses
    - Gráfico de pizza: distribuição de despesas por categoria no mês atual
    - Gráfico de barras: comparação de receitas vs despesas nos últimos 6 meses\n    - Gráfico de área: projeção de fluxo de caixa para os próximos 3 meses
    - Gráfico de barras horizontais: top 5 categorias com maiores gastos no mês\n    - Gráfico de linha: tendência de gastos por categoria ao longo do tempo
    - Heatmap: padrão de gastos por dia da semana e hora do dia
  - **Indicadores de performance:**
    - Taxa de economia mensal (percentual de receitas não gastas)
    - Média de gastos diários\n    - Comparação de gastos: mês atual vs média dos últimos 3 meses\n    - Indicador de saúde financeira (score baseado em saldo,dívidas e economia)
  - **Alertas visuais:**
    - Indicador de contas próximas ao vencimento (próximos 7 dias)
    - Alerta de gastos acima da média em categorias específicas
    - Indicador de limite de cartão de crédito próximo ao máximo (>80%)
  - **Filtros e personalização:**
    - Seleção de período (mês atual, últimos 3 meses, últimos 6 meses, ano atual)
    - Filtro por conta específica
    - Filtro por categoria
    - Opção de ocultar/exibir gráficos específicos
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
    6. Sistema exibe popup com lista de transações ordenadas por data
    7. Cada transação possui dropdown de categoria com sugestão pré-selecionada
    8. Usuário revisa e ajusta categorias conforme necessário
    9. Usuário clica em 'Cadastrar Transações' no popup
    10. Sistema cadastra todas as transações nas categorias selecionadas
    11. Popup é fechado e chatbot exibe mensagem de confirmação
- Funcionalidadesdo assistente:
  - Categorização automática de transações
  - Recomendações de economia\n  - Previsão de fluxo de caixa
  - Alertas de vencimentos
  - Simulações de parcelamento
  - Sugestões de orçamento
  - **Cadastro de transações mediante solicitação do usuário (receitas, despesas, pagamentos recorrentes, parcelamentos)**
  - **Análise e categorização automática de extratos bancários importados via chatbot**
  - **Sugestão de novas categorias baseadas em padrões identificados**
  - **Interpretação de gráficos e indicadores do dashboard**
  - **Análise de tendências financeiras e insights personalizados**
- Análise completa de contas cadastradas
- Consulta detalhada de extratos de transações
- Análise de pagamentos e recebimentos
- Geração e interpretação de relatórios\n- Consulta de saldos em tempo real
- **Integração com plugins configurados para funcionalidades estendidas**

### 2.7 Painel de Administração deIA
- Indicador visual de status de configuração:\n  - Badge ouícone na página de configurações indicando se há modelo de IA configurado
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
  - **Permissão para análise e categorização automática de extratos bancários**\n  - Toggle para ativar/desativar acesso total com confirmação e consentimento explícito do usuário
- Logs e histórico de conversas com IA
- Registro de auditoria detalhado:\n  - Quem ativou o acesso total
  - Quando foi ativado
  - Finalidade declarada
  - Histórico de consultas realizadas pelo modelo de IA
  - Dados acessados em cada interação
  - **Registro de todas as transações criadas pelo modelo de IA (data, hora, tipo, valor, usuário solicitante)**
  - **Registro de análises e categorizações automáticas realizadas pelo modelo de IA**
- Opções de apagar ou exportar histórico conforme políticas de retenção\n\n### 2.8 Gestão de Plugins
- **Cadastro e configuração de plugins na plataforma**
- **Interface de gerenciamento de plugins com as seguintes funcionalidades:**
  - Lista de plugins disponíveis e instalados
  - Ativação/desativação de plugins
  - Configuração de parâmetros específicos de cada plugin (chaves de API, endpoints, credenciais)
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
\n##3. Segurança e Privacidade\n
### 3.1 Proteção de Dados
- TLS/HTTPS em todas as comunicações
- Criptografia em repouso para dados sensíveis
- Mascaramento e criptografia de números de conta e cartão\n- Transmissão segura de dados completos ao modelo de IA quando acesso total estiver ativado
- **Transmissão segura de dados a plugins conforme permissões configuradas**
\n### 3.2 Autenticação e Autorização
- Autenticação por email/senha com MFA (autenticação multifator)
- RBAC (controle de acesso baseado em papéis): admin, financeiro, usuário\n- Consentimento explícito e informado do usuário para conceder acesso totaldo modelo de IA a todos os dados financeiros:\n  - Contas cadastradas
  - Transações completas
  - Pagamentos e recebimentos
  - Relatórios financeiros
  - Saldos das contas
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
  - Atualiza saldo automaticamente ao registrar, editar ou excluir transações\n  - Fornece saldo atualizado para exibição na interface e para consultas do assistente de IA
- **Módulo de análise e categorização automática:**
  - **Trigger manual:acionado pelo usuário atravésdo botão 'Analisar Extrato' no chatbot após upload e salvamento do arquivo**
  - Processa extratos salvos e identifica padrões\n  - Sugere categorias existentes ou novas categorias\n  - Exibe resultados em popup com lista ordenada de transações por data
  - Permite revisão e edição de categorias via dropdown antesdo cadastro
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
  - Lista ordenada de transações por data
  - Dropdown de seleção de categoria para cada transação
  - Categoria sugerida pelo modelo de IA pré-selecionada
  - Botão 'Cadastrar Transações' na parte inferior\n  - Cadastro em lote de todas as transações nas categorias selecionadas
- **Módulo de geração de gráficos e indicadores:**
  - Cálculo de indicadores financeiros em tempo real
  - Geração de gráficos de linha, pizza, barras e área
  - Processamento de dados históricos para tendências
  - Cache de dados agregados para performance
  - API de dados para visualizações no frontend
- Credenciais gerenciadas via vault/segredos (nunca na UI)
- Design modular: módulo de importação, processamento, API, UI, conector IA, **gerenciador de plugins**, **gerador de gráficos**
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
- **Testes de dashboard expandido:**
  - Validação de cálculo de indicadores financeiros
  - Validação de geração de gráficos de linha, pizza, barras e área
  - Validação de filtros de período e categoria
  - Validação de performance com grandes volumes de dados
  - Validação de responsividade de gráficos
- **Testes de integração com plugins**\n- **Testes de segurança e isolamento de plugins**
\n## 7. Escopodo MVP

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
- Conciliação manual\n- **Dashboard expandido com indicadores financeiros e gráficos:**
  - Saldo total consolidado
  - Receitas e despesas totais do mês
  - Saldo líquido e variação percentual
  - Gráfico de evolução do saldo (6 meses)
  - Gráfico de distribuição de despesas por categoria
  - Gráfico de comparação receitas vs despesas (6 meses)
  - Gráfico de projeção de fluxo de caixa (3 meses)
  - Top 5 categorias com maiores gastos
  - Indicadores de performance (taxa de economia, média de gastos diários)
  - Alertas visuais (vencimentos, gastos acima da média, limite de cartão)
- Assistente de IA com acesso total configurável a:\n  - Todas as contas cadastradas
  - Todas as transações (receitas e despesas)
  - Todos os pagamentos e recebimentos
  - Todos os relatórios financeiros
  - Saldos de todas as contas
  - Extratos completos de transações
  - **Permissão para cadastro de transações mediante solicitação do usuário**
  - **Permissão para análise e categorização automática de extratos**
  - **Interpretação de gráficos e indicadores do dashboard**
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
\n### 7.2 Versões Futuras (1.1/ 1.2)\n- Conciliação automática por Machine Learning
- Integração com APIs bancárias (Open Banking)
- Importação automatizada OFX\n- Permissões granulares avançadas para modelos de IA com níveis intermediários de acesso
- **Marketplace de plugins com plugins pré-aprovados**
- **SDK para desenvolvimento de plugins personalizados**
- Aplicativo móvel\n- **Gráficos avançados adicionais:**
  - Análise de tendências por categoria ao longo do tempo
  - Comparação de gastos entre diferentes períodos
  - Previsão de gastos futuros baseada em histórico
  - Análise de sazonalidade de despesas
\n## 8. Estilo de Design

### 8.1 Paleta de Cores
- Cores principais: azul profissional (#2C3E50) e verde financeiro (#27AE60) para transmitir confiança e estabilidade
- Cores de apoio: cinza claro (#ECF0F1) para fundos e branco (#FFFFFF) para cards
- **Cores para gráficos: paleta harmoniosa com azul (#3498DB), verde (#27AE60), laranja (#E67E22), roxo (#9B59B6) e vermelho (#E74C3C)**
\n### 8.2 Layout\n- Layout em cards para organização modular de informações financeiras
- Sidebar fixa com navegação principal
- **Dashboard com grid responsivo para visualização de métricas e gráficos:**
  - Seção superior com indicadores principais em cards destacados
  - Seção intermediária com gráficos principais em grid 2x2
  - Seção inferior com gráficos secundários e alertas
- **Popup centralizado para exibição de resultados de análise de extrato**
\n### 8.3 Elementos Visuais
-Ícones minimalistas para categorias e ações
- **Gráficos limpos e legíveis:**
  - Linhas suaves para gráficos de evolução
  - Cores distintas para gráficos de pizza
  - Barras com espaçamento adequado
  - Tooltips informativos ao passar o mouse
  - Legendas claras e posicionadas estrategicamente
- Botão flutuante do assistente de IA com ícone de chat, posicionado no canto inferior direito
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
- **Cards de indicadores comícones representativos e valores em destaque**
- **Animações suaves de transição ao carregar gráficos**

## 9. Referências de Interface

### 9.1 Imagens de Referência
- Exemplo de mensagem de erro de importação OFX: {2C7B1F61-7FE3-4148-B737-A544FBDEEF2D}.png
\n## 10. Plano Detalhado de Implementação

### 10.1 Sprint 1: Preparação do Ambiente (Semana 1)
\n**Objetivo:** Configurar infraestrutura base e ambiente de desenvolvimento

**Tarefas Backend:**
1. Configurar repositório Git com estrutura de pastas
2. Setupdo Docker e docker-compose com serviços:\n   - PostgreSQL (banco de dados)
   - Backend (Flask/FastAPI)
   - Frontend (React)\n3. Configurar CI/CD com GitHub Actions:\n   - Pipeline de build\n   - Pipeline de testes
   - Pipeline de deploy
4. Criar estrutura basedo backend:\n   - Configuração de rotas
   - Configuração de banco de dados
   - Middleware de autenticação
   - Middleware de logging
5. Implementar modelos de dados iniciais:
   - Modelo de Usuário
   - Modelo de Conta
   - Modelo de Cartão
   - Modelo de Transação
   - Modelo de Categoria
\n**Tarefas Frontend:**
1. Setup do projeto React com TypeScript
2. Configurar roteamento (React Router)
3. Configurar gerenciamento de estado (Redux/Context API)
4. Criar estrutura de componentes base:
   - Layout principal
   - Sidebar de navegação
   - Header\n   - Footer
5. Configurar biblioteca de UI (Material-UI/Ant Design)
6. **Configurar biblioteca de gráficos (Chart.js/Recharts/D3.js)**
\n**Tarefas DevOps:**
1. Configurar ambiente de desenvolvimento local
2. Configurar ambiente de staging
3. Configurar ambiente de produção
4. Implementar scripts de deploy automatizado
5. Configurar monitoramento básico

**Entregáveis:**
- Repositório Git configurado
- Ambiente Docker funcional
- CI/CD pipelineativo
- Estrutura base de backend e frontend
- Documentação de setup
\n### 10.2 Sprint 2: Módulo de Upload no Chatbot (Semana 2)

**Objetivo:** Implementar funcionalidade de upload de extrato via chatbot

**Tarefas Backend:**
1. Criar endpoint POST /api/extracts/upload:\n   - Validação de formato de arquivo (CSV, OFX, QIF)
   - Validação de tamanho de arquivo (máximo 10MB)
   - Salvamento seguro do arquivo no servidor
   - Geração de ID único para o arquivo
   - Retorno de status de upload
2. Implementar serviço de armazenamento de arquivos:
   - Estrutura de pastas organizada por usuário e data
   - Criptografia de arquivos em repouso
   - Limpeza automática de arquivos antigos
3. Criar endpoint GET /api/extracts/{id}/status:
   - Retorna status do processamento do arquivo
   - Retorna mensagens de erro se houver\n4. Implementar validação de integridade de arquivos:
   - Verificação de formato\n   - Verificação de encoding
   - Detecção de arquivos corrompidos
\n**Tarefas Frontend:**
1. Criar componente de chatbot flutuante:
   - Botão flutuante no canto inferior direito
   - Janela de chat expansível
   - Interface de mensagens\n2. Implementar botão de upload no chatbot:
   - Ícone de clipe ou upload
   - Seletor de arquivo
   - Filtro de tipos de arquivo (CSV, OFX, QIF)
3. Implementar barra de progresso de upload:\n   - Indicador visual de progresso
   - Mensagem de status
   - Tratamento de erros
4. Implementar feedback de upload concluído:
   - Mensagem de confirmação
   - Exibição de nome do arquivo
   - Geração de botão 'Analisar Extrato'
5. Implementar tratamento de erros:
   - Mensagens de erro claras
   - Sugestões de correção
   - Opção de tentar novamente

**Tarefas de Teste:**
1. Testes unitários de validação de formato
2. Testes de upload de arquivos válidos\n3. Testes de upload de arquivos inválidos
4. Testes de limite de tamanho
5. Testes de interface de upload
6. Testes de barra de progresso
7. Testes de mensagens de erro
\n**Entregáveis:**
- Endpoint de upload funcional
- Componente de chatbot com upload
- Testes automatizados
- Documentação de API
\n### 10.3 Sprint 3: Módulo de Análise de Extrato (Semana 3)

**Objetivo:** Implementar análise automática de extratos com IA

**Tarefas Backend:**
1. Criar endpoint POST /api/extracts/{id}/analyze:
   - Trigger manual de análise
   - Validação de arquivo salvo
   - Chamada ao modelo de IA
   - Retorno de resultados de análise
2. Implementar conector com modelo de IA:
   - Configuração de endpoint deIA
   - Autenticação com API de IA
   - Formatação de dados para envio\n   - Parsing de resposta da IA
3. Implementar lógica de sugestão de categorias:
   - Busca de categorias existentes
   - Matching de transações com categorias
   - Cálculo de score de confiança
4. Implementar lógica de sugestão de novas categorias:
   - Identificação de padrões não categorizados
   - Geração de nomes de categorias
   - Validação de unicidade
5. Criar endpoint GET /api/categories:
   - Lista de categorias existentes
   - Filtros e ordenação
6. Implementar parser de arquivos:\n   - Parser CSV com detecção de delimitadores
   - Parser OFX versões 1.x e 2.x
   - Parser QIF
   - Extração de dados de transações
\n**Tarefas Frontend:**
1. Implementar açãodo botão 'Analisar Extrato':
   - Chamada ao endpoint de análise
   - Feedback visual durante análise
   - Spinner ou mensagem de carregamento
2. Implementar tratamento de resposta de análise:
   - Parsing de dados de transações
   - Parsing de categorias sugeridas
   - Preparação de dados para popup
3. Implementar tratamento de erros de análise:\n   - Mensagens de erro claras
   - Sugestões de correção
   - Opção de tentar novamente
\n**Tarefas de Teste:**
1. Testes unitários de análise de transações
2. Testes de integração com modelo de IA
3. Testes de sugestão de categorias existentes
4. Testes de sugestão de novas categorias
5. Testes de parser CSV
6. Testes de parser OFX 1.x\n7. Testes de parser OFX 2.x
8. Testes de parser QIF
9. Testes de tratamento de erros
\n**Entregáveis:**
- Endpoint de análise funcional
- Conector com modelo de IA
- Parsers de arquivos
- Lógica de categorização
- Testes automatizados
- Documentação de API
\n### 10.4 Sprint 4: Popup de Resultados (Semana 4)

**Objetivo:** Implementar interface de revisão de transações analisadas

**Tarefas Backend:**
1. Criar endpoint GET /api/extracts/{id}/results:
   - Retorna dados formatados para popup
   - Ordenação de transações por data
   - Lista de categorias disponíveis
   - Categorias sugeridas por transação
2. Implementar lógica de ordenação:
   - Ordenação por data (mais antiga para mais recente)
   - Ordenação secundária por valor
3. Implementar lógica de pré-seleção:
   - Categoria sugerida como padrão
   - Score de confiança da sugestão
\n**Tarefas Frontend:**
1. Criar componente de popup:
   - Modal centralizado
   - Título'Resultado da Análise do Extrato'
   - Botão de fechar
   - Responsividade para mobile
2. Implementar lista de transações:
   - Renderização de lista ordenada
   - Exibição de data, descrição e valor
   - Layout responsivo
3. Implementar dropdown de categorias:
   - Dropdown ao lado de cada transação
   - Lista de categorias existentes
   - Categorias novas sugeridas
   - Pré-seleção de categoria sugerida
   - Indicador visual de categoria nova
4. Implementar botão 'Cadastrar Transações':
   - Posicionamento na parte inferior
   - Estilo destacado
   - Estado desabilitado durante processamento
5. Implementar lógica de fechamento:
   - Fechar ao clicar fora do popup
   - Fechar ao clicar no botão de fechar
   - Fechar após cadastro bem-sucedido

**Tarefas de Teste:**
1. Testes de renderização do popup
2. Testes de ordenação de transações
3. Testes de dropdown de categorias
4. Testes de pré-seleção de categoria\n5. Testes de responsividade
6. Testes de fechamento do popup
7. Testes de acessibilidade

**Entregáveis:**
- Componente de popup funcional
- Lista de transações ordenada
- Dropdowns de categorias
- Testes automatizados
- Documentação de componentes

### 10.5 Sprint 5: Cadastro em Lote de Transações (Semana 5)

**Objetivo:** Implementar cadastro automático de transações categorizadas

**Tarefas Backend:**
1. Criar endpoint POST /api/transactions/batch:
   - Recebe lista de transações com categorias
   - Validação de dados
   - Cadastro em lote
   - Retorno de status de cadastro
2. Implementar lógica de criação de categorias:
   - Verificação de existência\n   - Criação de categorias novas
   - Associação com usuário
3. Implementar lógica de cadastro de transações:
   - Criação de transações\n   - Associação com conta
   - Associação com categoria
   - Registro de timestamp
4. Implementar atualização de saldo:
   - Cálculo de saldo atual
   - Atualização de saldo da conta
   - Validação de saldo negativo
5. Implementar registro de auditoria:
   - Log de transações criadas
   - Log de categorias criadas
   - Log de usuário responsável
   - Timestamp de operação
6. Implementar transação de banco de dados:
   - Rollback em caso de erro
   - Garantia de consistência
\n**Tarefas Frontend:**
1. Implementar ação do botão 'Cadastrar Transações':\n   - Coleta de dados de transações e categorias
   - Validação de dados
   - Chamada ao endpoint de cadastro\n2. Implementar feedback visual:
   - Spinner durante processamento
   - Mensagem de carregamento
   - Desabilitação de botões\n3. Implementar mensagem de confirmação:
   - Mensagem de sucesso no chatbot
   - Quantidade de transações cadastradas
   - Quantidade de categorias criadas
4. Implementar fechamento do popup:
   - Fechamento automático após sucesso
   - Limpeza de estado
5. Implementar tratamento de erros:
   - Mensagens de erro claras
   - Detalhamento de transações com erro
   - Opção de tentar novamente

**Tarefas de Teste:**
1. Testes unitários de cadastro em lote
2. Testes de criação de categorias
3. Testes de atualização de saldo
4. Testes de auditoria
5. Testes de transação de banco de dados
6. Testes de rollback
7. Testes de interface de cadastro
8. Testes de mensagens de confirmação
9. Testes de tratamento de erros

**Entregáveis:**\n- Endpoint de cadastro em lote funcional
- Lógica de criação de categorias
- Lógica de atualização de saldo
- Sistema de auditoria
- Testes automatizados
- Documentação de API
\n### 10.6 Sprint 6: Dashboard Expandido com Indicadores e Gráficos (Semana 6)

**Objetivo:** Implementar dashboard com indicadores financeiros e gráficos avançados

**Tarefas Backend:**
1. Criar endpoint GET /api/dashboard/indicators:
   - Cálculo de saldo total consolidado
   - Cálculo de receitas e despesas totais do mês
   - Cálculo de saldo líquido e variação percentual
   - Cálculo de limite total disponível em cartões
   - Cálculo de contas a pagar e receber
- Cálculo de taxa de economia mensal
   - Cálculo de média de gastos diários
2. Criar endpoint GET /api/dashboard/charts/balance-evolution:
   - Dados de evolução do saldo nos últimos 6 meses
   - Agregação por mês\n   - Formatação para gráfico de linha
3. Criar endpoint GET /api/dashboard/charts/expenses-by-category:
   - Distribuição de despesas por categoria no mês atual
   - Agregação por categoria
   - Formatação para gráfico de pizza
4. Criar endpoint GET /api/dashboard/charts/income-vs-expenses:
   - Comparação de receitas vs despesas nos últimos 6 meses\n   - Agregação por mês
   - Formatação para gráfico de barras
5. Criar endpoint GET /api/dashboard/charts/cashflow-projection:
   - Projeção de fluxo de caixa para os próximos 3 meses
   - Cálculo baseado em histórico e compromissos agendados
   - Formatação para gráfico de área
6. Criar endpoint GET /api/dashboard/charts/top-categories:
   - Top 5 categorias com maiores gastos no mês\n   - Agregação por categoria
   - Formatação para gráfico de barras horizontais
7. Implementar sistema de cache:
   - Cache de dados agregados para performance
   - Invalidação de cache ao cadastrar/editar transações
   - TTL configurável
8. Implementar filtros de período:
   - Suporte a filtros: mês atual, últimos 3 meses, últimos 6 meses, ano atual
   - Recálculo de indicadores e gráficos baseado no período selecionado

**Tarefas Frontend:**\n1. Criar componente de dashboard:
   - Layout em grid responsivo
   - Seção de indicadores principais
   - Seção de gráficos
   - Seção de alertas
2. Implementar cards de indicadores:
   - Card de saldo total consolidado
   - Card de receitas totais do mês
   - Card de despesas totais do mês\n   - Card de saldo líquido com variação percentual
   - Card de limite disponível em cartões
   - Card de contas a pagar e receber
   -Ícones representativos para cada indicador
   - Formatação de valores monetários
   - Cores diferenciadas (verde para positivo, vermelho para negativo)
3. Implementar gráfico de evolução do saldo:
   - Gráfico de linha com dados dos últimos 6 meses\n   - Tooltips informativos
   - Legenda clara
   - Responsividade\n4. Implementar gráfico de distribuição de despesas:
   - Gráfico de pizza com categorias
   - Cores distintas para cada categoria
   - Percentuais exibidos
   - Legenda com nomes de categorias
5. Implementar gráfico de receitas vs despesas:
   - Gráfico de barras agrupadas\n   - Barras para receitas e despesas
   - Comparação visual clara
   - Eixos com valores formatados
6. Implementar gráfico de projeção de fluxo de caixa:
   - Gráfico de área com projeção de3 meses
   - Linha de tendência
   - Área preenchida para visualização de volume
7. Implementar gráfico de top categorias:
   - Gráfico de barras horizontais
   - Top 5 categorias com maiores gastos
   - Valores exibidos ao lado das barras
8. Implementar filtros de período:
   - Dropdown de seleção de período
   - Atualização automática de indicadores e gráficos
   - Feedback visual durante carregamento
9. Implementar alertas visuais:
   - Alerta de contas próximas ao vencimento (próximos 7 dias)
   - Alerta de gastos acima da média\n   - Alerta de limite de cartão próximo ao máximo (>80%)
   - Ícones e cores de destaque
10. Implementar animações:\n    - Animações suaves de transição ao carregar gráficos
    - Animações de hover em elementos interativos
    - Feedback visual ao aplicar filtros

**Tarefas de Teste:**
1. Testes unitários de cálculo de indicadores
2. Testes de endpoints de gráficos
3. Testes de cache de dados
4. Testes de filtros de período
5. Testes de renderização de gráficos
6. Testes de responsividade de dashboard
7. Testes de performance com grandes volumes de dados
8. Testes de alertas visuais
9. Testes de animações
\n**Entregáveis:**
- Endpoints de indicadores e gráficos funcionais
- Dashboard expandido com indicadores e gráficos
- Sistema de cache implementado
- Filtros de período funcionais
- Testes automatizados
- Documentação de API

### 10.7 Sprint 7: Integração e Testes End-to-End (Semana 7)

**Objetivo:** Integrar todos os módulos e validar fluxo completo\n
**Tarefas de Integração:**
1. Integrar módulo de upload com módulo de análise
2. Integrar módulo de análise com popup de resultados
3. Integrar popup com módulo de cadastro\n4. Integrar cadastro com atualização de dashboard
5. Validar fluxo completo de ponta a ponta
6. Ajustar interfaces entre módulos
7. Otimizar performance de integração

**Tarefas de Teste End-to-End:**
1. Teste de fluxo completo:\n   - Upload de extrato CSV
   - Análise automática\n   - Exibição de popup\n   - Revisão de categorias
   - Cadastro de transações
   - Atualização de dashboard
   - Confirmação de sucesso
2. Teste de fluxo completo com OFX 1.x\n3. Teste de fluxo completo com OFX 2.x
4. Teste de fluxo completo com QIF
5. Teste de criação de novas categorias
6. Teste de atualização de saldo
7. Teste de atualização de indicadores do dashboard
8. Teste de atualização de gráficos
9. Teste de auditoria completa
10. Teste de tratamento de erros em cada etapa
11. Teste de performance com arquivos grandes
12. Teste de concorrência (múltiplos uploads simultâneos)
13. Teste de filtros de período no dashboard
14. Teste de alertas visuais
\n**Tarefas de Usabilidade:**
1. Teste de usabilidade com usuários reais\n2. Coleta de feedback\n3. Ajustes de interface baseados em feedback
4. Validação de acessibilidade
5. Validação de responsividade
6. Validação de clareza de gráficos e indicadores
\n**Tarefas de Performance:**
1. Teste de carga do endpoint de upload
2. Teste de carga do endpoint de análise
3. Teste de carga do endpoint de cadastro
4. Teste de carga dos endpoints de dashboard
5. Otimização de queries de banco de dados
6. Otimização de chamadas à API deIA
7. Implementação de cache onde aplicável
8. Otimização de renderização de gráficos

**Tarefas de Segurança:**
1. Teste de injeção SQL
2. Teste de XSS
3. Teste de CSRF
4. Teste de autenticação e autorização
5. Teste de criptografia de dados
6. Validação de permissões de acesso
7. Auditoria de logs de segurança
\n**Entregáveis:**
- Fluxo completo integrado e funcional
- Dashboard expandido integrado
- Relatório de testes end-to-end
- Relatório de usabilidade
- Relatório de performance
- Relatório de segurança
- Documentação de integração

### 10.8 Sprint 8: Ajustes Finais e Deploy (Semana 8)

**Objetivo:** Corrigir bugs, finalizar documentação e realizar deploy

**Tarefas de Correção:**\n1. Correção de bugs críticos identificados
2. Correção de bugs de alta prioridade
3. Ajustes de interface baseados em feedback
4. Otimizações de performance
5. Ajustes de segurança
6. Ajustes de gráficos e indicadores
\n**Tarefas de Documentação:**
1. Documentação técnica completa:\n   - Arquitetura do sistema
   - Fluxo de dados
   - Endpoints de API
   - Modelos de dados
   - Configuração de ambiente
   - Documentação de gráficos e indicadores
2. Documentação de usuário:
   - Guia de usodo chatbot
   - Guia de upload de extratos
   - Guia de análise e categorização
   - Guia de interpretação do dashboard
   - FAQ
3. Documentação de deploy:
   - Procedimentos de deploy
   - Configuração de ambiente de produção
   - Procedimentos de rollback
   - Monitoramento e alertas
\n**Tarefas de Deploy:**
1. Preparação do ambiente de produção:
   - Configuração de servidores
   - Configuração de banco de dados
   - Configuração de SSL/TLS
   - Configuração de firewall
2. Deploy de backend:
   - Build de imagem Docker
   - Push para registry\n   - Deploy em produção
   - Validação de saúde
3. Deploy de frontend:
   - Build de produção
   - Deploy em CDN
   - Validação de carregamento
4. Configuração de monitoramento:
   - Logs estruturados
   - Métricas de performance
   - Alertas de erro
   - Dashboard de monitoramento
5. Configuração de backup:
   - Backup automático de banco de dados
   - Backup de arquivos
   - Procedimentos de recuperação

**Tarefas de Validação Pós-Deploy:**
1. Smoke tests em produção
2. Validação de endpoints\n3. Validação de fluxo completo
4. Validação de dashboard e gráficos
5. Validação de performance
6. Validação de monitoramento
7. Validação de backup
\n**Tarefas de Treinamento:**
1. Treinamento de equipe de suporte
2. Treinamento de usuários piloto
3. Preparação de materiais de treinamento
4. Treinamento sobre interpretação de gráficos e indicadores
\n**Entregáveis:**
- Sistema em produção\n- Dashboard expandido em produção
- Documentação completa\n- Monitoramento ativo
- Backup configurado
- Equipe treinada
- Relatório de deploy

##11. Cronograma Resumido

| Sprint | Semana | Foco | Entregáveis Principais |
|--------|--------|------|------------------------|
| Sprint 1 | Semana 1 | Preparação do Ambiente | Infraestrutura base, CI/CD, estrutura de código, biblioteca de gráficos |
| Sprint 2 | Semana 2 | Upload no Chatbot | Endpoint de upload, componente de chatbot |
| Sprint 3 | Semana 3 | Análise de Extrato | Conector de IA, parsers de arquivos, lógica de categorização |
| Sprint 4 | Semana 4 | Popup de Resultados | Componente de popup, lista de transações, dropdowns |
| Sprint 5 | Semana 5 | Cadastro em Lote | Endpoint de cadastro, atualização de saldo, auditoria |
| Sprint 6 | Semana 6 | Dashboard Expandido | Indicadores financeiros, gráficos avançados, filtros, alertas |
| Sprint 7 | Semana 7 | Integração e Testes | Fluxo completo integrado, testes end-to-end, dashboard integrado |
| Sprint 8 | Semana 8 | Ajustes e Deploy | Sistema em produção, documentação completa |

**Duração Total:** 8 semanas

## 12. Recursos Necessários

###12.1 Equipe\n- 1 Desenvolvedor Backend (Flask/FastAPI, PostgreSQL) - 8 semanas
- 1 Desenvolvedor Frontend (React, TypeScript, Chart.js/Recharts) - 8 semanas
- 1 Engenheiro de IA (integração com modelo de IA) - 4 semanas (Sprints 3-6)
- 1 QA Engineer (testes) - 8 semanas
- 1 DevOps Engineer (CI/CD, deploy) - 8 semanas
- 1 Product Owner (gestão de requisitos) - 8 semanas
\n### 12.2 Infraestrutura
- Servidor de desenvolvimento\n- Servidor de staging
- Servidor de produção
- Banco de dados PostgreSQL
- Serviço de armazenamento de arquivos
- API de modelo de IA (OpenAI ou similar)
- Serviço de CI/CD (GitHub Actions)
- Serviço de monitoramento\n\n### 12.3 Ferramentas
- Git e GitHub
- Docker e docker-compose
- IDE (VS Code, PyCharm)
- Postman ou similar para testes de API
- Ferramenta de design (Figma)\n- Ferramenta de gerenciamento de projeto (Jira, Trello)
- Biblioteca de gráficos (Chart.js/Recharts/D3.js)

## 13. Riscos e Mitigações

| Risco | Probabilidade | Impacto | Mitigação |
|-------|---------------|---------|----------|
| Complexidade de parsing de arquivos OFX | Média | Alto | Utilizar bibliotecas especializadas (ofxparse), realizar testes extensivos com diferentes versões de OFX |
| Performance de análise de IA para extratos grandes | Média | Médio | Implementar processamento assíncrono, feedback visual de progresso, limitar tamanho de arquivo |
| Usabilidade do popup em dispositivos móveis | Baixa | Médio | Design responsivo desde o início, testes em diferentes tamanhos de tela |
| Segurança de dados durante upload e análise | Baixa | Alto | Criptografia de dados em trânsito e em repouso, validação rigorosa de arquivos, auditoria completa |
| Atraso na integração com modelo de IA | Média | Alto | Definir interface clara desde o início, criar mock de IA para desenvolvimento paralelo |
| Bugs críticos descobertos em produção | Baixa | Alto | Testes extensivos, deploy gradual, monitoramento ativo, procedimentos de rollback |
| Indisponibilidade de API de IA | Baixa | Alto | Implementar retry logic, fallback para processamento manual, alertas de falha |
| Performance de renderização de gráficos com grandes volumes de dados | Média | Médio | Implementar paginação, agregação de dados, cache, otimização de queries |
| Complexidade de cálculo de indicadores financeiros | Média | Médio | Validação extensiva de fórmulas, testes com dados reais, revisão por especialista financeiro |
\n## 14. Critérios de Sucesso

### 14.1 Critérios Técnicos
- Sistema em produção e estável
- Cobertura de testes > 80%
- Tempo de resposta de endpoints < 2 segundos
- Tempo de carregamento de dashboard < 3 segundos
- Uptime > 99%
- Zero vulnerabilidades críticas de segurança
\n### 14.2 Critérios de Negócio
- Usuários conseguem fazer upload de extratos via chatbot
- Análise automática de transações funcional
- Categorização com precisão > 80%
- Cadastro em lote de transações funcional
- Dashboard com indicadores e gráficos funcionais
- Feedback positivo de usuários piloto

### 14.3 Critérios de Usabilidade
- Fluxo completo em menos de 5 cliques
- Tempo médio de upload e análise < 30 segundos
- Taxa de erro de usuário < 5%
- Satisfação de usuário > 4/5
- Gráficos e indicadores claros e compreensíveis
- Dashboard responsivo em diferentes dispositivos

## 15. Próximos Passos Após MVP

### 15.1 Versão 1.1 (Semanas 9-11)
- Conciliação automática por Machine Learning
- Aprendizado contínuo do modelo de IA
- Relatórios avançados de categorização
- Exportação de transações categorizadas
- Gráficos adicionais:\n  - Análise de tendências por categoria ao longo do tempo
  - Heatmap de padrão de gastos por dia da semana e hora
\n### 15.2 Versão 1.2 (Semanas 12-15)
- Integração com APIs bancárias (Open Banking)
- Importação automatizada OFX
- Permissões granulares avançadas para modelos de IA
- Marketplace de plugins\n- Gráficos avançados:
  - Comparação de gastos entre diferentes períodos
  - Previsão de gastos futuros baseada em histórico
  - Análise de sazonalidade de despesas

### 15.3 Versão 2.0 (Semanas 16-21)
- Aplicativo móvel (iOS e Android)
- SDK para desenvolvimento de plugins personalizados
- Análise preditiva de gastos
- Recomendações personalizadas de economia
- Dashboard personalizável com widgets arrastáveis
- Relatórios customizáveis com exportação avançada
