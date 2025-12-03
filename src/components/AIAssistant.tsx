import { useState, useEffect, useRef } from 'react';
import { supabase } from '@/db/supabase';
import { aiChatLogsApi } from '@/db/api';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useToast } from '@/hooks/use-toast';
import { MessageCircle, X, Send, Loader2 } from 'lucide-react';

interface Message {
  role: 'user' | 'assistant';
  content: string;
}

export default function AIAssistant() {
  const [isOpen, setIsOpen] = useState(false);
  const [messages, setMessages] = useState<Message[]>([]);
  const [input, setInput] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const scrollRef = useRef<HTMLDivElement>(null);
  const { toast } = useToast();

  useEffect(() => {
    if (scrollRef.current) {
      scrollRef.current.scrollTop = scrollRef.current.scrollHeight;
    }
  }, [messages]);

  const handleSend = async () => {
    if (!input.trim() || isLoading) return;

    const userMessage = input.trim();
    setInput('');
    setMessages(prev => [...prev, { role: 'user', content: userMessage }]);
    setIsLoading(true);

    try {
      const { data: { user } } = await supabase.auth.getUser();
      if (!user) throw new Error('Usuário não autenticado');

      const { data, error } = await supabase.functions.invoke('ai-assistant', {
        body: {
          message: userMessage,
          userId: user.id
        }
      });

      if (error) {
        let errorMsg = 'Erro ao chamar assistente de IA';
        try {
          if (error.context && typeof error.context.text === 'function') {
            errorMsg = await error.context.text();
          } else if (error.message) {
            errorMsg = error.message;
          }
        } catch (e) {
          console.error('Erro ao processar mensagem de erro:', e);
        }
        throw new Error(errorMsg);
      }

      const assistantMessage = data.response || 'Desculpe, não consegui processar sua solicitação.';
      setMessages(prev => [...prev, { role: 'assistant', content: assistantMessage }]);

      // Registrar log com informações de acesso aos dados
      await aiChatLogsApi.createChatLog({
        user_id: user.id,
        message: userMessage,
        response: assistantMessage,
        permission_level: data.permission_level || 'read_aggregated',
        data_accessed: data.data_accessed ? { fields: data.data_accessed } : null
      });
    } catch (error: any) {
      console.error('Erro no assistente de IA:', error);
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao comunicar com o assistente',
        variant: 'destructive'
      });
      setMessages(prev => [...prev, {
        role: 'assistant',
        content: 'Desculpe, ocorreu um erro. Por favor, tente novamente.'
      }]);
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

  return (
    <>
      {!isOpen && (
        <Button
          onClick={() => setIsOpen(true)}
          className="fixed bottom-6 right-6 h-14 w-14 rounded-full shadow-lg"
          size="icon"
        >
          <MessageCircle className="h-6 w-6" />
        </Button>
      )}

      {isOpen && (
        <Card className="fixed bottom-6 right-6 w-96 h-[600px] shadow-2xl flex flex-col">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-4 shrink-0">
            <CardTitle className="text-lg">Assistente Financeiro IA</CardTitle>
            <Button
              variant="ghost"
              size="icon"
              onClick={() => setIsOpen(false)}
            >
              <X className="h-4 w-4" />
            </Button>
          </CardHeader>
          <CardContent className="flex-1 flex flex-col p-0 min-h-0">
            <div 
              ref={scrollRef} 
              className="flex-1 overflow-y-auto px-4 py-4"
              style={{ maxHeight: 'calc(600px - 140px)' }}
            >
              <div className="space-y-4">
                {messages.length === 0 && (
                  <div className="text-center text-muted-foreground text-sm">
                    <p>Olá! 👋</p>
                    <p className="mt-2">Entendido, vou atuar como assistente financeiro sem consultar a documentação do site.</p>
                    <p className="mt-2">Estou aqui para ajudar você com:</p>
                    <ul className="mt-2 text-left space-y-1">
                      <li>💰 **Categorização de transações** (identificar e organizar seus gastos)</li>
                      <li>💡 **Dicas de economia** (como economizar no dia a dia)</li>
                      <li>📊 **Análise de gastos** (entender para onde vai seu dinheiro)</li>
                      <li>📅 **Planejamento financeiro** (metas, orçamento, investimentos)</li>
                      <li>🧮 **Conceitos financeiros** (explicar termos e estratégias)</li>
                    </ul>
                  </div>
                )}
                {messages.map((msg, idx) => (
                  <div
                    key={idx}
                    className={`flex ${msg.role === 'user' ? 'justify-end' : 'justify-start'}`}
                  >
                    <div
                      className={`max-w-[85%] rounded-lg px-4 py-2 ${
                        msg.role === 'user'
                          ? 'bg-primary text-primary-foreground'
                          : 'bg-muted'
                      }`}
                    >
                      <p className="text-sm whitespace-pre-wrap break-words">{msg.content}</p>
                    </div>
                  </div>
                ))}
                {isLoading && (
                  <div className="flex justify-start">
                    <div className="bg-muted rounded-lg px-4 py-2">
                      <Loader2 className="h-4 w-4 animate-spin" />
                    </div>
                  </div>
                )}
              </div>
            </div>
            <div className="p-4 border-t shrink-0 bg-background">
              <div className="flex gap-2">
                <Input
                  value={input}
                  onChange={(e) => setInput(e.target.value)}
                  onKeyPress={handleKeyPress}
                  placeholder="Digite sua mensagem..."
                  disabled={isLoading}
                  className="flex-1"
                />
                <Button
                  onClick={handleSend}
                  disabled={isLoading || !input.trim()}
                  size="icon"
                  className="shrink-0"
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
