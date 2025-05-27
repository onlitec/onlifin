// OnliFin - Templates de Componentes Reutilizáveis
// Este arquivo contém todos os componentes base para implementar o layout moderno

import React from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Progress } from "@/components/ui/progress";
import { ChartContainer } from "@/components/ui/chart";
import { ArrowUp, ArrowDown, LucideIcon } from "lucide-react";

// ============================================================================
// 1. TEMPLATE BASE DE PÁGINA
// ============================================================================

interface PageTemplateProps {
  title: string;
  subtitle?: string;
  children: React.ReactNode;
  className?: string;
}

export const PageTemplate: React.FC<PageTemplateProps> = ({ 
  title, 
  subtitle, 
  children, 
  className = "" 
}) => {
  return (
    <div className={`min-h-screen w-full bg-gradient-to-br from-slate-50 to-blue-50 ${className}`}>
      <div className="w-full space-y-8 p-6">
        {/* Header */}
        <div className="text-center space-y-4 animate-fade-in">
          <h1 className="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
            {title}
          </h1>
          {subtitle && (
            <p className="text-xl text-gray-600">
              {subtitle}
            </p>
          )}
        </div>
        
        {/* Conteúdo */}
        {children}
      </div>
    </div>
  );
};

// ============================================================================
// 2. CARD DE MÉTRICA
// ============================================================================

interface MetricCardProps {
  title: string;
  value: string;
  change: string;
  icon: LucideIcon;
  color?: "green" | "red" | "blue" | "purple";
  progress?: number;
  isPositive?: boolean;
}

export const MetricCard: React.FC<MetricCardProps> = ({ 
  title, 
  value, 
  change, 
  icon: Icon, 
  color = "green",
  progress = 75,
  isPositive = true 
}) => {
  return (
    <Card className="hover-scale transition-all duration-300 hover:shadow-lg">
      <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
        <CardTitle className="text-sm font-medium">{title}</CardTitle>
        <Icon className="h-4 w-4 text-muted-foreground" />
      </CardHeader>
      <CardContent>
        <div className={`text-2xl font-bold text-${color}-600`}>{value}</div>
        <p className="text-xs text-muted-foreground flex items-center">
          {isPositive ? (
            <ArrowUp className="h-3 w-3 text-green-500 mr-1" />
          ) : (
            <ArrowDown className="h-3 w-3 text-red-500 mr-1" />
          )}
          {change}
        </p>
        <Progress value={progress} className="mt-2" />
      </CardContent>
    </Card>
  );
};

// ============================================================================
// 3. CARD DE GRÁFICO
// ============================================================================

interface ChartCardProps {
  title: string;
  description?: string;
  icon: LucideIcon;
  children: React.ReactNode;
  className?: string;
}

export const ChartCard: React.FC<ChartCardProps> = ({ 
  title, 
  description, 
  icon: Icon, 
  children,
  className = ""
}) => {
  return (
    <Card className={`hover-scale transition-all duration-300 hover:shadow-lg ${className}`}>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Icon className="h-5 w-5 text-blue-500" />
          {title}
        </CardTitle>
        {description && (
          <CardDescription>{description}</CardDescription>
        )}
      </CardHeader>
      <CardContent>
        {children}
      </CardContent>
    </Card>
  );
};

// ============================================================================
// 4. CARD DE META
// ============================================================================

interface GoalCardProps {
  title: string;
  description: string;
  progress: number;
  progressText: string;
  className?: string;
}

export const GoalCard: React.FC<GoalCardProps> = ({ 
  title, 
  description, 
  progress, 
  progressText,
  className = ""
}) => {
  return (
    <Card className={`hover-scale transition-all duration-300 hover:shadow-lg ${className}`}>
      <CardHeader>
        <CardTitle className="text-lg">{title}</CardTitle>
        <CardDescription>{description}</CardDescription>
      </CardHeader>
      <CardContent>
        <Progress value={progress} className="mb-2" />
        <p className="text-sm text-gray-600">{progressText}</p>
      </CardContent>
    </Card>
  );
};

// ============================================================================
// 5. GRID DE MÉTRICAS
// ============================================================================

interface MetricsGridProps {
  metrics: Array<{
    title: string;
    value: string;
    change: string;
    icon: LucideIcon;
    color?: "green" | "red" | "blue" | "purple";
    progress?: number;
    isPositive?: boolean;
  }>;
}

export const MetricsGrid: React.FC<MetricsGridProps> = ({ metrics }) => {
  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      {metrics.map((metric, index) => (
        <MetricCard key={index} {...metric} />
      ))}
    </div>
  );
};

// ============================================================================
// 6. GRID DE GRÁFICOS
// ============================================================================

interface ChartsGridProps {
  charts: Array<{
    title: string;
    description?: string;
    icon: LucideIcon;
    component: React.ReactNode;
    className?: string;
  }>;
  columns?: 1 | 2;
}

export const ChartsGrid: React.FC<ChartsGridProps> = ({ charts, columns = 2 }) => {
  const gridClass = columns === 2 ? "lg:grid-cols-2" : "lg:grid-cols-1";
  
  return (
    <div className={`grid grid-cols-1 ${gridClass} gap-6`}>
      {charts.map((chart, index) => (
        <ChartCard 
          key={index}
          title={chart.title}
          description={chart.description}
          icon={chart.icon}
          className={chart.className}
        >
          {chart.component}
        </ChartCard>
      ))}
    </div>
  );
};

// ============================================================================
// 7. GRID DE METAS
// ============================================================================

interface GoalsGridProps {
  goals: Array<{
    title: string;
    description: string;
    progress: number;
    progressText: string;
  }>;
}

export const GoalsGrid: React.FC<GoalsGridProps> = ({ goals }) => {
  return (
    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
      {goals.map((goal, index) => (
        <GoalCard key={index} {...goal} />
      ))}
    </div>
  );
};

// ============================================================================
// 8. CONFIGURAÇÕES DE GRÁFICO PADRÃO
// ============================================================================

export const defaultChartConfig = {
  revenue: {
    label: "Receita",
    color: "#2563eb",
  },
  expenses: {
    label: "Despesas",
    color: "#dc2626",
  },
  profit: {
    label: "Lucro",
    color: "#16a34a",
  },
  investment: {
    label: "Investimento",
    color: "#9333ea",
  },
};

// ============================================================================
// 9. WRAPPER PARA GRÁFICOS
// ============================================================================

interface ChartWrapperProps {
  children: React.ReactNode;
  config?: any;
  height?: string;
  className?: string;
}

export const ChartWrapper: React.FC<ChartWrapperProps> = ({ 
  children, 
  config = defaultChartConfig,
  height = "h-[300px]",
  className = ""
}) => {
  return (
    <ChartContainer config={config} className={`${height} ${className}`}>
      {children}
    </ChartContainer>
  );
};

// ============================================================================
// 10. EXEMPLO DE USO COMPLETO
// ============================================================================

/*
EXEMPLO DE IMPLEMENTAÇÃO:

import { 
  PageTemplate, 
  MetricsGrid, 
  ChartsGrid, 
  GoalsGrid 
} from './component-templates';
import { DollarSign, TrendingUp, Activity } from 'lucide-react';

const ExamplePage = () => {
  const metrics = [
    {
      title: "Receita Total",
      value: "R$ 36.300",
      change: "+12.5% em relação ao mês anterior",
      icon: DollarSign,
      color: "green" as const,
      progress: 75,
      isPositive: true
    }
  ];

  const charts = [
    {
      title: "Receita vs Despesas",
      description: "Comparativo mensal",
      icon: TrendingUp,
      component: <YourChartComponent />
    }
  ];

  const goals = [
    {
      title: "Meta Mensal",
      description: "Receita esperada: R$ 10.000",
      progress: 85,
      progressText: "85% da meta atingida"
    }
  ];

  return (
    <PageTemplate 
      title="Dashboard Financeiro" 
      subtitle="Sua visão completa das finanças"
    >
      <MetricsGrid metrics={metrics} />
      <ChartsGrid charts={charts} />
      <GoalsGrid goals={goals} />
    </PageTemplate>
  );
};
*/
