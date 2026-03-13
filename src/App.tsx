import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider, RequireAuth, useAuth } from 'miaoda-auth-react';
import { supabase } from '@/db/client';
import { Toaster } from '@/components/ui/toaster';
import { SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';
import { OnlifinSidebar } from '@/components/layout/OnlifinSidebar';
import { ThemeProvider } from '@/contexts/ThemeContext';
import { CompanyProvider } from '@/contexts/CompanyContext';
import { ThemeToggle } from '@/components/layout/ThemeToggle';
import { TooltipProvider } from '@/components/ui/tooltip';
import AIAssistant from '@/components/AIAssistant';
import { InstallPrompt } from '@/components/pwa/InstallPrompt';
import { UpdateNotification } from '@/components/pwa/UpdateNotification';
import { PWAStatus } from '@/components/pwa/PWAStatus';
import { CompanySelectorCompact } from '@/components/company';
import { useFinanceScope } from '@/hooks/useFinanceScope';
import { PersonProvider } from '@/contexts/PersonContext';
import routes from './routes';
import { Search, Bell, Building2, User } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import { useCompany } from '@/contexts/CompanyContext';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { PersonSelector } from '@/components/person/PersonSelector';

function App() {
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
                <PersonProvider>
                  <MainLayout />
                </PersonProvider>
              </CompanyProvider>
            </RequireAuth>
          </AuthProvider>
        </TooltipProvider>
      </ThemeProvider>
    </Router>
  );
}

function MainLayout() {
  const flattenRoutes = (routeList: typeof routes) => {
    const flattened: any[] = [];
    routeList.forEach(route => {
      if (route.element) {
        flattened.push(route);
      }
      if (route.children) {
        route.children.forEach(child => {
          if (child.element) {
            flattened.push(child);
          }
        });
      }
    });
    return flattened;
  };

  const allRoutes = flattenRoutes(routes);
  const { isPJ, isPF } = useFinanceScope();
  const { user } = useAuth();
  const navigate = useNavigate();

  const { selectedCompany } = useCompany();

  const toggleToPJ = () => {
    if (selectedCompany) {
      navigate(`/pj/${selectedCompany.id}`);
    } else {
      navigate('/companies');
    }
  };

  const toggleToPF = () => {
    if (isPF) return;
    navigate('/pf');
  };

  return (
    <SidebarProvider defaultOpen={true}>
      <OnlifinSidebar />
      <div className="flex flex-1 flex-col ml-0 md:ml-64 transition-[margin] duration-200 group-data-[state=collapsed]/sidebar-wrapper:md:ml-12 min-h-screen bg-background text-foreground">
        <header className="sticky top-0 z-40 flex h-20 shrink-0 items-center justify-between gap-4 bg-white border-b-2 border-slate-300 px-8 transition-all duration-300 shadow-md">
          <div className="flex items-center gap-4 flex-1">
            <SidebarTrigger className="text-slate-500 hover:text-slate-900 transition-colors" />
            <div className="relative max-w-md w-full ml-4">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
              <Input
                placeholder="Buscar transações, contas..."
                className="pl-10 h-11 bg-slate-100 border-transparent rounded-xl text-sm focus-visible:ring-0 focus-visible:border-slate-300"
              />
            </div>
          </div>

          <div className="flex items-center gap-4">
            <div className="flex items-center gap-1 bg-slate-100 p-1 rounded-2xl border border-slate-200 mr-4">
              <Button
                variant={isPF ? "default" : "ghost"}
                size="sm"
                onClick={toggleToPF}
                className={cn(
                  "h-8 px-4 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all",
                  isPF ? "bg-white text-blue-600 shadow-sm hover:bg-white" : "text-slate-500 hover:text-slate-900"
                )}
              >
                <User className="h-3 w-3 mr-2" />
                Pessoa Física
              </Button>
              <Button
                variant={isPJ ? "default" : "ghost"}
                size="sm"
                onClick={toggleToPJ}
                className={cn(
                  "h-8 px-4 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all text-nowrap",
                  isPJ ? "bg-white text-blue-600 shadow-sm hover:bg-white" : "text-slate-500 hover:text-slate-900"
                )}
              >
                <Building2 className="h-3 w-3 mr-2" />
                Empresa
              </Button>
            </div>

            <div className="flex items-center gap-3 mr-4">
              {!isPJ ? (
                <PersonSelector size="sm" className="h-9 border-slate-200 rounded-xl font-bold" />
              ) : (
                <CompanySelectorCompact />
              )}
            </div>

            <div className="flex items-center gap-2 pr-4 border-r border-slate-100">
              <ThemeToggle />
              <Button variant="ghost" size="icon" className="text-slate-500 rounded-xl relative">
                <Bell className="h-5 w-5" />
                <span className="absolute top-2.5 right-2.5 h-2 w-2 bg-red-500 rounded-full border-2 border-white" />
              </Button>
            </div>

            <div className="flex items-center gap-3 pl-2">
              <div className="text-right hidden sm:block">
                <p className="text-sm font-bold text-slate-900 leading-none mb-1">{user?.email?.split('@')[0] || 'alfreire'}</p>
                <p className="text-[10px] font-medium text-slate-500 uppercase tracking-wider">Admin</p>
              </div>
              <Avatar className="h-10 w-10 border-2 border-slate-100 shadow-sm">
                <AvatarImage src="" />
                <AvatarFallback className="bg-primary text-white font-bold">AF</AvatarFallback>
              </Avatar>
            </div>
          </div>
        </header>
        <main className="flex-1 overflow-auto animate-slide-up">
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
  );
}

export default App;
