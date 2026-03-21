import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import * as React from 'react';
import { AuthProvider, RequireAuth, useAuth } from 'miaoda-auth-react';
import { supabase } from '@/db/client';
import { Toaster } from '@/components/ui/toaster';
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';
import { OnlifinSidebar } from '@/components/layout/OnlifinSidebar';
import { ThemeProvider } from '@/contexts/ThemeContext';
import { CompanyProvider } from '@/contexts/CompanyContext';
import { ThemeToggle } from '@/components/layout/ThemeToggle';
import { TooltipProvider } from '@/components/ui/tooltip';
import { InstallPrompt } from '@/components/pwa/InstallPrompt';
import { UpdateNotification } from '@/components/pwa/UpdateNotification';
import { PWAStatus } from '@/components/pwa/PWAStatus';
import { CompanySelectorCompact } from '@/components/company';
import { useFinanceScope } from '@/hooks/useFinanceScope';
import { PersonProvider } from '@/contexts/PersonContext';
import { AuthProfileProvider, useAuthProfile } from '@/contexts/AuthProfileContext';
import routes from './routes';
import { Search, Bell, Building2, User, Loader2 } from 'lucide-react';
import { useLocation, useNavigate } from 'react-router-dom';
import { useCompany } from '@/contexts/CompanyContext';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { PersonSelector } from '@/components/person/PersonSelector';
import { NotificationBell } from '@/components/notifications/NotificationBell';

const AIAssistant = React.lazy(() => import('@/components/AIAssistant'));

const LOGIN_PATH = '/login';
const CHANGE_PASSWORD_PATH = '/change-password';
const PUBLIC_PATHS = new Set([LOGIN_PATH]);
const ADMIN_PATHS = new Set([
  '/admin-general',
  '/admin-notifications',
  '/categories',
  '/chat',
  '/user-management',
  '/ai-admin'
]);

function RouteLoader() {
  return (
    <div className="min-h-screen flex items-center justify-center bg-background">
      <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
    </div>
  );
}

function withSuspense(element: React.ReactNode) {
  return (
    <React.Suspense fallback={<RouteLoader />}>
      {element}
    </React.Suspense>
  );
}

function App() {
  const loginRoute = routes.find((route) => route.path === LOGIN_PATH);
  const changePasswordRoute = routes.find((route) => route.path === CHANGE_PASSWORD_PATH);

  return (
    <Router>
      <ThemeProvider>
        <TooltipProvider>
          <AuthProvider client={supabase}>
            <Toaster />
            <PWAStatus />
            <UpdateNotification />
            <InstallPrompt />
            <Routes>
              {loginRoute && <Route path={loginRoute.path} element={withSuspense(loginRoute.element)} />}
              {changePasswordRoute && (
                <Route
                  path={changePasswordRoute.path}
                  element={withSuspense(
                    <RequireAuth>
                      <AuthProfileProvider>
                        <ProfileAccessGuard>
                          {changePasswordRoute.element}
                        </ProfileAccessGuard>
                      </AuthProfileProvider>
                    </RequireAuth>
                  )}
                />
              )}
              <Route
                path="/*"
                element={withSuspense(
                  <RequireAuth>
                    <AuthProfileProvider>
                      <ProfileAccessGuard>
                        <CompanyProvider>
                          <PersonProvider>
                            <MainLayout />
                          </PersonProvider>
                        </CompanyProvider>
                      </ProfileAccessGuard>
                    </AuthProfileProvider>
                  </RequireAuth>
                )}
              />
            </Routes>
          </AuthProvider>
        </TooltipProvider>
      </ThemeProvider>
    </Router>
  );
}

function ProfileAccessGuard({ children }: { children: React.ReactNode }) {
  const location = useLocation();
  const { profile, isLoading } = useAuthProfile();

  React.useEffect(() => {
    if (profile?.status && profile.status !== 'active') {
      void supabase.auth.signOut();
    }
  }, [profile?.status]);

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-background">
        <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  const forcePasswordChange = Boolean(profile?.force_password_change);

  if (profile?.status && profile.status !== 'active') {
    return <Navigate to={LOGIN_PATH} replace />;
  }

  if (forcePasswordChange && location.pathname !== CHANGE_PASSWORD_PATH) {
    return <Navigate to={CHANGE_PASSWORD_PATH} replace />;
  }

  if (!forcePasswordChange && location.pathname === CHANGE_PASSWORD_PATH) {
    return <Navigate to="/" replace />;
  }

  return <>{children}</>;
}

function RequireAdmin({ children }: { children: React.ReactNode }) {
  const { user } = useAuth();
  const { profile, isLoading } = useAuthProfile();
  const userRole = (
    profile?.role ||
    (user as any)?.app_metadata?.role ||
    (user as any)?.role ||
    'user'
  ).toString();

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-background">
        <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (userRole !== 'admin') {
    return <Navigate to="/pf" replace />;
  }

  return <>{children}</>;
}

function MainLayout() {
  const flattenRoutes = (routeList: typeof routes) => {
    const flattened: any[] = [];
    routeList.forEach(route => {
      if (route.element && !PUBLIC_PATHS.has(route.path)) {
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

  return (
    <SidebarProvider defaultOpen={true}>
      <MainLayoutShell allRoutes={allRoutes} />
    </SidebarProvider>
  );
}

function MainLayoutShell({ allRoutes }: { allRoutes: Array<{ path: string; element?: React.ReactNode }> }) {
  const { isPJ, isPF } = useFinanceScope();
  const { user } = useAuth();
  const { profile } = useAuthProfile();
  const navigate = useNavigate();
  const userRole = (
    profile?.role ||
    (user as any)?.app_metadata?.role ||
    (user as any)?.role ||
    'user'
  ).toString();
  const userRoleLabel = userRole === 'admin' ? 'Admin' : 'Usuário';
  const userLabel = profile?.full_name?.trim() || user?.email?.split('@')[0] || 'usuario';
  const userInitials = userLabel
    .split(/[._\s-]+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() || '')
    .join('') || 'ON';

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
    <>
      <OnlifinSidebar />
      <SidebarInset className="min-h-screen bg-background text-foreground">
        <header className="sticky top-0 z-20 flex h-20 shrink-0 items-center justify-between gap-4 bg-white border-b-2 border-slate-300 px-8 transition-all duration-300 shadow-md">
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
              {user?.id ? <NotificationBell userId={user.id} /> : (
                <Button variant="ghost" size="icon" className="text-slate-500 rounded-xl">
                  <Bell className="h-5 w-5" />
                </Button>
              )}
            </div>

            <div className="flex items-center gap-3 pl-2">
              <div className="text-right hidden sm:block">
                <p className="text-sm font-bold text-slate-900 leading-none mb-1">{userLabel}</p>
                <p className="text-[10px] font-medium text-slate-500 uppercase tracking-wider">{userRoleLabel}</p>
              </div>
              <Avatar className="h-10 w-10 border-2 border-slate-100 shadow-sm">
                <AvatarImage src="" />
                <AvatarFallback className="bg-primary text-white font-bold">{userInitials}</AvatarFallback>
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
                element={ADMIN_PATHS.has(route.path)
                  ? (
                    <RequireAdmin>
                      {route.element}
                    </RequireAdmin>
                  )
                  : route.element}
              />
            ))}
            <Route path="*" element={<Navigate to="/" replace />} />
          </Routes>
        </main>
        <React.Suspense fallback={null}>
          <AIAssistant />
        </React.Suspense>
      </SidebarInset>
    </>
  );
}

export default App;
