import * as React from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { RefreshCw, X } from 'lucide-react';
import { skipWaiting } from '@/utils/registerSW';

export function UpdateNotification() {
  const [showUpdate, setShowUpdate] = React.useState(false);
  const [registration, setRegistration] = React.useState<ServiceWorkerRegistration | null>(null);

  React.useEffect(() => {
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.ready.then((reg) => {
        reg.addEventListener('updatefound', () => {
          const newWorker = reg.installing;
          if (newWorker) {
            newWorker.addEventListener('statechange', () => {
              if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                setShowUpdate(true);
                setRegistration(reg);
              }
            });
          }
        });
      });
    }
  }, []);

  const handleUpdate = () => {
    skipWaiting();
    setShowUpdate(false);
    window.location.reload();
  };

  const handleDismiss = () => {
    setShowUpdate(false);
  };

  if (!showUpdate) {
    return null;
  }

  return (
    <div className="fixed top-4 right-4 z-50 max-w-sm animate-in slide-in-from-top-5">
      <Card className="border-primary/20 shadow-lg">
        <CardHeader className="pb-3">
          <div className="flex items-start justify-between">
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                <RefreshCw className="h-5 w-5 text-primary" />
              </div>
              <div>
                <CardTitle className="text-base">Atualização Disponível</CardTitle>
                <CardDescription className="text-xs">
                  Nova versão do OnliFin
                </CardDescription>
              </div>
            </div>
            <Button
              variant="ghost"
              size="icon"
              className="h-6 w-6 -mr-2 -mt-1"
              onClick={handleDismiss}
            >
              <X className="h-4 w-4" />
            </Button>
          </div>
        </CardHeader>
        <CardContent className="space-y-3 pt-0">
          <p className="text-sm text-muted-foreground">
            Uma nova versão está disponível. Atualize para obter as últimas melhorias.
          </p>
          <div className="flex gap-2">
            <Button onClick={handleUpdate} className="flex-1" size="sm">
              Atualizar Agora
            </Button>
            <Button onClick={handleDismiss} variant="outline" size="sm">
              Depois
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
