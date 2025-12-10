import * as React from 'react';
import { supabase } from '@/db/supabase';
import { forecastsApi } from '@/db/api';
import type { FinancialForecast } from '@/types/types';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Skeleton } from '@/components/ui/skeleton';
import { useToast } from '@/hooks/use-toast';
import {
  LineChart,
  Line,
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer
} from 'recharts';
import {
  TrendingUp,
  TrendingDown,
  AlertTriangle,
  CheckCircle2,
  RefreshCw,
  Calendar,
  DollarSign,
  Lightbulb
} from 'lucide-react';

export default function ForecastDashboard() {
  const { toast } = useToast();
  const [userId, setUserId] = React.useState<string | null>(null);
  const [forecast, setForecast] = React.useState<FinancialForecast | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [generating, setGenerating] = React.useState(false);

  React.useEffect(() => {
    const initUser = async () => {
      const { data: { user } } = await supabase.auth.getUser();
      if (user) {
        setUserId(user.id);
      }
    };
    initUser();
  }, []);

  React.useEffect(() => {
    if (userId) {
      loadForecast();
    }
  }, [userId]);

  const loadForecast = async () => {
    try {
      setLoading(true);
      const data = await forecastsApi.getLatest(userId!);
      setForecast(data);
    } catch (error) {
      console.error('Erro ao carregar previsão:', error);
      toast({
        title: 'Erro',
        description: 'Não foi possível carregar a previsão financeira',
        variant: 'destructive'
      });
    } finally {
      setLoading(false);
    }
  };

  const handleGenerateForecast = async () => {
    try {
      setGenerating(true);
      await forecastsApi.triggerGeneration(userId!);
      toast({
        title: 'Sucesso',
        description: 'Previsão financeira gerada com sucesso!',
      });
      // Aguardar um pouco e recarregar
      setTimeout(() => {
        loadForecast();
      }, 2000);
    } catch (error) {
      console.error('Erro ao gerar previsão:', error);
      toast({
        title: 'Erro',
        description: 'Não foi possível gerar a previsão financeira',
        variant: 'destructive'
      });
    } finally {
      setGenerating(false);
    }
  };

  // Preparar dados para o gráfico diário
  const dailyChartData = forecast
    ? Object.entries(forecast.forecast_daily).map(([date, balance]) => ({
        date: new Date(date).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' }),
        saldo: balance
      }))
    : [];

  // Preparar dados para o gráfico semanal
  const weeklyChartData = forecast
    ? Object.entries(forecast.forecast_weekly).map(([week, balance]) => ({
        semana: week.replace('semana_', 'Sem '),
        saldo: balance
      }))
    : [];

  // Preparar dados para o gráfico mensal
  const monthlyChartData = forecast
    ? Object.entries(forecast.forecast_monthly).map(([month, balance]) => ({
        mes: month.charAt(0).toUpperCase() + month.slice(1),
        saldo: balance
      }))
    : [];

  // Função para determinar cor do alerta
  const getAlertColor = (gravidade: string) => {
    switch (gravidade) {
      case 'alta':
        return 'destructive';
      case 'media':
        return 'default';
      case 'baixa':
        return 'default';
      default:
        return 'default';
    }
  };

  // Função para determinar ícone do alerta
  const getAlertIcon = (gravidade: string) => {
    switch (gravidade) {
      case 'alta':
        return <AlertTriangle className="h-5 w-5" />;
      case 'media':
        return <AlertTriangle className="h-5 w-5" />;
      case 'baixa':
        return <CheckCircle2 className="h-5 w-5" />;
      default:
        return <AlertTriangle className="h-5 w-5" />;
    }
  };

  if (loading) {
    return (
      <div className="container mx-auto p-4 xl:p-8 space-y-6">
        <div className="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-4 pb-2">
          <div>
            <Skeleton className="h-10 w-64 mb-2" />
            <Skeleton className="h-5 w-96" />
          </div>
          <Skeleton className="h-10 w-48" />
        </div>
        <div className="grid gap-6 grid-cols-1 xl:grid-cols-2">
          <Skeleton className="h-64" />
          <Skeleton className="h-64" />
          <Skeleton className="h-64" />
          <Skeleton className="h-64" />
        </div>
      </div>
    );
  }

  if (!forecast) {
    return (
      <div className="container mx-auto p-4 xl:p-8 space-y-6">
        <div className="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-4 pb-2">
          <div>
            <h1 className="text-3xl xl:text-4xl font-bold tracking-tight">Previsão Financeira Inteligente</h1>
            <p className="text-muted-foreground mt-1">Análise preditiva com IA dos seus dados financeiros</p>
          </div>
        </div>

        <Card>
          <CardContent className="flex flex-col items-center justify-center py-16">
            <TrendingUp className="h-16 w-16 text-muted-foreground mb-4" />
            <h3 className="text-xl font-semibold mb-2">Nenhuma previsão disponível</h3>
            <p className="text-muted-foreground text-center mb-6">
              Gere sua primeira previsão financeira para visualizar insights e alertas inteligentes
            </p>
            <Button onClick={handleGenerateForecast} disabled={generating} size="lg">
              {generating ? (
                <>
                  <RefreshCw className="mr-2 h-5 w-5 animate-spin" />
                  Gerando Previsão...
                </>
              ) : (
                <>
                  <TrendingUp className="mr-2 h-5 w-5" />
                  Gerar Previsão Agora
                </>
              )}
            </Button>
          </CardContent>
        </Card>
      </div>
    );
  }

  return (
    <div className="container mx-auto p-4 xl:p-8 space-y-6">
      {/* Header */}
      <div className="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-4 pb-2">
        <div>
          <h1 className="text-3xl xl:text-4xl font-bold tracking-tight">Previsão Financeira Inteligente</h1>
          <p className="text-muted-foreground mt-1">
            Última atualização: {new Date(forecast.calculation_date).toLocaleString('pt-BR')}
          </p>
        </div>
        <Button onClick={handleGenerateForecast} disabled={generating} size="lg">
          {generating ? (
            <>
              <RefreshCw className="mr-2 h-5 w-5 animate-spin" />
              Atualizando...
            </>
          ) : (
            <>
              <RefreshCw className="mr-2 h-5 w-5" />
              Atualizar Previsão
            </>
          )}
        </Button>
      </div>

      {/* Status Card */}
      <Card className={forecast.risk_negative ? 'border-destructive' : 'border-primary'}>
        <CardContent className="flex items-center gap-4 p-6">
          {forecast.risk_negative ? (
            <>
              <div className="p-3 rounded-full bg-destructive/10">
                <TrendingDown className="h-8 w-8 text-destructive" />
              </div>
              <div>
                <h3 className="text-xl font-semibold text-destructive">Atenção: Risco Detectado</h3>
                <p className="text-muted-foreground">
                  Saldo negativo previsto para {forecast.risk_date ? new Date(forecast.risk_date).toLocaleDateString('pt-BR') : 'breve'}
                </p>
              </div>
            </>
          ) : (
            <>
              <div className="p-3 rounded-full bg-primary/10">
                <CheckCircle2 className="h-8 w-8 text-primary" />
              </div>
              <div>
                <h3 className="text-xl font-semibold text-primary">Situação Estável</h3>
                <p className="text-muted-foreground">
                  Suas finanças estão equilibradas no período analisado
                </p>
              </div>
            </>
          )}
        </CardContent>
      </Card>

      {/* Alertas */}
      {forecast.alerts && forecast.alerts.length > 0 && (
        <div className="space-y-3">
          <h2 className="text-2xl font-semibold flex items-center gap-2">
            <AlertTriangle className="h-6 w-6" />
            Alertas
          </h2>
          <div className="grid gap-3">
            {forecast.alerts.map((alert, index) => (
              <Alert key={index} variant={getAlertColor(alert.gravidade)}>
                <div className="flex items-start gap-3">
                  {getAlertIcon(alert.gravidade)}
                  <div className="flex-1">
                    <AlertTitle className="capitalize">{alert.tipo.replace(/_/g, ' ')}</AlertTitle>
                    <AlertDescription>{alert.descricao}</AlertDescription>
                  </div>
                  <span className="text-xs font-medium px-2 py-1 rounded-full bg-background">
                    {alert.gravidade}
                  </span>
                </div>
              </Alert>
            ))}
          </div>
        </div>
      )}

      {/* Insights */}
      {forecast.insights && forecast.insights.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Lightbulb className="h-5 w-5" />
              Insights da IA
            </CardTitle>
            <CardDescription>Análises inteligentes sobre seus padrões financeiros</CardDescription>
          </CardHeader>
          <CardContent>
            <ul className="space-y-3">
              {forecast.insights.map((insight, index) => (
                <li key={index} className="flex items-start gap-3 p-3 rounded-lg bg-muted/50">
                  <span className="text-lg">{insight.split(' ')[0]}</span>
                  <p className="flex-1">{insight.substring(insight.indexOf(' ') + 1)}</p>
                </li>
              ))}
            </ul>
          </CardContent>
        </Card>
      )}

      {/* Gráfico de Previsão Diária */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Calendar className="h-5 w-5" />
            Previsão Diária (30 dias)
          </CardTitle>
          <CardDescription>Projeção do saldo para os próximos 30 dias</CardDescription>
        </CardHeader>
        <CardContent>
          <ResponsiveContainer width="100%" height={300}>
            <LineChart data={dailyChartData}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="date" />
              <YAxis />
              <Tooltip
                formatter={(value: number) => `R$ ${value.toFixed(2)}`}
                labelStyle={{ color: '#000' }}
              />
              <Legend />
              <Line
                type="monotone"
                dataKey="saldo"
                stroke="hsl(var(--primary))"
                strokeWidth={2}
                name="Saldo Previsto"
              />
            </LineChart>
          </ResponsiveContainer>
        </CardContent>
      </Card>

      {/* Gráficos Semanais e Mensais */}
      <div className="grid gap-6 grid-cols-1 xl:grid-cols-2">
        {/* Gráfico Semanal */}
        <Card>
          <CardHeader>
            <CardTitle>Previsão Semanal (12 semanas)</CardTitle>
            <CardDescription>Projeção do saldo por semana</CardDescription>
          </CardHeader>
          <CardContent>
            <ResponsiveContainer width="100%" height={250}>
              <BarChart data={weeklyChartData}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="semana" />
                <YAxis />
                <Tooltip
                  formatter={(value: number) => `R$ ${value.toFixed(2)}`}
                  labelStyle={{ color: '#000' }}
                />
                <Bar dataKey="saldo" fill="hsl(var(--primary))" name="Saldo" />
              </BarChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>

        {/* Gráfico Mensal */}
        <Card>
          <CardHeader>
            <CardTitle>Previsão Mensal (6 meses)</CardTitle>
            <CardDescription>Projeção do saldo por mês</CardDescription>
          </CardHeader>
          <CardContent>
            <ResponsiveContainer width="100%" height={250}>
              <BarChart data={monthlyChartData}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="mes" />
                <YAxis />
                <Tooltip
                  formatter={(value: number) => `R$ ${value.toFixed(2)}`}
                  labelStyle={{ color: '#000' }}
                />
                <Bar dataKey="saldo" fill="hsl(var(--chart-2))" name="Saldo" />
              </BarChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>
      </div>

      {/* Padrões de Gastos */}
      {forecast.spending_patterns && (
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <DollarSign className="h-5 w-5" />
              Padrões de Gastos
            </CardTitle>
            <CardDescription>Análise dos seus hábitos financeiros</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="grid gap-4 grid-cols-1 md:grid-cols-2 xl:grid-cols-4">
              <div className="p-4 rounded-lg bg-income/10 border border-income/20">
                <p className="text-sm text-muted-foreground mb-1">Receita Mensal Média</p>
                <p className="text-2xl font-bold text-income">
                  R$ {(forecast.spending_patterns.avg_monthly_income as number)?.toFixed(2) || '0.00'}
                </p>
              </div>
              <div className="p-4 rounded-lg bg-expense/10 border border-expense/20">
                <p className="text-sm text-muted-foreground mb-1">Despesa Mensal Média</p>
                <p className="text-2xl font-bold text-expense">
                  R$ {(forecast.spending_patterns.avg_monthly_expense as number)?.toFixed(2) || '0.00'}
                </p>
              </div>
              <div className="p-4 rounded-lg bg-primary/10 border border-primary/20">
                <p className="text-sm text-muted-foreground mb-1">Receita Diária Média</p>
                <p className="text-2xl font-bold text-primary">
                  R$ {(forecast.spending_patterns.avg_daily_income as number)?.toFixed(2) || '0.00'}
                </p>
              </div>
              <div className="p-4 rounded-lg bg-muted border">
                <p className="text-sm text-muted-foreground mb-1">Despesa Diária Média</p>
                <p className="text-2xl font-bold">
                  R$ {(forecast.spending_patterns.avg_daily_expense as number)?.toFixed(2) || '0.00'}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
