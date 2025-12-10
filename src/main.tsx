import * as React from "react";
import { createRoot } from "react-dom/client";
import "./index.css";
import App from "./App.tsx";
import { AppWrapper } from "./components/common/PageMeta.tsx";
import { registerServiceWorker } from "./utils/registerSW";
import { toast } from "sonner";

registerServiceWorker({
  onSuccess: () => {
    console.log('[PWA] Application ready to work offline');
  },
  onUpdate: () => {
    console.log('[PWA] New version available');
  },
  onOffline: () => {
    toast.warning('Offline Mode', {
      description: 'You are offline. Some features may be limited.'
    });
  },
  onOnline: () => {
    toast.success('Connection Restored', {
      description: 'You are online again.'
    });
  }
});

createRoot(document.getElementById("root")!).render(
  <React.StrictMode>
    <AppWrapper>
      <App />
    </AppWrapper>
  </React.StrictMode>
);
