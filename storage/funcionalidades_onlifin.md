# Funcionalidades da Plataforma OnliFin

A plataforma OnliFin oferece as seguintes funcionalidades:

## 1. Transações
- Acompanhar entradas e saídas financeiras
- Importação de extratos bancários
- Visualizar saldo líquido e número total de transações
- Métricas detalhadas com variação percentual e indicadores de progresso
- Gráfico de fluxo de caixa (entradas vs saídas)
- Distribuição de gastos por categoria

## 2. Investimentos
- Gerenciar portfólio de investimentos
- Métricas de rendimento mensal e diversificação
- Gráfico de performance do portfólio vs benchmark
- Gráfico de alocação de ativos

## 3. Metas Financeiras
- Definir metas como emergência, viagem, carro, casa, aposentadoria e cursos
- Acompanhar progresso de cada meta com barra de progresso e valores poupados
- Gráfico de evolução da poupança total ao longo do tempo

## 4. Relatórios
- Gerar relatórios financeiros e insights
- Gráfico comparativo anual (receita atual vs ano anterior)
- Análise de categorias de gastos vs limites estabelecidos

## 5. Configurações
- Personalizar perfil de usuário
- Gerenciar notificações

## Componentes Reutilizáveis
- `PageTemplate`: template base de página com título e subtítulo
- `MetricsGrid` e `MetricCard`: exibição de métricas em cards
- `ChartsGrid` e `ChartCard`: exibição de gráficos em cards
- `GoalsGrid` e `GoalCard`: exibição de metas em cards
- `ChartWrapper` e `defaultChartConfig`: wrapper para gráficos com configurações padrão

## Integração e Roteamento
- Estrutura de rotas com React Router
- Suporte a diferentes páginas e navegação 