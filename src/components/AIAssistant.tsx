import * as React from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useToast } from '@/hooks/use-toast';
import { MessageCircle, X, Send, Loader2, RotateCcw } from 'lucide-react';
import { chatWithAssistant, getDegradedResponse } from '@/services/ollamaService';

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
      // Usar Ollama local para gerar resposta
      let responseText: string;
      try {
        responseText = await chatWithAssistant(userMessage);
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
          className="fixed bottom-6 right-6 h-14 w-14 rounded-full shadow-lg z-50"
          size="icon"
        >
          <MessageCircle className="h-6 w-6" />
        </Button>
      )}

      {isOpen && (
        <Card className="fixed bottom-6 right-6 w-96 h-[600px] shadow-2xl flex flex-col z-50">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-4 shrink-0">
            <CardTitle className="text-lg">Assistente Financeiro IA</CardTitle>
            <div className="flex gap-1">
              <Button
                variant="ghost"
                size="icon"
                onClick={handleNewConversation}
                title="Nova conversa"
                disabled={isLoading}
              >
                <RotateCcw className="h-4 w-4" />
              </Button>
              <Button
                variant="ghost"
                size="icon"
                onClick={handleClose}
                title="Fechar"
              >
                <X className="h-4 w-4" />
              </Button>
            </div>
          </CardHeader>
          <CardContent className="flex-1 flex flex-col p-0 min-h-0">
            <div
              ref={scrollRef}
              className="flex-1 overflow-y-auto px-4 py-4"
              style={{ maxHeight: 'calc(600px - 140px)' }}
            >
              <div className="space-y-4">
                {messages.map((msg, idx) => (
                  <div
                    key={idx}
                    className={`flex ${msg.role === 'user' ? 'justify-end' : 'justify-start'}`}
                  >
                    <div
                      className={`max-w-[85%] rounded-lg px-4 py-2 ${msg.role === 'user'
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
