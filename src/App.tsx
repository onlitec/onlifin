import * as React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider, RequireAuth } from 'miaoda-auth-react';
import { supabase } from '@/db/supabase';
import { Toaster } from '@/components/ui/toaster';
import Header from '@/components/common/Header';
import AIAssistant from '@/components/AIAssistant';
import { InstallPrompt } from '@/components/pwa/InstallPrompt';
import { UpdateNotification } from '@/components/pwa/UpdateNotification';
import { PWAStatus } from '@/components/pwa/PWAStatus';
import routes from './routes';

function App() {
  const flattenRoutes = (routeList: typeof routes) => {
    const flattened: typeof routes = [];
    routeList.forEach(route => {
      flattened.push(route);
      if (route.children) {
        route.children.forEach(child => {
          flattened.push(child);
        });
      }
    });
    return flattened;
  };

  const allRoutes = flattenRoutes(routes);

  return (
    <Router>
      <AuthProvider client={supabase}>
        <Toaster />
        <PWAStatus />
        <UpdateNotification />
        <InstallPrompt />
        <RequireAuth whiteList={['/login']}>
          <div className="flex flex-col min-h-screen">
            <Header />
            <main className="flex-grow bg-background">
              <Routes>
                {allRoutes.map((route, index) => (
                  <Route
                    key={index}
                    path={route.path}
                    element={route.element}
                  />
                ))}
                <Route path="*" element={<Navigate to="/" replace />} />
              </Routes>
            </main>
            <AIAssistant />
          </div>
        </RequireAuth>
      </AuthProvider>
    </Router>
  );
}

export default App;
