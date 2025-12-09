import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import "./index.css";
import App from "./App.tsx";
import { AppWrapper } from "./components/common/PageMeta.tsx";
import { registerServiceWorker } from "./utils/registerSW";
import { toast } from "sonner";

registerServiceWorker({
  onSuccess: () => {
    console.log('[PWA] Aplicação pronta para funcionar offline');
  },
  onUpdate: () => {
    console.log('[PWA] Nova versão disponível');
  },
  onOffline: () => {
    toast.warning('Modo Offline', {
      description: 'Você está sem conexão. Algumas funcionalidades podem estar limitadas.'
    });
  },
  onOnline: () => {
    toast.success('Conexão Restaurada', {
      description: 'Você está online novamente.'
    });
  }
});

createRoot(document.getElementById("root")!).render(
  <StrictMode>
    <AppWrapper>
      <App />
    </AppWrapper>
  </StrictMode>
);
