# Documento de Requisitos e Implementação da Plataforma de Gestão Financeira Pessoal

## 1. Visão Geral

### 1.1 Nome da Plataforma
Plataforma de Gestão Financeira Pessoal com Assistente de IA e Análise Preditiva Automática

### 1.2 Descrição
Plataforma web (MVP) para gestão de finanças pessoais que permite importar extratos bancários, gerenciar contas e cartões, cadastrar receitas e despesas, realizar transferências entre contas cadastradas, e oferece um assistente de IA contextual com memória persistente acessível em toda a interface. Inclui painel de administração para configurar modelos de IA, controlar permissões de acesso aos dados (leitura e escrita), configurar plugins e registrar auditoria de interações. **Agora com módulo completo de Análise Financeira Automática baseada em IA, que gera previsões de saldo futuro, análises inteligentes de gastos, alertas de risco, dashboards interativos, insights automáticos e notificações proativas ao usuário.**

## 2. Funcionalidades Principais

### 2.1 Gestão de Contas e Cartões
- Cadastro de contas bancárias (nome, banco, agência, conta, moeda, **tipo_conta: corrente/poupança/digital, instituição**)
- Cadastro de cartões de crédito (limite, data de fechamento, data de vencimento)\n- **Notificação toast ao cadastrar conta bancária:**
  - Exibição de notificação toast no canto superior direito da tela
  - Mensagem:'Conta bancária cadastrada com sucesso!'
  - Ícone de check verde\n  - Duração: 3 segundos
  - Animação suave de entrada e saída
- **Visualização de saldo atual da conta com cálculo automático:**
  - Saldo inicial da conta
  - Saldo atualizado em tempo real considerando:\n    - Despesas pagas: diminuem o saldo da conta
    - Receitas recebidas: aumentam o saldo da conta
    - Transferências enviadas: diminuem o saldo da conta de origem
    - Transferências recebidas: aumentam o saldo da conta de destino\n  - Fórmula: Saldo Atual = Saldo Inicial + Receitas Recebidas - Despesas Pagas - Transferências Enviadas + Transferências Recebidas\n  - Exibição clarado saldo atual na página de contas (https://onlifin.onlitec.com.br/accounts)
  - Atualização automática do saldo sempre que uma transação for registrada, editada ou excluída
  - **Campo atualizado_em para registrar timestamp da última atualização de saldo**
- Visualização de limites disponíveis de cartões de crédito
\n### 2.2 Sistema de Transferências entre Contas
- **Cadastro de transferências entre contas cadastradas na plataforma:**
  - Interface dedicada para registro de transferências
  - Seleção de conta de origem (dropdown com lista de contas cadastradas)
  - Seleção de conta de destino (dropdown com lista de contas cadastradas, excluindo a conta de origem)
  - Campo de valor da transferência com validação\n  - Campo de data da transferência
  - Campo de descrição ou observação (opcional)
  - Validação de saldo suficiente na conta de origem antes de confirmar transferência
  - Mensagem de erro clara caso saldo seja insuficiente
- **Processamento automático de transferências:**\n  - Ao confirmar transferência, sistema registra duas movimentações vinculadas:\n    - Débito na conta de origem (diminui saldo)
    - Crédito na conta de destino (aumenta saldo)
  - Ambas as movimentações são registradas com a mesma data e valor
  - Vínculo entre as duas movimentações para rastreabilidade
  - Atualização automática dos saldos das contas envolvidas
- **Visualização de transferências:**
  - Transferências aparecem na lista de transações de ambas as contas (origem e destino)
  - Identificação visual clara de que se trata de uma transferência (ícone ou tag específica)
  - Exibição da conta de origem e destino em cada movimentação
  - Filtro específico para visualizar apenas transferências na página de transações
- **Edição e exclusão de transferências:**
  - Possibilidade de editar valor, data e descrição de transferências existentes
  - Ao editar transferência, ambas as movimentações vinculadas são atualizadas automaticamente
  - Ao excluir transferência, ambas as movimentações vinculadas são removidas
  - Saldos das contas são recalculados automaticamente após edição ou exclusão
- **Categorização de transferências:**
  - Categoria padrão 'Transferência' criada automaticamente no sistema
  - Transferências são automaticamente categorizadas como 'Transferência'\n  - Possibilidade de criar subcategorias de transferência (ex: 'Transferência para Poupança', 'Transferência para Investimentos')
- **Integração com assistente de IA:**
  - Assistente de IA pode criar, editar e excluir transferências mediante solicitação do usuário
  - Assistente valida saldo suficiente antes de criar transferência
  - Assistente fornece feedback claro sobre transferências realizadas
  - Assistente pode consultar histórico de transferências entre contas específicas
\n### 2.3 Importação e Conciliação\n- **Importação de extratos bancários nos formatos CSV, OFX e QIF:**
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
    - Botão de upload de arquivo visível no chatbot flutuante (ícone de clipe ou upload)
    - Suporte aos formatos CSV, OFX e QIF\n    - Após seleção do arquivo, sistema salva o extrato na plataforma
    - Feedback visual de upload concluído (barra de progresso e mensagem de confirmação)
  - **Passo 2: Comando de análise**
    - Após salvamento do extrato, surge botão 'Analisar Extrato' no chatbot\n    - Usuário clica no botão para acionar análise do modelo de IA
    - Modelo de IA analisa o arquivo já salvo na plataforma
  - **Passo 3: Exibição de resultados em popup**
    - Após análise, sistema exibe janela popup com resultados\n    - **Estrutura do popup:**
      - Título: 'Resultado da Análise do Extrato'
      - Lista de transações ordenadas por data (da mais antiga para a mais recente)
      - Cada transação exibida com:
        - Data da transação
        - Descrição/títulodo estabelecimento
        - Valor da transação
        - Dropdown de seleção de categoria ao lado de cada transação
        - Categoria sugerida pelo modelo de IA pré-selecionada no dropdown
        - Possibilidade de alterar categoria manualmente via dropdown
        - **Possibilidade de deixar dropdown sem seleção (categoria vazia)**
      - Botão 'Cadastrar Transações' na parte inferior do popup
    - **Ação do botão 'Cadastrar Transações':**
      - **Cadastra apenas as transações que possuem uma categoria selecionada no dropdown correspondente**
      - **Transações sem categoria selecionada (dropdown vazio) não são cadastradas**
      - Cada transação cadastrada é registrada na categoria selecionada no dropdown
      - Após cadastro, popup é fechado e chatbot exibe mensagem de confirmação
      - **Mensagem de confirmação indica o número de transações cadastradas (ex: '15 transações cadastradas com sucesso')**
- Saldo das contas é atualizado automaticamente\n- Mapeamento automático de transações importadas\n- Ferramenta de conciliação manual de lançamentos
- Classificação automática de transações\n
### 2.4 Análise e Categorização Automática de Transações com IA
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
  - Popup exibe lista ordenada de transações por data\n  - Cada transação possui dropdown de categoria com sugestãodo modelo de IA pré-selecionada
  - Usuário pode alterar categoria manualmente antesdo cadastro
  - **Usuário pode deixar dropdown sem seleção (categoria vazia) para transações que não deseja cadastrar**
  - Ao clicar em 'Cadastrar Transações', sistema:\n    - **Cadastra apenas as transações que possuem uma categoria selecionada**
    - **Ignora transações sem categoria selecionada**
    - Cria automaticamente as categorias novas selecionadas pelo usuário (se houver)
    - Registra as transações nas categorias correspondentes (existentes ou recém-criadas)
    - Fecha o popup e exibe mensagem de confirmação no chatbot com número de transações cadastradas
- **Aprendizado contínuo:**
  - Histórico de aprendizado do modelo: quanto mais o usuário confirmar ou corrigir categorias, mais preciso o modelo se torna
  - Sugestão de categoria automática baseada em padrões frequentes do usuário
\n### 2.5 Movimentações Financeiras
- Cadastro de receitas e despesas (valor, data, categoria, conta, tag, nota, **tipo: entrada/saída, origem: banco/manual/importado, data_criacao**)
- **Notificação toast ao cadastrar transação:**
  - Exibição de notificação toast no canto superior direito da tela
  - Mensagem: 'Transação cadastrada com sucesso!' (para receitas ou despesas)
  - Ícone de check verde
  - Duração: 3 segundos
  - Animação suave de entrada e saída
- **Cadastro de transferências entre contas cadastradas (valor, data, conta origem, conta destino, descrição)**
- **Edição de transações existentes: permite alterar valor, data, descrição, título e categoria de transações já cadastradas**
- **Edição de transferências existentes: permite alterar valor, data e descrição, com atualização automática das movimentações vinculadas**
- **Atualização automática do saldo da conta ao cadastrar, editar ou excluir transações e transferências**
- Suporte a pagamentos recorrentes\n- Suporte a receitas recorrentes
- Controle de parcelamentos com acompanhamento de parcelas
- Agendamento de compromissos e pagamentos
- Sistema de alertas para vencimentos\n- **Cadastro de transações e transferências pelo modelo de IA: o assistente de IA pode criar, editar e excluir transações e transferências na plataforma mediante solicitação do usuário, incluindo receitas, despesas, pagamentos recorrentes, parcelamentos e transferências entre contas**
- **Página de transações (https://onlifin.onlitec.com.br/transactions) com funcionalidades avançadas de filtragem, busca e ordenação:**
  - **Campo de busca de transações:**
    - Campo de texto para busca por descrição, título ou estabelecimento
    - Busca em tempo real com atualização automática da lista
    - Destaque visual dos termos encontrados nos resultados
    - Posicionamento proeminente no topo da página
  - **Filtros de transações:**
    - **Filtro por conta bancária:** dropdown com lista de todas as contas cadastradas, permitindo selecionar uma ou múltiplas contas
    - **Filtro por categoria:** dropdown com lista de todas as categorias cadastradas, permitindo selecionar uma ou múltiplas categorias
    - **Filtro por tipo de transação:** opções para filtrar por receita, despesa, transferência ou combinações (checkboxes ou botões de seleção)
    - **Filtro por data:** seletor de intervalo de datas (data inicial e data final) ou opções predefinidas (hoje, esta semana, este mês, últimos 30 dias, últimos 90 dias, este ano)\n    - Botão 'Limpar Filtros' para resetar todos os filtros aplicados
    - Indicador visual de filtros ativos (badge com número de filtros aplicados)
- Seção de filtros organizada em linha horizontal ou painel lateral retrátil para melhor usabilidade
    - **Notificação toast fixa ao aplicar filtro:**
      - Exibição de notificação toast fixa no topo da página de transações
      - Mensagem: 'Filtro aplicado: [descrição dos filtros ativos]' (ex: 'Filtro aplicado: Conta Corrente, Categoria Alimentação, Últimos 30 dias')
      - Ícone de filtro azul
      - Toast permanece visível enquanto filtros estiverem ativos
      - Botão 'X' no toast para fechar a notificação (filtros permanecem ativos)
      - Botão 'Limpar Filtros' dentro do toast para remover todos os filtros\n      - Cor de fundo azul claro (#E3F2FD) para diferenciação\n      - Posicionamento fixo no topo da lista de transações
  - **Opções de ordenação:**
    - **Ordenar por data:** crescente (mais antiga primeiro) ou decrescente (mais recente primeiro)
    - **Ordenar por categoria:** ordem alfabética crescente ou decrescente
    - **Ordenar por valor:** crescente (menor para maior) ou decrescente (maior para menor)
    - Seletor de ordenação visível no topo da lista de transações
    - Indicador visual da ordenação ativa (seta para cima ou para baixo ao lado do critério selecionado)
  - **Exibição de resultados:**
    - Lista de transações atualizada em tempo real conforme filtros e ordenação aplicados
    - Contador de transações exibidas vs total de transações (ex: 'Exibindo 25 de 150 transações')
    - Paginação para grandes volumes de dados
    - Opção de exportar resultados filtrados em CSV ou Excel
    - Exibição clara de cada transação com data, descrição, categoria, valor e conta associada
    - **Identificação visual de transferências com ícone específico e exibição de conta origem/destino**
\n### 2.6 Gestão de Contas a Pagar e Receber
- **Cadastro de contas a pagar:**
  - Campos: id, descrição, valor, vencimento, categoria, status (pendente/paga/atrasada), recorrência (sim/não, período)
  - Interface de cadastro com validação de campos obrigatórios
  - Possibilidade de vincular a uma conta bancária específica
  - Notificações automáticas de vencimento próximo
  - **Atualização automática de status:**
    - Status muda para 'atrasada' automaticamente após data de vencimento
    - Status muda para 'paga' ao registrar pagamento
- **Cadastro de contas a receber:**
  - Campos: id, descrição, valor, vencimento, categoria, status (pendente/recebido), recorrente (sim/não)
  - Interface de cadastro com validação de campos obrigatórios
  - Possibilidade de vincular a uma conta bancária específica
  - Notificações automáticas de recebimento esperado
  - **Atualização automática de status:**\n    - Status muda para 'recebido' ao registrar recebimento
- **Visualização consolidada:**
  - Lista de contas a pagar ordenadas por vencimento
  - Lista de contas a receber ordenadas por vencimento
  - Filtros por status, categoria e período
  - Indicadores visuais de urgência (vermelho para atrasadas, amarelo para próximas do vencimento)
- **Integração com previsão financeira:**
  - Contas a pagar e receber são consideradas no cálculo de previsão de saldo futuro
  - Alertas automáticos quando contas a pagar podem causar saldo negativo
\n### 2.7 Controle Financeiro
- Gestão de contas a pagar e receber\n- Visualização de fluxo de caixa
- Previsões financeiras simples
- **Dashboard expandido com indicadores financeiros e gráficos:**
  - **Seletor de mês para visualização de dados históricos:**
    - Dropdown ou calendário para seleção de mês específico
    - Opções de navegação: mês anterior, próximo mês, mês atual\n    - Exibição clarado mês selecionado no topo do dashboard
    - Atualização automática de todos os indicadores e gráficos ao selecionar novo mês
    - Possibilidade de comparar dados de diferentes meses lado a lado
  - **Indicadores principais (ajustados conforme mês selecionado):**\n    - Saldo total consolidado de todas as contas no final do mês selecionado
    - Receitas totais do mês selecionado
    - Despesas totais do mês selecionado
    - **Total de transferências realizadas no mês selecionado**
    - Saldo líquido do mês selecionado (receitas - despesas)
    - Variação percentual em relação ao mês anterior ao selecionado
    - Limite total disponível em cartões de crédito no mês selecionado
- Valor total de contas a pagar no mês selecionado
    - Valor total de contas a receber no mês selecionado
  - **Gráficos e visualizações (ajustados conforme mês selecionado):**
    - Gráfico de linha: evolução do saldo ao longo dos últimos 6 meses a partir do mês selecionado
    - Gráfico de pizza: distribuição de despesas por categoria no mês selecionado
    - Gráfico de barras: comparação de receitas vs despesas nos últimos 6 meses a partir do mês selecionado
    - Gráfico de área: projeção de fluxo de caixa para os próximos 3 meses a partir do mês selecionado
    - Gráfico de barras horizontais: top 5 categorias com maiores gastos no mês selecionado
    - Gráfico de linha: tendência de gastos por categoria ao longo do tempo (6 meses a partir do mês selecionado)
    - Heatmap: padrão de gastos por dia da semana e hora do dia no mês selecionado
    - **Gráfico de fluxo: visualização de transferências entre contas no mês selecionado**
  - **Indicadores de performance (ajustados conforme mês selecionado):**
    - Taxa de economia mensal (percentual de receitas não gastas) no mês selecionado\n    - Média de gastos diários no mês selecionado
    - Comparação de gastos: mês selecionado vs média dos últimos 3 meses anteriores
    - Indicador de saúde financeira (score baseado em saldo,dívidas e economia) no mês selecionado\n  - **Alertas visuais (ajustados conforme mês selecionado):**
    - Indicador de contas próximas ao vencimento no mês selecionado
    - Alerta de gastos acima da média em categorias específicas no mês selecionado
    - Indicador de limite de cartão de crédito próximo ao máximo (>80%) no mês selecionado
  - **Filtros e personalização:**
    - Seleção de mês específico (navegação por meses anteriores e futuros)
    - Filtro por conta específica\n    - Filtro por categoria\n    - Opção de ocultar/exibir gráficos específicos
    - Botão de'Voltar ao Mês Atual' para retornar rapidamente aos dados do mês corrente
- Exportação de relatórios em CSV, Excel e PDF
\n### 2.8 Análise Financeira Automática Baseada em IA
\n####2.8.1 Visão Geral do Módulo
**Módulo completo de análise preditiva que processa automaticamente todos os dados financeiros da plataforma (contas, saldos, transações, contas a pagar, contas a receber) e gera previsões inteligentes, alertas de risco, insights automáticos e dashboards interativos.**

#### 2.8.2 Agente de Previsão Financeira (IA)
- **Nomedo Agente:** Agente de Previsão Financeira
- **Funções principais:**
  1. **Análise de histórico:**
     - Leitura de todas as transações dos últimos 6 meses\n     - Leitura de contas a pagar e receber futuras
     - Análise de saldos históricos de todas as contas
  2. **Identificação de padrões:**
     - Cálculo de média de entradas mensais
     - Cálculo de média de saídas mensais
     - Identificação de categorias com maior peso nos gastos
     - Detecção de sazonalidade semanal ou mensal
     - Identificação de gastos recorrentes e variáveis
     - Análise de comportamento de gastos por dia da semana
  3. **Geração de previsões:**
     - **Previsão de saldo diário:** próximos 30 dias
     - **Previsão de saldo semanal:** próximas 12 semanas
     - **Previsão de saldo mensal:** próximos 6 meses
     - Consideração de contas a pagar e receber agendadas
     - Aplicação de padrões históricos identificados
  4. **Detecção de riscos:**
     - Identificação de dia previsto para saldo negativo
     - Detecção de contas que podem causar falta de caixa
     - Previsão de atrasos prováveis em pagamentos
     - Alerta de gastos excessivos em categorias específicas\n     - Identificação de anomalias no padrão de gastos
  5. **Geração de insights automáticos:**
     - Detecção de mudanças de comportamento financeiro
     - Alertas de gastos excessivos por categoria
     - Sugestões de economia baseadas em padrões\n     - Identificação de oportunidades de otimização financeira
     - Comparação com períodos anteriores
  6. **Formato de saída (JSON estruturado):**
```json
{
  \"forecast_daily\": {
    \"2025-01-01\": 1250.90,
    \"2025-01-02\": 1180.10,
    \"...\": \"...\"\n  },
  \"forecast_weekly\": {
    \"semana_1\": 1300,
    \"semana_2\": 1120,
    \"...\": \"...\"
  },\n  \"forecast_monthly\": {
    \"janeiro\": 1400,
    \"fevereiro\": 900,
    \"...\": \"...\"
  },
  \"insights\": [\n    \"Gastos com mercado aumentaram 18% este mês.\",
    \"Risco de saldo negativo no dia 12.\"\n  ],
  \"alerts\": [
    {
      \"tipo\": \"risco\",
      \"descricao\": \"Saldo negativo em 7 dias\",
      \"gravidade\": \"alta\"\n    },
    {\n      \"tipo\": \"despesa_alta\",
      \"descricao\": \"Restaurantes acima da média\",
      \"gravidade\": \"media\"
    }
  ],
  \"risk_negative\": true\n}
```

#### 2.8.3 Entidade de Previsões (Banco de Dados)
- **Tabela: Previsões**
- **Campos:**\n  - id (chave primária)
  - data_calculo (timestamp da execução da análise)
  - saldo_inicial (float, saldo no momentodo cálculo)
  - saldo_previsto_dia (JSON com valores por dia: {\"2025-01-01\": 1250.90, ...})
  - saldo_previsto_semana (JSON com valores por semana: {\"semana_1\": 1300, ...})
  - saldo_previsto_mes (JSON com valores por mês: {\"janeiro\": 1400, ...})
  - alertas_gerados (JSON com lista de alertas: [{\"tipo\": \"risco\", \"descricao\": \"...\", \"gravidade\": \"alta\"}, ...])
  - insights (texto com insights gerados pela IA)
  - risco_negativo (booleano: sim/não)
  - user_id (relacionamento com usuário)
- **Índices:**
  - Índice em data_calculo para consultas rápidas
  - Índice em user_id para filtrar por usuário
\n#### 2.8.4 Rotina Automática de Análise
- **Agendamento:** Execução diária às 02:00 (horário do servidor)
- **Processo automatizado:**
  1. **Coleta de dados:**
     - Leitura de todas as contas bancárias do usuário
     - Leitura de todas as transações dos últimos 6 meses\n     - Leitura de contas a pagar e receber futuras
     - Cálculo de saldos atuais
  2. **Execução do Agente de Previsão Financeira:**
     - Processamento de dados coletados
     - Geração de previsões (diária, semanal, mensal)
     - Identificação de riscos e anomalias
     - Geração de insights automáticos
  3. **Salvamento de resultados:**
     - Criação de novo registro na entidade 'Previsões'
     - Armazenamento de JSON com previsões e alertas\n     - Registro de timestamp da análise
  4. **Atualização automática de status:**
     - Atualização de status de contas a pagar (pendente → atrasada se vencida)
     - Atualização de status de contas a receber\n     - Marcação de alertas críticos para notificação
  5. **Envio de alertas automáticos:**
     - **Condições para envio:**
       - Saldo negativo previsto nos próximos 7 dias\n       - Contas próximas do vencimento (3 dias ou menos)
       - Anomalias detectadas no padrão de gastos
       - Gastos excessivos em categorias específicas
     - **Canais de notificação:**
       - Notificação in-app (toast ou modal)
       - Email (opcional, configurável pelo usuário)
       - Push notification (se aplicativo móvel estiver disponível)
- **Logs de execução:**
  - Registro de cada execução da rotina
  - Timestamp de início e fim\n  - Status de sucesso ou erro
  - Número de alertas gerados
\n#### 2.8.5 Dashboard de Previsão Financeira Inteligente
- **Nome da página:** 'Previsão Financeira Inteligente'
- **URL:** https://onlifin.onlitec.com.br/forecast
- **Componentes da página:**
\n  1. **Seção de Status Geral:**
     - **Cartão de status de risco:**
       - Cartão vermelho para risco alto (saldo negativo previsto em 7 dias ou menos)
       - Cartão amarelo para risco moderado (saldo baixo ou gastos acima da média)
       - Cartão verde para situação saudável (sem riscos detectados)
       - Ícone representativo e mensagem clara
     - **Indicadores principais:**
       - Saldo atual consolidado
       - Saldo previsto para 7 dias
       - Saldo previsto para 30 dias
       - Variação percentual prevista
\n  2. **Gráfico de Previsão de Saldo Diário:**
     - Gráfico de linha mostrando saldo previsto para os próximos 30 dias\n     - Linha de referência para saldo zero (destacada em vermelho)
     - Área sombreada para indicar zona de risco (abaixo de zero)
     - Tooltips com valores exatos ao passar o mouse
     - Marcadores para dias com contas a pagar ou receber agendadas

  3. **Gráfico de Distribuição de Gastos por Categoria:**
     - Gráfico de barras horizontais mostrando gastos dos últimos 30 dias por categoria
     - Ordenado por valor (maior para menor)
     - Cores distintas para cada categoria
     - Percentual de cada categoria em relação ao total
     - Comparação com média dos últimos 3 meses (indicador visual)

  4. **Seção de Alertas:**
     - **Lista de alertas ordenados por gravidade:**
       - Alertas de alta gravidade no topo (ícone vermelho)
       - Alertas de média gravidade (ícone amarelo)
       - Alertas de baixa gravidade (ícone azul)
     - **Cada alerta contém:**
       - Tipo de alerta (risco, despesa_alta, vencimento, anomalia)
       - Descrição clara eacionável
       - Data/período relacionado
       - Ícone representativo
     - **Ações disponíveis:**
       - Marcar alerta como lido
       - Descartar alerta
       - Ver detalhes (link para transações ou contas relacionadas)

  5. **Seção de Insights Gerados pela IA:**
     - **Lista de insights em cards:**
       - Cada insight em um card separado
       - Ícone de lâmpada ou cérebro para representar IA
       - Texto claro e objetivo
       - Data de geração do insight
     - **Exemplos de insights:**
       -'Gastos com mercado aumentaram 18% este mês.'
       - 'Risco de saldo negativo no dia 12.'
       - 'Categoria Restaurantes está35% acima da média.'
- 'Oportunidade de economia: reduzir gastos com transporte em 10% evitaria saldo negativo.'

  6. **Tabela de Previsão Semanal e Mensal:**
     - **Tabela de previsão semanal:**
       - Colunas: Semana, Saldo Previsto, Variação, Status
       - 12 semanas futuras
       - Indicador visual de risco (cor vermelha para saldo negativo previsto)
     - **Tabela de previsão mensal:**
       - Colunas: Mês, Saldo Previsto, Variação, Status
       - 6 meses futuros
       - Indicador visual de risco (cor vermelha para saldo negativo previsto)

  7. **Botão 'Atualizar Previsão Agora':**
     - Botão em destaque no topo da página
     - Permite ao usuário forçar execução imediata da análise
     - Feedback visual durante processamento (spinner ou barra de progresso)
     - Mensagem de confirmação após conclusão
     - Atualização automática de todos os gráficos e indicadores

  8. **Filtros e Opções:**
     - Filtro por período de previsão (7 dias, 30 dias, 3 meses, 6 meses)
     - Filtro por conta específica (para análise isolada)
     - Opção de exportar previsão em PDF ou Excel
     - Opção de compartilhar insights via email
\n#### 2.8.6 Sistema de Notificações Automáticas
- **Notificações in-app:**
  - Toast no canto superior direito para alertas críticos
  - Badge com número de alertas não lidos no menu de navegação
  - Modal de alerta para riscos de alta gravidade (saldo negativo iminente)
- **Notificações por email (opcional):**
  - Email diário com resumo de previsão (configurável pelo usuário)
  - Email imediato para alertas críticos
  - Template de email com gráficos e insights principais
- **Configurações de notificações:**
  - Painel de configuração paraativar/desativar notificações
  - Seleção de tipos de alertas a receber
  - Configuração de horário preferido para notificações diárias
  - Opção de silenciar notificações temporariamente

#### 2.8.7 Integração com Assistente de IA
- **Consulta de previsões via chatbot:**
  - Usuário pode perguntar:'Qual meu saldo previsto para a próxima semana?'
  - Assistente consulta última previsão gerada e responde com dados atualizados
- **Análise de alertas via chatbot:**
  - Usuário pode perguntar: 'Quais são meus alertas financeiros?'
  - Assistente lista alertas ordenados por gravidade com explicações detalhadas
- **Solicitação de insights via chatbot:**
  - Usuário pode perguntar: 'Quais insights você tem sobre meus gastos?'
  - Assistente apresenta insights gerados pela análise automática
- **Atualização manual via chatbot:**
  - Usuário pode solicitar: 'Atualize minha previsão financeira agora'
  - Assistente aciona execução imediata do Agente de Previsão Financeira

### 2.9 Assistente de IA Contextual com Memória Persistente
- Elemento visível em todas as páginas (botão flutuante ou ícone de chat)
- Chat contextual com acesso total aos dados da plataforma
- **Sistema de memória persistente para o modelo de IA:**
  - **Armazenamento de histórico completo de conversas:**
    - Todas as mensagens do usuário são armazenadas no banco de dados
    - Todas as respostas do modelo de IA são armazenadas no banco de dados
    - Timestamp de cada interação\n    - Contexto da conversa (página onde ocorreu, dados acessados)\n  - **Armazenamento de solicitações eações executadas:**
    - Registro de todas as solicitações do usuário (criar transação, criar transferência, analisar extrato, consultar saldo, etc.)
    - Registro de todas as ações executadas pelo modelo de IA em resposta às solicitações
    - Resultado de cada ação (sucesso, erro, dados retornados)
    - Parâmetros utilizados em cada solicitação
  - **Recuperação de contexto em novas conversas:**
    - Ao iniciar nova conversa, modelo de IA carrega histórico relevante
    - Acesso a conversas anteriores para manter continuidade\n    - Referência a solicitações passadas para melhor compreensãodo contexto
  - **Consulta de histórico pelo usuário:**
    - Interface para visualizar histórico completo de conversas
    - Busca por palavra-chave em conversas anteriores
    - Filtro por data, tipo de solicitação ouação executada
    - Exportação de histórico de conversas\n  - **Aprendizado baseado em histórico:**
    - Modelo de IA utiliza histórico para personalizar respostas
    - Identificação de padrões de uso e preferências do usuário
    - Sugestões proativas baseadas em solicitações anteriores
  - **Gestão de memória:**
    - Configuração de período de retenção de histórico
    - Opção de limpar histórico de conversas
    - Opção de excluir conversas específicas
    - Backup automático de histórico de conversas
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
    9. **Usuário pode deixar dropdown sem seleção para transações que não deseja cadastrar**
    10. Usuário clica em 'Cadastrar Transações' no popup
    11. **Sistema cadastra apenas as transações que possuem categoria selecionada**
    12. Popup é fechado e chatbot exibe mensagem de confirmação com número de transações cadastradas
- Funcionalidadesdo assistente:\n  - Categorização automática de transações
  - Recomendações de economia\n  - Previsão de fluxo de caixa
  - Alertas de vencimentos
  - Simulações de parcelamento
  - Sugestões de orçamento
  - **Cadastro, edição e exclusão de transações mediante solicitação do usuário (receitas, despesas, pagamentos recorrentes, parcelamentos)**
  - **Cadastro, edição e exclusão de transferências entre contas mediante solicitação do usuário**
  - **Validação de saldo suficiente antes de criar transferências**
  - **Consulta de histórico de transferências entre contas específicas**
  - **Criação, edição e exclusão de categorias mediante solicitação do usuário**\n  - **Análise e categorização automática de extratos bancários importados via chatbot**
  - **Sugestão de novas categorias baseadas em padrões identificados**
  - **Interpretação de gráficos e indicadores do dashboard**
  - **Análise de tendências financeiras e insights personalizados**
  - **Consulta de dados históricos de meses anteriores**
  - **Referência a conversas e solicitações anteriores para continuidade contextual**
  - **Consulta de previsões financeiras geradas automaticamente**
  - **Explicação de alertas e insights da análise preditiva**
  - **Solicitação de atualização manual de previsão financeira**
- Análise completa de contas cadastradas\n- Consulta detalhada de extratos de transações
- Análise de pagamentos e recebimentos
- **Análise de transferências entre contas**
- Geração e interpretação de relatórios\n- Consulta de saldos em tempo real
- **Integração com plugins configurados para funcionalidades estendidas**

### 2.10 Painel de Administração deIA
- Indicador visual de status de configuração:\n  - Badge ou ícone na página de configurações indicando se há modelo de IA configurado
  - Mensagem clara exibindo 'Modelo Configurado' (comícone de check verde) ou 'Nenhum Modelo Configurado' (com ícone de alerta laranja)
  - Exibição do nome do modelo ativo quando configurado
- Configuração de modelos de IA (seleção de modelo, endpoint, chave de API)
- Ajuste de prompts-padrão e templates de resposta
- **Configuração de sistema de memória:**
  - **Ativação/desativação de memória persistente**
  - **Configuração de período de retenção de histórico (dias, meses, ilimitado)**
  - **Configuração de limite de armazenamento de conversas**
  - **Opção de incluir/excluir tipos específicos de dados no contexto de memória**
- **Controles de permissão de acesso completo (leitura e escrita):**
  - **Permissão de leitura (read_full):**
    - Acesso completo a todas as contas cadastradas
    - Acesso completo a todas as transações (receitas, despesas e transferências)
    - Acesso completo a pagamentos e recebimentos
    - Acesso completo a relatórios financeiros
    - Acesso completo a saldos das contas
    - Acesso completo a extratos de transações
    - **Acesso completo ao histórico de conversas e solicitações**
    - **Acesso completo a previsões financeiras geradas**
  - **Permissão de escrita (write_full):**
    - **Permissão para criar, editar e excluir transações: toggle paraativar/desativar a capacidade do modelo de IA modificar transações na plataforma**
    - **Permissão para criar, editar e excluir transferências: toggle para ativar/desativar a capacidade do modelo de IA modificar transferências entre contas**
    - **Permissão para criar, editar e excluir categorias: toggle para ativar/desativar a capacidade do modelo de IA modificar categorias**
    - **Permissão para análise e categorização automática de extratos bancários com cadastro direto no banco de dados**
    - **Permissão para modificar contas e cartões (opcional, desabilitado por padrão)**
  - Toggle para ativar/desativar acesso de leitura com confirmação e consentimento explícito do usuário
  - **Toggle para ativar/desativar acesso de escrita com confirmação e consentimento explícito do usuário**
  - **Termo de consentimento separado para permissões de leitura e escrita**\n- Logs e histórico de conversas comIA
- **Visualização de histórico de memória do modelo de IA:**
  - **Interface para consultar todas as conversas armazenadas**
  - **Busca e filtro por data, palavra-chave ou tipo de solicitação**
  - **Visualização detalhada de cada conversa com timestamp e contexto**
  - **Estatísticas de uso da memória (número de conversas, tamanho de armazenamento)**
- Registro de auditoria detalhado:\n  - Quem ativou o acesso total (leitura e escrita)
  - Quando foi ativado\n  - Finalidade declarada
  - Histórico de consultas realizadas pelo modelo de IA
  - Dados acessados em cada interação
  - **Registro de todas as transações criadas, editadas ou excluídas pelo modelo de IA (data, hora, tipo, valor, usuário solicitante,ação executada)**
  - **Registro de todas as transferências criadas, editadas ou excluídas pelo modelo de IA (data, hora, conta origem, conta destino, valor, usuário solicitante, ação executada)**
  - **Registro de todas as categorias criadas, editadas ou excluídas pelo modelo de IA**
  - **Registro de análises e categorizações automáticas realizadas pelo modelo de IA**
  - **Registro de acesso ao histórico de memória pelo modelo de IA**
  - **Registro de execuções do Agente de Previsão Financeira (timestamp, status, alertas gerados)**
- Opções de apagar ou exportar histórico conforme políticas de retenção
\n### 2.11 Gestão de Plugins
- **Cadastro e configuração de plugins na plataforma**
- **Interface de gerenciamento de plugins com as seguintes funcionalidades:**
  - Lista de plugins disponíveis e instalados
  - Ativação/desativação de plugins
  - Configuração de parâmetros específicos de cada plugin (chaves de API, endpoints, credenciais)
  - Indicador visual de status (ativo/inativo) comícone de check verde ou alerta laranja
- **Permissões de acesso para plugins:**
  - Controle granular de quais dados da plataforma cada plugin pode acessar
  - Toggle individual para cada tipo de acesso (contas, transações, transferências, relatórios, etc.)
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
\n### 2.12 Gestão de Categorias
- Cadastro de categorias personalizadas para classificação de transações
- **Notificação toast ao cadastrar categoria:**
  - Exibição de notificação toast no canto superior direito da tela
  - Mensagem:'Categoria cadastrada com sucesso!'
  - Ícone de check verde\n  - Duração: 3 segundos\n  - Animação suave de entrada e saída
- Edição e exclusão de categorias existentes
- Organização hierárquica de categorias (categorias e subcategorias)
- Atribuição de cores e ícones personalizados para cada categoria
\n## 3. Segurança e Privacidade\n
### 3.1 Proteção de Dados
- TLS/HTTPS em todas as comunicações
- Criptografia em repouso para dados sensíveis
- Mascaramento e criptografia de números de conta e cartão\n- Transmissão segura de dados completos ao modelo de IA quando acesso total estiver ativado
- **Transmissão segura de comandos de escrita ao modelo de IA quando permissão de escrita estiver ativada**
- **Transmissão segura de dados a plugins conforme permissões configuradas**
- **Criptografia de histórico de conversas armazenado no banco de dados**
- **Proteção de dados de memória do modelo de IA com controles de acesso rigorosos**
- **Criptografia de dados de previsões financeiras armazenados**
\n### 3.2 Autenticação e Autorização
- Autenticação por email/senha com MFA (autenticação multifator)
- RBAC (controle de acesso baseado em papéis): admin, financeiro, usuário\n- Consentimento explícito e informado do usuário para conceder acesso totaldo modelo de IA a todos os dados financeiros:\n  - Contas cadastradas
  - Transações completas (receitas, despesas e transferências)
  - Pagamentos e recebimentos
  - Relatórios financeiros
  - Saldos das contas
  - Extratos de transações
  - **Histórico completo de conversas e solicitações**
  - **Previsões financeiras geradas**
- **Consentimento explícito e separado para permissões de escrita do modelo de IA:**
  - **Permissão para criar, editar e excluir transações**
  - **Permissão para criar, editar e excluir transferências**
  - **Permissão para criar, editar e excluir categorias**
  - **Permissão para análise e categorização automática de extratos com cadastro direto**
- **Consentimento explícito para cada plugin com detalhamento de dados acessados**
- **Consentimento explícito para armazenamento e uso de histórico de conversas**
- Termo de consentimento detalhado explicando o escopo do acesso total (leitura e escrita) e das permissões de cadastro
- Opção de revogar acesso total e permissões de escrita a qualquer momento
- **Opção de revogar permissões de plugins individualmente**
- **Opção de limpar histórico de memória do modelo de IA**\n\n### 3.3 Auditoria\n- Registro completo e detalhado de todos os acessos realizados pelo modelo de IA
- Auditoria de ações de usuários humanos\n- Log de todas as consultas do modelo de IA aos dados da plataforma
- **Log detalhado de todas as operações de escrita realizadas pelo modelo de IA:**
  - **Transações criadas, editadas ou excluídas (timestamp, usuário solicitante, dados da transação, ação executada)**
  - **Transferências criadas, editadas ou excluídas (timestamp, usuário solicitante, conta origem, conta destino, valor, ação executada)**
  - **Categorias criadas, editadas ou excluídas (timestamp, usuário solicitante, dados da categoria, ação executada)**
- **Log de análises e categorizações automáticas realizadas pelo modelo de IA**
- **Log completo de todas as ações executadas por plugins (acessos, modificações, chamadas de API)**
- **Log de acesso ao histórico de memória pelo modelo de IA:**
  - **Timestamp de cada acesso**
  - **Conversas recuperadas**
  - **Contexto utilizado**
  - **Finalidade do acesso**
- **Log de execuções do Agente de Previsão Financeira:**
  - **Timestamp de cada execução (manual ou automática)**
  - **Status de sucesso ou erro**
  - **Número de alertas gerados**
  - **Riscos detectados**
- Middleware de validação e registro antes de enviar dados ao conector de IA
- **Middleware de validação e registro para operações de escrita do modelo de IA**
- **Middleware de validação e registro para comunicação com plugins**
- **Middleware de validação e registro para acesso ao sistema de memória**
- Relatório de auditoria acessível ao usuário para transparência

## 4. Extensibilidade\n
### 4.1 API\n- API REST/GraphQL bemdocumentada\n- Suporte para integrações (apps móveis, plugins, serviços de contabilidade)
- **Endpoints específicos para integração de plugins externos**
- **Endpoints específicos para operações de escrita do modelo de IA (com autenticação e autorização rigorosas)**
- **Endpoints para consulta e gestão de histórico de memória do modelo de IA**\n- **Endpoints para criação, edição e exclusão de transferências entre contas**
- **Endpoints para consulta de previsões financeiras:**
  - GET /api/forecast/daily (previsão diária)
  - GET /api/forecast/weekly (previsão semanal)
  - GET /api/forecast/monthly (previsão mensal)\n  - GET /api/forecast/alerts (alertas ativos)
  - GET /api/forecast/insights (insights gerados)
  - POST /api/forecast/update (forçar atualização de previsão)
\n### 4.2 Webhooks
- Eventos disponíveis:\n  - Nova transação\n  - Vencimento próximo\n  - Sugestão gerada pelo assistente\n  - **Transação criada, editada ou excluída pelo modelo de IA**
  - **Transferência criada, editada ou excluída pelo modelo de IA**
  - **Categoria criada, editada ou excluída pelo modelo de IA**
  - **Categorização automática concluída**
  - **Ação executada por plugin**
  - **Nova conversa armazenada no histórico de memória**
  - **Previsão financeira atualizada**
  - **Alerta de risco gerado**
  - **Saldo negativo previsto**
\n## 5. Observabilidade e Confiabilidade

### 5.1 Monitoramento
- Sistema de logs estruturados
- Métricas de usodo assistente de IA
- Alertas para erros e falhas de importação\n- Monitoramento de acessos do modelo de IA aos dados\n- **Monitoramento de operações de escrita realizadas pelo modelo de IA**
- **Monitoramento de transações e categorias criadas, editadas ou excluídas pelo modelo de IA**
- **Monitoramento de transferências criadas, editadas ou excluídas pelo modelo de IA**\n- **Monitoramento de análises e categorizações automáticas**
- **Monitoramento de performance e erros de plugins**
- **Alertas para falhas de comunicação com plugins**
- **Alertas para operações de escrita não autorizadas ou suspeitas**
- **Monitoramento de usodo sistema de memória:**
  - **Volume de conversas armazenadas**
  - **Tamanho de armazenamento utilizado**
  - **Frequência de acesso ao histórico**
  - **Performance de recuperação de contexto**
- **Alertas para limite de armazenamento de memória próximo ao máximo**
- **Monitoramento de execuções do Agente de Previsão Financeira:**
  - **Taxa de sucesso/erro das execuções**
  - **Tempo médio de processamento**
  - **Número de alertas gerados por execução**
  - **Performance de geração de previsões**
- **Alertas para falhas na rotina automática de análise**
- **Alertas para riscos críticos detectados (saldo negativo iminente)**

### 5.2 Backup e Recuperação
- Backup automático do banco de dados
- Procedimentos documentados de recuperação de desastres
- **Backup de logs de auditoria de operações de escrita do modelo de IA**
- **Backup de histórico de conversas e memória do modelo de IA**\n- **Procedimentos de recuperação de histórico de memória em caso de falha**
- **Backup de previsões financeiras geradas**
- **Procedimentos de recuperação de dados de análise preditiva**
\n## 6. Arquitetura Técnica

### 6.1 Stack Tecnológica
- Backend: Flask ou FastAPI
- Banco de dados: PostgreSQL\n- Frontend: React\n- Containerização: Docker / docker-compose
- CI/CD: GitHub Actions
- **Agendador de tarefas: Celery ou APScheduler (para rotina automática de análise)**
- **Broker de mensagens: Redis ou RabbitMQ (para processamento assíncrono)**
\n### 6.2 Componentes\n- Serviço de IA como componente externo configurável
- Conector que chama endpoints de modelos (OpenAI/compatíveis, instâncias privadas)
- **Sistema de gerenciamento de plugins com arquitetura modular**
- **Conector de plugins para comunicação segura com serviços externos**
- **Sistema de memória persistente para o modelo de IA:**
  - **Módulo de armazenamento de conversas:**
    - Tabela de conversas no banco de dados com campos: id, user_id, timestamp, mensagem_usuario, resposta_ia, contexto, página, dados_acessados
    - Índices otimizados para busca rápida por data, palavra-chave e usuário
    - Compressão de dados para otimizar armazenamento
  - **Módulo de armazenamento de solicitações:**
    - Tabela de solicitações com campos: id, conversa_id, timestamp, tipo_solicitacao, parametros, acao_executada, resultado, status\n    - Relacionamento com tabela de conversas\n  - **Módulo de recuperação de contexto:**
    - Algoritmo de busca semântica para recuperar conversas relevantes
    - Cache de contexto frequentemente acessado
    - Limite configurável de conversas recuperadas por consulta
  - **Módulo de gestão de memória:**
    - Interface para configurar período de retenção\n    - Processo automatizado de limpeza de histórico antigo
    - Exportação de histórico em formato JSON ou CSV
    - Estatísticas de uso de memória
- Camada de acesso a dados que fornece ao modelo de IA:\n  - **Acesso de leitura (read_full):**
    - Dados completos de contas cadastradas
    - Histórico completo de transações (receitas, despesas e transferências)
    - Registros de pagamentos e recebimentos
    - Relatórios financeiros gerados
    - Saldos atualizados das contas
    - Extratos detalhados de transações
    - Dados históricos de meses anteriores para consulta e análise
    - **Histórico completo de conversas e solicitações anteriores**
    - **Previsões financeiras geradas**
  - **Acesso de escrita (write_full):**
    - **Interface de escrita para criar, editar e excluir transações**
    - **Interface de escrita para criar, editar e excluir transferências entre contas**
    - **Validação de saldo suficiente antes de criar transferências**
    - **Interface de escrita para criar, editar e excluir categorias**\n    - **Interface de escrita para cadastro direto de transações analisadas e categorizadas**
    - **Validação de permissões antes de cada operação de escrita**
    - **Registro de auditoria de todas as operações de escrita**
- **Camada de acesso controlado para plugins conforme permissões configuradas**\n- **Módulo de cálculo de saldo em tempo real:**
  - Calcula saldo atual baseado em saldo inicial, receitas recebidas, despesas pagas, transferências enviadas e transferências recebidas
  - Atualiza saldo automaticamente ao registrar, editar ou excluir transações e transferências
  - Fornece saldo atualizado para exibição na interface e para consultas do assistente de IA
  - **Calcula saldo histórico para qualquer mês selecionado**
- **Módulo de gestão de transferências:**
  - **Validação de saldo suficiente na conta de origem antes de criar transferência**
  - **Criação automática de duas movimentações vinculadas (débito na origem e crédito no destino)**
  - **Atualização automática de ambas as movimentações ao editar transferência**
  - **Exclusão automática de ambas as movimentações ao excluir transferência**
  - **Recálculo de saldos das contas envolvidas após cada operação**
  - **Registro de auditoria de todas as operações de transferência**
- **Módulo de análise e categorização automática:**
  - **Trigger manual:acionado pelo usuário atravésdo botão 'Analisar Extrato' no chatbot após upload e salvamento do arquivo**
  - Processa extratos salvos e identifica padrões\n  - Sugere categorias existentes ou novas categorias
  - Exibe resultados em popup com lista ordenada de transações por data
  - Permite revisão e edição de categorias via dropdown antesdo cadastro
  - **Permite deixar dropdown sem seleção para transações que não devem ser cadastradas**
  - **Cadastra apenas transações com categoria selecionada ao clicar em 'Cadastrar Transações'**
  - Aprende com confirmações e correções do usuário
- **Módulo de importação de arquivos OFX:**
  - Parser OFX para versões1.x (SGML) e 2.x (XML)\n  - Extração de dados de transações: data, descrição, valor, tipo, saldo\n  - Validação de integridade e tratamento de erros
  - Mapeamento automático de conta bancária\n- **Módulo de upload e salvamento de extratos no chatbot:**
  - Interface de upload de arquivo no chatbot flutuante
  - Salvamento seguro do arquivo na plataforma
  - Feedback visual de progresso e confirmação de upload
  - Geração de botão 'Analisar Extrato' após salvamento
- **Módulo de popup de resultados de análise:**
  - Exibição de janela popup após análise do modelo de IA
  - Lista ordenada de transações por data\n  - Dropdown de seleção de categoria para cada transação
  - Categoria sugerida pelo modelo de IA pré-selecionada
  - **Possibilidade de deixar dropdown sem seleção**
  - Botão 'Cadastrar Transações' na parte inferior\n  - **Cadastro apenas de transações com categoria selecionada**
  - **Mensagem de confirmação com número de transações cadastradas**
- **Módulo de geração de gráficos e indicadores:**
  - Cálculo de indicadores financeiros em tempo real
  - Geração de gráficos de linha, pizza, barras e área
  - **Geração de gráfico de fluxo para visualização de transferências entre contas**
  - Processamento de dados históricos para tendências
  - Cache de dados agregados para performance
  - API de dados para visualizações no frontend
  - **Suporte a consulta de dados de meses anteriores**
  - **Recálculo de indicadores e gráficos baseado no mês selecionado**
- **Módulo de filtragem e busca de transações:**
  - **Sistema de busca em tempo real por descrição, título ou estabelecimento**
  - **Motor de filtragem por conta bancária, categoria, tipo de transação (receita/despesa/transferência) e intervalo de datas**
  - **Sistema de ordenação por data, categoria e valor (crescente/decrescente)**\n  - **Cache de resultados de filtros frequentes para performance**
  - **API de consulta com suporte a múltiplos filtros simultâneos**
  - **Paginação otimizada para grandes volumes de dados**
  - **Exportação de resultados filtrados em CSV e Excel**
  - **Índices de banco de dados otimizados para busca e filtragem rápida**
  - **Validação de parâmetros de filtro e ordenação**
- **Módulo de notificações toast:**
  - **Sistema de exibição de notificações toast no canto superior direito da tela**
  - **Notificações temporárias (3 segundos) para ações de cadastro:**
    - Cadastro de transação
    - Cadastro de conta bancária
    - Cadastro de categoria
  - **Notificação toast fixa para filtros aplicados:**
    - Exibição no topo da página de transações
    - Permanece visível enquanto filtros estiverem ativos
    - Descrição dos filtros ativos na mensagem\n    - Botão 'X' para fechar notificação\n    - Botão 'Limpar Filtros' dentrodo toast\n  - **Componente reutilizável de toast com:**
    - Animação suave de entrada e saída
    - Ícones personalizados (check verde, filtro azul)\n    - Cores de fundo diferenciadas (verde claro para sucesso, azul claro para filtros)
    - Posicionamento fixo e responsivo
    - Fila de notificações para múltiplas mensagens simultâneas
- Credenciais gerenciadas via vault/segredos (nunca na UI)
- Design modular: módulo de importação, processamento, API, UI, conector IA, **gerenciador de plugins**, **gerador de gráficos**, **sistema de memória**, **sistema de filtragem e busca**, **gerenciador de transferências**, **sistema de notificações toast**, **Agente de Previsão Financeira**, **módulo de análise preditiva**
- **Módulo de validação e confirmação para operações de escrita do modelo de IA:**
  - **Validação de permissões antes de cada operação**
  - **Validação de dados antes de escrita no banco**
  - **Validação de saldo suficiente antes de criar transferências**
  - **Registro de auditoria de todas as operações**
  - **Rollback automático em caso de erro**
- **Módulo de validação e sandbox para execução segura de plugins**
- **Módulo de navegação temporal:**
  - Gerenciamento de seleção de mês no dashboard
  - Cálculo de dados históricos para mês selecionado
  - Cache de dados históricos para performance
  - API para consulta de dados de meses específicos
- **Módulo do Agente de Previsão Financeira:**
  - **Componente de coleta de dados:**
    - Consulta de transações dos últimos 6 meses
    - Consulta de contas a pagar e receber futuras
    - Consulta de saldos atuais de todas as contas
  - **Componente de análise de padrões:**
    - Algoritmo de identificação de padrões de gastos
    - Cálculo de médias de entradas e saídas
    - Detecção de sazonalidade\n    - Identificação de gastos recorrentes
  - **Componente de geração de previsões:**
    - Algoritmo de previsão de saldo diário (30 dias)
    - Algoritmo de previsão de saldo semanal (12 semanas)
    - Algoritmo de previsão de saldo mensal (6 meses)
    - Consideração de contas a pagar e receber agendadas
  - **Componente de detecção de riscos:**
    - Algoritmo de identificação de saldo negativo futuro
    - Detecção de contas que podem causar falta de caixa
    - Identificação de anomalias no padrão de gastos
  - **Componente de geração de insights:**
    - Algoritmo de geração de insights baseados em padrões\n    - Sugestões de economia personalizadas
    - Comparação com períodos anteriores
  - **Componente de formatação de saída:**
    - Geração de JSON estruturado com previsões, alertas e insights
    - Validação de formato de saída
- **Módulo de rotina automática:**
  - **Agendador de tarefas (Celery/APScheduler):**
    - Configuração de execução diária às 02:00\n    - Gerenciamento de fila de tarefas
    - Retry automático em caso de falha\n  - **Worker de processamento:**
    - Execução do Agente de Previsão Financeira
    - Salvamento de resultados no banco de dados
    - Atualização de status de contas a pagar/receber
    - Envio de notificações automáticas
  - **Sistema de logs:**
    - Registro de cada execução\n    - Timestamp de início e fim\n    - Status de sucesso ou erro
    - Número de alertas gerados
- **Módulo de dashboard de previsão:**
  - **Componente de visualização de status:**
    - Cartões de risco (vermelho/amarelo/verde)
    - Indicadores principais (saldo atual, previsto 7 dias, previsto 30 dias)\n  - **Componente de gráficos:**
    - Gráfico de linha de previsão diária
    - Gráfico de barras de gastos por categoria
    - Tabelas de previsão semanal e mensal
  - **Componente de alertas:**
    - Lista de alertas ordenados por gravidade
    - Ações disponíveis (marcar como lido, descartar, ver detalhes)
  - **Componente de insights:**
    - Cards de insights gerados pelaIA
    - Data de geração
  - **Componente de atualização manual:**
    - Botão 'Atualizar Previsão Agora'\n    - Feedback visual de processamento
    - Atualização automática de todos os componentes
\n### 6.3 Testes\n- Cobertura de testes unitários e de integração
- Testes de segurança (SAST/DAST)
- Testes de acessodo modelo de IA aos dados
- **Testes de operações de escrita do modelo de IA:**
  - **Validação de criação de transações**
  - **Validação de edição de transações**
  - **Validação de exclusão de transações**
  - **Validação de criação de transferências**
  - **Validação de edição de transferências**
  - **Validação de exclusão de transferências**
  - **Validação de saldo suficiente antes de criar transferências**
  - **Validação de criação de categorias**
  - **Validação de edição de categorias**
  - **Validação de exclusão de categorias**
  - **Validação de permissões de escrita**
  - **Validação de auditoria de operações de escrita**
  - **Validação de rollback em caso de erro**
- **Testes do sistema de transferências:**
  - **Validação de criação de transferência com saldo suficiente**
  - **Validação de erro ao tentar criar transferência com saldo insuficiente**
  - **Validação de criação automática de duas movimentações vinculadas (débito e crédito)**
  - **Validação de atualização de saldos das contas envolvidas**
  - **Validação de edição de transferência com atualização de ambas as movimentações**
  - **Validação de exclusão de transferência com remoção de ambas as movimentações**
  - **Validação de recálculo de saldos após edição ou exclusão**
  - **Validação de vínculo entre movimentações de débito e crédito**
  - **Validação de exibição de transferências na lista de transações**
  - **Validação de filtro específico para transferências**
  - **Validação de identificação visual de transferências**
- **Testes de análise e categorização automática:**
  - Validação de trigger manual após upload e salvamento\n  - Validação de sugestões de categorias existentes
  - Validação de sugestões de novas categorias
  - Validação de exibição de popup com lista ordenada de transações
  - Validação de dropdown de categorias com pré-seleção
  - **Validação de possibilidade de deixar dropdown sem seleção**
  - **Validação de cadastro apenas de transações com categoria selecionada**
  - **Validação de mensagem de confirmação com número de transações cadastradas**
  - Validação de aprendizado contínuo do modelo\n- **Testes de importação de arquivos OFX:**
  - Validação de parsing de OFX versões 1.x e 2.x
  - Validação de extração correta de transações
  - Validação de tratamento de erros para arquivos corrompidos
  - Validação de mapeamento automático de conta bancária
- **Testes de cálculo de saldo em tempo real:**
  - Validação de cálculo correto ao adicionar receitas (aumento de saldo)
  - Validação de cálculo correto ao adicionar despesas (diminuição de saldo)
  - **Validação de cálculo correto ao adicionar transferências enviadas (diminuição de saldo da origem)**
  - **Validação de cálculo correto ao adicionar transferências recebidas (aumento de saldo do destino)**
  - Validação de atualização de saldo ao editar ou excluir transações
  - **Validação de atualização de saldo ao editar ou excluir transferências**
  - **Validação de cálculo de saldo histórico para meses anteriores**
- **Testes de upload e salvamento de extrato no chatbot:**
  - Validação de upload de arquivo via chatbot\n  - Validação de salvamento seguro na plataforma
  - Validação de feedback visual de progresso
  - Validação de geração de botão 'Analisar Extrato'\n- **Testes de popup de resultados:**
  - Validação de exibição de popup após análise\n  - Validação de ordenação de transações por data
  - Validação de dropdown de categorias\n  - Validação de pré-seleção de categoria sugerida
  - **Validação de possibilidade de deixar dropdown sem seleção**
  - **Validação de cadastro apenas de transações com categoria selecionada ao clicar em 'Cadastrar Transações'**
  - **Validação de mensagem de confirmação com número de transações cadastradas**\n- **Testes de dashboard expandido:**
  - Validação de cálculo de indicadores financeiros
  - **Validação de cálculo de total de transferências realizadas**
  - Validação de geração de gráficos de linha, pizza, barras e área
  - **Validação de geração de gráfico de fluxo de transferências**
  - Validação de filtros de período e categoria
  - Validação de performance com grandes volumes de dados
  - Validação de responsividade de gráficos
  - **Validação de seletor de mês e navegação entre meses**
  - **Validação de atualização de indicadores ao selecionar novo mês**
  - **Validação de atualização de gráficos ao selecionar novo mês**\n  - **Validação de cálculo correto de dados históricos**
  - **Validação de comparação entre meses diferentes**
- **Testes de filtragem e busca de transações:**
  - **Validação de busca em tempo real por descrição, título e estabelecimento**
  - **Validação de destaque visual dos termos encontrados**
  - **Validação de filtro por conta bancária (seleção única e múltipla)**
  - **Validação de filtro por categoria (seleção única e múltipla)**
  - **Validação de filtro por tipo de transação (receita, despesa, transferência, combinações)**
  - **Validação de filtro por intervalo de datas**
  - **Validação de filtros predefinidos (hoje, esta semana, este mês, últimos 30 dias, últimos 90 dias, este ano)**
  - **Validação de ordenação por data (crescente e decrescente)**
  - **Validação de ordenação por categoria (alfabética crescente e decrescente)**
  - **Validação de ordenação por valor (crescente e decrescente)**
  - **Validação de indicador visual da ordenação ativa**
  - **Validação de combinação de múltiplos filtros simultâneos**
  - **Validação de botão 'Limpar Filtros'**
  - **Validação de indicador visual de filtros ativos (badge com número)**
  - **Validação de contador de transações exibidas vs total**
  - **Validação de paginação com grandes volumes de dados**
  - **Validação de exportação de resultados filtrados em CSV**
  - **Validação de exportação de resultados filtrados em Excel**
  - **Validação de performance com múltiplos filtros aplicados**
  - **Validação de atualização em tempo real da lista ao aplicar filtros**
  - **Validação de persistência de filtros ao navegar entre páginas**
- **Testes de notificações toast:**
  - **Validação de exibição de toast ao cadastrar transação**
  - **Validação de exibição de toast ao cadastrar conta bancária**
  - **Validação de exibição de toast ao cadastrar categoria**
  - **Validação de duração de3 segundos para toasts temporários**
  - **Validação de animação suave de entrada e saída**
  - **Validação de exibição de toast fixo ao aplicar filtros**
  - **Validação de permanência do toast fixo enquanto filtros estiverem ativos**
  - **Validação de descrição correta dos filtros ativos no toast**
  - **Validação de botão 'X' para fechar toast fixo**
  - **Validação de botão 'Limpar Filtros' dentro do toast**
  - **Validação de remoção do toast fixo ao limpar todos os filtros**
  - **Validação de posicionamento correto dos toasts (canto superior direito para temporários, topo da página para fixos)**
  - **Validação de cores eícones corretos para cada tipo de toast**
  - **Validação de fila de notificações para múltiplas mensagens simultâneas**
- **Testes de integração com plugins**\n- **Testes de segurança e isolamento de plugins**
- **Testes do sistema de memória do modelo de IA:**
  - **Validação de armazenamento correto de conversas**
  - **Validação de armazenamento correto de solicitações**
  - **Validação de recuperação de contexto relevante**
  - **Validação de busca por palavra-chave no histórico**
  - **Validação de filtros por data e tipo de solicitação**
  - **Validação de limpeza de histórico antigo**
  - **Validação de exportação de histórico**
  - **Validação de performance com grande volume de conversas**
  - **Validação de criptografia de dados de memória**
  - **Validação de controles de acesso ao histórico**
- **Testes do Agente de Previsão Financeira:**
  - **Validação de coleta correta de dados (transações, contas a pagar/receber, saldos)**\n  - **Validação de identificação de padrões de gastos**
  - **Validação de cálculo de médias de entradas e saídas**
  - **Validação de detecção de sazonalidade**
  - **Validação de geração de previsão diária (30 dias)**
  - **Validação de geração de previsão semanal (12 semanas)**
  - **Validação de geração de previsão mensal (6 meses)**
  - **Validação de detecção de risco de saldo negativo**
  - **Validação de identificação de contas que podem causar falta de caixa**
  - **Validação de detecção de anomalias no padrão de gastos**
  - **Validação de geração de insights automáticos**
  - **Validação de formato de saída JSON**
  - **Validação de performance com grandes volumes de dados**
- **Testes da rotina automática:**
  - **Validação de execução diária às 02:00**
  - **Validação de salvamento correto de resultados na entidade Previsões**
  - **Validação de atualização automática de status de contas a pagar/receber**
  - **Validação de envio de alertas quando riscos são detectados**
  - **Validação de retry automático em caso de falha**
  - **Validação de logs de execução**
- **Testes do dashboard de previsão:**
  - **Validação de exibição de cartão de status de risco (vermelho/amarelo/verde)**
  - **Validação de exibição de indicadores principais (saldo atual, previsto 7 dias, previsto 30 dias)**
  - **Validação de gráfico de previsão de saldo diário**
  - **Validação de gráfico de distribuição de gastos por categoria**
  - **Validação de lista de alertas ordenados por gravidade**
  - **Validação de exibição de insights gerados pela IA**
  - **Validação de tabelas de previsão semanal e mensal**
  - **Validação de botão 'Atualizar Previsão Agora'**
  - **Validação de atualização automática de todos os componentes após atualização manual**
  - **Validação de filtros por período e conta**
  - **Validação de exportação de previsão em PDF ou Excel**
- **Testes de notificações automáticas:**
  - **Validação de notificação in-app para alertas críticos**
  - **Validação de envio de email para alertas críticos (se configurado)**
  - **Validação de badge com número de alertas não lidos**
  - **Validação de modal de alerta para riscos de alta gravidade**
- **Testes de integração com assistente de IA:**
  - **Validação de consulta de previsões via chatbot**
  - **Validação de análise de alertas via chatbot**
  - **Validação de solicitação de insights via chatbot**
  - **Validação de atualização manual via chatbot**
\n## 7. Escopo do MVP

### 7.1 Funcionalidades Iniciais
- Autenticação com MFA
- Cadastro de contas e cartões\n- **Notificações toast ao cadastrar conta bancária**
- **Exibição de saldo atual da conta com cálculo automático baseado em receitas recebidas, despesas pagas, transferências enviadas e transferências recebidas**
- **Sistema completo de transferências entre contas cadastradas:**
  - Interface de cadastro de transferências
  - Validação de saldo suficiente\n  - Criação automática de movimentações vinculadas
  - Atualização automática de saldos\n  - Edição e exclusão de transferências
  - Visualização de transferências na lista de transações
  - Filtro específico para transferências
- **Importação de extratos CSV, OFX e QIF com suporte completo ao formato OFX (versões 1.x e 2.x)**
- **Importação de extratos diretamente na interface**
- **Upload de extrato bancário via chatbot flutuante (CSV, OFX e QIF) com novo fluxo:**
  - Botão de upload de arquivo no chatbot
  - Salvamento do extrato na plataforma
  - Botão 'Analisar Extrato' após salvamento
  - Análise do arquivo salvo pelo modelo de IA
  - Exibição de popup com lista ordenada de transações por data
  - Dropdown de categoria para cada transação com sugestão pré-selecionada
- **Possibilidade de deixar dropdown sem seleção**
  - Botão 'Cadastrar Transações' no popup para cadastro em lote
  - **Cadastro apenas de transações com categoria selecionada**
- **Análise e categorização automática de transações comIA:**
  - Trigger manual via botão 'Analisar Extrato' no chatbot
  - Sugestão de categorias existentes
  - Sugestão de novas categorias incluídas no dropdown
  - Interface de revisão em popup antesdo cadastro
  - **Cadastro em lote apenas de transações com categoria selecionada**
  - Aprendizado contínuo baseado em confirmações do usuário
- CRUD de transações (incluindo edição de valor, data, descrição, título e categoria)
- **Notificações toast ao cadastrar transação**
- **CRUD de transferências (incluindo edição de valor, data e descrição)**
- **Atualização automática do saldo da conta ao cadastrar, editar ou excluir transações e transferências**
- Conciliação manual\n- **Gestão de categorias com CRUD completo**
- **Notificações toast ao cadastrar categoria**
- **Gestão de contas a pagar e receber:**
  - Cadastro de contas a pagar (descrição, valor, vencimento, categoria, status, recorrência)
  - Cadastro de contas a receber (descrição, valor, vencimento, categoria, status, recorrente)
  - Atualização automática de status
  - Visualização consolidada\n  - Integração com previsão financeira
- **Página de transações (https://onlifin.onlitec.com.br/transactions) com funcionalidades avançadas:**
  - **Campo de busca em tempo real**
  - **Filtros completos (conta, categoria, tipo, data)**
  - **Ordenação flexível (data, categoria, valor)**
  - **Notificação toast fixa ao aplicar filtros**
  - **Exibição de resultados com contador e paginação**
  - **Exportação em CSV e Excel**
  - **Identificação visual de transferências**
- **Dashboard expandido com indicadores financeiros e gráficos:**
  - **Seletor de mês para visualização de dados históricos**
  - **Indicadores principais ajustados conforme mês selecionado**
  - **Gráficos e visualizações ajustados conforme mês selecionado**
  - **Gráfico de fluxo de transferências entre contas**
  - **Indicadores de performance e alertas visuais**
- **Módulo completo de Análise Financeira Automática baseada em IA:**
  - **Agente de Previsão Financeira:**
    - Análise de histórico de transações (6 meses)
    - Identificação de padrões de gastos
    - Geração de previsões (diária, semanal, mensal)
    - Detecção de riscos (saldo negativo, falta de caixa)
    - Geração de insights automáticos
    - Formato de saída JSON estruturado
  - **Entidade de Previsões no banco de dados**
  - **Rotina automática diária às 02:00:**
    - Execução do Agente de Previsão Financeira
    - Salvamento de resultados\n    - Atualização de status de contas a pagar/receber
    - Envio de alertas automáticos
  - **Dashboard de Previsão Financeira Inteligente:**
    - Cartão de status de risco (vermelho/amarelo/verde)
    - Gráfico de previsão de saldo diário (30 dias)
    - Gráfico de distribuição de gastos por categoria
    - Lista de alertas ordenados por gravidade
    - Seção de insights gerados pela IA
    - Tabelas de previsão semanal e mensal
    - Botão 'Atualizar Previsão Agora'
  - **Sistema de notificações automáticas:**
    - Notificações in-app para alertas críticos\n    - Email para alertas críticos (opcional)
    - Badge com número de alertas não lidos
  - **Integração com assistente de IA:**
    - Consulta de previsões via chatbot
    - Análise de alertas via chatbot
    - Solicitação de insights via chatbot
    - Atualização manual via chatbot
- Assistente de IA com acesso total configurável e memória persistente:\n  - **Acesso de leitura (read_full)**
  - **Acesso de escrita (write_full)**
  - **Sistema de memória persistente**
  - **Funcionalidade de upload de extrato via chatbot**
- Botão de chat comIA nas telas principais
- Painel de administração com:
  - Indicador visual de status de configuração
  - Configuração de modelo de IA
  - **Configuração de sistema de memória**
  - Toggle de acesso de leitura e escrita
  - **Toggles de permissões específicas**
  - **Interface de gerenciamento de plugins**
  - Logs detalhados de chat e acessos
  - **Logs de operações de escrita**
  - **Logs de análises e categorizações**
  - **Logs de execuções do Agente de Previsão Financeira**
  - **Visualização de histórico de memória**
- Relatório de auditoria de acessos e operações
- **Sistema completo de notificações toast**
\n### 7.2 Versões Futuras (1.1/1.2)\n- Conciliação automática por Machine Learning
- Integração com APIs bancárias (Open Banking)
- Importação automatizada OFX\n- Permissões granulares avançadas para modelos de IA com níveis intermediários de acesso
- **Marketplace de plugins com plugins pré-aprovados**
- **SDK para desenvolvimento de plugins personalizados**
- Aplicativo móvel\n- **Gráficos avançados adicionais**
- **Funcionalidades avançadas de memória**
- **Notificações toast avançadas**
- **Análise preditiva avançada:**
  - Previsões de longo prazo (12 meses ou mais)
  - Simulações de cenários financeiros (otimista, pessimista, realista)
  - Recomendações automáticas de investimentos baseadas em padrões\n  - Análise de impacto de decisões financeiras
  - Comparação com benchmarks de mercado
- **Integração com serviços externos:**
  - Integração com plataformas de investimentos
  - Integração com sistemas de contabilidade
  - Integração com ERPs empresariais
\n## 8. Estilo de Design

### 8.1 Paleta de Cores
- Cores principais: azul profissional (#2C3E50) e verde financeiro (#27AE60) para transmitir confiança e estabilidade
- Cores de apoio: cinza claro (#ECF0F1) para fundos e branco (#FFFFFF) para cards
- **Cores para gráficos: paleta harmoniosa com azul (#3498DB), verde (#27AE60), laranja (#E67E22), roxo (#9B59B6) e vermelho (#E74C3C)**
- **Cor específica para transferências: azul claro (#5DADE2) para diferenciação visual**
- **Cores para notificações toast:**
  - Verde claro (#C8E6C9) para toasts de sucesso (cadastros)\n  - Azul claro (#E3F2FD) para toasts informativos (filtros aplicados)
  - Ícone de check verde (#4CAF50) para sucesso\n  - Ícone de filtro azul (#2196F3) para filtros\n- **Cores para cartões de status de risco:**
  - Vermelho (#F44336) para risco alto
  - Amarelo (#FFC107) para risco moderado
  - Verde (#4CAF50) para situação saudável
\n### 8.2 Layout\n- Layout em cards para organização modular de informações financeiras
- Sidebar fixa com navegação principal
- **Layout responsivo e adaptável para diferentes tamanhos de tela**
- **Espaçamento consistente entre elementos (16px para espaçamento padrão, 24px para seções)**
- **Dashboard com grid responsivo para visualização de métricas e gráficos:**
  - **Seletor de mês no topo do dashboard com navegação intuitiva**
  - Seção superior com indicadores principais em cards destacados (grid 3colunas em desktop, 1 coluna em mobile)
  - Seção intermediária com gráficos principais em grid 2x2 (responsivo para1 coluna em mobile)
  - Seção inferior com gráficos secundários e alertas (grid flexível)
- **Popup centralizado para exibição de resultados de análise de extrato:**
  - Largura máxima de 800px\n  - Altura máxima de 80% da viewport
  - Scroll interno para lista de transações
  - Botão de fechar no canto superior direito
- **Interface de histórico de conversas com layout de timeline:**
  - Mensagens do usuário alinhadas à direita
  - Respostas da IA alinhadas à esquerda
  - Timestamp visível para cada mensagem
- **Página de transações com layout limpo e funcional (conforme especificado anteriormente)**
- **Interface de cadastro de transferências (conforme especificado anteriormente)**\n- **Notificações toast (conforme especificado anteriormente)**
- **Dashboard de Previsão Financeira Inteligente:**
  - **Layout em seções verticais:**
    - Seção 1: Cartão de status de risco (largura completa, altura de 120px)
    - Seção 2: Indicadores principais (grid 4 colunas em desktop, 2 colunas em tablet, 1 coluna em mobile)\n    - Seção 3: Gráfico de previsão de saldo diário (largura completa, altura de 400px)
    - Seção 4: Grid 2 colunas (desktop) ou 1 coluna (mobile):\n      - Coluna esquerda: Gráfico de distribuição de gastos por categoria\n      - Coluna direita: Lista de alertas\n    - Seção 5: Seção de insights (cards em grid flexível)
    - Seção 6: Tabelas de previsão semanal e mensal (tabs ou accordion)
  - **Botão 'Atualizar Previsão Agora' fixo no topo da página (canto superior direito)**
  - **Filtros e opções em barra horizontal abaixo do título da página**
\n### 8.3 Elementos Visuais
- Ícones minimalistas para categorias e ações (biblioteca Material Icons ou Font Awesome)
- **Gráficos limpos e legíveis:**
  - Linhas suaves para gráficos de evolução (espessura de 2px)
  - Cores distintas para gráficos de pizza (paleta harmoniosa)
  - Barras com espaçamento adequado (20% de gap entre barras)
  - **Gráfico de fluxo com setas indicando direção das transferências (espessura proporcional ao valor)**
  - Tooltips informativos ao passar o mouse (fundo branco, sombra suave, padding de 8px)
  - Legendas claras e posicionadas estrategicamente (abaixo ou ao lado do gráfico)
  - Grid de fundo sutil para facilitar leitura de valores
- Botão flutuante do assistente de IA com ícone de chat, posicionado no canto inferior direito (60px x 60px, sombra elevada)\n- **Botão de upload de arquivo no chatbot (ícone de clipe ou upload, tamanho 24px)**
- **Botão 'Analisar Extrato' no chatbot após upload (ícone de lupa ou análise, cor azul, tamanho 24px)**\n- **Popup de resultados (conforme especificado anteriormente)**\n- Bordas suaves com raio de 8px para cards, botões e popup
- Sombras sutis para criar hierarquia visual (elevação de 2dp para cards,4dp para botões, 8dp para popup)
- Badge de status com ícone de check verde para modelo configurado ouícone de alerta laranja para ausência de configuração (tamanho 16px)
- Indicador visual de acesso de leitura ativo (ícone de olho verde, tamanho 20px) quando o modelo de IA tiver permissão completa de leitura
- **Indicador visual de acesso de escrita ativo (ícone de lápis verde, tamanho 20px) quando o modelo de IA tiver permissão completa de escrita**
- **Indicador visual de permissão de cadastro ativa (ícone de check verde, tamanho 20px) quando o modelo de IA tiver permissão para criar transações**
- **Indicador visual de permissão de análise ativa (ícone de lupa verde, tamanho 20px) quando o modelo de IA tiver permissão para analisar e categorizar extratos**
- **Badge de status para plugins (ícone de check verde paraativo, ícone cinza para inativo, tamanho 16px)**
- **Ícone de engrenagem para acesso às configurações de plugins (tamanho 24px)**
- **Exibição destacada do saldo atual da conta (conforme especificado anteriormente)**\n- **Cards de indicadores (conforme especificado anteriormente)**\n- **Animações suaves de transição ao carregar gráficos (duração de 300ms, easing ease-in-out)**
- **Seletor de mês (conforme especificado anteriormente)**
- **Indicador visual de memória ativa (ícone de cérebro ou banco de dados verde, tamanho 20px) quando sistema de memória estiver habilitado**
- **Badge com número de conversas armazenadas no histórico (fundo azul, texto branco, tamanho 12px)**
- **Ícone de histórico para acesso rápido às conversas anteriores (tamanho 24px)**
- **Ícone específico para transferências (conforme especificado anteriormente)**\n- **Tag visual'Transferência' (conforme especificado anteriormente)**\n- **Elementos de filtragem e busca (conforme especificado anteriormente)**
- **Notificações toast (conforme especificado anteriormente)**
- **Elementos visuais do Dashboard de Previsão Financeira:**
  - **Cartão de status de risco:**
    - Fundo vermelho (#F44336) para risco alto
    - Fundo amarelo (#FFC107) para risco moderado
    - Fundo verde (#4CAF50) para situação saudável
    - Ícone grande (48px) representativo (alerta para vermelho,atenção para amarelo, check para verde)
    - Texto em branco para contraste
    - Mensagem clara e objetiva
  - **Gráfico de previsão de saldo diário:**
    - Linha azul (#3498DB) para saldo previsto
    - Linha vermelha tracejada para saldo zero (referência)\n    - Área sombreada vermelha (#FFEBEE) para zona de risco (abaixo de zero)
    - Marcadores circulares (8px) para dias com contas a pagar/receber
    - Tooltips com valores exatos e data
  - **Lista de alertas:**
    -Ícone de alerta vermelho (24px) para alta gravidade
    - Ícone de atenção amarelo (24px) para média gravidade
    - Ícone de informação azul (24px) para baixa gravidade
    - Cards de alerta com padding de 12px
    - Botões de ação (marcar como lido, descartar) no canto direito
  - **Cards de insights:**
    - Ícone de lâmpada ou cérebro (32px) no canto superior esquerdo
    - Fundo branco com borda azul claro (1px)
    - Texto em fonte 14px\n    - Data de geração em fonte 12px, cor cinza
  - **Tabelas de previsão:**
    - Cabeçalho com fundo azul claro (#E3F2FD)
    - Linhas alternadas (zebra striping) para melhor leitura
    - Indicador visual de risco (ícone de alerta vermelho) para saldo negativo previsto
    - Valores em negrito para destaque
  - **Botão 'Atualizar Previsão Agora':**
    - Cor azul (#2196F3)\n    - Ícone de atualização (24px) ao lado do texto
    - Padding de 12px 24px
    - Border-radius de 4px
    - Sombra elevada (4dp)
    - Spinner ou barra de progresso durante processamento
\n## 9. Referências de Interface

### 9.1 Imagens de Referência
- Exemplo de mensagem de erro de importação OFX: {2C7B1F61-7FE3-4148-B737-A544FBDEEF2D}.png
- Imagens de referência fornecidas pelo usuário: {50F059AF-0D4A-40FE-B3EE-4BA1DA4340B1}.png, image.png