import * as React from 'react';
import { supabase } from '@/db/client';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { CheckCircle2, Sparkles } from 'lucide-react';

interface CategorizedTransaction {
  id: string;
  description: string;
  suggestedCategory: string;
  suggestedCategoryId?: string;
  selectedCategoryId?: string;
  type: 'income' | 'expense';
  confidence?: number;
  matchedByRule?: boolean;
}

interface CategoryCorrectionProps {
  transactions: CategorizedTransaction[];
  categories: Array<{ id: string; name: string; type: string }>;
  onCorrected: (transactionId: string, categoryId: string) => void;
  onAllCorrected: () => void;
}

export function CategoryCorrection({
  transactions,
  categories,
  onCorrected,
  onAllCorrected
}: CategoryCorrectionProps) {
  const [corrections, setCorrections] = React.useState<Record<string, string>>({});
  const [isSaving, setIsSaving] = React.useState(false);
  const [savedCount, setSavedCount] = React.useState(0);
  const [newRulesCreated, setNewRulesCreated] = React.useState(0);

  const handleCategoryChange = (transactionId: string, categoryId: string) => {
    setCorrections(prev => ({ ...prev, [transactionId]: categoryId }));
  };

  const handleSaveCorrections = async () => {
    setIsSaving(true);
    let rulesCreated = 0;
    let saved = 0;

    try {
      const { data: { user } } = await supabase.auth.getUser();
      if (!user) return;

      for (const [transactionId, categoryId] of Object.entries(corrections)) {
        const transaction = transactions.find(t => t.id === transactionId);
        if (!transaction) continue;

        // Atualizar categoria da transação
        const { error: updateError } = await supabase
          .from('transactions')
          .update({ category_id: categoryId })
          .eq('id', transactionId);

        if (updateError) {
          console.error('Erro ao atualizar transação:', updateError);
          continue;
        }

        // Salvar correção para rastreamento de métricas
        const category = categories.find(c => c.id === categoryId);
        await supabase.from('category_corrections').insert({
          user_id: user.id,
          transaction_id: transactionId,
          original_category_id: transaction.suggestedCategoryId,
          corrected_category_id: categoryId,
          original_category: transaction.suggestedCategory,
          corrected_category: category?.name || '',
          description: transaction.description
        });

        // Criar nova regra se não existir
        const { data: existingRule } = await supabase
          .from('category_rules')
          .select('id')
          .eq('user_id', user.id)
          .ilike('keyword', extractKeyword(transaction.description))
          .single();

        if (!existingRule && category) {
          await supabase.from('category_rules').insert({
            user_id: user.id,
            company_id: null, // Regra global
            keyword: extractKeyword(transaction.description),
            category_name: category.name,
            type: transaction.type,
            match_type: 'contains'
          });
          rulesCreated++;
        }

        saved++;
        onCorrected(transactionId, categoryId);
      }

      setSavedCount(saved);
      setNewRulesCreated(rulesCreated);
      setCorrections({});
      
      if (saved > 0) {
        onAllCorrected();
      }
    } catch (error) {
      console.error('Erro ao salvar correções:', error);
    } finally {
      setIsSaving(false);
    }
  };

  const extractKeyword = (description: string): string => {
    const stopWords = new Set([
      'de', 'da', 'do', 'das', 'dos', 'em', 'na', 'no', 'nas', 'nos',
      'para', 'pelo', 'pela', 'pelos', 'pelas', 'com', 'sem', 'sob',
      'pix', 'ted', 'doc', 'debito', 'credito', 'débito', 'crédito',
      'transferencia', 'transferência', 'enviada', 'enviado', 'recebida', 'recebido',
      'pagamento', 'compra', 'saque', 'deposito', 'depósito',
      'agencia', 'agência', 'conta', 'banco', 'bco'
    ]);

    const words = description
      .toUpperCase()
      .replace(/[^A-ZÁÉÍÓÚÃÕÇÂÊÎ\s]/g, '')
      .split(/\s+/);

    for (const word of words) {
      const normalized = word.toLowerCase();
      if (normalized.length >= 3 && !stopWords.has(normalized)) {
        return word;
      }
    }

    return description.substring(0, 20).toUpperCase();
  };

  const filteredTransactions = transactions.filter(
    t => t.matchedByRule === false && t.confidence && t.confidence < 0.9
  );

  if (filteredTransactions.length === 0) {
    return null;
  }

  return (
    <div className="space-y-4">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h3 className="text-lg font-semibold flex items-center gap-2">
            <Sparkles className="h-5 w-5 text-purple-500" />
            Correção de Categorização
          </h3>
          <p className="text-sm text-muted-foreground">
            {filteredTransactions.length} transações com baixa confiança da IA
          </p>
        </div>
        <Button
          onClick={handleSaveCorrections}
          disabled={Object.keys(corrections).length === 0 || isSaving}
        >
          {isSaving ? (
            <>Salvando...</>
          ) : (
            <>
              <CheckCircle2 className="mr-2 h-4 w-4" />
              Salvar {Object.keys(corrections).length} Correção{Object.keys(corrections).length !== 1 ? 'ões' : ''}
            </>
          )}
        </Button>
      </div>

      {/* Success Message */}
      {savedCount > 0 && (
        <Alert className="bg-green-50 border-green-200">
          <CheckCircle2 className="h-4 w-4 text-green-600" />
          <AlertDescription className="text-green-900">
            {savedCount} correção{savedCount !== 1 ? 'ões' : ''} salva{savedCount !== 1 ? 's' : ''} com sucesso!
            {newRulesCreated > 0 && (
              <span className="ml-2">
                {newRulesCreated} nova{newRulesCreated !== 1 ? 's' : ''} regra{newRulesCreated !== 1 ? 's' : ''} criada{newRulesCreated !== 1 ? 's' : ''} automaticamente.
              </span>
            )}
          </AlertDescription>
        </Alert>
      )}

      {/* Transactions List */}
      <div className="space-y-3 max-h-96 overflow-y-auto">
        {filteredTransactions.map((transaction) => (
          <div
            key={transaction.id}
            className="p-4 border rounded-lg bg-card space-y-3"
          >
            <div className="flex items-start justify-between">
              <div className="flex-1">
                <p className="font-medium">{transaction.description}</p>
                <p className="text-sm text-muted-foreground">
                  Sugerido: <span className="text-purple-600">{transaction.suggestedCategory}</span>
                  {transaction.confidence && (
                    <span className="ml-2 text-xs text-muted-foreground">
                      ({(transaction.confidence * 100).toFixed(0)}% confiança)
                    </span>
                  )}
                </p>
              </div>
              <div className="flex items-center gap-2 text-sm">
                {transaction.type === 'income' ? (
                  <span className="text-green-600">Receita</span>
                ) : (
                  <span className="text-red-600">Despesa</span>
                )}
              </div>
            </div>

            <div className="flex items-center gap-4">
              <div className="flex-1">
                <Label htmlFor={`category-${transaction.id}`} className="text-sm">
                  Categoria Correta
                </Label>
                <Select
                  value={corrections[transaction.id] || transaction.selectedCategoryId || transaction.suggestedCategoryId}
                  onValueChange={(value) => handleCategoryChange(transaction.id, value)}
                >
                  <SelectTrigger id={`category-${transaction.id}`}>
                    <SelectValue placeholder="Selecione..." />
                  </SelectTrigger>
                  <SelectContent>
                    {categories
                      .filter(c => c.type === transaction.type)
                      .map((category) => (
                        <SelectItem key={category.id} value={category.id}>
                          {category.name}
                        </SelectItem>
                      ))}
                  </SelectContent>
                </Select>
              </div>

              {corrections[transaction.id] && (
                <CheckCircle2 className="h-5 w-5 text-green-500 flex-shrink-0 mt-5" />
              )}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
