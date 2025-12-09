import { useState, useEffect } from 'react';
import { Wifi, WifiOff } from 'lucide-react';
import { toast } from 'sonner';

export function PWAStatus() {
  const [isOnline, setIsOnline] = useState(navigator.onLine);

  useEffect(() => {
    const handleOnline = () => {
      setIsOnline(true);
      toast.success('Conexão restaurada', {
        description: 'Você está online novamente',
        icon: <Wifi className="h-4 w-4" />
      });
    };

    const handleOffline = () => {
      setIsOnline(false);
      toast.warning('Sem conexão', {
        description: 'Você está offline. Algumas funcionalidades podem estar limitadas.',
        icon: <WifiOff className="h-4 w-4" />,
        duration: 5000
      });
    };

    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);

    return () => {
      window.removeEventListener('online', handleOnline);
      window.removeEventListener('offline', handleOffline);
    };
  }, []);

  if (isOnline) {
    return null;
  }

  return (
    <div className="fixed top-0 left-0 right-0 z-50 bg-yellow-500/90 backdrop-blur-sm px-4 py-2 text-center text-sm font-medium text-yellow-950">
      <div className="flex items-center justify-center gap-2">
        <WifiOff className="h-4 w-4" />
        <span>Modo Offline - Algumas funcionalidades podem estar limitadas</span>
      </div>
    </div>
  );
}
