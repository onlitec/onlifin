import * as React from 'react';
import { useState, useEffect } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { supabase } from '@/db/supabase';
import { profilesApi } from '@/db/api';
import routes from '@/routes';
import { Button } from '@/components/ui/button';
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover';
import { User, LogOut, Settings, Menu, X, ChevronDown } from 'lucide-react';
import { useToast } from '@/hooks/use-toast';
import type { Profile } from '@/types/types';

export default function Header() {
  const [isMenuOpen, setIsMenuOpen] = React.useState(false);
  const [profile, setProfile] = useState<Profile | null>(null);
  const location = useLocation();
  const navigate = useNavigate();
  const { toast } = useToast();
  const navigation = routes.filter((route) => route.visible !== false);

  React.useEffect(() => {
    loadProfile();
    
    // Escutar mudanças no estado de autenticação
    const { data: { subscription } } = supabase.auth.onAuthStateChange((_event, session) => {
      console.log('🔄 Auth state changed:', _event, 'Session:', session);
      if (session?.user) {
        loadProfile();
      } else {
        setProfile(null);
      }
    });

    return () => {
      subscription.unsubscribe();
    };
  }, []);

  const loadProfile = async () => {
    try {
      const { data: { user } } = await supabase.auth.getUser();
      console.log('🔍 User from auth:', user);
      if (user) {
        const userProfile = await profilesApi.getProfile(user.id);
        console.log('👤 Profile loaded:', userProfile);
        setProfile(userProfile);
      }
    } catch (error) {
      console.error('❌ Erro ao carregar perfil:', error);
    }
  };

  const handleLogout = async () => {
    try {
      await supabase.auth.signOut();
      toast({
        title: 'Logout realizado',
        description: 'Você foi desconectado com sucesso'
      });
      navigate('/login');
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: error.message || 'Erro ao fazer logout',
        variant: 'destructive'
      });
    }
  };

  const isActiveRoute = (path: string, children?: any[]) => {
    if (location.pathname === path) return true;
    if (children) {
      return children.some(child => location.pathname === child.path);
    }
    return false;
  };

  return (
    <header className="bg-card border-b border-border sticky top-0 z-50">
      <nav className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between h-16">
          <div className="flex items-center">
            <Link to="/" className="flex items-center space-x-2">
              <div className="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
                <span className="text-primary-foreground font-bold text-lg">O</span>
              </div>
              <span className="text-xl font-bold text-primary">OnliFin</span>
            </Link>
          </div>

          <div className="hidden md:flex items-center space-x-1">
            {navigation.map((item) => {
              if (item.children && item.children.length > 0) {
                return (
                  <Popover key={item.path}>
                    <PopoverTrigger asChild>
                      <Button
                        variant="ghost"
                        className={`px-3 py-2 text-sm font-medium rounded-md transition-colors ${
                          isActiveRoute(item.path, item.children)
                            ? 'bg-primary text-primary-foreground'
                            : 'text-foreground hover:bg-muted'
                        }`}
                      >
                        {item.name}
                        <ChevronDown className="ml-1 h-4 w-4" />
                      </Button>
                    </PopoverTrigger>
                    <PopoverContent align="start" className="w-48 p-2">
                      <div className="space-y-1">
                        <Link
                          to={item.path}
                          className="block px-3 py-2 text-sm rounded-md hover:bg-muted transition-colors"
                        >
                          {item.name}
                        </Link>
                        <div className="border-t border-border my-1" />
                        {item.children.map((child) => (
                          <Link
                            key={child.path}
                            to={child.path}
                            className="block px-3 py-2 text-sm rounded-md hover:bg-muted transition-colors"
                          >
                            {child.name}
                          </Link>
                        ))}
                      </div>
                    </PopoverContent>
                  </Popover>
                );
              }
              return (
                <Link
                  key={item.path}
                  to={item.path}
                  className={`px-3 py-2 text-sm font-medium rounded-md transition-colors ${
                    location.pathname === item.path
                      ? 'bg-primary text-primary-foreground'
                      : 'text-foreground hover:bg-muted'
                  }`}
                >
                  {item.name}
                </Link>
              );
            })}
            {(() => {
              console.log('🔐 Checking admin access - Profile:', profile, 'Role:', profile?.role, 'Is Admin:', profile?.role === 'admin');
              return null;
            })()}
            {profile?.role === 'admin' && (
              <Popover>
                <PopoverTrigger asChild>
                  <Button
                    variant="ghost"
                    className={`px-3 py-2 text-sm font-medium rounded-md transition-colors ${
                      location.pathname === '/admin' || location.pathname === '/ai-admin' || location.pathname === '/user-management'
                        ? 'bg-primary text-primary-foreground'
                        : 'text-foreground hover:bg-muted'
                    }`}
                  >
                    Admin
                    <ChevronDown className="ml-1 h-4 w-4" />
                  </Button>
                </PopoverTrigger>
                <PopoverContent align="start" className="w-48 p-2">
                  <div className="space-y-1">
                    <Link
                      to="/admin"
                      className="block px-3 py-2 text-sm rounded-md hover:bg-muted transition-colors"
                    >
                      Admin
                    </Link>
                    <div className="border-t border-border my-1" />
                    <Link
                      to="/user-management"
                      className="block px-3 py-2 text-sm rounded-md hover:bg-muted transition-colors"
                    >
                      Gestão de Usuários
                    </Link>
                    <Link
                      to="/ai-admin"
                      className="block px-3 py-2 text-sm rounded-md hover:bg-muted transition-colors"
                    >
                      IA Admin
                    </Link>
                  </div>
                </PopoverContent>
              </Popover>
            )}
          </div>

          <div className="flex items-center space-x-4">
            {profile && (
              <Popover>
                <PopoverTrigger asChild>
                  <Button variant="ghost" size="icon" className="rounded-full">
                    <User className="h-5 w-5" />
                  </Button>
                </PopoverTrigger>
                <PopoverContent align="end" className="w-56 p-2">
                  <div className="space-y-1">
                    <div className="px-3 py-2">
                      <p className="text-sm font-medium">{profile.username}</p>
                      <p className="text-xs text-muted-foreground capitalize">{profile.role}</p>
                    </div>
                    <div className="border-t border-border my-1" />
                    {profile.role === 'admin' && (
                      <>
                        <button
                          onClick={() => navigate('/admin')}
                          className="w-full flex items-center px-3 py-2 text-sm rounded-md hover:bg-muted transition-colors text-left"
                        >
                          <Settings className="mr-2 h-4 w-4" />
                          Administração
                        </button>
                        <div className="border-t border-border my-1" />
                      </>
                    )}
                    <button
                      onClick={handleLogout}
                      className="w-full flex items-center px-3 py-2 text-sm rounded-md hover:bg-muted transition-colors text-left"
                    >
                      <LogOut className="mr-2 h-4 w-4" />
                      Sair
                    </button>
                  </div>
                </PopoverContent>
              </Popover>
            )}

            <Button
              variant="ghost"
              size="icon"
              className="md:hidden"
              onClick={() => setIsMenuOpen(!isMenuOpen)}
            >
              {isMenuOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
            </Button>
          </div>
        </div>

        {isMenuOpen && (
          <div className="md:hidden py-4 space-y-2">
            {navigation.map((item) => (
              <div key={item.path}>
                <Link
                  to={item.path}
                  onClick={() => setIsMenuOpen(false)}
                  className={`block px-3 py-2 text-base font-medium rounded-md ${
                    location.pathname === item.path
                      ? 'bg-primary text-primary-foreground'
                      : 'text-foreground hover:bg-muted'
                  }`}
                >
                  {item.name}
                </Link>
                {item.children && item.children.length > 0 && (
                  <div className="ml-4 mt-1 space-y-1">
                    {item.children.map((child) => (
                      <Link
                        key={child.path}
                        to={child.path}
                        onClick={() => setIsMenuOpen(false)}
                        className={`block px-3 py-2 text-sm font-medium rounded-md ${
                          location.pathname === child.path
                            ? 'bg-primary/80 text-primary-foreground'
                            : 'text-muted-foreground hover:bg-muted'
                        }`}
                      >
                        {child.name}
                      </Link>
                    ))}
                  </div>
                )}
              </div>
            ))}
            {profile?.role === 'admin' && (
              <div>
                <Link
                  to="/admin"
                  onClick={() => setIsMenuOpen(false)}
                  className={`block px-3 py-2 text-base font-medium rounded-md ${
                    location.pathname === '/admin'
                      ? 'bg-primary text-primary-foreground'
                      : 'text-foreground hover:bg-muted'
                  }`}
                >
                  Admin
                </Link>
                <div className="ml-4 mt-1 space-y-1">
                  <Link
                    to="/user-management"
                    onClick={() => setIsMenuOpen(false)}
                    className={`block px-3 py-2 text-sm font-medium rounded-md ${
                      location.pathname === '/user-management'
                        ? 'bg-primary/80 text-primary-foreground'
                        : 'text-muted-foreground hover:bg-muted'
                    }`}
                  >
                    Gestão de Usuários
                  </Link>
                  <Link
                    to="/ai-admin"
                    onClick={() => setIsMenuOpen(false)}
                    className={`block px-3 py-2 text-sm font-medium rounded-md ${
                      location.pathname === '/ai-admin'
                        ? 'bg-primary/80 text-primary-foreground'
                        : 'text-muted-foreground hover:bg-muted'
                    }`}
                  >
                    IA Admin
                  </Link>
                </div>
              </div>
            )}
          </div>
        )}
      </nav>
    </header>
  );
}
