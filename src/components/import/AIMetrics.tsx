import * as React from 'react';
import { requireCurrentUser, supabase } from '@/db/client';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { TrendingUp, AlertCircle, CheckCircle2, Sparkles, BarChart3 } from 'lucide-react';

interface AIMetricsProps {
  userId: string;
}

interface CategoryAccuracy {
  categoryName: string;
  total: number;
  correct: number;
  accuracy: number;
}

interface Correction {
  id: string;
  description: string;
  originalCategory: string;
  correctedCategory: string;
  createdAt: string;
}

export function AIMetrics({ userId }: AIMetricsProps) {
  const [loading, setLoading] = React.useState(true);
  const [categoryAccuracy, setCategoryAccuracy] = React.useState<CategoryAccuracy[]>([]);
  const [recentCorrections, setRecentCorrections] = React.useState<Correction[]>([]);
  const [overallAccuracy, setOverallAccuracy] = React.useState(0);
  const [suggestedRules, setSuggestedRules] = React.useState<Array<{ keyword: string; category: string; count: number }>>([]);

  React.useEffect(() => {
    loadMetrics();
  }, [userId]);

  const loadMetrics = async () => {
    setLoading(true);
    try {
      const user = await requireCurrentUser();

      // Buscar transações categorizadas por IA
      const { data: transactions } = await supabase
        .from('transactions')
        .select('*, categories(name, type)')
        .eq('user_id', user.id)
        .contains('tags', ['importado']);

      if (!transactions) return;

      // Buscar correções recentes
      const { data: corrections } = await supabase
        .from('category_corrections')
        .select('*')
        .eq('user_id', user.id)
        .order('created_at', { ascending: false })
        .limit(10);

      // Calcular acurácia por categoria
      const categoryMap = new Map<string, { total: number; correct: number }>();

      transactions.forEach((tx: any) => {
        const categoryName = tx.categories?.name || 'Outros';
        if (!categoryMap.has(categoryName)) {
          categoryMap.set(categoryName, { total: 0, correct: 0 });
        }
        const stats = categoryMap.get(categoryName)!;
        stats.total++;

        // Se não foi corrigido, consideramos correto
        const wasCorrected = corrections?.some((c: any) => c.transaction_id === tx.id);
        if (!wasCorrected) {
          stats.correct++;
        }
      });

      const accuracyData: CategoryAccuracy[] = [];
      categoryMap.forEach((stats, categoryName) => {
        accuracyData.push({
          categoryName,
          total: stats.total,
          correct: stats.correct,
          accuracy: stats.total > 0 ? (stats.correct / stats.total) * 100 : 0
        });
      });

      // Ordenar por acurácia (menor primeiro)
      accuracyData.sort((a, b) => a.accuracy - b.accuracy);

      // Calcular acurácia geral
      const totalCorrect = accuracyData.reduce((sum, cat) => sum + cat.correct, 0);
      const totalTransactions = accuracyData.reduce((sum, cat) => sum + cat.total, 0);
      const overall = totalTransactions > 0 ? (totalCorrect / totalTransactions) * 100 : 0;

      // Gerar sugestões de regras baseadas em correções
      const ruleSuggestions = new Map<string, { category: string; count: number }>();
      corrections?.forEach((correction: any) => {
        const keyword = extractKeyword(correction.description);
        if (keyword && keyword.length >= 3) {
          const key = keyword.toUpperCase();
          if (!ruleSuggestions.has(key)) {
            ruleSuggestions.set(key, { category: correction.corrected_category, count: 0 });
          }
          ruleSuggestions.get(key)!.count++;
        }
      });

      const suggestedRulesArray = Array.from(ruleSuggestions.entries())
        .map(([keyword, data]) => ({ keyword, category: data.category, count: data.count }))
        .filter(r => r.count >= 2) // Pelo menos 2 correções iguais
        .sort((a, b) => b.count - a.count)
        .slice(0, 5);

      setCategoryAccuracy(accuracyData);
      setRecentCorrections(corrections || []);
      setOverallAccuracy(overall);
      setSuggestedRules(suggestedRulesArray);
    } catch (error) {
      console.error('Erro ao carregar métricas:', error);
    } finally {
      setLoading(false);
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

  if (loading) {
    return (
      <Card>
        <CardContent className="py-8">
          <div className="flex items-center justify-center">
            <div className="text-sm text-muted-foreground">Carregando métricas...</div>
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <div className="space-y-4">
      {/* Acurácia Geral */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <BarChart3 className="h-5 w-5 text-blue-500" />
            Acurácia Geral da IA
          </CardTitle>
          <CardDescription>
            Taxa de acerto das categorizações automáticas
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            <div className="flex items-center justify-between">
              <span className="text-2xl font-bold">
                {overallAccuracy.toFixed(1)}%
              </span>
              <div className="flex items-center gap-1 text-sm">
                {overallAccuracy >= 80 ? (
                  <CheckCircle2 className="h-4 w-4 text-green-500" />
                ) : overallAccuracy >= 60 ? (
                  <AlertCircle className="h-4 w-4 text-amber-500" />
                ) : (
                  <AlertCircle className="h-4 w-4 text-red-500" />
                )}
                <span className={
                  overallAccuracy >= 80 ? 'text-green-600' :
                  overallAccuracy >= 60 ? 'text-amber-600' : 'text-red-600'
                }>
                  {overallAccuracy >= 80 ? 'Excelente' :
                   overallAccuracy >= 60 ? 'Boa' : 'Precisa melhorar'}
                </span>
              </div>
            </div>
            <Progress value={overallAccuracy} className="h-2" />
          </div>
        </CardContent>
      </Card>

      {/* Acurácia por Categoria */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <TrendingUp className="h-5 w-5 text-purple-500" />
            Acurácia por Categoria
          </CardTitle>
          <CardDescription>
            Desempenho da IA em cada categoria
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            {categoryAccuracy.slice(0, 5).map((cat) => (
              <div key={cat.categoryName} className="space-y-2">
                <div className="flex items-center justify-between text-sm">
                  <span className="font-medium">{cat.categoryName}</span>
                  <span className="text-muted-foreground">
                    {cat.correct}/{cat.total} ({cat.accuracy.toFixed(0)}%)
                  </span>
                </div>
                <Progress value={cat.accuracy} className="h-2" />
              </div>
            ))}
          </div>
        </CardContent>
      </Card>

      {/* Sugestões de Novas Regras */}
      {suggestedRules.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Sparkles className="h-5 w-5 text-amber-500" />
              Sugestões de Novas Regras
            </CardTitle>
            <CardDescription>
              Crie regras automáticas baseadas em correções frequentes
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-2">
              {suggestedRules.map((rule, idx) => (
                <Alert key={idx} className="bg-amber-50 border-amber-200">
                  <Sparkles className="h-4 w-4 text-amber-600" />
                  <AlertDescription className="text-amber-900 text-sm">
                    <strong>"{rule.keyword}"</strong> → {rule.category} ({rule.count}x)
                  </AlertDescription>
                </Alert>
              ))}
            </div>
          </CardContent>
        </Card>
      )}

      {/* Correções Recentes */}
      {recentCorrections.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <AlertCircle className="h-5 w-5 text-red-500" />
              Correções Recentes
            </CardTitle>
            <CardDescription>
              Últimas 10 correções feitas
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-2 max-h-64 overflow-y-auto">
              {recentCorrections.map((correction) => (
                <div key={correction.id} className="p-3 bg-muted rounded text-sm">
                  <p className="font-medium">{correction.description}</p>
                  <p className="text-muted-foreground text-xs mt-1">
                    {correction.originalCategory} → {correction.correctedCategory}
                  </p>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
