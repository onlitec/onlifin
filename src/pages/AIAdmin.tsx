import { useEffect, useState } from 'react';
import { aiChatLogsApi, aiConfigApi } from '@/db/api';
import { supabase } from '@/db/supabase';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { useToast } from '@/hooks/use-toast';
import { Bot, MessageSquare, Settings, Shield, Download } from 'lucide-react';
import type { AIChatLog, AIConfiguration } from '@/types/types';
import { format } from 'date-fns';
import { ptBR } from 'date-fns/locale';

export default function AIAdmin() {
  const [chatLogs, setChatLogs] = useState<AIChatLog[]>([]);
  const [config, setConfig] = useState<AIConfiguration | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [formData, setFormData] = useState({
    model_name: 'gemini-2.5-flash',
    endpoint: '',
    permission_level: 'read_aggregated' as 'read_aggregated' | 'read_transactional' | 'read_full'
  });
  const [apiKey, setApiKey] = useState('');
  const { toast } = useToast();

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    setIsLoading(true);
    try {
      const [logs, configurations] = await Promise.all([
        aiChatLogsApi.getAllChatLogs(),
        aiConfigApi.getAllConfigs()
      ]);

      setChatLogs(logs);
      if (configurations.length > 0) {
        const activeConfig = configurations[0];
        setConfig(activeConfig);
        setFormData({
          model_name: activeConfig.model_name,
          endpoint: activeConfig.endpoint || '',
          permission_level: activeConfig.permission_level
        });
      }
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao carregar dados',
        variant: 'destructive'
      });
    } finally {
      setIsLoading(false);
    }
  };

  const handleSaveConfig = async () => {
    try {
      // Save API key to Supabase secrets if provided
      if (apiKey) {
        const { error: secretError } = await supabase.functions.invoke('save-api-key', {
          body: { apiKey }
        });
        if (secretError) {
          console.error('Erro ao salvar chave da API:', secretError);
          toast({
            title: 'Aviso',
            description: 'Configuração salva, mas houve erro ao armazenar a chave da API',
            variant: 'destructive'
          });
        }
      }

      if (config) {
        await aiConfigApi.updateConfig(config.id, formData);
        toast({ title: 'Sucesso', description: 'Configuração atualizada com sucesso' });
      } else {
        await aiConfigApi.createConfig({
          ...formData,
          is_active: true
        });
        toast({ title: 'Sucesso', description: 'Configuração criada com sucesso' });
      }
      loadData();
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao salvar configuração',
        variant: 'destructive'
      });
    }
  };

  const exportChatLogs = () => {
    try {
      if (chatLogs.length === 0) {
        toast({
          title: 'Aviso',
          description: 'Nenhum log disponível para exportar',
          variant: 'destructive'
        });
        return;
      }

      let csvContent = 'Data,Usuário,Mensagem,Resposta,Nível de Permissão\n';
      chatLogs.forEach(log => {
        const date = format(new Date(log.created_at), 'dd/MM/yyyy HH:mm', { locale: ptBR });
        const message = log.message.replace(/"/g, '""');
        const response = log.response.replace(/"/g, '""');
        csvContent += `"${date}","${log.user_id}","${message}","${response}","${log.permission_level}"\n`;
      });

      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = 'logs_ia.csv';
      link.click();

      toast({
        title: 'Sucesso',
        description: 'Logs exportados com sucesso'
      });
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao exportar logs',
        variant: 'destructive'
      });
    }
  };

  const deleteChatLog = async (id: string) => {
    if (!confirm('Tem certeza que deseja excluir este log?')) return;

    try {
      await aiChatLogsApi.deleteChatLog(id);
      toast({ title: 'Sucesso', description: 'Log excluído com sucesso' });
      loadData();
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao excluir log',
        variant: 'destructive'
      });
    }
  };

  return (
    <div className="container mx-auto p-6 space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold">Administração de IA</h1>
      </div>

      <Tabs defaultValue="config" className="w-full">
        <TabsList className="grid w-full grid-cols-3">
          <TabsTrigger value="config">
            <Settings className="mr-2 h-4 w-4" />
            Configurações
          </TabsTrigger>
          <TabsTrigger value="permissions">
            <Shield className="mr-2 h-4 w-4" />
            Permissões
          </TabsTrigger>
          <TabsTrigger value="logs">
            <MessageSquare className="mr-2 h-4 w-4" />
            Logs de Chat
          </TabsTrigger>
        </TabsList>

        <TabsContent value="config" className="space-y-4 mt-4">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Bot className="h-5 w-5" />
                Configuração do Modelo de IA
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="model_name">Nome do Modelo</Label>
                <Input
                  id="model_name"
                  value={formData.model_name}
                  onChange={(e) => setFormData({ ...formData, model_name: e.target.value })}
                  placeholder="gemini-2.5-flash"
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="api_key">Chave da API</Label>
                <Input
                  id="api_key"
                  type="password"
                  value={apiKey}
                  onChange={(e) => setApiKey(e.target.value)}
                  placeholder="Insira a chave da API do modelo de IA"
                />
                <p className="text-xs text-muted-foreground">
                  A chave será armazenada de forma segura no Supabase
                </p>
              </div>
              <div className="space-y-2">
                <Label htmlFor="endpoint">Endpoint da API (Opcional)</Label>
                <Input
                  id="endpoint"
                  value={formData.endpoint}
                  onChange={(e) => setFormData({ ...formData, endpoint: e.target.value })}
                  placeholder="https://api.example.com/v1"
                />
                <p className="text-xs text-muted-foreground">
                  Deixe em branco para usar o endpoint padrão
                </p>
              </div>
              <Button onClick={handleSaveConfig} disabled={isLoading}>
                Salvar Configurações
              </Button>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="permissions" className="space-y-4 mt-4">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Shield className="h-5 w-5" />
                Controle de Permissões
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="permission_level">Nível de Permissão Padrão</Label>
                <Select
                  value={formData.permission_level}
                  onValueChange={(value: any) => setFormData({ ...formData, permission_level: value })}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="read_aggregated">Leitura Agregada</SelectItem>
                    <SelectItem value="read_transactional">Leitura Transacional</SelectItem>
                    <SelectItem value="read_full">Leitura Completa</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-3 pt-4">
                <h3 className="font-medium">Descrição dos Níveis</h3>
                <div className="space-y-2">
                  <div className="p-3 border rounded-lg">
                    <p className="font-medium text-sm">Leitura Agregada</p>
                    <p className="text-xs text-muted-foreground">
                      Acesso apenas a totais e estatísticas. Não vê transações individuais.
                    </p>
                  </div>
                  <div className="p-3 border rounded-lg">
                    <p className="font-medium text-sm">Leitura Transacional</p>
                    <p className="text-xs text-muted-foreground">
                      Acesso a transações individuais, mas sem dados sensíveis como números de conta.
                    </p>
                  </div>
                  <div className="p-3 border rounded-lg">
                    <p className="font-medium text-sm">Leitura Completa</p>
                    <p className="text-xs text-muted-foreground">
                      Acesso total a todos os dados financeiros. Requer consentimento explícito.
                    </p>
                  </div>
                </div>
              </div>

              <Button onClick={handleSaveConfig} disabled={isLoading}>
                Salvar Permissões
              </Button>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="logs" className="space-y-4 mt-4">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between">
              <CardTitle className="flex items-center gap-2">
                <MessageSquare className="h-5 w-5" />
                Histórico de Conversas
              </CardTitle>
              <Button onClick={exportChatLogs} variant="outline" size="sm">
                <Download className="mr-2 h-4 w-4" />
                Exportar
              </Button>
            </CardHeader>
            <CardContent>
              <div className="space-y-3 max-h-[600px] overflow-y-auto">
                {chatLogs.map((log) => (
                  <div key={log.id} className="p-4 border rounded-lg space-y-2">
                    <div className="flex items-center justify-between">
                      <div className="text-xs text-muted-foreground">
                        {format(new Date(log.created_at), "dd/MM/yyyy 'às' HH:mm", { locale: ptBR })}
                      </div>
                      <div className="flex items-center gap-2">
                        <span className="text-xs px-2 py-1 bg-muted rounded">
                          {log.permission_level}
                        </span>
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => deleteChatLog(log.id)}
                        >
                          Excluir
                        </Button>
                      </div>
                    </div>
                    <div className="space-y-2">
                      <div>
                        <p className="text-xs font-medium text-muted-foreground">Usuário:</p>
                        <p className="text-sm">{log.message}</p>
                      </div>
                      <div>
                        <p className="text-xs font-medium text-muted-foreground">Assistente:</p>
                        <p className="text-sm">{log.response}</p>
                      </div>
                    </div>
                  </div>
                ))}
                {chatLogs.length === 0 && (
                  <div className="text-center py-12 text-muted-foreground">
                    <MessageSquare className="h-12 w-12 mx-auto mb-4 opacity-50" />
                    <p>Nenhuma conversa registrada</p>
                  </div>
                )}
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}
