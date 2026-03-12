import * as React from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Progress } from '@/components/ui/progress';
import { AlertCircle, CheckCircle2, XCircle, Eye, EyeOff, Zap } from 'lucide-react';
import { format } from 'date-fns';
import { ptBR } from 'date-fns/locale';
import { ScrollArea } from '@/components/ui/scroll-area';

interface DuplicateMatch {
  newTransaction: {
    date: string;
    description: string;
    amount: number;
    type: 'income' | 'expense';
  };
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

interface BatchDuplicateReviewProps {
  duplicates: DuplicateMatch[];
  onAction: (action: 'import' | 'ignore' | 'review', selections?: Record<number, string>) => void;
  isLoading?: boolean;
}

export function BatchDuplicateReview({ duplicates, onAction, isLoading = false }: BatchDuplicateReviewProps) {
  const [selectedAction, setSelectedAction] = React.useState<'import' | 'ignore' | 'review'>('review');
  const [selections, setSelections] = React.useState<Record<number, string>>({});
  const [showDetails, setShowDetails] = React.useState<Record<number, boolean>>({});

  // Classificar duplicatas por nível de confiança
  const highConfidence = duplicates.filter(d => d.similarity >= 0.95);
  const mediumConfidence = duplicates.filter(d => d.similarity >= 0.75 && d.similarity < 0.95);
  const lowConfidence = duplicates.filter(d => d.similarity < 0.75);

  const handleSelectAll = (action: string) => {
    const newSelections: Record<number, string> = {};
    duplicates.forEach((_, index) => {
      newSelections[index] = action;
    });
    setSelections(newSelections);
  };

  const handleSelectionChange = (index: number, action: string) => {
    setSelections(prev => ({
      ...prev,
      [index]: action
    }));
  };

  const handleConfirm = () => {
    if (selectedAction === 'import') {
      onAction('import');
    } else if (selectedAction === 'ignore') {
      onAction('ignore');
    } else {
      onAction('review', selections);
    }
  };

  const getConfidenceColor = (similarity: number) => {
    if (similarity >= 0.95) return 'destructive';
    if (similarity >= 0.75) return 'secondary';
    return 'outline';
  };

  const getConfidenceLabel = (similarity: number) => {
    if (similarity >= 0.95) return 'Alta';
    if (similarity >= 0.75) return 'Média';
    return 'Baixa';
  };

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL'
    }).format(amount);
  };

  return (
    <div className="space-y-6">
      {/* Resumo */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <AlertCircle className="h-5 w-5 text-orange-500" />
            Duplicatas Detectadas
          </CardTitle>
          <CardDescription>
            Encontramos {duplicates.length} transações possivelmente duplicadas. Revise e decida o que fazer.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-3 gap-4 mb-4">
            <div className="text-center">
              <div className="text-2xl font-bold text-red-600">{highConfidence.length}</div>
              <div className="text-sm text-muted-foreground">Alta confiança</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-yellow-600">{mediumConfidence.length}</div>
              <div className="text-sm text-muted-foreground">Média confiança</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-green-600">{lowConfidence.length}</div>
              <div className="text-sm text-muted-foreground">Baixa confiança</div>
            </div>
          </div>

          {/* Ações rápidas */}
          <div className="flex gap-2 mb-4">
            <Button
              variant="outline"
              size="sm"
              onClick={() => setSelectedAction('import')}
              className={selectedAction === 'import' ? 'bg-green-50 border-green-200' : ''}
            >
              <Zap className="h-4 w-4 mr-1" />
              Importar Todas
            </Button>
            <Button
              variant="outline"
              size="sm"
              onClick={() => setSelectedAction('ignore')}
              className={selectedAction === 'ignore' ? 'bg-red-50 border-red-200' : ''}
            >
              <XCircle className="h-4 w-4 mr-1" />
              Ignorar Todas
            </Button>
            <Button
              variant="outline"
              size="sm"
              onClick={() => setSelectedAction('review')}
              className={selectedAction === 'review' ? 'bg-blue-50 border-blue-200' : ''}
            >
              <Eye className="h-4 w-4 mr-1" />
              Revisar Individualmente
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Lista de duplicatas */}
      {selectedAction === 'review' && (
        <Card>
          <CardHeader>
            <CardTitle>Revisão Individual</CardTitle>
            <CardDescription>
              Selecione a ação para cada transação duplicada
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-2 mb-4">
              <Button
                variant="outline"
                size="sm"
                onClick={() => handleSelectAll('import')}
              >
                Marcar todas para importar
              </Button>
              <Button
                variant="outline"
                size="sm"
                onClick={() => handleSelectAll('ignore')}
              >
                Marcar todas para ignorar
              </Button>
            </div>

            <ScrollArea className="h-[400px]">
              <div className="space-y-4">
                {duplicates.map((duplicate, index) => (
                  <Card key={index} className="p-4">
                    <div className="flex items-start justify-between mb-2">
                      <div className="flex items-center gap-2">
                        <Badge variant={getConfidenceColor(duplicate.similarity)}>
                          {getConfidenceLabel(duplicate.similarity)}
                        </Badge>
                        <span className="text-sm text-muted-foreground">
                          {(duplicate.similarity * 100).toFixed(0)}% similar
                        </span>
                      </div>
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => setShowDetails(prev => ({ ...prev, [index]: !prev[index] }))}
                      >
                        {showDetails[index] ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                      </Button>
                    </div>

                    <div className="grid grid-cols-2 gap-4 mb-3">
                      <div>
                        <div className="text-sm font-medium text-green-600">Nova Transação</div>
                        <div className="text-sm">{duplicate.newTransaction.description}</div>
                        <div className="text-sm font-medium">{formatCurrency(duplicate.newTransaction.amount)}</div>
                        <div className="text-xs text-muted-foreground">
                          {format(new Date(duplicate.newTransaction.date), 'dd/MM/yyyy', { locale: ptBR })}
                        </div>
                      </div>
                      <div>
                        <div className="text-sm font-medium text-red-600">Existente</div>
                        <div className="text-sm">{duplicate.existingTransaction.description}</div>
                        <div className="text-sm font-medium">{formatCurrency(duplicate.existingTransaction.amount)}</div>
                        <div className="text-xs text-muted-foreground">
                          {format(new Date(duplicate.existingTransaction.date), 'dd/MM/yyyy', { locale: ptBR })}
                        </div>
                      </div>
                    </div>

                    {showDetails[index] && (
                      <Alert className="mb-3">
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription>
                          {duplicate.matchReason}
                        </AlertDescription>
                      </Alert>
                    )}

                    <div className="flex gap-2">
                      <Checkbox
                        id={`import-${index}`}
                        checked={selections[index] === 'import'}
                        onCheckedChange={() => handleSelectionChange(index, 'import')}
                      />
                      <label htmlFor={`import-${index}`} className="text-sm">
                        Importar mesmo assim
                      </label>
                      <Checkbox
                        id={`ignore-${index}`}
                        checked={selections[index] === 'ignore'}
                        onCheckedChange={() => handleSelectionChange(index, 'ignore')}
                      />
                      <label htmlFor={`ignore-${index}`} className="text-sm">
                        Ignorar
                      </label>
                    </div>
                  </Card>
                ))}
              </div>
            </ScrollArea>
          </CardContent>
        </Card>
      )}

      {/* Botões de confirmação */}
      <div className="flex justify-end gap-2">
        <Button variant="outline" onClick={() => onAction('ignore')}>
          Cancelar Importação
        </Button>
        <Button onClick={handleConfirm} disabled={isLoading}>
          {isLoading ? 'Processando...' : 'Confirmar'}
        </Button>
      </div>
    </div>
  );
}
