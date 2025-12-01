import { useEffect, useState } from 'react';
import { supabase } from '@/db/supabase';
import { transactionsApi } from '@/db/api';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useToast } from '@/hooks/use-toast';
import { Download, FileText, Calendar } from 'lucide-react';
import { BarChart, Bar, LineChart, Line, PieChart, Pie, Cell, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';
import type { CategoryExpense, MonthlyData } from '@/types/types';

export default function Reports() {
  const [startDate, setStartDate] = useState(() => {
    const date = new Date();
    date.setMonth(date.getMonth() - 6);
    return date.toISOString().split('T')[0];
  });
  const [endDate, setEndDate] = useState(new Date().toISOString().split('T')[0]);
  const [reportType, setReportType] = useState<'category' | 'monthly' | 'cashflow'>('category');
  const [categoryExpenses, setCategoryExpenses] = useState<CategoryExpense[]>([]);
  const [monthlyData, setMonthlyData] = useState<MonthlyData[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const { toast } = useToast();

  useEffect(() => {
    loadReportData();
  }, [startDate, endDate, reportType]);

  const loadReportData = async () => {
    setIsLoading(true);
    try {
      const { data: { user } } = await supabase.auth.getUser();
      if (!user) return;

      if (reportType === 'category') {
        const expenses = await transactionsApi.getCategoryExpenses(user.id, startDate, endDate);
        setCategoryExpenses(expenses);
      } else if (reportType === 'monthly') {
        const monthly = await transactionsApi.getMonthlyData(user.id, 12);
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

  return (
    <div className="container mx-auto p-6 space-y-6">
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
                <div className="flex items-center justify-center h-[400px] text-muted-foreground">
                  Nenhum dado disponível
                </div>
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
                    Nenhuma despesa no período selecionado
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
              <div className="flex items-center justify-center h-[400px] text-muted-foreground">
                Nenhum dado disponível
              </div>
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
              <div className="flex items-center justify-center h-[400px] text-muted-foreground">
                Nenhum dado disponível
              </div>
            )}
          </CardContent>
        </Card>
      )}
    </div>
  );
}
