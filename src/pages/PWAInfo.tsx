import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Smartphone, Wifi, Download, RefreshCw, Bell, Zap, Shield, HardDrive } from 'lucide-react';
import { useState, useEffect } from 'react';

export default function PWAInfo() {
  const [isPWA, setIsPWA] = useState(false);
  const [isOnline, setIsOnline] = useState(navigator.onLine);
  const [swRegistration, setSwRegistration] = useState<ServiceWorkerRegistration | null>(null);

  useEffect(() => {
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches;
    const isIOSStandalone = (window.navigator as any).standalone === true;
    setIsPWA(isStandalone || isIOSStandalone);

    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.ready.then((registration) => {
        setSwRegistration(registration);
      });
    }

    const handleOnline = () => setIsOnline(true);
    const handleOffline = () => setIsOnline(false);

    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);

    return () => {
      window.removeEventListener('online', handleOnline);
      window.removeEventListener('offline', handleOffline);
    };
  }, []);

  const handleCheckUpdate = async () => {
    if (swRegistration) {
      await swRegistration.update();
      alert('Verificação de atualização concluída!');
    }
  };

  const features = [
    {
      icon: Smartphone,
      title: 'Instalável',
      description: 'Instale o OnliFin como um aplicativo nativo no seu dispositivo',
      color: 'text-blue-500'
    },
    {
      icon: Wifi,
      title: 'Funciona Offline',
      description: 'Acesse suas informações financeiras mesmo sem conexão',
      color: 'text-green-500'
    },
    {
      icon: Zap,
      title: 'Rápido e Responsivo',
      description: 'Carregamento instantâneo com cache inteligente',
      color: 'text-yellow-500'
    },
    {
      icon: RefreshCw,
      title: 'Sempre Atualizado',
      description: 'Atualizações automáticas em segundo plano',
      color: 'text-purple-500'
    },
    {
      icon: Bell,
      title: 'Notificações Push',
      description: 'Receba alertas importantes sobre suas finanças',
      color: 'text-red-500'
    },
    {
      icon: Shield,
      title: 'Seguro',
      description: 'Dados protegidos com criptografia de ponta',
      color: 'text-indigo-500'
    }
  ];

  return (
    <div className="container mx-auto p-6 space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Progressive Web App</h1>
          <p className="text-muted-foreground mt-2">
            Informações sobre as funcionalidades PWA do OnliFin
          </p>
        </div>
        <div className="flex gap-2">
          <Badge variant={isPWA ? 'default' : 'secondary'}>
            {isPWA ? '✓ Instalado' : 'Navegador'}
          </Badge>
          <Badge variant={isOnline ? 'default' : 'destructive'}>
            {isOnline ? '✓ Online' : '✗ Offline'}
          </Badge>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Status do PWA</CardTitle>
          <CardDescription>
            Informações sobre a instalação e funcionalidades
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-4 md:grid-cols-2">
            <div className="flex items-center gap-3 p-4 border rounded-lg">
              <Download className="h-5 w-5 text-primary" />
              <div>
                <p className="font-medium">Modo de Instalação</p>
                <p className="text-sm text-muted-foreground">
                  {isPWA ? 'Aplicativo Instalado' : 'Modo Navegador'}
                </p>
              </div>
            </div>

            <div className="flex items-center gap-3 p-4 border rounded-lg">
              <Wifi className="h-5 w-5 text-primary" />
              <div>
                <p className="font-medium">Status da Conexão</p>
                <p className="text-sm text-muted-foreground">
                  {isOnline ? 'Conectado' : 'Offline'}
                </p>
              </div>
            </div>

            <div className="flex items-center gap-3 p-4 border rounded-lg">
              <HardDrive className="h-5 w-5 text-primary" />
              <div>
                <p className="font-medium">Service Worker</p>
                <p className="text-sm text-muted-foreground">
                  {swRegistration ? 'Ativo' : 'Não Registrado'}
                </p>
              </div>
            </div>

            <div className="flex items-center gap-3 p-4 border rounded-lg">
              <RefreshCw className="h-5 w-5 text-primary" />
              <div>
                <p className="font-medium">Atualizações</p>
                <p className="text-sm text-muted-foreground">
                  Automáticas
                </p>
              </div>
            </div>
          </div>

          {swRegistration && (
            <div className="flex gap-2">
              <Button onClick={handleCheckUpdate} variant="outline">
                <RefreshCw className="h-4 w-4 mr-2" />
                Verificar Atualizações
              </Button>
            </div>
          )}
        </CardContent>
      </Card>

      <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        {features.map((feature, index) => (
          <Card key={index}>
            <CardHeader>
              <div className="flex items-center gap-3">
                <div className={`p-2 rounded-lg bg-primary/10 ${feature.color}`}>
                  <feature.icon className="h-6 w-6" />
                </div>
                <CardTitle className="text-lg">{feature.title}</CardTitle>
              </div>
            </CardHeader>
            <CardContent>
              <p className="text-sm text-muted-foreground">
                {feature.description}
              </p>
            </CardContent>
          </Card>
        ))}
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Como Instalar</CardTitle>
          <CardDescription>
            Siga os passos abaixo para instalar o OnliFin no seu dispositivo
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-6">
          <div>
            <h3 className="font-semibold mb-3">📱 No Android (Chrome)</h3>
            <ol className="list-decimal list-inside space-y-2 text-sm text-muted-foreground">
              <li>Toque no menu (três pontos) no canto superior direito</li>
              <li>Selecione "Adicionar à tela inicial" ou "Instalar app"</li>
              <li>Confirme a instalação</li>
              <li>O ícone do OnliFin aparecerá na sua tela inicial</li>
            </ol>
          </div>

          <div>
            <h3 className="font-semibold mb-3">🍎 No iOS (Safari)</h3>
            <ol className="list-decimal list-inside space-y-2 text-sm text-muted-foreground">
              <li>Toque no botão de compartilhar (quadrado com seta)</li>
              <li>Role para baixo e toque em "Adicionar à Tela de Início"</li>
              <li>Edite o nome se desejar e toque em "Adicionar"</li>
              <li>O ícone do OnliFin aparecerá na sua tela inicial</li>
            </ol>
          </div>

          <div>
            <h3 className="font-semibold mb-3">💻 No Desktop (Chrome/Edge)</h3>
            <ol className="list-decimal list-inside space-y-2 text-sm text-muted-foreground">
              <li>Clique no ícone de instalação na barra de endereço</li>
              <li>Ou vá no menu e selecione "Instalar OnliFin"</li>
              <li>Confirme a instalação</li>
              <li>O app abrirá em uma janela separada</li>
            </ol>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Benefícios do PWA</CardTitle>
          <CardDescription>
            Por que usar o OnliFin como aplicativo instalado
          </CardDescription>
        </CardHeader>
        <CardContent>
          <ul className="space-y-3">
            <li className="flex items-start gap-3">
              <span className="text-primary">✓</span>
              <div>
                <p className="font-medium">Acesso Rápido</p>
                <p className="text-sm text-muted-foreground">
                  Abra direto da tela inicial sem precisar abrir o navegador
                </p>
              </div>
            </li>
            <li className="flex items-start gap-3">
              <span className="text-primary">✓</span>
              <div>
                <p className="font-medium">Menos Espaço</p>
                <p className="text-sm text-muted-foreground">
                  Ocupa muito menos espaço que um app nativo tradicional
                </p>
              </div>
            </li>
            <li className="flex items-start gap-3">
              <span className="text-primary">✓</span>
              <div>
                <p className="font-medium">Sempre Atualizado</p>
                <p className="text-sm text-muted-foreground">
                  Não precisa atualizar manualmente, sempre terá a versão mais recente
                </p>
              </div>
            </li>
            <li className="flex items-start gap-3">
              <span className="text-primary">✓</span>
              <div>
                <p className="font-medium">Funciona Offline</p>
                <p className="text-sm text-muted-foreground">
                  Consulte suas informações mesmo sem internet
                </p>
              </div>
            </li>
          </ul>
        </CardContent>
      </Card>
    </div>
  );
}
