import * as React from 'react';
import { useNavigate } from 'react-router-dom';
import { supabase } from '@/db/client';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useToast } from '@/hooks/use-toast';
import { Loader2, AlertCircle } from 'lucide-react';
import { isValidUsername, validatePassword, checkRateLimit, resetRateLimit } from '@/utils/security';
import { Alert, AlertDescription } from '@/components/ui/alert';

// Constantes de rate limiting
const LOGIN_RATE_LIMIT_KEY = 'login_attempts';
const MAX_LOGIN_ATTEMPTS = 5;
const LOCKOUT_DURATION_MS = 60000; // 1 minuto

export default function Login() {
  const [username, setUsername] = React.useState('');
  const [password, setPassword] = React.useState('');
  const [isLoading, setIsLoading] = React.useState(false);
  const [isLocked, setIsLocked] = React.useState(false);
  const [lockoutEndsAt, setLockoutEndsAt] = React.useState<number | null>(null);
  const [remainingAttempts, setRemainingAttempts] = React.useState(MAX_LOGIN_ATTEMPTS);
  const navigate = useNavigate();
  const { toast } = useToast();

  // Verifica se está bloqueado ao montar
  React.useEffect(() => {
    const { blocked } = checkRateLimit(LOGIN_RATE_LIMIT_KEY, MAX_LOGIN_ATTEMPTS, LOCKOUT_DURATION_MS);
    if (blocked) {
      setIsLocked(true);
      setLockoutEndsAt(Date.now() + LOCKOUT_DURATION_MS);
    }
  }, []);

  // Countdown do lockout
  React.useEffect(() => {
    if (!isLocked || !lockoutEndsAt) return;

    const interval = setInterval(() => {
      const remaining = lockoutEndsAt - Date.now();
      if (remaining <= 0) {
        setIsLocked(false);
        setLockoutEndsAt(null);
        setRemainingAttempts(MAX_LOGIN_ATTEMPTS);
        resetRateLimit(LOGIN_RATE_LIMIT_KEY);
      }
    }, 1000);

    return () => clearInterval(interval);
  }, [isLocked, lockoutEndsAt]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    // Verifica rate limiting
    const { blocked, remainingAttempts: remaining } = checkRateLimit(
      LOGIN_RATE_LIMIT_KEY,
      MAX_LOGIN_ATTEMPTS,
      LOCKOUT_DURATION_MS
    );

    if (blocked) {
      setIsLocked(true);
      setLockoutEndsAt(Date.now() + LOCKOUT_DURATION_MS);
      toast({
        title: 'Muitas tentativas',
        description: 'Aguarde 1 minuto antes de tentar novamente.',
        variant: 'destructive'
      });
      return;
    }

    setRemainingAttempts(remaining);
    setIsLoading(true);

    // Validação de campos
    if (!username || !password) {
      toast({
        title: 'Erro',
        description: 'Por favor, preencha todos os campos',
        variant: 'destructive'
      });
      setIsLoading(false);
      return;
    }

    // Validação de username
    if (!isValidUsername(username)) {
      toast({
        title: 'Erro',
        description: 'Nome de usuário ou email inválido. Use apenas letras, números, (@), (.) e (-).',
        variant: 'destructive'
      });
      setIsLoading(false);
      return;
    }

    // Se já for um email completo, usa como está. Caso contrário, adiciona o domínio padrão.
    const email = username.includes('@') ? username : `${username}@miaoda.com`;
    // Validação de senha
    const passwordValidation = validatePassword(password);
    if (!passwordValidation.valid) {
      toast({
        title: 'Erro',
        description: passwordValidation.message,
        variant: 'destructive'
      });
      setIsLoading(false);
      return;
    }


    try {
      const { error } = await supabase.auth.signInWithPassword({
        email,
        password
      });

      if (error) throw error;

      // Reset rate limit em caso de sucesso
      resetRateLimit(LOGIN_RATE_LIMIT_KEY);

      // Verificar se precisa trocar senha
      const { data: profile } = await supabase.from('profiles').select('force_password_change').eq('id', (await supabase.auth.getUser()).data.user?.id).maybeSingle();

      if (profile?.force_password_change) {
        toast({
          title: 'Troca de senha obrigatória',
          description: 'Por segurança, você deve alterar sua senha agora.',
          variant: 'default'
        });
        navigate('/change-password');
        return;
      }

      toast({
        title: 'Bem-vindo!',
        description: 'Login realizado com sucesso'
      });
      navigate('/');
    } catch (error: any) {
      toast({
        title: 'Erro',
        description: 'Credenciais inválidas. Tente novamente.',
        variant: 'destructive'
      });

      // Atualiza tentativas restantes
      const { remainingAttempts: newRemaining } = checkRateLimit(
        LOGIN_RATE_LIMIT_KEY,
        MAX_LOGIN_ATTEMPTS,
        LOCKOUT_DURATION_MS
      );
      setRemainingAttempts(newRemaining);
    } finally {
      setIsLoading(false);
    }
  };

  const getLockoutTimeRemaining = () => {
    if (!lockoutEndsAt) return '';
    const remaining = Math.max(0, Math.ceil((lockoutEndsAt - Date.now()) / 1000));
    return `${remaining} segundos`;
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-background p-4">
      <Card className="w-full max-w-md">
        <CardHeader className="space-y-1">
          <div className="flex justify-center mb-4">
            <div className="w-16 h-16 bg-primary rounded-lg flex items-center justify-center">
              <span className="text-primary-foreground font-bold text-3xl">O</span>
            </div>
          </div>
          <CardTitle className="text-2xl font-bold text-center">
            OnliFin
          </CardTitle>
          <CardDescription className="text-center">
            Entre com suas credenciais para acessar sua conta
          </CardDescription>
        </CardHeader>
        <CardContent>
          {isLocked && (
            <Alert variant="destructive" className="mb-4">
              <AlertCircle className="h-4 w-4" />
              <AlertDescription>
                Muitas tentativas de login. Tente novamente em {getLockoutTimeRemaining()}.
              </AlertDescription>
            </Alert>
          )}

          {!isLocked && remainingAttempts < MAX_LOGIN_ATTEMPTS && remainingAttempts > 0 && (
            <Alert className="mb-4">
              <AlertCircle className="h-4 w-4" />
              <AlertDescription>
                {remainingAttempts} tentativa(s) restante(s) antes do bloqueio temporário.
              </AlertDescription>
            </Alert>
          )}

          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="username">Nome de Usuário</Label>
              <Input
                id="username"
                type="text"
                placeholder="usuario123"
                value={username}
                onChange={(e) => setUsername(e.target.value.toLowerCase())}
                disabled={isLoading || isLocked}
                required
                autoComplete="username"
                maxLength={100}
              />
              <p className="text-xs text-muted-foreground">
                Use seu usuário (ex: admin) ou email completo.
              </p>
            </div>
            <div className="space-y-2">
              <Label htmlFor="password">Senha</Label>
              <Input
                id="password"
                type="password"
                placeholder="••••••••"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                disabled={isLoading || isLocked}
                required
                autoComplete="current-password"
                maxLength={128}
              />
            </div>
            <Button
              type="submit"
              className="w-full"
              disabled={isLoading || isLocked}
            >
              {isLoading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              {isLocked ? 'Bloqueado' : 'Entrar'}
            </Button>
          </form>
          <div className="mt-4 text-center text-sm text-muted-foreground">
            Não tem uma conta? Entre em contato com o administrador.
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
