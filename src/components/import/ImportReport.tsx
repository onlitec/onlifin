import * as React from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { CheckCircle2, XCircle, AlertCircle, TrendingUp, Clock, Sparkles, FileCheck } from 'lucide-react';

export interface ImportReport {
  total: number;
  imported: number;
  duplicates: number;
  errors: number;
  categorized: {
    byRules: number;
    byAI: number;
    manual: number;
  };
  newRulesCreated: number;
  processingTime: number;
  errorDetails?: Array<{ line: number; error: string }>;
}

interface ImportReportProps {
  report: ImportReport;
  onClose?: () => void;
}

export function ImportReportComponent({ report }: ImportReportProps) {
  const successRate = report.total > 0 ? ((report.imported / report.total) * 100).toFixed(1) : '0';
  const categorizationRate = report.imported > 0 
    ? (((report.categorized.byRules + report.categorized.byAI) / report.imported) * 100).toFixed(1)
    : '0';

  return (
    <div className="space-y-4">
      {/* Resumo Geral */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <FileCheck className="h-5 w-5 text-green-500" />
            Relatório de Importação
          </CardTitle>
          <CardDescription>
            Processado em {(report.processingTime / 1000).toFixed(2)}s
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            {/* Total */}
            <div className="text-center p-4 bg-slate-50 rounded-lg">
              <div className="text-3xl font-bold text-slate-900">{report.total}</div>
              <div className="text-xs text-slate-500 mt-1">Total Processadas</div>
            </div>

            {/* Importadas */}
            <div className="text-center p-4 bg-green-50 rounded-lg border border-green-200">
              <div className="text-3xl font-bold text-green-600">{report.imported}</div>
              <div className="text-xs text-green-600 mt-1">Importadas</div>
              <div className="text-[10px] text-green-500 mt-0.5">{successRate}% sucesso</div>
            </div>

            {/* Duplicatas */}
            <div className="text-center p-4 bg-amber-50 rounded-lg border border-amber-200">
              <div className="text-3xl font-bold text-amber-600">{report.duplicates}</div>
              <div className="text-xs text-amber-600 mt-1">Duplicatas</div>
            </div>

            {/* Erros */}
            <div className="text-center p-4 bg-red-50 rounded-lg border border-red-200">
              <div className="text-3xl font-bold text-red-600">{report.errors}</div>
              <div className="text-xs text-red-600 mt-1">Erros</div>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Categorização */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2 text-lg">
            <Sparkles className="h-4 w-4 text-purple-500" />
            Categorização Automática
          </CardTitle>
          <CardDescription>
            {categorizationRate}% das transações foram categorizadas automaticamente
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            {/* Por Regras */}
            <div className="flex items-center justify-between p-3 bg-blue-50 rounded-lg border border-blue-200">
              <div className="flex items-center gap-2">
                <CheckCircle2 className="h-4 w-4 text-blue-600" />
                <span className="text-sm font-medium text-blue-900">Por Regras</span>
              </div>
              <div className="text-right">
                <div className="text-lg font-bold text-blue-600">{report.categorized.byRules}</div>
                <div className="text-[10px] text-blue-500">100% confiança</div>
              </div>
            </div>

            {/* Por IA */}
            <div className="flex items-center justify-between p-3 bg-purple-50 rounded-lg border border-purple-200">
              <div className="flex items-center gap-2">
                <Sparkles className="h-4 w-4 text-purple-600" />
                <span className="text-sm font-medium text-purple-900">Por IA (Ollama)</span>
              </div>
              <div className="text-right">
                <div className="text-lg font-bold text-purple-600">{report.categorized.byAI}</div>
                <div className="text-[10px] text-purple-500">~70% confiança</div>
              </div>
            </div>

            {/* Manual */}
            <div className="flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-200">
              <div className="flex items-center gap-2">
                <AlertCircle className="h-4 w-4 text-slate-600" />
                <span className="text-sm font-medium text-slate-900">Categorização Manual</span>
              </div>
              <div className="text-right">
                <div className="text-lg font-bold text-slate-600">{report.categorized.manual}</div>
                <div className="text-[10px] text-slate-500">Requer atenção</div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Aprendizado */}
      {report.newRulesCreated > 0 && (
        <Alert className="bg-green-50 border-green-200">
          <TrendingUp className="h-4 w-4 text-green-600" />
          <AlertDescription className="text-green-900">
            <strong>{report.newRulesCreated} nova{report.newRulesCreated > 1 ? 's' : ''} regra{report.newRulesCreated > 1 ? 's' : ''}</strong> de categorização criada{report.newRulesCreated > 1 ? 's' : ''} automaticamente. 
            Futuras importações serão mais rápidas e precisas!
          </AlertDescription>
        </Alert>
      )}

      {/* Performance */}
      <div className="flex items-center gap-2 text-sm text-slate-600">
        <Clock className="h-4 w-4" />
        <span>
          Tempo de processamento: <strong>{(report.processingTime / 1000).toFixed(2)}s</strong>
          {report.total > 0 && (
            <span className="text-slate-500 ml-2">
              (~{((report.processingTime / report.total) / 1000).toFixed(3)}s por transação)
            </span>
          )}
        </span>
      </div>

      {/* Erros Detalhados */}
      {report.errorDetails && report.errorDetails.length > 0 && (
        <Card className="border-red-200">
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-lg text-red-600">
              <XCircle className="h-4 w-4" />
              Detalhes dos Erros
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-2 max-h-48 overflow-y-auto">
              {report.errorDetails.map((error, idx) => (
                <div key={idx} className="text-sm p-2 bg-red-50 rounded border border-red-100">
                  <span className="font-medium text-red-700">Linha {error.line}:</span>{' '}
                  <span className="text-red-600">{error.error}</span>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
