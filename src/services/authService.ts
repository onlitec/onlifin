// ===========================================
// Serviço de Autenticação Local (PostgreSQL)
// ===========================================

// URL base da API (PostgREST)
const API_URL = import.meta.env.VITE_SUPABASE_URL || '/api';
const JWT_SECRET = import.meta.env.VITE_SUPABASE_ANON_KEY || '';

// Interface do usuário
interface User {
    id: string;
    email: string;
}

// Interface de sessão
interface Session {
    access_token: string;
    user: User;
}

// Interface de resposta de auth
interface AuthResponse {
    data: {
        user: User | null;
        session: Session | null;
    };
    error: Error | null;
}

// Chave de armazenamento
const STORAGE_KEY = 'onlifin_auth_session';

// Gerar JWT simples (para demo - em produção usar backend)
function generateToken(userId: string, email: string): string {
    // Token simples para desenvolvimento
    // Em produção, isso deve ser gerado pelo backend
    const header = btoa(JSON.stringify({ alg: 'HS256', typ: 'JWT' }));
    const payload = btoa(JSON.stringify({
        sub: userId,
        email: email,
        role: 'authenticated',
        iat: Math.floor(Date.now() / 1000),
        exp: Math.floor(Date.now() / 1000) + (24 * 60 * 60) // 24 horas
    }));
    const signature = btoa(userId + JWT_SECRET).substring(0, 43);
    return `${header}.${payload}.${signature}`;
}

// Classe de autenticação local
class LocalAuth {
    private session: Session | null = null;
    private listeners: ((event: string, session: Session | null) => void)[] = [];

    constructor() {
        this.loadSession();
    }

    // Carregar sessão do localStorage
    private loadSession() {
        try {
            const stored = localStorage.getItem(STORAGE_KEY);
            if (stored) {
                this.session = JSON.parse(stored);
            }
        } catch (e) {
            console.error('Erro ao carregar sessão:', e);
        }
    }

    // Salvar sessão no localStorage
    private saveSession(session: Session | null) {
        this.session = session;
        if (session) {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(session));
        } else {
            localStorage.removeItem(STORAGE_KEY);
        }
        this.notifyListeners('SIGNED_IN', session);
    }

    // Notificar listeners de mudança
    private notifyListeners(event: string, session: Session | null) {
        this.listeners.forEach(listener => listener(event, session));
    }

    // Registrar listener de mudança de estado
    onAuthStateChange(callback: (event: string, session: Session | null) => void) {
        this.listeners.push(callback);
        // Retornar função de cleanup
        return {
            data: {
                subscription: {
                    unsubscribe: () => {
                        this.listeners = this.listeners.filter(l => l !== callback);
                    }
                }
            }
        };
    }

    // Login com email/senha
    async signInWithPassword({ email, password }: { email: string; password: string }): Promise<AuthResponse> {
        try {
            // Chamar função de login no PostgreSQL via PostgREST
            const response = await fetch(`${API_URL}/rpc/login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ p_email: email, p_password: password })
            });

            if (!response.ok) {
                throw new Error('Credenciais inválidas');
            }

            const userId = await response.text();

            // Limpar aspas se existirem
            const cleanUserId = userId.replace(/"/g, '').trim();

            if (!cleanUserId || cleanUserId === 'null') {
                throw new Error('Credenciais inválidas');
            }

            // Criar sessão
            const user: User = { id: cleanUserId, email };
            const token = generateToken(cleanUserId, email);
            const session: Session = { access_token: token, user };

            this.saveSession(session);

            return {
                data: { user, session },
                error: null
            };
        } catch (error: any) {
            return {
                data: { user: null, session: null },
                error: new Error(error.message || 'Erro ao fazer login')
            };
        }
    }

    // Registrar novo usuário
    async signUp({ email, password }: { email: string; password: string }): Promise<AuthResponse> {
        try {
            const response = await fetch(`${API_URL}/rpc/register`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ p_email: email, p_password: password })
            });

            if (!response.ok) {
                throw new Error('Erro ao registrar usuário');
            }

            const userId = await response.text();
            const cleanUserId = userId.replace(/"/g, '').trim();

            if (!cleanUserId || cleanUserId === 'null') {
                throw new Error('Email já cadastrado');
            }

            // Auto-login após registro
            return this.signInWithPassword({ email, password });
        } catch (error: any) {
            return {
                data: { user: null, session: null },
                error: new Error(error.message || 'Erro ao registrar')
            };
        }
    }

    // Logout
    async signOut(): Promise<{ error: Error | null }> {
        this.saveSession(null);
        this.notifyListeners('SIGNED_OUT', null);
        return { error: null };
    }

    // Obter sessão atual
    async getSession(): Promise<{ data: { session: Session | null }; error: Error | null }> {
        return {
            data: { session: this.session },
            error: null
        };
    }

    // Obter usuário atual
    async getUser(): Promise<{ data: { user: User | null }; error: Error | null }> {
        return {
            data: { user: this.session?.user || null },
            error: null
        };
    }
}

// Instância singleton
const localAuth = new LocalAuth();

// Exportar cliente compatível com Supabase
export const authClient = {
    auth: localAuth
};

export default authClient;
