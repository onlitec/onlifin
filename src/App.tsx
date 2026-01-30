import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider, RequireAuth } from 'miaoda-auth-react';
import { supabase } from '@/db/client';
import { Toaster } from '@/components/ui/toaster';
import { SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';
import { OnlifinSidebar } from '@/components/layout/OnlifinSidebar';
import { ThemeProvider } from '@/contexts/ThemeContext';
import { CompanyProvider } from '@/contexts/CompanyContext';
import { ThemeToggle } from '@/components/layout/ThemeToggle';
import { Separator } from '@/components/ui/separator';
import { TooltipProvider } from '@/components/ui/tooltip';
import AIAssistant from '@/components/AIAssistant';
import { InstallPrompt } from '@/components/pwa/InstallPrompt';
import { UpdateNotification } from '@/components/pwa/UpdateNotification';
import { PWAStatus } from '@/components/pwa/PWAStatus';
import { CompanySelectorCompact } from '@/components/company';
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
              <CompanyProvider>
                <SidebarProvider defaultOpen={true}>
                  <OnlifinSidebar />
                  <div className="flex flex-1 flex-col ml-0 md:ml-64 transition-[margin] duration-200 group-data-[state=collapsed]/sidebar-wrapper:md:ml-12">
                    <header className="flex h-16 shrink-0 items-center gap-2 border-b border-border px-6 bg-card">
                      <SidebarTrigger className="text-muted-foreground hover:text-foreground" />
                      <Separator orientation="vertical" className="mr-2 h-4" />
                      <div className="flex flex-1 items-center justify-between">
                        <div className="flex items-center gap-4">
                          <h2 className="text-lg font-semibold text-foreground">Onlifin PJ</h2>
                          <CompanySelectorCompact />
                        </div>
                        <ThemeToggle />
                      </div>
                    </header>
                    <main className="flex-1 overflow-auto bg-background">
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
                </SidebarProvider>
              </CompanyProvider>
            </RequireAuth>
          </AuthProvider>
        </TooltipProvider>
      </ThemeProvider>
    </Router>
  );
}

export default App;
