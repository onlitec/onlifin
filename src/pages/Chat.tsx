import * as React from 'react';
import { useNavigate } from 'react-router-dom';
import { requireCurrentUser, supabase } from '@/db/client';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import { ScrollArea } from '@/components/ui/scroll-area';
import { useToast } from '@/hooks/use-toast';
import { parseOFX, isValidOFX } from '@/utils/ofxParser';
import { chatWithAssistant, categorizeTransactionsWithAI, getDegradedResponse, getDestructiveActionGuardrail } from '@/services/ollamaService';
import { buildLocalFinancialResponse, loadFinancialContext, formatFinancialContextForPrompt, FinancialContext } from '@/services/financialContext';
import { useFinanceScope } from '@/hooks/useFinanceScope';
import { Bot, User, Send, Loader2, Paperclip, FileText, X, RotateCcw, XCircle, RefreshCw, Wallet, Plus, CreditCard, ArrowRight } from 'lucide-react';

interface Message {
  role: 'user' | 'assistant';
  content: string;
  timestamp: Date;
}

const initialMessage: Message = {
  role: 'assistant',
  content: `🤖 Olá! Sou o **Onlifin AI**, seu consultor financeiro pessoal.

Tenho acesso completo aos seus dados financeiros e posso ajudar com:

📊 **Análise Financeira**
• Analisar suas receitas e despesas
• Identificar padrões de gastos
• Calcular sua taxa de poupança

📈 **Previsões**
• Projetar seu saldo futuro
• Alertar sobre problemas de caixa
• Definir metas de economia

💡 **Consultoria**
• Dar dicas personalizadas
• Sugerir cortes de gastos
• Orientar investimentos

📁 **Importação**
• Analisar extratos bancários
• Categorizar transações automaticamente

Como posso ajudar você hoje?`,
  timestamp: new Date()
};

export default function Chat() {
  const navigate = useNavigate();
  const [messages, setMessages] = React.useState<Message[]>([initialMessage]);
  const [input, setInput] = React.useState('');
  const [isLoading, setIsLoading] = React.useState(false);
  const [selectedFile, setSelectedFile] = React.useState<File | null>(null);
  const [fileContent, setFileContent] = React.useState('');
  const [financialContext, setFinancialContext] = React.useState<FinancialContext | null>(null);
  const [isLoadingContext, setIsLoadingContext] = React.useState(true);
  const scrollRef = React.useRef<HTMLDivElement>(null);
  const fileInputRef = React.useRef<HTMLInputElement>(null);
  const { toast } = useToast();
  const { companyId, personId, isPF, isPJ } = useFinanceScope();
  const prefix = isPJ && companyId ? `/pj/${companyId}` : '/pf';

  // Carregar contexto financeiro ao montar
  React.useEffect(() => {
    loadUserFinancialContext();
  }, [companyId, personId, isPF, isPJ]);

  const loadUserFinancialContext = async () => {
    try {
      setIsLoadingContext(true);
      const user = await requireCurrentUser();
      const ctx = await loadFinancialContext(user.id, {
        mode: isPF ? 'PF' : isPJ ? 'PJ' : 'GERAL',
        companyId,
        personId
      });
      setFinancialContext(ctx);
    } catch (error) {
      console.error('Erro ao carregar contexto financeiro:', error);
    } finally {
      setIsLoadingContext(false);
    }
  };

  // Reiniciar conversa
  const handleNewConversation = () => {
    setMessages([{ ...initialMessage, timestamp: new Date() }]);
    setInput('');
    setSelectedFile(null);
    setFileContent('');
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
    toast({
      title: 'Nova conversa',
      description: 'O histórico foi limpo. Como posso ajudar?',
    });
  };

  // Fechar chat
  const handleCloseChat = () => {
    navigate(prefix);
  };

  React.useEffect(() => {
    if (scrollRef.current) {
      scrollRef.current.scrollTop = scrollRef.current.scrollHeight;
    }
  }, [messages]);

  const handleFileSelect = async (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file) return;

    // Check file type
    const validTypes = ['.csv', '.txt', '.ofx'];
    const fileExtension = '.' + file.name.split('.').pop()?.toLowerCase();

    if (!validTypes.includes(fileExtension)) {
      toast({
        title: 'Arquivo inválido',
        description: 'Por favor, envie um arquivo CSV, TXT ou OFX',
        variant: 'destructive',
      });
      return;
    }

    // Check file size (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
      toast({
        title: 'Arquivo muito grande',
        description: 'O arquivo deve ter no máximo 5MB',
        variant: 'destructive',
      });
      return;
    }

    try {
      const text = await file.text();
      setSelectedFile(file);
      setFileContent(text);
      toast({
        title: 'Arquivo carregado',
        description: `${file.name} pronto para envio`,
      });
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao ler arquivo',
        variant: 'destructive',
      });
    }
  };

  const removeFile = () => {
    setSelectedFile(null);
    setFileContent('');
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  const parseCSV = (content: string) => {
    const lines = content.trim().split('\n');
    const transactions: any[] = [];
    const startIndex = lines[0].toLowerCase().includes('data') ? 1 : 0;

    for (let i = startIndex; i < lines.length; i++) {
      const line = lines[i].trim();
      if (!line) continue;

      const fields: string[] = [];
      let currentField = '';
      let inQuotes = false;

      for (let j = 0; j < line.length; j++) {
        const char = line[j];
        if (char === '"') {
          inQuotes = !inQuotes;
        } else if (char === ',' && !inQuotes) {
          fields.push(currentField.trim());
          currentField = '';
        } else {
          currentField += char;
        }
      }
      fields.push(currentField.trim());

      if (fields.length >= 3) {
        const [dateStr, description, amountStr] = fields;
        const amount = Math.abs(parseFloat(amountStr.replace(/[^\d.,-]/g, '').replace(',', '.')));

        if (!isNaN(amount) && amount > 0) {
          const isNegative = amountStr.includes('-');
          const type = isNegative ? 'expense' : 'income';

          transactions.push({
            date: dateStr,
            description: description.replace(/^"|"$/g, ''),
            amount,
            type,
            merchant: description.replace(/^"|"$/g, '').split(' ')[0],
          });
        }
      }
    }

    return transactions;
  };

  const handleSend = async () => {
    if (!input.trim() && !selectedFile) return;

    const userMessage = selectedFile
      ? `${input || 'Analise este extrato bancário e categorize as transações'}\n\n[Arquivo anexado: ${selectedFile.name}]`
      : input;

    const newUserMessage: Message = {
      role: 'user',
      content: userMessage,
      timestamp: new Date()
    };

    setMessages(prev => [...prev, newUserMessage]);
    setInput('');
    setIsLoading(true);

    try {
      const destructiveGuardrail = getDestructiveActionGuardrail(userMessage);
      if (destructiveGuardrail) {
        const guardrailMessage: Message = {
          role: 'assistant',
          content: destructiveGuardrail,
          timestamp: new Date()
        };
        setMessages(prev => [...prev, guardrailMessage]);
        return;
      }

      const user = await requireCurrentUser();

      // If there's a file, process it for categorization
      if (selectedFile && fileContent) {
        // Detecta e faz parse baseado no formato
        let parsed;

        if (isValidOFX(fileContent)) {
          console.log('Arquivo OFX detectado no chat');
          parsed = parseOFX(fileContent);
        } else {
          parsed = parseCSV(fileContent);
        }

        if (parsed.length === 0) {
          throw new Error('Nenhuma transação encontrada no arquivo');
        }

        // Get existing categories
        const { data: categories } = await supabase
          .from('categories')
          .select('*')
          .eq('user_id', user.id);

        // Send to AI for categorization using Ollama
        let result;
        try {
          result = await categorizeTransactionsWithAI(parsed, categories || []);
        } catch (aiError: any) {
          console.error('Erro ao categorizar com IA:', aiError);
          // Fallback simples se IA falhar
          result = {
            categorizedTransactions: parsed.map((t: any) => ({
              ...t,
              suggestedCategory: t.type === 'income' ? 'Outros Receitas' : 'Outros',
              confidence: 0.5
            })),
            newCategories: []
          };
        }

        const categorized = result.categorizedTransactions || [];
        const newCategories = result.newCategories || [];

        // Format response
        let response = `✅ Análise concluída!\n\n`;
        response += `📊 **${parsed.length} transações** encontradas no extrato\n\n`;

        if (newCategories.length > 0) {
          response += `💡 **Novas categorias sugeridas:**\n`;
          newCategories.forEach((cat: any) => {
            response += `• ${cat.name} (${cat.type === 'income' ? 'Receita' : 'Despesa'})\n`;
          });
          response += `\n`;
        }

        response += `📋 **Resumo das transações:**\n\n`;

        // Group by category
        const byCategory: any = {};
        categorized.forEach((t: any) => {
          const cat = t.suggestedCategory;
          if (!byCategory[cat]) {
            byCategory[cat] = { count: 0, total: 0, type: t.type };
          }
          byCategory[cat].count++;
          byCategory[cat].total += t.amount;
        });

        Object.entries(byCategory).forEach(([cat, data]: [string, any]) => {
          const emoji = data.type === 'income' ? '💰' : '💸';
          response += `${emoji} **${cat}**: ${data.count} transações - R$ ${data.total.toFixed(2)}\n`;
        });

        response += `\n🔗 Para importar essas transações, acesse a página [Importar Extrato](/import-statements)`;

        const assistantMessage: Message = {
          role: 'assistant',
          content: response,
          timestamp: new Date()
        };

        setMessages(prev => [...prev, assistantMessage]);
        removeFile();
      } else {
        // Regular chat message using Ollama
        let responseText: string;
        try {
          const localResponse = buildLocalFinancialResponse(input, financialContext);

          if (localResponse) {
            responseText = localResponse;
          } else {
            // Build conversation history from messages
            const conversationHistory = messages.map(m => ({
              role: m.role,
              content: m.content
            }));

            // Format financial context for the AI
            const contextText = financialContext
              ? formatFinancialContextForPrompt(financialContext)
              : undefined;

            responseText = await chatWithAssistant(input, conversationHistory, contextText);
            if (!responseText.trim()) {
              responseText = getDegradedResponse(input);
            }
          }
        } catch {
          responseText = getDegradedResponse(input);
        }

        const assistantMessage: Message = {
          role: 'assistant',
          content: responseText || getDegradedResponse(input),
          timestamp: new Date()
        };

        setMessages(prev => [...prev, assistantMessage]);
      }
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao enviar mensagem',
        variant: 'destructive',
      });

      const errorMessage: Message = {
        role: 'assistant',
        content: `❌ Erro: ${error.message || 'Não foi possível processar sua solicitação'}`,
        timestamp: new Date()
      };

      setMessages(prev => [...prev, errorMessage]);
    } finally {
      setIsLoading(false);
    }
  };

  const handleKeyPress = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleSend();
    }
  };

  const hasAccounts = (financialContext?.accounts.total ?? 0) > 0;
  const hasTransactions = (financialContext?.transactions.count ?? 0) > 0;
  const hasCards = (financialContext?.cards.total ?? 0) > 0;
  const quickPrompts = hasTransactions
    ? [
      'Resuma minhas despesas do mês e diga o que mais pesou no orçamento.',
      'Quais próximos passos devo seguir para melhorar meu caixa?',
      'Analise minhas contas a pagar e destaque riscos dos próximos dias.',
    ]
    : [];

  return (
    <div className="w-full max-w-[1600px] mx-auto p-6 h-[calc(100vh-8rem)]">
      <Card className="h-full flex flex-col">
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <div className="flex items-center gap-3">
            <CardTitle className="flex items-center gap-2">
              <Bot className="h-6 w-6" />
              Onlifin AI
            </CardTitle>
            {isLoadingContext ? (
              <span className="text-xs text-muted-foreground flex items-center gap-1">
                <Loader2 className="h-3 w-3 animate-spin" />
                Carregando dados...
              </span>
            ) : financialContext ? (
              <span className="text-xs text-green-500 flex items-center gap-1">
                ● Dados carregados
              </span>
            ) : (
              <span className="text-xs text-yellow-500">
                ⚠ Sem dados financeiros
              </span>
            )}
          </div>
          <div className="flex items-center gap-2">
            <Button
              variant="outline"
              size="sm"
              onClick={loadUserFinancialContext}
              disabled={isLoading || isLoadingContext}
              title="Atualizar dados financeiros"
            >
              <RefreshCw className={`h-4 w-4 ${isLoadingContext ? 'animate-spin' : ''}`} />
            </Button>
            <Button
              variant="outline"
              size="sm"
              onClick={handleNewConversation}
              disabled={isLoading}
              title="Nova conversa"
            >
              <RotateCcw className="h-4 w-4 mr-1" />
              Nova
            </Button>
            <Button
              variant="ghost"
              size="sm"
              onClick={handleCloseChat}
              title="Fechar chat"
            >
              <XCircle className="h-5 w-5" />
            </Button>
          </div>
        </CardHeader>
        <CardContent className="flex-1 flex flex-col gap-4 overflow-hidden">
          {!isLoadingContext && (
            <Card className="border-dashed">
              <CardContent className="flex flex-col gap-3 py-4">
                {!hasAccounts ? (
                  <>
                    <div className="space-y-1">
                      <p className="text-sm font-bold text-slate-900">Cadastre a primeira conta para destravar o assistente</p>
                      <p className="text-sm text-muted-foreground">
                        Sem contas ou movimentações, a IA responde de forma genérica e perde contexto do seu financeiro.
                      </p>
                    </div>
                    <div className="flex flex-col gap-2 sm:flex-row">
                      <Button onClick={() => navigate(`${prefix}/accounts?onboarding=account`)}>
                        <Wallet className="mr-2 h-4 w-4" />
                        Criar Primeira Conta
                      </Button>
                      <Button variant="outline" onClick={() => navigate(prefix)}>
                        <ArrowRight className="mr-2 h-4 w-4" />
                        Ir para o Dashboard
                      </Button>
                    </div>
                  </>
                ) : !hasTransactions ? (
                  <>
                    <div className="space-y-1">
                      <p className="text-sm font-bold text-slate-900">Registre movimentações para respostas mais úteis</p>
                      <p className="text-sm text-muted-foreground">
                        Com transações no histórico, a IA consegue analisar padrões, caixa, categorias e próximos riscos.
                      </p>
                    </div>
                    <div className="flex flex-col gap-2 sm:flex-row">
                      <Button onClick={() => navigate(`${prefix}/transactions?onboarding=transaction`)}>
                        <Plus className="mr-2 h-4 w-4" />
                        Registrar Primeira Transação
                      </Button>
                      <Button variant="outline" onClick={() => navigate(`${prefix}/import-statements`)}>
                        <FileText className="mr-2 h-4 w-4" />
                        Importar Extrato
                      </Button>
                    </div>
                  </>
                ) : (
                  <>
                    <div className="space-y-1">
                      <p className="text-sm font-bold text-slate-900">Ações rápidas</p>
                      <p className="text-sm text-muted-foreground">
                        Use um atalho para começar uma análise útil sem digitar tudo do zero.
                      </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                      {quickPrompts.map((prompt) => (
                        <Button
                          key={prompt}
                          variant="outline"
                          size="sm"
                          onClick={() => setInput(prompt)}
                          disabled={isLoading}
                          className="h-auto whitespace-normal text-left"
                        >
                          {prompt}
                        </Button>
                      ))}
                      {!hasCards && (
                        <Button variant="outline" size="sm" onClick={() => navigate(`${prefix}/cards?onboarding=card`)}>
                          <CreditCard className="mr-2 h-4 w-4" />
                          Cadastrar Primeiro Cartão
                        </Button>
                      )}
                    </div>
                  </>
                )}
              </CardContent>
            </Card>
          )}

          <ScrollArea className="flex-1 pr-4" ref={scrollRef}>
            <div className="space-y-4">
              {messages.map((message, index) => (
                <div
                  key={index}
                  className={`flex gap-3 ${message.role === 'user' ? 'justify-end' : 'justify-start'}`}
                >
                  {message.role === 'assistant' && (
                    <div className="flex-shrink-0">
                      <div className="w-8 h-8 rounded-full bg-primary flex items-center justify-center">
                        <Bot className="h-5 w-5 text-primary-foreground" />
                      </div>
                    </div>
                  )}
                  <div
                    className={`max-w-[80%] rounded-lg p-4 ${message.role === 'user'
                      ? 'bg-primary text-primary-foreground'
                      : 'bg-muted'
                      }`}
                  >
                    <p className="whitespace-pre-wrap break-words">{message.content}</p>
                    <p className="text-xs opacity-70 mt-2">
                      {message.timestamp.toLocaleTimeString('pt-BR', {
                        hour: '2-digit',
                        minute: '2-digit'
                      })}
                    </p>
                  </div>
                  {message.role === 'user' && (
                    <div className="flex-shrink-0">
                      <div className="w-8 h-8 rounded-full bg-secondary flex items-center justify-center">
                        <User className="h-5 w-5" />
                      </div>
                    </div>
                  )}
                </div>
              ))}
              {isLoading && (
                <div className="flex gap-3 justify-start">
                  <div className="flex-shrink-0">
                    <div className="w-8 h-8 rounded-full bg-primary flex items-center justify-center">
                      <Bot className="h-5 w-5 text-primary-foreground" />
                    </div>
                  </div>
                  <div className="bg-muted rounded-lg p-4">
                    <Loader2 className="h-5 w-5 animate-spin" />
                  </div>
                </div>
              )}
            </div>
          </ScrollArea>

          {selectedFile && (
            <div className="flex items-center gap-2 p-3 bg-muted rounded-lg">
              <FileText className="h-5 w-5 text-muted-foreground" />
              <span className="flex-1 text-sm">{selectedFile.name}</span>
              <Button
                variant="ghost"
                size="sm"
                onClick={removeFile}
              >
                <X className="h-4 w-4" />
              </Button>
            </div>
          )}

          <div className="flex gap-2">
            <input
              ref={fileInputRef}
              type="file"
              accept=".csv,.txt,.ofx"
              onChange={handleFileSelect}
              className="hidden"
            />
            <Button
              variant="outline"
              size="icon"
              onClick={() => fileInputRef.current?.click()}
              disabled={isLoading}
            >
              <Paperclip className="h-5 w-5" />
            </Button>
            <Textarea
              value={input}
              onChange={(e) => setInput(e.target.value)}
              onKeyDown={handleKeyPress}
              placeholder="Digite sua mensagem ou anexe um extrato bancário..."
              className="flex-1 min-h-[60px] max-h-[120px]"
              disabled={isLoading}
            />
            <Button
              onClick={handleSend}
              disabled={isLoading || (!input.trim() && !selectedFile)}
              size="icon"
            >
              {isLoading ? (
                <Loader2 className="h-5 w-5 animate-spin" />
              ) : (
                <Send className="h-5 w-5" />
              )}
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
