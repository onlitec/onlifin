import * as React from 'react';
import { useNavigate } from 'react-router-dom';
import { requireCurrentUser } from '@/db/client';
import { accountsApi, transactionsApi } from '@/db/api';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useToast } from '@/hooks/use-toast';
import { Download, FileText, Plus, Wallet } from 'lucide-react';
import { BarChart, Bar, LineChart, Line, PieChart, Pie, Cell, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';
import type { CategoryExpense, MonthlyData } from '@/types/types';
import { useFinanceScope } from '@/hooks/useFinanceScope';

export default function Reports() {
  const navigate = useNavigate();
  const [startDate, setStartDate] = React.useState(() => {
    const date = new Date();
    date.setMonth(date.getMonth() - 6);
    return date.toISOString().split('T')[0];
  });
  const [endDate, setEndDate] = React.useState(new Date().toISOString().split('T')[0]);
  const [reportType, setReportType] = React.useState<'category' | 'monthly' | 'cashflow'>('category');
  const [categoryExpenses, setCategoryExpenses] = React.useState<CategoryExpense[]>([]);
  const [monthlyData, setMonthlyData] = React.useState<MonthlyData[]>([]);
  const [setupStatus, setSetupStatus] = React.useState({ accountsCount: 0, transactionsCount: 0 });
  const [isLoading, setIsLoading] = React.useState(false);
  const { companyId, personId } = useFinanceScope();
  const { toast } = useToast();

  React.useEffect(() => {
    loadReportData();
  }, [startDate, endDate, reportType, companyId, personId]);

  const loadReportData = async () => {
    setIsLoading(true);
    try {
      const user = await requireCurrentUser();

      const [accountList, transactionList] = await Promise.all([
        accountsApi.getAccounts(user.id, companyId, personId),
        transactionsApi.getTransactions(user.id, { companyId, personId }),
      ]);

      setSetupStatus({
        accountsCount: accountList.length,
        transactionsCount: transactionList.length,
      });

      if (reportType === 'category') {
        const expenses = await transactionsApi.getCategoryExpenses(user.id, startDate, endDate, companyId, personId);
        setCategoryExpenses(expenses);
      } else if (reportType === 'monthly') {
        const monthly = await transactionsApi.getMonthlyData(user.id, 12, { companyId, personId });
        setMonthlyData(monthly);
      }
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao carregar relatório',
        variant: 'destructive'
      });
    } finally {
      setIsLoading(false);
    }
  };

  const exportToCSV = () => {
    try {
      let csvContent = '';
      let filename = '';

      if (reportType === 'category' && categoryExpenses.length > 0) {
        csvContent = 'Categoria,Valor\n';
        categoryExpenses.forEach(item => {
          csvContent += `${item.category},${item.amount}\n`;
        });
        filename = 'relatorio_categorias.csv';
      } else if (reportType === 'monthly' && monthlyData.length > 0) {
        csvContent = 'Mês,Receitas,Despesas,Saldo\n';
        monthlyData.forEach(item => {
          const balance = item.income - item.expenses;
          csvContent += `${item.month},${item.income},${item.expenses},${balance}\n`;
        });
        filename = 'relatorio_mensal.csv';
      }

      if (!csvContent) {
        toast({
          title: 'Aviso',
          description: 'Nenhum dado disponível para exportar',
          variant: 'destructive'
        });
        return;
      }

      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = filename;
      link.click();

      toast({
        title: 'Sucesso',
        description: 'Relatório exportado com sucesso'
      });
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao exportar relatório',
        variant: 'destructive'
      });
    }
  };

  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL'
    }).format(value);
  };

  const prefix = companyId ? `/pj/${companyId}` : '/pf';
  const EmptyReportState = ({
    title,
    description,
  }: {
    title: string;
    description: string;
  }) => (
    <div className="flex h-[400px] items-center justify-center">
      <div className="text-center space-y-3 max-w-sm px-6">
        <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-50 border border-slate-100">
          <FileText className="h-8 w-8 text-slate-300" />
        </div>
        <h3 className="text-lg font-bold text-slate-900">{title}</h3>
        <p className="text-sm text-muted-foreground">{description}</p>
        <div className="flex flex-col gap-2 sm:flex-row sm:justify-center">
          {setupStatus.accountsCount === 0 ? (
            <Button onClick={() => navigate(`${prefix}/accounts?onboarding=account`)}>
              <Wallet className="mr-2 h-4 w-4" />
              Criar Primeira Conta
            </Button>
          ) : (
            <Button onClick={() => navigate(`${prefix}/transactions?onboarding=transaction`)}>
              <Plus className="mr-2 h-4 w-4" />
              Registrar Primeira Transação
            </Button>
          )}
        </div>
      </div>
    </div>
  );

  return (
    <div className="w-full max-w-[1600px] mx-auto p-6 space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold">Relatórios</h1>
        <Button onClick={exportToCSV} disabled={isLoading}>
          <Download className="mr-2 h-4 w-4" />
          Exportar CSV
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <FileText className="h-5 w-5" />
            Configurações do Relatório
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid gap-4 md:grid-cols-3">
            <div className="space-y-2">
              <Label htmlFor="reportType">Tipo de Relatório</Label>
              <Select value={reportType} onValueChange={(value: any) => setReportType(value)}>
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="category">Despesas por Categoria</SelectItem>
                  <SelectItem value="monthly">Histórico Mensal</SelectItem>
                  <SelectItem value="cashflow">Fluxo de Caixa</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="startDate">Data Inicial</Label>
              <Input
                id="startDate"
                type="date"
                value={startDate}
                onChange={(e) => setStartDate(e.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="endDate">Data Final</Label>
              <Input
                id="endDate"
                type="date"
                value={endDate}
                onChange={(e) => setEndDate(e.target.value)}
              />
            </div>
          </div>
        </CardContent>
      </Card>

      {reportType === 'category' && (
        <div className="grid gap-4 md:grid-cols-2">
          <Card>
            <CardHeader>
              <CardTitle>Gráfico de Pizza</CardTitle>
            </CardHeader>
            <CardContent>
              {categoryExpenses.length > 0 ? (
                <ResponsiveContainer width="100%" height={400}>
                  <PieChart>
                    <Pie
                      data={categoryExpenses}
                      dataKey="amount"
                      nameKey="category"
                      cx="50%"
                      cy="50%"
                      outerRadius={120}
                      label={(entry) => `${entry.category}`}
                    >
                      {categoryExpenses.map((entry, index) => (
                        <Cell key={`cell-${index}`} fill={entry.color} />
                      ))}
                    </Pie>
                    <Tooltip formatter={(value: number) => formatCurrency(value)} />
                    <Legend />
                  </PieChart>
                </ResponsiveContainer>
              ) : (
                <EmptyReportState
                  title="Sem dados para o gráfico"
                  description="As despesas por categoria aparecem aqui depois que você registra as primeiras movimentações."
                />
              )}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Detalhamento por Categoria</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-3 max-h-[400px] overflow-y-auto">
                {categoryExpenses.map((item, index) => (
                  <div key={index} className="flex items-center justify-between p-3 border rounded-lg">
                    <div className="flex items-center gap-3">
                      <div
                        className="w-4 h-4 rounded-full"
                        style={{ backgroundColor: item.color }}
                      />
                      <span className="font-medium">{item.category}</span>
                    </div>
                    <span className="font-bold">{formatCurrency(item.amount)}</span>
                  </div>
                ))}
                {categoryExpenses.length === 0 && (
                  <p className="text-center text-muted-foreground py-8">
                    Nenhuma despesa encontrada no período selecionado.
                  </p>
                )}
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {reportType === 'monthly' && (
        <Card>
          <CardHeader>
            <CardTitle>Histórico Mensal</CardTitle>
          </CardHeader>
          <CardContent>
            {monthlyData.length > 0 ? (
              <>
                <ResponsiveContainer width="100%" height={400}>
                  <BarChart data={monthlyData}>
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis dataKey="month" />
                    <YAxis />
                    <Tooltip formatter={(value: number) => formatCurrency(value)} />
                    <Legend />
                    <Bar dataKey="income" name="Receitas" fill="hsl(var(--income))" />
                    <Bar dataKey="expenses" name="Despesas" fill="hsl(var(--expense))" />
                  </BarChart>
                </ResponsiveContainer>
                <div className="mt-6 space-y-2">
                  <div className="grid grid-cols-4 gap-4 font-medium text-sm border-b pb-2">
                    <div>Mês</div>
                    <div className="text-right">Receitas</div>
                    <div className="text-right">Despesas</div>
                    <div className="text-right">Saldo</div>
                  </div>
                  {monthlyData.map((item, index) => {
                    const balance = item.income - item.expenses;
                    return (
                      <div key={index} className="grid grid-cols-4 gap-4 text-sm py-2 border-b">
                        <div>{item.month}</div>
                        <div className="text-right text-income">{formatCurrency(item.income)}</div>
                        <div className="text-right text-expense">{formatCurrency(item.expenses)}</div>
                        <div className={`text-right font-medium ${balance >= 0 ? 'text-income' : 'text-expense'}`}>
                          {formatCurrency(balance)}
                        </div>
                      </div>
                    );
                  })}
                </div>
              </>
            ) : (
              <EmptyReportState
                title="Sem histórico mensal"
                description="O comparativo mensal será gerado assim que existirem movimentações suficientes."
              />
            )}
          </CardContent>
        </Card>
      )}

      {reportType === 'cashflow' && (
        <Card>
          <CardHeader>
            <CardTitle>Fluxo de Caixa</CardTitle>
          </CardHeader>
          <CardContent>
            {monthlyData.length > 0 ? (
              <ResponsiveContainer width="100%" height={400}>
                <LineChart data={monthlyData}>
                  <CartesianGrid strokeDasharray="3 3" />
                  <XAxis dataKey="month" />
                  <YAxis />
                  <Tooltip formatter={(value: number) => formatCurrency(value)} />
                  <Legend />
                  <Line
                    type="monotone"
                    dataKey="income"
                    name="Receitas"
                    stroke="hsl(var(--income))"
                    strokeWidth={2}
                  />
                  <Line
                    type="monotone"
                    dataKey="expenses"
                    name="Despesas"
                    stroke="hsl(var(--expense))"
                    strokeWidth={2}
                  />
                </LineChart>
              </ResponsiveContainer>
            ) : (
              <EmptyReportState
                title="Sem base para fluxo de caixa"
                description="Registre receitas e despesas para visualizar a evolução do fluxo de caixa."
              />
            )}
          </CardContent>
        </Card>
      )}
    </div>
  );
}
