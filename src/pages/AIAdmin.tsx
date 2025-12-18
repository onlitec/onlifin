import * as React from 'react';
import { aiChatLogsApi, aiConfigApi } from '@/db/api';
import { supabase } from '@/db/supabase';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { useToast } from '@/hooks/use-toast';
import { Bot, MessageSquare, Settings, Shield, Download, CheckCircle2, XCircle, AlertCircle } from 'lucide-react';
import type { AIChatLog, AIConfiguration } from '@/types/types';
import { format } from 'date-fns';
import { ptBR } from 'date-fns/locale';

export default function AIAdmin() {
  const [chatLogs, setChatLogs] = React.useState<AIChatLog[]>([]);
  const [config, setConfig] = React.useState<AIConfiguration | null>(null);
  const [isLoading, setIsLoading] = React.useState(false);
  const [localModels, setLocalModels] = React.useState<string[]>([]);
  const [ollamaStatus, setOllamaStatus] = React.useState<'checking' | 'online' | 'offline'>('checking');
  const [formData, setFormData] = React.useState({
    model_name: 'gemini-2.5-flash',
    endpoint: '',
    permission_level: 'read_aggregated' as 'read_aggregated' | 'read_transactional' | 'read_full',
    can_write_transactions: false,
    provider: 'cloud' as 'cloud' | 'local' // New field
  });
  const [apiKey, setApiKey] = React.useState('');
  const { toast } = useToast();

  React.useEffect(() => {
    loadData();
    loadLocalModels();
  }, []);

  // Verificar modelos locais do Ollama
  const loadLocalModels = async () => {
    try {
      setOllamaStatus('checking');
      const response = await fetch('/ollama/api/tags');
      if (response.ok) {
        const data = await response.json();
        const models = data.models?.map((m: any) => m.name) || [];
        setLocalModels(models);
        setOllamaStatus('online');
      } else {
        setOllamaStatus('offline');
      }
    } catch (error) {
      console.error('Ollama não disponível:', error);
      setOllamaStatus('offline');
    }
  };

  const loadData = async () => {
    setIsLoading(true);
    try {
      const [logs, configurations] = await Promise.all([
        aiChatLogsApi.getAllChatLogs(),
        aiConfigApi.getAllConfigs()
      ]);

      setChatLogs(Array.isArray(logs) ? logs : []);

      if (Array.isArray(configurations) && configurations.length > 0) {
        const activeConfig = configurations[0];
        setConfig(activeConfig);
        // Detectar se é modelo local (Ollama) ou cloud
        const isLocalModel = activeConfig.model_name?.includes(':') || activeConfig.endpoint?.includes('ollama');
        setFormData({
          model_name: activeConfig.model_name || 'gemini-2.5-flash',
          endpoint: activeConfig.endpoint || '',
          permission_level: activeConfig.permission_level || 'read_aggregated',
          can_write_transactions: activeConfig.can_write_transactions || false,
          provider: isLocalModel ? 'local' : 'cloud'
        });
      } else {
        // Initialize with default values if no config exists
        setConfig(null);
        setFormData({
          model_name: 'gemini-2.5-flash',
          endpoint: '',
          permission_level: 'read_aggregated',
          can_write_transactions: false,
          provider: 'cloud'
        });
      }
    } catch (error: any) {
      console.error('Erro ao carregar dados:', error);
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao carregar dados',
        variant: 'destructive'
      });
      // Set empty arrays on error
      setChatLogs([]);
      setConfig(null);
    } finally {
      setIsLoading(false);
    }
  };

  const handleSaveConfig = async () => {
    try {
      // Excluir 'provider' pois não existe no schema do banco
      const { provider, ...dataToSave } = formData;

      if (config) {
        await aiConfigApi.updateConfig(config.id, dataToSave);
        toast({ title: 'Sucesso', description: 'Configuração atualizada com sucesso' });
      } else {
        await aiConfigApi.createConfig({
          ...dataToSave,
          is_active: true
        });
        toast({ title: 'Sucesso', description: 'Configuração criada com sucesso' });
      }

      // Clear API key field after saving
      if (apiKey) {
        toast({
          title: 'Informação',
          description: 'A chave da API deve ser configurada nas variáveis de ambiente do Supabase',
          variant: 'default'
        });
        setApiKey('');
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
          {/* Status Card */}
          <Card className={config ? 'border-green-500/50 bg-green-500/5' : 'border-yellow-500/50 bg-yellow-500/5'}>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                {config ? (
                  <>
                    <CheckCircle2 className="h-5 w-5 text-green-500" />
                    <span>Modelo de IA Configurado</span>
                  </>
                ) : (
                  <>
                    <AlertCircle className="h-5 w-5 text-yellow-500" />
                    <span>Nenhum Modelo Configurado</span>
                  </>
                )}
              </CardTitle>
              <CardDescription>
                {config ? (
                  <div className="space-y-1 mt-2">
                    <p className="text-sm">
                      <span className="font-medium">Modelo:</span> {config.model_name}
                    </p>
                    {config.endpoint && (
                      <p className="text-sm">
                        <span className="font-medium">Endpoint:</span> {config.endpoint}
                      </p>
                    )}
                    <p className="text-sm">
                      <span className="font-medium">Nível de Permissão:</span>{' '}
                      {config.permission_level === 'read_aggregated' && 'Leitura Agregada'}
                      {config.permission_level === 'read_transactional' && 'Leitura Transacional'}
                      {config.permission_level === 'read_full' && 'Leitura Completa'}
                    </p>
                    <p className="text-sm">
                      <span className="font-medium">Criação de Transações:</span>{' '}
                      <span className={config.can_write_transactions ? 'text-yellow-600' : 'text-muted-foreground'}>
                        {config.can_write_transactions ? 'Ativada ⚠️' : 'Desativada'}
                      </span>
                    </p>
                    <p className="text-sm">
                      <span className="font-medium">Status:</span>{' '}
                      <span className={config.is_active ? 'text-green-600' : 'text-red-600'}>
                        {config.is_active ? 'Ativo' : 'Inativo'}
                      </span>
                    </p>
                    <p className="text-xs text-muted-foreground mt-2">
                      Última atualização: {format(new Date(config.updated_at), "dd/MM/yyyy 'às' HH:mm", { locale: ptBR })}
                    </p>
                  </div>
                ) : (
                  <p className="text-sm mt-2">
                    Configure um modelo de IA abaixo para ativar o assistente financeiro.
                  </p>
                )}
              </CardDescription>
            </CardHeader>
          </Card>

          {/* Configuration Form */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Bot className="h-5 w-5" />
                Configuração do Modelo de IA
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              {/* Provider Selection */}
              <div className="space-y-2">
                <Label>Provedor de IA</Label>
                <Select
                  value={formData.provider}
                  onValueChange={(value: 'cloud' | 'local') => {
                    setFormData({
                      ...formData,
                      provider: value,
                      model_name: value === 'local' ? (localModels[0] || 'qwen2.5:0.5b') : 'gemini-2.5-flash',
                      endpoint: value === 'local' ? '/ollama/api' : ''
                    });
                  }}
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Selecione o provedor" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="cloud">☁️ Cloud (Gemini/API Externa)</SelectItem>
                    <SelectItem value="local">🖥️ Local (Ollama)</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              {/* Ollama Status */}
              {formData.provider === 'local' && (
                <div className={`p-3 rounded-lg ${ollamaStatus === 'online' ? 'bg-green-500/10 border border-green-500/30' :
                  ollamaStatus === 'offline' ? 'bg-red-500/10 border border-red-500/30' :
                    'bg-yellow-500/10 border border-yellow-500/30'
                  }`}>
                  <div className="flex items-center gap-2">
                    <div className={`w-2 h-2 rounded-full ${ollamaStatus === 'online' ? 'bg-green-500' :
                      ollamaStatus === 'offline' ? 'bg-red-500' :
                        'bg-yellow-500 animate-pulse'
                      }`} />
                    <span className="text-sm font-medium">
                      Ollama: {ollamaStatus === 'online' ? 'Conectado' : ollamaStatus === 'offline' ? 'Desconectado' : 'Verificando...'}
                    </span>
                    {ollamaStatus === 'online' && localModels.length > 0 && (
                      <span className="text-xs text-muted-foreground">
                        ({localModels.length} modelo{localModels.length > 1 ? 's' : ''} disponível)
                      </span>
                    )}
                    <Button variant="ghost" size="sm" onClick={loadLocalModels} className="ml-auto">
                      Atualizar
                    </Button>
                  </div>
                </div>
              )}

              {/* Model Selection */}
              <div className="space-y-2">
                <Label htmlFor="model_name">Modelo de IA</Label>
                {formData.provider === 'local' && localModels.length > 0 ? (
                  <Select
                    value={formData.model_name}
                    onValueChange={(value) => setFormData({ ...formData, model_name: value })}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Selecione um modelo" />
                    </SelectTrigger>
                    <SelectContent>
                      {localModels.map((model) => (
                        <SelectItem key={model} value={model}>
                          🤖 {model}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                ) : (
                  <Input
                    id="model_name"
                    value={formData.model_name}
                    onChange={(e) => setFormData({ ...formData, model_name: e.target.value })}
                    placeholder={formData.provider === 'local' ? 'llama3.2:3b' : 'gemini-2.5-flash'}
                  />
                )}
                {formData.provider === 'local' && localModels.length === 0 && ollamaStatus === 'online' && (
                  <p className="text-xs text-yellow-600">
                    Nenhum modelo instalado. Use: docker exec onlifin-ollama ollama pull qwen2.5:0.5b
                  </p>
                )}
              </div>

              {/* API Key - only for cloud */}
              {formData.provider === 'cloud' && (
                <div className="space-y-2">
                  <Label htmlFor="api_key">Chave da API (Referência)</Label>
                  <Input
                    id="api_key"
                    type="password"
                    value={apiKey}
                    onChange={(e) => setApiKey(e.target.value)}
                    placeholder="Insira a chave da API do modelo de IA"
                  />
                  <p className="text-xs text-muted-foreground">
                    Para segurança, configure a chave nas variáveis de ambiente do Supabase (GEMINI_API_KEY)
                  </p>
                </div>
              )}
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

              <div className="space-y-2 pt-4 border-t">
                <div className="flex items-center justify-between">
                  <div className="space-y-0.5">
                    <Label htmlFor="can_write_transactions">Permitir Criação de Transações</Label>
                    <p className="text-xs text-muted-foreground">
                      Permite que a IA crie transações em nome do usuário
                    </p>
                  </div>
                  <Switch
                    id="can_write_transactions"
                    checked={formData.can_write_transactions}
                    onCheckedChange={(checked) => setFormData({ ...formData, can_write_transactions: checked })}
                  />
                </div>
                {formData.can_write_transactions && (
                  <div className="p-3 border border-yellow-500/50 bg-yellow-500/10 rounded-lg">
                    <p className="text-xs text-yellow-700 dark:text-yellow-300 font-medium">
                      ⚠️ Atenção: Com esta permissão ativada, a IA poderá criar transações automaticamente quando solicitado pelo usuário.
                    </p>
                    <p className="text-xs text-yellow-600 dark:text-yellow-400 mt-1">
                      Todas as transações criadas pela IA serão registradas nos logs de auditoria.
                    </p>
                  </div>
                )}
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
