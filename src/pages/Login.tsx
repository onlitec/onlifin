import * as React from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { persistSessionFromToken, supabase } from '@/db/client';
import { profilesApi } from '@/db/api';
import { profileService } from '@/services/profileService';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useToast } from '@/hooks/use-toast';
import { Loader2, AlertCircle, Mail, Lock } from 'lucide-react';
import { isValidUsername, checkRateLimit, resetRateLimit } from '@/utils/security';
import { Alert, AlertDescription } from '@/components/ui/alert';

const LOGIN_RATE_LIMIT_KEY = 'login_attempts';
const MAX_LOGIN_ATTEMPTS = 5;
const LOCKOUT_DURATION_MS = 60000;

const isValidPlanCode = (value: string | null): value is 'basic' | 'medium' | 'full' =>
  value === 'basic' || value === 'medium' || value === 'full';

const resolvePostLoginPath = ({
  forcePasswordChange,
  selectedPlanFromQuery,
  isSignupFlow,
}: {
  forcePasswordChange?: boolean;
  selectedPlanFromQuery: string | null;
  isSignupFlow: boolean;
}) => {
  if (forcePasswordChange) {
    return '/change-password';
  }

  if (!isSignupFlow) {
    return '/';
  }

  if (selectedPlanFromQuery === 'medium' || selectedPlanFromQuery === 'full') {
    return '/companies';
  }

  return '/pf';
};

export default function Login() {
  const [username, setUsername] = React.useState('');
  const [password, setPassword] = React.useState('');
  const [isLoading, setIsLoading] = React.useState(false);
  const [isLocked, setIsLocked] = React.useState(false);
  const [lockoutEndsAt, setLockoutEndsAt] = React.useState<number | null>(null);
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const { toast } = useToast();
  const selectedPlanFromQuery = searchParams.get('plan');
  const isSignupFlow = searchParams.get('signup') === '1';

  React.useEffect(() => {
    const prefilledEmail = searchParams.get('email');
    if (prefilledEmail) {
      setUsername(prefilledEmail.toLowerCase());
    }
  }, [searchParams]);

  React.useEffect(() => {
    const { blocked } = checkRateLimit(LOGIN_RATE_LIMIT_KEY, MAX_LOGIN_ATTEMPTS, LOCKOUT_DURATION_MS);
    if (blocked) {
      setIsLocked(true);
      setLockoutEndsAt(Date.now() + LOCKOUT_DURATION_MS);
    }
  }, []);

  React.useEffect(() => {
    if (!isLocked || !lockoutEndsAt) return;
    const interval = setInterval(() => {
      const remaining = lockoutEndsAt - Date.now();
      if (remaining <= 0) {
        setIsLocked(false);
        setLockoutEndsAt(null);
        resetRateLimit(LOGIN_RATE_LIMIT_KEY);
      }
    }, 1000);
    return () => clearInterval(interval);
  }, [isLocked, lockoutEndsAt]);

  React.useEffect(() => {
    const hashParams = new URLSearchParams(window.location.hash.replace(/^#/, ''));
    const tokenFromHash = hashParams.get('token');
    if (!tokenFromHash) return;

    let isMounted = true;

    const completeSignupLogin = async () => {
      setIsLoading(true);

      try {
        const session = persistSessionFromToken(tokenFromHash);
        const profile = session.user?.id ? await profilesApi.getProfile(session.user.id) : null;

        if (isValidPlanCode(selectedPlanFromQuery) && !(profile as any)?.settings?.plan_code) {
          await profileService.updateSettings({ plan_code: selectedPlanFromQuery });
        }

        resetRateLimit(LOGIN_RATE_LIMIT_KEY);
        window.history.replaceState({}, document.title, `${window.location.pathname}${window.location.search}`);

        if (!isMounted) return;

        navigate(
          resolvePostLoginPath({
            forcePasswordChange: profile?.force_password_change,
            selectedPlanFromQuery,
            isSignupFlow,
          }),
          { replace: true }
        );
        toast({
          title: 'Bem-vindo!',
          description: isSignupFlow && (selectedPlanFromQuery === 'medium' || selectedPlanFromQuery === 'full')
            ? 'Conta criada. Cadastre agora seu primeiro CNPJ.'
            : 'Conta criada e conectada com sucesso'
        });
      } catch (error: any) {
        window.history.replaceState({}, document.title, `${window.location.pathname}${window.location.search}`);

        if (!isMounted) return;

        toast({
          title: 'Erro de Acesso',
          description: error.message || 'Não foi possível concluir o login automático.',
          variant: 'destructive'
        });
      } finally {
        if (isMounted) {
          setIsLoading(false);
        }
      }
    };

    void completeSignupLogin();

    return () => {
      isMounted = false;
    };
  }, [isSignupFlow, navigate, searchParams, selectedPlanFromQuery, toast]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    const { blocked } = checkRateLimit(
      LOGIN_RATE_LIMIT_KEY,
      MAX_LOGIN_ATTEMPTS,
      LOCKOUT_DURATION_MS
    );

    if (blocked) {
      setIsLocked(true);
      setLockoutEndsAt(Date.now() + LOCKOUT_DURATION_MS);
      toast({ title: 'Muitas tentativas', description: 'Aguarde 1 minuto.', variant: 'destructive' });
      return;
    }

    setIsLoading(true);

    if (!username || !password) {
      toast({ title: 'Erro', description: 'Preencha todos os campos', variant: 'destructive' });
      setIsLoading(false);
      return;
    }

    if (!isValidUsername(username)) {
      toast({ title: 'Erro', description: 'Usuário ou email inválido.', variant: 'destructive' });
      setIsLoading(false);
      return;
    }

    const email = username.includes('@') ? username : `${username}@miaoda.com`;
    try {
      const { data, error } = await supabase.auth.signInWithPassword({ email, password });
      if (error) throw error;

      const profile = data.user?.id ? await profilesApi.getProfile(data.user.id) : null;

      if (isValidPlanCode(selectedPlanFromQuery) && !(profile as any)?.settings?.plan_code) {
        await profileService.updateSettings({ plan_code: selectedPlanFromQuery });
      }

      resetRateLimit(LOGIN_RATE_LIMIT_KEY);
      navigate(
        resolvePostLoginPath({
          forcePasswordChange: profile?.force_password_change,
          selectedPlanFromQuery,
          isSignupFlow,
        })
      );
      toast({
        title: 'Bem-vindo!',
        description: profile?.force_password_change
          ? 'Troque sua senha provisória para continuar.'
          : isSignupFlow && (selectedPlanFromQuery === 'medium' || selectedPlanFromQuery === 'full')
            ? 'Conta criada. Cadastre agora seu primeiro CNPJ.'
            : 'Conectado com sucesso'
      });
    } catch (error: any) {
      toast({
        title: 'Erro de Acesso',
        description: error.message || 'Credenciais inválidas.',
        variant: 'destructive'
      });
      const { remainingAttempts: newRemaining } = checkRateLimit(LOGIN_RATE_LIMIT_KEY, MAX_LOGIN_ATTEMPTS, LOCKOUT_DURATION_MS);
      if (newRemaining <= 0) {
        setIsLocked(true);
        setLockoutEndsAt(Date.now() + LOCKOUT_DURATION_MS);
      }
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-slate-50 relative overflow-hidden p-6 font-sans">
      {/* Background Decor */}
      <div className="absolute top-0 left-0 w-full h-[300px] bg-blue-600 rounded-b-[100px] opacity-10" />

      <div className="w-full max-w-[450px] z-10 animate-slide-up">
        <div className="bg-white border border-slate-200 p-10 rounded-[2.5rem] shadow-xl">
          <div className="text-center mb-10 space-y-6">
            <div className="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-lg shadow-blue-200">
              <span className="text-3xl font-bold">O</span>
            </div>
            <div className="space-y-1">
              <h1 className="text-2xl font-bold text-slate-900">OnliFin</h1>
              <p className="text-sm font-medium text-slate-400">Entre em sua conta para gerenciar suas finanças</p>
            </div>
          </div>

          {isLocked && (
            <Alert variant="destructive" className="mb-6 rounded-xl border-red-100 bg-red-50 text-red-600">
              <AlertCircle className="h-4 w-4" />
              <AlertDescription className="text-xs font-bold">
                Sistema bloqueado por {Math.max(0, Math.ceil((lockoutEndsAt! - Date.now()) / 1000))} segundos.
              </AlertDescription>
            </Alert>
          )}

          <form onSubmit={handleSubmit} className="space-y-6">
            <div className="space-y-2">
              <Label htmlFor="username" className="text-xs font-bold text-slate-500 uppercase tracking-wider ml-1">Usuário ou E-mail</Label>
              <div className="relative group">
                <Mail className="absolute left-4 top-1/2 -translate-y-1/2 size-4 text-slate-400 group-focus-within:text-blue-600 transition-colors" />
                <Input
                  id="username"
                  type="text"
                  placeholder="ex: joao.silva"
                  value={username}
                  onChange={(e) => setUsername(e.target.value.toLowerCase())}
                  disabled={isLoading || isLocked}
                  required
                  className="h-12 pl-12 bg-slate-50 border-slate-200 rounded-xl text-sm focus-visible:ring-blue-500/20"
                  autoComplete="username"
                />
              </div>
            </div>

            <div className="space-y-2">
              <Label htmlFor="password" className="text-xs font-bold text-slate-500 uppercase tracking-wider ml-1">Senha</Label>
              <div className="relative group">
                <Lock className="absolute left-4 top-1/2 -translate-y-1/2 size-4 text-slate-400 group-focus-within:text-blue-600 transition-colors" />
                <Input
                  id="password"
                  type="password"
                  placeholder="••••••••"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  disabled={isLoading || isLocked}
                  required
                  className="h-12 pl-12 bg-slate-50 border-slate-200 rounded-xl text-sm focus-visible:ring-blue-500/20"
                  autoComplete="current-password"
                />
              </div>
            </div>

            <Button
              type="submit"
              className="w-full h-12 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm shadow-md shadow-blue-100 transition-all active:scale-[0.98] disabled:opacity-50"
              disabled={isLoading || isLocked}
            >
              {isLoading ? <Loader2 className="h-5 w-5 animate-spin" /> : 'Entrar na Plataforma'}
            </Button>
          </form>

          <footer className="mt-10 text-center">
            <p className="text-xs font-medium text-slate-400">
              Esqueceu sua senha? <span className="text-blue-600 cursor-pointer hover:underline">Contate o suporte</span>
            </p>
          </footer>
        </div>
      </div>
    </div>
  );
}
