import * as React from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { AlertCircle, CheckCircle2, XCircle, Copy, ArrowRight } from 'lucide-react';
import { format } from 'date-fns';
import { ptBR } from 'date-fns/locale';

interface DuplicateMatch {
  existingTransaction: {
    id: string;
    date: string;
    description: string;
    amount: number;
    type: 'income' | 'expense';
  };
  similarity: number;
  matchFields: ('date' | 'amount' | 'description')[];
  matchReason: string;
}

interface DuplicateResolverProps {
  newTransaction: {
    date: string;
    description: string;
    amount: number;
    type: 'income' | 'expense';
  };
  duplicates: DuplicateMatch[];
  onAction: (action: 'new' | 'merge' | 'ignore', existingId?: string) => void;
}

export function DuplicateResolver({ newTransaction, duplicates, onAction }: DuplicateResolverProps) {
  const [selectedAction, setSelectedAction] = React.useState<'new' | 'merge' | 'ignore'>('new');
  const [selectedDuplicate, setSelectedDuplicate] = React.useState<string | null>(null);

  const bestMatch = duplicates[0];
  const highConfidence = bestMatch.similarity >= 0.9;

  const handleConfirm = () => {
    if (selectedAction === 'merge' && selectedDuplicate) {
      onAction('merge', selectedDuplicate);
    } else {
      onAction(selectedAction);
    }
  };

  const formatDate = (dateStr: string) => {
    try {
      return format(new Date(dateStr), 'dd/MM/yyyy', { locale: ptBR });
    } catch {
      return dateStr;
    }
  };

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL'
    }).format(amount);
  };

  return (
    <Card className="border-amber-200">
      <CardHeader>
        <CardTitle className="flex items-center gap-2 text-amber-700">
          <AlertCircle className="h-5 w-5" />
          Possível Duplicata Detectada
        </CardTitle>
        <CardDescription>
          Encontrada transação similar com {(bestMatch.similarity * 100).toFixed(0)}% de confiança
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        {/* Alerta de alta confiança */}
        {highConfidence && (
          <Alert className="bg-red-50 border-red-200">
            <AlertCircle className="h-4 w-4 text-red-600" />
            <AlertDescription className="text-red-900">
              Alta probabilidade de duplicata. Recomendamos ignorar esta transação.
            </AlertDescription>
          </Alert>
        )}

        {/* Comparação visual */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {/* Transação Nova */}
          <div className="p-4 bg-blue-50 rounded-lg border border-blue-200">
            <div className="flex items-center justify-between mb-3">
              <span className="text-sm font-semibold text-blue-900">Transação Nova</span>
              <Badge variant="outline" className="bg-blue-100 text-blue-700">
                Importação
              </Badge>
            </div>
            <div className="space-y-2 text-sm">
              <div className="flex justify-between">
                <span className="text-muted-foreground">Data:</span>
                <span className="font-medium">{formatDate(newTransaction.date)}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Descrição:</span>
                <span className="font-medium">{newTransaction.description}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Valor:</span>
                <span className={`font-medium ${newTransaction.type === 'income' ? 'text-green-600' : 'text-red-600'}`}>
                  {formatCurrency(newTransaction.amount)}
                </span>
              </div>
            </div>
          </div>

          {/* Transação Existente */}
          <div className="p-4 bg-purple-50 rounded-lg border border-purple-200">
            <div className="flex items-center justify-between mb-3">
              <span className="text-sm font-semibold text-purple-900">Transação Existente</span>
              <Badge variant="outline" className="bg-purple-100 text-purple-700">
                Banco de Dados
              </Badge>
            </div>
            <div className="space-y-2 text-sm">
              <div className="flex justify-between">
                <span className="text-muted-foreground">Data:</span>
                <span className="font-medium">{formatDate(bestMatch.existingTransaction.date)}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Descrição:</span>
                <span className="font-medium">{bestMatch.existingTransaction.description}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Valor:</span>
                <span className={`font-medium ${bestMatch.existingTransaction.type === 'income' ? 'text-green-600' : 'text-red-600'}`}>
                  {formatCurrency(bestMatch.existingTransaction.amount)}
                </span>
              </div>
            </div>
          </div>
        </div>

        {/* Detalhes do Match */}
        <div className="p-3 bg-muted rounded-lg">
          <div className="text-sm font-medium mb-2">Detalhes da Similaridade:</div>
          <div className="flex flex-wrap gap-2">
            {bestMatch.matchFields.map((field) => (
              <Badge key={field} variant="secondary">
                {field === 'date' ? 'Data' : field === 'amount' ? 'Valor' : 'Descrição'}
              </Badge>
            ))}
          </div>
          <div className="text-xs text-muted-foreground mt-2">
            {bestMatch.matchReason}
          </div>
        </div>

        {/* Ações */}
        <div className="space-y-3">
          <div className="text-sm font-medium">Como proceder?</div>
          
          <div className="space-y-2">
            <Button
              variant={selectedAction === 'new' ? 'default' : 'outline'}
              className="w-full justify-start"
              onClick={() => setSelectedAction('new')}
            >
              <Copy className="mr-2 h-4 w-4" />
              Importar como nova transação
            </Button>

            <Button
              variant={selectedAction === 'merge' ? 'default' : 'outline'}
              className="w-full justify-start"
              onClick={() => setSelectedAction('merge')}
              disabled={duplicates.length === 0}
            >
              <ArrowRight className="mr-2 h-4 w-4" />
              Mesclar com existente
            </Button>

            <Button
              variant={selectedAction === 'ignore' ? 'default' : 'outline'}
              className="w-full justify-start"
              onClick={() => setSelectedAction('ignore')}
            >
              <XCircle className="mr-2 h-4 w-4" />
              Ignorar (não importar)
            </Button>
          </div>

          {/* Seleção de transação para merge */}
          {selectedAction === 'merge' && duplicates.length > 1 && (
            <div className="space-y-2">
              <label className="text-sm font-medium">Selecione a transação para mesclar:</label>
              <div className="space-y-1">
                {duplicates.map((dup, idx) => (
                  <div
                    key={dup.existingTransaction.id}
                    className={`p-2 rounded cursor-pointer border ${
                      selectedDuplicate === dup.existingTransaction.id
                        ? 'bg-blue-50 border-blue-300'
                        : 'bg-muted border-transparent'
                    }`}
                    onClick={() => setSelectedDuplicate(dup.existingTransaction.id)}
                  >
                    <div className="text-sm">
                      {dup.existingTransaction.description} - {formatCurrency(dup.existingTransaction.amount)}
                      <span className="ml-2 text-xs text-muted-foreground">
                        ({(dup.similarity * 100).toFixed(0)}%)
                      </span>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Botão de confirmação */}
          <Button
            onClick={handleConfirm}
            disabled={selectedAction === 'merge' && !selectedDuplicate}
            className="w-full"
          >
            <CheckCircle2 className="mr-2 h-4 w-4" />
            {selectedAction === 'new' && 'Importar como Nova'}
            {selectedAction === 'merge' && 'Mesclar Transações'}
            {selectedAction === 'ignore' && 'Ignorar Transação'}
          </Button>
        </div>
      </CardContent>
    </Card>
  );
}
