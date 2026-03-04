import * as React from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useToast } from '@/hooks/use-toast';
import { MessageCircle, X, Send, Loader2, RotateCcw } from 'lucide-react';
import { chatWithAssistant, getDegradedResponse } from '@/services/ollamaService';
import { loadFinancialContext, formatFinancialContextForPrompt } from '@/services/financialContext';
import { supabase } from '@/db/client';

interface Message {
  role: 'user' | 'assistant';
  content: string;
}

const welcomeMessage: Message = {
  role: 'assistant',
  content: `Olá! 👋 Sou seu assistente financeiro com IA.

Posso ajudar você com:
• 💡 Dicas de economia
• 📊 Análise de gastos
• 📅 Planejamento financeiro
• 🧮 Conceitos financeiros

Como posso ajudar você hoje?`
};

export default function AIAssistant() {
  const [isOpen, setIsOpen] = React.useState(false);
  const [messages, setMessages] = React.useState<Message[]>([welcomeMessage]);
  const [input, setInput] = React.useState('');
  const [isLoading, setIsLoading] = React.useState(false);
  const scrollRef = React.useRef<HTMLDivElement>(null);
  const { toast } = useToast();

  // Carregar histórico do localStorage ao montar o componente
  React.useEffect(() => {
    const savedHistory = localStorage.getItem('ai_conversation_history');
    if (savedHistory) {
      try {
        const parsed = JSON.parse(savedHistory);
        if (Array.isArray(parsed) && parsed.length > 0) {
          setMessages(parsed);
        }
      } catch (error) {
        console.error('Erro ao carregar histórico:', error);
      }
    }
  }, []);

  // Salvar histórico no localStorage sempre que mudar
  React.useEffect(() => {
    if (messages.length > 0) {
      localStorage.setItem('ai_conversation_history', JSON.stringify(messages));
    }
  }, [messages]);

  React.useEffect(() => {
    if (scrollRef.current) {
      scrollRef.current.scrollTop = scrollRef.current.scrollHeight;
    }
  }, [messages]);

  const handleSend = async () => {
    if (!input.trim() || isLoading) return;

    const userMessage = input.trim();
    setInput('');

    // Adicionar mensagem do usuário ao histórico
    const updatedMessages: Message[] = [...messages, { role: 'user', content: userMessage }];
    setMessages(updatedMessages);
    setIsLoading(true);

    try {
      // Carregar contexto financeiro
      const { data: { user } } = await supabase.auth.getUser();
      let contextText: string | undefined;

      if (user) {
        try {
          const financialContext = await loadFinancialContext(user.id);
          contextText = formatFinancialContextForPrompt(financialContext);
        } catch (ctxError) {
          console.error('Erro ao carregar contexto financeiro:', ctxError);
        }
      }

      // Usar Ollama local para gerar resposta
      let responseText: string;
      try {
        // Passamos o histórico de mensagens (não apenas a última) e o contexto financeiro
        responseText = await chatWithAssistant(userMessage, updatedMessages, contextText);
      } catch (aiError: any) {
        console.warn('Ollama indisponível, usando modo degradado:', aiError.message);
        responseText = getDegradedResponse(userMessage);
      }

      const assistantMessage = responseText || 'Desculpe, não consegui processar sua solicitação.';
      setMessages(prev => [...prev, { role: 'assistant', content: assistantMessage }]);

    } catch (error: any) {
      console.error('Erro no assistente de IA:', error);
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao comunicar com o assistente',
        variant: 'destructive'
      });
      setMessages(prev => [...prev, {
        role: 'assistant',
        content: getDegradedResponse(userMessage)
      }]);
    } finally {
      setIsLoading(false);
    }
  };

  // Função para iniciar nova conversa (limpar histórico)
  const handleNewConversation = () => {
    setMessages([{ ...welcomeMessage }]);
    localStorage.removeItem('ai_conversation_history');
    toast({
      title: 'Nova Conversa',
      description: 'O histórico foi limpo. Como posso ajudar?',
    });
  };

  // Função para fechar o chat
  const handleClose = () => {
    setIsOpen(false);
  };

  const handleKeyPress = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleSend();
    }
  };

  return (
    <>
      {!isOpen && (
        <Button
          onClick={() => setIsOpen(true)}
          className="fixed bottom-6 right-6 h-14 w-14 rounded-2xl shadow-2xl z-50 glass group transition-all duration-300 hover:scale-110 hover:shadow-primary/20"
          size="icon"
        >
          <div className="absolute inset-0 rounded-2xl bg-primary/20 animate-pulse group-hover:bg-primary/40" />
          <MessageCircle className="h-6 w-6 text-foreground relative z-10" />
        </Button>
      )}

      {isOpen && (
        <Card className="fixed bottom-6 right-6 w-[400px] h-[650px] shadow-2xl flex flex-col z-50 glass-card premium-card border-white/10 overflow-hidden">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-4 shrink-0 bg-primary/5 border-b border-white/5">
            <div className="flex items-center gap-2">
              <div className="size-2 rounded-full bg-primary animate-pulse" />
              <CardTitle className="text-sm font-black tracking-widest uppercase">Assistente IA</CardTitle>
            </div>
            <div className="flex gap-1">
              <Button
                variant="ghost"
                size="icon"
                onClick={handleNewConversation}
                title="Nova conversa"
                disabled={isLoading}
                className="hover:bg-primary/20 rounded-xl"
              >
                <RotateCcw className="h-4 w-4" />
              </Button>
              <Button
                variant="ghost"
                size="icon"
                onClick={handleClose}
                title="Fechar"
                className="hover:bg-destructive/20 rounded-xl"
              >
                <X className="h-4 w-4" />
              </Button>
            </div>
          </CardHeader>
          <CardContent className="flex-1 flex flex-col p-0 min-h-0 bg-transparent">
            <div
              ref={scrollRef}
              className="flex-1 overflow-y-auto px-6 py-6"
              style={{ maxHeight: 'calc(650px - 140px)' }}
            >
              <div className="space-y-6">
                {messages.map((msg, idx) => (
                  <div
                    key={idx}
                    className={`flex ${msg.role === 'user' ? 'justify-end' : 'justify-start'} animate-in fade-in slide-in-from-bottom-2 duration-300`}
                  >
                    <div
                      className={`max-w-[85%] rounded-2xl px-4 py-3 shadow-sm ${msg.role === 'user'
                        ? 'bg-primary text-primary-foreground font-medium rounded-tr-none'
                        : 'bg-white/5 border border-white/10 text-foreground font-medium rounded-tl-none backdrop-blur-sm'
                        }`}
                    >
                      <p className="text-sm whitespace-pre-wrap break-words leading-relaxed">{msg.content}</p>
                    </div>
                  </div>
                ))}
                {isLoading && (
                  <div className="flex justify-start animate-pulse">
                    <div className="bg-white/5 border border-white/10 rounded-2xl rounded-tl-none px-4 py-3">
                      <Loader2 className="h-4 w-4 animate-spin text-primary" />
                    </div>
                  </div>
                )}
              </div>
            </div>
            <div className="p-4 border-t border-white/5 shrink-0 bg-white/5 backdrop-blur-3xl">
              <div className="flex gap-2 bg-background/50 p-1 rounded-2xl border border-white/10 ring-offset-background focus-within:ring-2 focus-within:ring-primary/50 transition-all">
                <Input
                  value={input}
                  onChange={(e) => setInput(e.target.value)}
                  onKeyPress={handleKeyPress}
                  placeholder="Pergunte qualquer coisa..."
                  disabled={isLoading}
                  className="flex-1 bg-transparent border-0 focus-visible:ring-0 focus-visible:ring-offset-0 text-sm font-medium"
                />
                <Button
                  onClick={handleSend}
                  disabled={isLoading || !input.trim()}
                  size="icon"
                  className="shrink-0 rounded-xl shadow-lg bg-primary hover:scale-105 transition-transform"
                >
                  <Send className="h-4 w-4" />
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>
      )}
    </>
  );
}
