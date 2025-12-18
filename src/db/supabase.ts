// ===========================================
// Cliente de Banco de Dados - Onlifin
// ===========================================
// Usa autenticação local via PostgreSQL + PostgREST

import { createClient, SupabaseClient } from "@supabase/supabase-js";

const supabaseUrl = import.meta.env.VITE_SUPABASE_URL || window.location.origin + '/api';
const supabaseAnonKey = import.meta.env.VITE_SUPABASE_ANON_KEY || 'anonymous-key-for-postgrest';

// Chave de armazenamento de sessão
const STORAGE_KEY = 'onlifin_auth_session';

// Interface do usuário
interface LocalUser {
    id: string;
    email: string;
    app_metadata?: any;
    user_metadata?: any;
    aud?: string;
    created_at?: string;
}

// Interface de sessão
interface LocalSession {
    access_token: string;
    refresh_token?: string;
    user: LocalUser;
    expires_at?: number;
}

// Carregar sessão do localStorage
function loadSession(): LocalSession | null {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored) {
            return JSON.parse(stored);
        }
    } catch (e) {
        console.error('Erro ao carregar sessão:', e);
    }
    return null;
}

// Salvar sessão no localStorage
function saveSession(session: LocalSession | null) {
    if (session) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(session));
    } else {
        localStorage.removeItem(STORAGE_KEY);
    }
}

// Gerar token simples
function generateToken(userId: string, email: string): string {
    const header = btoa(JSON.stringify({ alg: 'HS256', typ: 'JWT' }));
    const payload = btoa(JSON.stringify({
        sub: userId,
        email: email,
        role: 'authenticated',
        iat: Math.floor(Date.now() / 1000),
        exp: Math.floor(Date.now() / 1000) + (24 * 60 * 60)
    }));
    const signature = btoa(userId).substring(0, 43);
    return `${header}.${payload}.${signature}`;
}

// Criar cliente Supabase base para operações de banco
const supabaseClient = createClient(supabaseUrl, supabaseAnonKey, {
    auth: {
        persistSession: false,
        autoRefreshToken: false,
    }
});

// Listeners de autenticação
const authListeners: ((event: string, session: any) => void)[] = [];

// Override do auth para usar PostgreSQL
const customAuth = {
    // Login com email/senha
    async signInWithPassword({ email, password }: { email: string; password: string }) {
        try {
            const response = await fetch(`${supabaseUrl}/rpc/login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ p_email: email, p_password: password })
            });

            if (!response.ok) {
                return { data: { user: null, session: null }, error: new Error('Credenciais inválidas') };
            }

            const userId = await response.text();
            const cleanUserId = userId.replace(/"/g, '').trim();

            if (!cleanUserId || cleanUserId === 'null') {
                return { data: { user: null, session: null }, error: new Error('Credenciais inválidas') };
            }

            const user: LocalUser = {
                id: cleanUserId,
                email,
                app_metadata: {},
                user_metadata: {},
                aud: 'authenticated',
                created_at: new Date().toISOString()
            };
            const token = generateToken(cleanUserId, email);
            const session: LocalSession = {
                access_token: token,
                user,
                expires_at: Math.floor(Date.now() / 1000) + (24 * 60 * 60)
            };

            saveSession(session);
            authListeners.forEach(l => l('SIGNED_IN', session));

            return { data: { user, session }, error: null };
        } catch (error: any) {
            return { data: { user: null, session: null }, error: new Error(error.message || 'Erro ao fazer login') };
        }
    },

    // Registrar novo usuário
    async signUp({ email, password }: { email: string; password: string }) {
        try {
            const response = await fetch(`${supabaseUrl}/rpc/register`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ p_email: email, p_password: password })
            });

            if (!response.ok) {
                return { data: { user: null, session: null }, error: new Error('Erro ao registrar') };
            }

            const userId = await response.text();
            const cleanUserId = userId.replace(/"/g, '').trim();

            if (!cleanUserId || cleanUserId === 'null') {
                return { data: { user: null, session: null }, error: new Error('Email já cadastrado') };
            }

            // Auto-login após registro
            return customAuth.signInWithPassword({ email, password });
        } catch (error: any) {
            return { data: { user: null, session: null }, error: new Error(error.message) };
        }
    },

    // Logout
    async signOut() {
        saveSession(null);
        authListeners.forEach(l => l('SIGNED_OUT', null));
        return { error: null };
    },

    // Obter sessão atual
    async getSession() {
        const session = loadSession();
        return { data: { session }, error: null };
    },

    // Obter usuário atual
    async getUser() {
        const session = loadSession();
        return { data: { user: session?.user || null }, error: null };
    },

    // Listener de mudança de estado
    onAuthStateChange(callback: (event: string, session: any) => void) {
        authListeners.push(callback);
        // Chamar imediatamente com sessão atual
        const session = loadSession();
        if (session) {
            setTimeout(() => callback('INITIAL_SESSION', session), 0);
        }
        return {
            data: {
                subscription: {
                    unsubscribe: () => {
                        const index = authListeners.indexOf(callback);
                        if (index > -1) authListeners.splice(index, 1);
                    }
                }
            }
        };
    },

    // Métodos adicionais para compatibilidade
    async refreshSession() {
        const session = loadSession();
        return { data: { session }, error: null };
    },

    async updateUser(data: any) {
        const session = loadSession();
        if (session) {
            session.user = { ...session.user, ...data };
            saveSession(session);
        }
        return { data: { user: session?.user || null }, error: null };
    }
};

// Criar objeto supabase com auth customizado mas mantendo métodos do cliente
export const supabase = Object.assign(
    Object.create(Object.getPrototypeOf(supabaseClient)),
    supabaseClient,
    { auth: customAuth }
) as SupabaseClient;

export default supabase;