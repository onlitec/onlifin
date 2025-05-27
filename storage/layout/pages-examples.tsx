
// OnliFin - Exemplos de Páginas Implementando o Layout Moderno
// Este arquivo contém exemplos completos de como implementar diferentes páginas

import React from 'react';
import { 
  PageTemplate, 
  MetricsGrid, 
  ChartsGrid, 
  GoalsGrid,
  ChartWrapper,
  defaultChartConfig 
} from './component-templates';
import { 
  DollarSign, CreditCard, TrendingUp, TrendingDown, 
  PieChart, BarChart3, Target, Users, Calendar,
  FileText, Settings, Wallet, ArrowUpRight
} from 'lucide-react';
import { LineChart, Line, AreaChart, Area, BarChart, Bar, XAxis, YAxis, PieChart as RechartsPie, Pie, Cell } from 'recharts';

// ============================================================================
// 1. PÁGINA DE TRANSAÇÕES
// ============================================================================

export const TransactionsPage = () => {
  const transactionMetrics = [
    {
      title: "Total Entradas",
      value: "R$ 15.420",
      change: "+8.2% este mês",
      icon: TrendingUp,
      color: "green" as const,
      progress: 82,
      isPositive: true
    },
    {
      title: "Total Saídas",
      value: "R$ 9.180",
      change: "-3.1% este mês",
      icon: TrendingDown,
      color: "red" as const,
      progress: 65,
      isPositive: true
    },
    {
      title: "Saldo Líquido",
      value: "R$ 6.240",
      change: "+15.3% este mês",
      icon: Wallet,
      color: "blue" as const,
      progress: 90,
      isPositive: true
    },
    {
      title: "Transações",
      value: "127",
      change: "+12 este mês",
      icon: ArrowUpRight,
      color: "purple" as const,
      progress: 75,
      isPositive: true
    }
  ];

  const transactionData = [
    { month: "Jan", entradas: 12000, saidas: 8000 },
    { month: "Fev", entradas: 13500, saidas: 8500 },
    { month: "Mar", entradas: 14200, saidas: 9200 },
    { month: "Abr", entradas: 15420, saidas: 9180 }
  ];

  const categoryData = [
    { name: "Alimentação", value: 2800, color: "#8884d8" },
    { name: "Transporte", value: 1200, color: "#82ca9d" },
    { name: "Lazer", value: 800, color: "#ffc658" },
    { name: "Outros", value: 1100, color: "#ff7c7c" }
  ];

  const transactionCharts = [
    {
      title: "Fluxo de Caixa",
      description: "Entradas vs Saídas mensais",
      icon: BarChart3,
      component: (
        <ChartWrapper>
          <BarChart data={transactionData}>
            <XAxis dataKey="month" />
            <YAxis />
            <Bar dataKey="entradas" fill="#2563eb" radius={[4, 4, 0, 0]} />
            <Bar dataKey="saidas" fill="#dc2626" radius={[4, 4, 0, 0]} />
          </BarChart>
        </ChartWrapper>
      )
    },
    {
      title: "Gastos por Categoria",
      description: "Distribuição dos gastos do mês",
      icon: PieChart,
      component: (
        <ChartWrapper>
          <RechartsPie>
            <Pie
              data={categoryData}
              cx="50%"
              cy="50%"
              innerRadius={60}
              outerRadius={100}
              paddingAngle={5}
              dataKey="value"
            >
              {categoryData.map((entry, index) => (
                <Cell key={`cell-${index}`} fill={entry.color} />
              ))}
            </Pie>
          </RechartsPie>
        </ChartWrapper>
      )
    }
  ];

  return (
    <PageTemplate 
      title="Transações" 
      subtitle="Acompanhe todas as suas movimentações financeiras"
    >
      <MetricsGrid metrics={transactionMetrics} />
      <ChartsGrid charts={transactionCharts} />
    </PageTemplate>
  );
};

// ============================================================================
// 2. PÁGINA DE INVESTIMENTOS
// ============================================================================

export const InvestmentsPage = () => {
  const investmentMetrics = [
    {
      title: "Portfolio Total",
      value: "R$ 145.800",
      change: "+8.7% este mês",
      icon: TrendingUp,
      color: "green" as const,
      progress: 87,
      isPositive: true
    },
    {
      title: "Rendimento Mensal",
      value: "R$ 2.340",
      change: "+12.1% este mês",
      icon: DollarSign,
      color: "blue" as const,
      progress: 92,
      isPositive: true
    },
    {
      title: "Diversificação",
      value: "8 ativos",
      change: "+2 novos ativos",
      icon: PieChart,
      color: "purple" as const,
      progress: 70,
      isPositive: true
    }
  ];

  const performanceData = [
    { month: "Jan", portfolio: 120000, benchmark: 118000 },
    { month: "Fev", portfolio: 125000, benchmark: 122000 },
    { month: "Mar", portfolio: 135000, benchmark: 128000 },
    { month: "Abr", portfolio: 145800, benchmark: 135000 }
  ];

  const allocationData = [
    { name: "Ações", value: 45, color: "#8884d8" },
    { name: "Renda Fixa", value: 30, color: "#82ca9d" },
    { name: "Cripto", value: 15, color: "#ffc658" },
    { name: "Fundos", value: 10, color: "#ff7c7c" }
  ];

  const investmentCharts = [
    {
      title: "Performance do Portfolio",
      description: "Evolução vs benchmark de mercado",
      icon: TrendingUp,
      component: (
        <ChartWrapper>
          <LineChart data={performanceData}>
            <XAxis dataKey="month" />
            <YAxis />
            <Line type="monotone" dataKey="portfolio" stroke="#2563eb" strokeWidth={3} />
            <Line type="monotone" dataKey="benchmark" stroke="#94a3b8" strokeWidth={2} />
          </LineChart>
        </ChartWrapper>
      )
    },
    {
      title: "Alocação de Ativos",
      description: "Distribuição atual do portfolio",
      icon: PieChart,
      component: (
        <ChartWrapper>
          <RechartsPie>
            <Pie
              data={allocationData}
              cx="50%"
              cy="50%"
              innerRadius={60}
              outerRadius={100}
              paddingAngle={5}
              dataKey="value"
            >
              {allocationData.map((entry, index) => (
                <Cell key={`cell-${index}`} fill={entry.color} />
              ))}
            </Pie>
          </RechartsPie>
        </ChartWrapper>
      )
    }
  ];

  return (
    <PageTemplate 
      title="Investimentos" 
      subtitle="Gerencie e acompanhe seu portfolio de investimentos"
    >
      <MetricsGrid metrics={investmentMetrics} />
      <ChartsGrid charts={investmentCharts} />
    </PageTemplate>
  );
};

// ============================================================================
// 3. PÁGINA DE METAS FINANCEIRAS
// ============================================================================

export const GoalsPage = () => {
  const goalMetrics = [
    {
      title: "Metas Ativas",
      value: "6",
      change: "+2 novas metas",
      icon: Target,
      color: "blue" as const,
      progress: 85,
      isPositive: true
    },
    {
      title: "Total Poupado",
      value: "R$ 28.500",
      change: "+R$ 3.200 este mês",
      icon: DollarSign,
      color: "green" as const,
      progress: 70,
      isPositive: true
    },
    {
      title: "Progresso Médio",
      value: "68%",
      change: "+8% este mês",
      icon: TrendingUp,
      color: "purple" as const,
      progress: 68,
      isPositive: true
    }
  ];

  const goals = [
    {
      title: "Emergência",
      description: "Meta: R$ 30.000",
      progress: 85,
      progressText: "R$ 25.500 economizados"
    },
    {
      title: "Viagem Europa",
      description: "Meta: R$ 15.000",
      progress: 60,
      progressText: "R$ 9.000 economizados"
    },
    {
      title: "Carro Novo",
      description: "Meta: R$ 80.000",
      progress: 35,
      progressText: "R$ 28.000 economizados"
    },
    {
      title: "Casa Própria",
      description: "Meta: R$ 200.000",
      progress: 25,
      progressText: "R$ 50.000 economizados"
    },
    {
      title: "Aposentadoria",
      description: "Meta: R$ 1.000.000",
      progress: 15,
      progressText: "R$ 150.000 investidos"
    },
    {
      title: "Curso MBA",
      description: "Meta: R$ 25.000",
      progress: 80,
      progressText: "R$ 20.000 economizados"
    }
  ];

  const progressData = [
    { month: "Jan", total: 22000 },
    { month: "Fev", total: 24500 },
    { month: "Mar", total: 26200 },
    { month: "Abr", total: 28500 }
  ];

  const goalCharts = [
    {
      title: "Evolução da Poupança",
      description: "Progresso total das metas ao longo do tempo",
      icon: TrendingUp,
      component: (
        <ChartWrapper>
          <AreaChart data={progressData}>
            <XAxis dataKey="month" />
            <YAxis />
            <Area
              type="monotone"
              dataKey="total"
              stroke="#2563eb"
              fill="#2563eb"
              fillOpacity={0.3}
            />
          </AreaChart>
        </ChartWrapper>
      ),
      className: "lg:col-span-2"
    }
  ];

  return (
    <PageTemplate 
      title="Metas Financeiras" 
      subtitle="Defina e acompanhe seus objetivos financeiros"
    >
      <MetricsGrid metrics={goalMetrics} />
      <ChartsGrid charts={goalCharts} columns={1} />
      <GoalsGrid goals={goals} />
    </PageTemplate>
  );
};

// ============================================================================
// 4. PÁGINA DE RELATÓRIOS
// ============================================================================

export const ReportsPage = () => {
  const reportMetrics = [
    {
      title: "Relatórios Gerados",
      value: "24",
      change: "+6 este mês",
      icon: FileText,
      color: "blue" as const,
      progress: 80,
      isPositive: true
    },
    {
      title: "Economia Identificada",
      value: "R$ 1.840",
      change: "Através de análises",
      icon: TrendingDown,
      color: "green" as const,
      progress: 65,
      isPositive: true
    },
    {
      title: "Insights Ativos",
      value: "12",
      change: "+3 novos insights",
      icon: TrendingUp,
      color: "purple" as const,
      progress: 90,
      isPositive: true
    }
  ];

  const comparisonData = [
    { period: "Jan", atual: 15000, anterior: 14200 },
    { period: "Fev", atual: 16500, anterior: 15800 },
    { period: "Mar", atual: 14800, anterior: 16200 },
    { period: "Abr", atual: 18200, anterior: 15400 }
  ];

  const categoryAnalysis = [
    { category: "Alimentação", atual: 2800, limite: 3000 },
    { category: "Transporte", atual: 1200, limite: 1500 },
    { category: "Lazer", atual: 1800, limite: 1200 },
    { category: "Outros", atual: 900, limite: 1000 }
  ];

  const reportCharts = [
    {
      title: "Comparativo Anual",
      description: "Receita atual vs ano anterior",
      icon: BarChart3,
      component: (
        <ChartWrapper>
          <BarChart data={comparisonData}>
            <XAxis dataKey="period" />
            <YAxis />
            <Bar dataKey="atual" fill="#2563eb" radius={[4, 4, 0, 0]} />
            <Bar dataKey="anterior" fill="#94a3b8" radius={[4, 4, 0, 0]} />
          </BarChart>
        </ChartWrapper>
      )
    },
    {
      title: "Análise de Categorias",
      description: "Gastos vs limites estabelecidos",
      icon: PieChart,
      component: (
        <ChartWrapper>
          <BarChart data={categoryAnalysis}>
            <XAxis dataKey="category" />
            <YAxis />
            <Bar dataKey="atual" fill="#dc2626" radius={[4, 4, 0, 0]} />
            <Bar dataKey="limite" fill="#16a34a" radius={[4, 4, 0, 0]} />
          </BarChart>
        </ChartWrapper>
      )
    }
  ];

  return (
    <PageTemplate 
      title="Relatórios" 
      subtitle="Análises detalhadas das suas finanças"
    >
      <MetricsGrid metrics={reportMetrics} />
      <ChartsGrid charts={reportCharts} />
    </PageTemplate>
  );
};

// ============================================================================
// 5. PÁGINA DE CONFIGURAÇÕES
// ============================================================================

export const SettingsPage = () => {
  const settingsMetrics = [
    {
      title: "Perfil Completo",
      value: "95%",
      change: "Quase 100%",
      icon: Users,
      color: "green" as const,
      progress: 95,
      isPositive: true
    },
    {
      title: "Notificações",
      value: "8 ativas",
      change: "2 configuradas hoje",
      icon: Settings,
      color: "blue" as const,
      progress: 70,
      isPositive: true
    }
  ];

  return (
    <PageTemplate 
      title="Configurações" 
      subtitle="Personalize sua experiência no OnliFin"
    >
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {settingsMetrics.map((metric, index) => (
          <div key={index} className="hover-scale transition-all duration-300 hover:shadow-lg">
            {/* Conteúdo das configurações */}
          </div>
        ))}
      </div>
    </PageTemplate>
  );
};

// ============================================================================
// 6. EXEMPLO DE ROTEAMENTO
// ============================================================================

/*
Para implementar as rotas no App.tsx:

import { BrowserRouter, Routes, Route } from "react-router-dom";
import { TransactionsPage, InvestmentsPage, GoalsPage, ReportsPage, SettingsPage } from './pages-examples';

const App = () => (
  <BrowserRouter>
    <Routes>
      <Route path="/" element={<Index />} />
      <Route path="/transactions" element={<TransactionsPage />} />
      <Route path="/investments" element={<InvestmentsPage />} />
      <Route path="/goals" element={<GoalsPage />} />
      <Route path="/reports" element={<ReportsPage />} />
      <Route path="/settings" element={<SettingsPage />} />
      <Route path="*" element={<NotFound />} />
    </Routes>
  </BrowserRouter>
);
*/
