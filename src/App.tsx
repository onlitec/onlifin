import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider, RequireAuth } from 'miaoda-auth-react';
import { supabase } from '@/db/client';
import { Toaster } from '@/components/ui/toaster';
import { SidebarProvider, SidebarInset, SidebarTrigger } from '@/components/ui/sidebar';
import { OnlifinSidebar } from '@/components/layout/OnlifinSidebar';
import { ThemeProvider } from '@/contexts/ThemeContext';
import { ThemeToggle } from '@/components/layout/ThemeToggle';
import { Separator } from '@/components/ui/separator';
import { TooltipProvider } from '@/components/ui/tooltip';
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
      <ThemeProvider>
        <TooltipProvider>
          <AuthProvider client={supabase}>
            <Toaster />
            <PWAStatus />
            <UpdateNotification />
            <InstallPrompt />
            <RequireAuth whiteList={['/login']}>
              <SidebarProvider defaultOpen={true}>
                <div className="flex min-h-screen w-full bg-background">
                  <OnlifinSidebar />
                  <SidebarInset>
                    <header className="flex h-16 shrink-0 items-center gap-2 border-b border-border px-6 bg-card">
                      <SidebarTrigger className="text-muted-foreground hover:text-foreground" />
                      <Separator orientation="vertical" className="mr-2 h-4" />
                      <div className="flex flex-1 items-center justify-between">
                        <div className="flex items-center gap-2">
                          <h2 className="text-lg font-semibold text-foreground">Onlifin</h2>
                        </div>
                        <ThemeToggle />
                      </div>
                    </header>
                    <main className="flex-1 overflow-y-auto bg-background">
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
                  </SidebarInset>
                </div>
              </SidebarProvider>
            </RequireAuth>
          </AuthProvider>
        </TooltipProvider>
      </ThemeProvider>
    </Router>
  );
}

export default App;
