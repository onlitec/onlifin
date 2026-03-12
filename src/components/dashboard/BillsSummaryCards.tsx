import * as React from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { AlertTriangle, TrendingUp, TrendingDown, Calendar, DollarSign, Eye } from 'lucide-react';
import { format } from 'date-fns';
import { ptBR } from 'date-fns/locale';
import { cn } from '@/lib/utils';

interface BillsSummary {
  toPay: {
    total: number;
    count: number;
    dueToday: number;
    overdue: number;
    dueThisWeek: number;
  };
  toReceive: {
    total: number;
    count: number;
    dueToday: number;
    dueThisWeek: number;
  };
}

interface BillsSummaryCardsProps {
  summary: BillsSummary;
  onViewBills?: (type: 'pay' | 'receive') => void;
  isLoading?: boolean;
}

export function BillsSummaryCards({ summary, onViewBills, isLoading = false }: BillsSummaryCardsProps) {
  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL'
    }).format(amount);
  };

  const getSeverityColor = (amount: number, type: 'overdue' | 'due-today' | 'due-week') => {
    if (type === 'overdue') return 'text-red-600 bg-red-50 border-red-200';
    if (type === 'due-today') return 'text-orange-600 bg-orange-50 border-orange-200';
    return 'text-yellow-600 bg-yellow-50 border-yellow-200';
  };

  const getSeverityIcon = (type: 'overdue' | 'due-today' | 'due-week') => {
    if (type === 'overdue') return <AlertTriangle className="h-4 w-4" />;
    if (type === 'due-today') return <Calendar className="h-4 w-4" />;
    return <Eye className="h-4 w-4" />;
  };

  if (isLoading) {
    return (
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <Card className="animate-pulse">
          <CardHeader className="space-y-2">
            <div className="h-4 bg-gray-200 rounded w-1/2"></div>
            <div className="h-3 bg-gray-200 rounded w-1/3"></div>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              <div className="h-8 bg-gray-200 rounded"></div>
              <div className="grid grid-cols-3 gap-2">
                <div className="h-6 bg-gray-200 rounded"></div>
                <div className="h-6 bg-gray-200 rounded"></div>
                <div className="h-6 bg-gray-200 rounded"></div>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card className="animate-pulse">
          <CardHeader className="space-y-2">
            <div className="h-4 bg-gray-200 rounded w-1/2"></div>
            <div className="h-3 bg-gray-200 rounded w-1/3"></div>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              <div className="h-8 bg-gray-200 rounded"></div>
              <div className="grid grid-cols-2 gap-2">
                <div className="h-6 bg-gray-200 rounded"></div>
                <div className="h-6 bg-gray-200 rounded"></div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    );
  }

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
      {/* Contas a Pagar */}
      <Card className={cn(
        "transition-all duration-200 hover:shadow-lg",
        summary.toPay.overdue > 0 && "border-red-200 bg-red-50/30"
      )}>
        <CardHeader className="pb-3">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <TrendingDown className="h-5 w-5 text-red-600" />
              <CardTitle className="text-lg">Contas a Pagar</CardTitle>
            </div>
            {summary.toPay.overdue > 0 && (
              <Badge variant="destructive" className="text-xs">
                {summary.toPay.overdue} vencida{summary.toPay.overdue > 1 ? 's' : ''}
              </Badge>
            )}
          </div>
          <CardDescription>
            Resumo das suas contas pendentes de pagamento
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          {/* Total */}
          <div className="flex items-center justify-between p-3 bg-red-50 rounded-lg border border-red-200">
            <span className="text-sm font-medium text-red-900">Total a Pagar</span>
            <span className="text-lg font-bold text-red-900">
              {formatCurrency(summary.toPay.total)}
            </span>
          </div>

          {/* Detalhes */}
          <div className="space-y-2">
            {/* Vencidas */}
            {summary.toPay.overdue > 0 && (
              <div className="flex items-center justify-between p-2 bg-red-100 rounded-lg border border-red-200">
                <div className="flex items-center gap-2">
                  {getSeverityIcon('overdue')}
                  <span className="text-sm font-medium text-red-900">Vencidas</span>
                </div>
                <div className="text-right">
                  <span className="text-sm font-bold text-red-900">
                    {formatCurrency(summary.toPay.overdue)}
                  </span>
                  <span className="text-xs text-red-700 ml-1">
                    ({summary.toPay.overdue} {summary.toPay.overdue === 1 ? 'conta' : 'contas'})
                  </span>
                </div>
              </div>
            )}

            {/* Vence Hoje */}
            {summary.toPay.dueToday > 0 && (
              <div className="flex items-center justify-between p-2 bg-orange-100 rounded-lg border border-orange-200">
                <div className="flex items-center gap-2">
                  {getSeverityIcon('due-today')}
                  <span className="text-sm font-medium text-orange-900">Vence Hoje</span>
                </div>
                <div className="text-right">
                  <span className="text-sm font-bold text-orange-900">
                    {formatCurrency(summary.toPay.dueToday)}
                  </span>
                  <span className="text-xs text-orange-700 ml-1">
                    ({summary.toPay.dueToday === 1 ? '1 conta' : `${summary.toPay.dueToday} contas`})
                  </span>
                </div>
              </div>
            )}

            {/* Próximos 7 dias */}
            {summary.toPay.dueThisWeek > 0 && (
              <div className="flex items-center justify-between p-2 bg-yellow-100 rounded-lg border border-yellow-200">
                <div className="flex items-center gap-2">
                  {getSeverityIcon('due-week')}
                  <span className="text-sm font-medium text-yellow-900">Próximos 7 dias</span>
                </div>
                <div className="text-right">
                  <span className="text-sm font-bold text-yellow-900">
                    {formatCurrency(summary.toPay.dueThisWeek)}
                  </span>
                </div>
              </div>
            )}
          </div>

          {/* Ações */}
          {onViewBills && (
            <div className="pt-2">
              <Button
                variant="outline"
                size="sm"
                onClick={() => onViewBills('pay')}
                className="w-full"
              >
                <Eye className="h-4 w-4 mr-2" />
                Ver Todas as Contas
              </Button>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Contas a Receber */}
      <Card className="transition-all duration-200 hover:shadow-lg">
        <CardHeader className="pb-3">
          <div className="flex items-center gap-2">
            <TrendingUp className="h-5 w-5 text-green-600" />
            <CardTitle className="text-lg">Contas a Receber</CardTitle>
          </div>
          <CardDescription>
            Valores que você espera receber em breve
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          {/* Total */}
          <div className="flex items-center justify-between p-3 bg-green-50 rounded-lg border border-green-200">
            <span className="text-sm font-medium text-green-900">Total a Receber</span>
            <span className="text-lg font-bold text-green-900">
              {formatCurrency(summary.toReceive.total)}
            </span>
          </div>

          {/* Detalhes */}
          <div className="space-y-2">
            {/* Recebe Hoje */}
            {summary.toReceive.dueToday > 0 && (
              <div className="flex items-center justify-between p-2 bg-green-100 rounded-lg border border-green-200">
                <div className="flex items-center gap-2">
                  <Calendar className="h-4 w-4 text-green-700" />
                  <span className="text-sm font-medium text-green-900">Recebe Hoje</span>
                </div>
                <div className="text-right">
                  <span className="text-sm font-bold text-green-900">
                    {formatCurrency(summary.toReceive.dueToday)}
                  </span>
                  <span className="text-xs text-green-700 ml-1">
                    ({summary.toReceive.dueToday === 1 ? '1 conta' : `${summary.toReceive.dueToday} contas`})
                  </span>
                </div>
              </div>
            )}

            {/* Próximos 7 dias */}
            {summary.toReceive.dueThisWeek > 0 && (
              <div className="flex items-center justify-between p-2 bg-blue-100 rounded-lg border border-blue-200">
                <div className="flex items-center gap-2">
                  <Eye className="h-4 w-4 text-blue-700" />
                  <span className="text-sm font-medium text-blue-900">Próximos 7 dias</span>
                </div>
                <div className="text-right">
                  <span className="text-sm font-bold text-blue-900">
                    {formatCurrency(summary.toReceive.dueThisWeek)}
                  </span>
                </div>
              </div>
            )}
          </div>

          {/* Resumo */}
          <div className="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
            <span className="text-sm text-gray-600">
              {summary.toReceive.count} {summary.toReceive.count === 1 ? 'conta' : 'contas'} pendentes
            </span>
            <DollarSign className="h-4 w-4 text-gray-500" />
          </div>

          {/* Ações */}
          {onViewBills && (
            <div className="pt-2">
              <Button
                variant="outline"
                size="sm"
                onClick={() => onViewBills('receive')}
                className="w-full"
              >
                <Eye className="h-4 w-4 mr-2" />
                Ver Todas as Contas
              </Button>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
