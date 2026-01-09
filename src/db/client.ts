// ===========================================
// Cliente API - Onlifin
// ===========================================
// Gerencia comunicação com a API PostgREST
// Substitui antigo cliente Supabase

import { createClient } from "@supabase/supabase-js";

const apiUrl = import.meta.env.VITE_SUPABASE_URL || window.location.origin + '/api';
const anonKey = import.meta.env.VITE_SUPABASE_ANON_KEY || 'anonymous-key-for-postgrest';

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
    role?: string;
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
            const session = JSON.parse(stored);

            // Validar se o token parece válido (tem 3 partes separadas por ponto)
            if (session.access_token && session.access_token.split('.').length === 3) {
                // Validar expiração
                if (session.expires_at && session.expires_at < Math.floor(Date.now() / 1000)) {
                    console.warn('⚠️ Sessão expirada, limpando...');
                    localStorage.removeItem(STORAGE_KEY);
                    return null;
                }
                return session;
            } else {
                console.warn('⚠️ Token inválido detectado, limpando sessão...');
                localStorage.removeItem(STORAGE_KEY);
                return null;
            }
        }
    } catch (e) {
        console.error('Erro ao carregar sessão:', e);
        localStorage.removeItem(STORAGE_KEY);
    }
    return null;
}

// Salvar sessão no localStorage
function saveSession(session: LocalSession | null) {
    if (session) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(session));

        // Atualizar headers do cliente global se existir
        if (onlifinClient) {
            (onlifinClient as any).headers['Authorization'] = `Bearer ${session.access_token}`;
            // Força atualização da sessão no cliente interno do Supabase também
            // para garantir que ele envie o token no header
            onlifinClient.auth.setSession({
                access_token: session.access_token,
                refresh_token: session.refresh_token || ''
            });
        }
    } else {
        localStorage.removeItem(STORAGE_KEY);
        if (onlifinClient) {
            delete (onlifinClient as any).headers['Authorization'];
            onlifinClient.auth.signOut();
        }
    }
}

// Decodificar JWT (simples, apenas para ler payload)
function parseJwt(token: string) {
    try {
        console.log('🔍 Decodificando token (tamanho):', token?.length);
        const parts = token.split('.');
        if (parts.length !== 3) {
            console.error('❌ Token malformado (partes != 3)');
            return null;
        }
        // Remove whitespace/newlines e faz decode
        const base64 = parts[1].replace(/\s/g, '').replace(/-/g, '+').replace(/_/g, '/');
        const jsonPayload = decodeURIComponent(atob(base64).split('').map(function (c) {
            return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
        }).join(''));

        return JSON.parse(jsonPayload);
    } catch (e) {
        console.error('❌ Erro crítico ao decodificar JWT:', e);
        return null;
    }
}

// Função fetch customizada para injetar o token JWT
const customFetch = async (input: RequestInfo | URL, init?: RequestInit) => {
    const session = loadSession();
    const options = init || {};

    if (session?.access_token) {
        const headers = new Headers(options.headers);
        headers.set('Authorization', `Bearer ${session.access_token}`);
        options.headers = headers;
    }

    return fetch(input, options);
};

// Criar cliente base
// Usamos o cliente do supabase-js pois ele é um excelente cliente PostgREST
// Mas configuramos para NÃO usar auth do Supabase, nós gerenciamos o token
const onlifinClient = createClient(apiUrl, anonKey, {
    auth: {
        persistSession: false,
        autoRefreshToken: false,
        detectSessionInUrl: false
    },
    global: {
        fetch: customFetch
    }
});

// Inicializar sessão se existir
const initialSession = loadSession();
if (initialSession) {
    onlifinClient.auth.setSession({
        access_token: initialSession.access_token,
        refresh_token: initialSession.refresh_token || ''
    });
}

// Listeners de autenticação
const authListeners: ((event: string, session: any) => void)[] = [];

// Sistema de Auth Customizado
const auth = {
    // Login com email/senha chamando RPC
    async signInWithPassword({ email, password }: { email: string; password: string }) {
        try {
            console.log('🔐 Tentando login via RPC:', `${apiUrl}/rpc/login`);

            // Usar fetch direto para evitar interferência do cliente
            const response = await fetch(`${apiUrl}/rpc/login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                    // Removido Authorization implícito para evitar 401 antes do login
                },
                body: JSON.stringify({ p_email: email, p_password: password })
            });

            if (!response.ok) {
                const errorText = await response.text();
                console.error('❌ Erro no login:', response.status, errorText);
                return { data: { user: null, session: null }, error: new Error('Credenciais inválidas ou erro no servidor') };
            }

            // A resposta AGORA é o JWT (string), não mais apenas o UUID
            // O PostgREST retorna uma string JSON, ex: "eyJhbGciOi..."
            // Precisamos limpar as aspas se vierem
            let token = await response.text();
            token = token.replace(/^"/, '').replace(/"$/, '');

            if (!token || token.length < 20) {
                console.error('❌ Token inválido recebido:', token);
                return { data: { user: null, session: null }, error: new Error('Resposta inválida do servidor') };
            }

            // Ler dados do token
            console.log('📦 Token bruto recebido:', token.substring(0, 20) + '...');
            const payload = parseJwt(token);
            if (!payload) {
                console.error('❌ Não foi possível extrair payload do token');
                return { data: { user: null, session: null }, error: new Error('Token inválido ou corrompido') };
            }

            console.log('👤 Dados do payload:', payload);

            const user: LocalUser = {
                id: payload.user_id,
                email: payload.email,
                role: payload.app_role || 'user',
                app_metadata: { role: payload.app_role },
                user_metadata: {},
                aud: 'authenticated',
                created_at: new Date().toISOString()
            };

            const session: LocalSession = {
                access_token: token,
                user,
                expires_at: payload.exp
            };

            console.log('✅ Login sucesso! User:', user.email, 'Role:', user.role);

            saveSession(session);
            authListeners.forEach(l => l('SIGNED_IN', session));

            return { data: { user, session }, error: null };
        } catch (e: any) {
            console.error('❌ Exceção no login:', e);
            return { data: { user: null, session: null }, error: e };
        }
    },

    async signOut() {
        saveSession(null);
        authListeners.forEach(l => l('SIGNED_OUT', null));
        return { error: null };
    },

    async getUser() {
        const session = loadSession();
        return { data: { user: session?.user || null }, error: null };
    },

    async getSession() {
        const session = loadSession();
        return { data: { session }, error: null };
    },

    onAuthStateChange(callback: (event: string, session: any) => void) {
        authListeners.push(callback);
        // Notificar estado atual imediatamente
        const session = loadSession();
        callback(session ? 'SIGNED_IN' : 'SIGNED_OUT', session);

        return {
            data: {
                subscription: {
                    unsubscribe: () => {
                        const idx = authListeners.indexOf(callback);
                        if (idx >= 0) authListeners.splice(idx, 1);
                    }
                }
            }
        };
    },

    async signUp({ email, password }: { email: string; password: string }) {
        try {
            console.log('📝 Tentando registro via RPC:', `${apiUrl}/rpc/register`);

            const response = await fetch(`${apiUrl}/rpc/register`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ p_email: email, p_password: password })
            });

            if (!response.ok) {
                const errorText = await response.text();
                return { data: { user: null }, error: new Error(errorText || 'Erro ao registrar usuário') };
            }

            const userId = (await response.text()).replace(/^"/, '').replace(/"$/, '');

            return {
                data: {
                    user: {
                        id: userId,
                        email: email
                    }
                },
                error: null
            };
        } catch (e: any) {
            console.error('❌ Exceção no registro:', e);
            return { data: { user: null }, error: e };
        }
    }
};

// Exportar cliente unificado
// Mantemos a estrutura parecida com supabase-js para minimizar refatoração
export const api = {
    ...onlifinClient,
    auth: {
        ...onlifinClient.auth, // Manter métodos originais como fallback se não sobescritos
        ...auth // Sobrescrever com nossa auth customizada
    },
    // Suporte a edge functions (stub para compatibilidade)
    functions: {
        invoke: async (name: string, options?: any) => {
            console.warn(`🚀 Chamada para Edge Function '${name}' ignorada (ambiente standalone).`, options);
            return { data: null, error: null };
        }
    },
    // Atalhos úteis
    from: (table: string) => onlifinClient.from(table),
    rpc: (fn: string, args?: any) => onlifinClient.rpc(fn, args)
};

// Compatibilidade retroativa (para mudar gradualmente)
export const supabase = api;
