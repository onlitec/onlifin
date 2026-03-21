// ===========================================
// Cliente API - Onlifin
// ===========================================
// Gerencia comunicação com a API PostgREST
// Substitui antigo cliente Supabase

import { createClient } from "@supabase/supabase-js";
import axios from 'axios';

const apiUrl = import.meta.env.VITE_SUPABASE_URL || window.location.origin + '/api';
const anonKey = import.meta.env.VITE_SUPABASE_ANON_KEY || 'anonymous-key-for-postgrest';

console.log('🌐 Configurando Cliente API:', apiUrl);

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

let cachedSession: LocalSession | null | undefined;
let cachedUser: LocalUser | null | undefined;

function setCachedAuthState(session: LocalSession | null) {
    cachedSession = session;
    cachedUser = session?.user || null;
}

function buildSessionFromToken(token: string): LocalSession | null {
    if (!token || token.split('.').length !== 3) {
        return null;
    }

    const payload = parseJwt(token);
    if (!payload?.user_id) {
        return null;
    }

    const user: LocalUser = {
        id: payload.user_id,
        email: payload.email,
        role: payload.app_role || 'user',
        app_metadata: {
            role: payload.app_role,
            account_admin: payload.account_admin,
            tenant_id: payload.tenant_id,
            status: payload.status,
            force_password_change: payload.force_password_change
        },
        user_metadata: {
            account_admin: payload.account_admin,
            tenant_id: payload.tenant_id,
            status: payload.status,
            force_password_change: payload.force_password_change
        },
        aud: 'authenticated',
        created_at: new Date().toISOString()
    };

    return {
        access_token: token,
        user,
        expires_at: payload.exp
    };
}

// Carregar sessão do localStorage
function loadSession(): LocalSession | null {
    if (cachedSession !== undefined) {
        return cachedSession;
    }

    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (!stored) {
            setCachedAuthState(null);
            return null;
        }

        const session = JSON.parse(stored);

        // Validar se o token parece válido (tem 3 partes separadas por ponto)
        if (session.access_token && session.access_token.split('.').length === 3) {
            // Validar expiração
            if (session.expires_at && session.expires_at < Math.floor(Date.now() / 1000)) {
                console.warn('⚠️ Sessão expirada em:', new Date(session.expires_at * 1000).toLocaleString());
                localStorage.removeItem(STORAGE_KEY);
                setCachedAuthState(null);
                return null;
            }
            setCachedAuthState(session);
            return session;
        } else {
            console.warn('⚠️ Token inválido detectado, limpando sessão...');
            localStorage.removeItem(STORAGE_KEY);
            setCachedAuthState(null);
            return null;
        }
    } catch (e) {
        console.error('Erro ao carregar sessão:', e);
        localStorage.removeItem(STORAGE_KEY);
        setCachedAuthState(null);
    }
    setCachedAuthState(null);
    return null;
}

// Sincronizar headers globais (para Axios e outros clientes legados)
function syncGlobalHeaders(token: string | null) {
    if (token) {
        console.log('🛰️ Sincronizando headers globais com token');

        // Axios
        if (axios && axios.defaults) {
            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        }

        // Headers globais do navegador (experimental/apenas para log)
        (window as any).__ONLIFIN_TOKEN = token;
    } else {
        console.log('🛰️ Limpando headers globais');
        if (axios && axios.defaults) {
            delete axios.defaults.headers.common['Authorization'];
        }
        delete (window as any).__ONLIFIN_TOKEN;
    }
}

// Salvar sessão no localStorage
function saveSession(session: LocalSession | null) {
    setCachedAuthState(session);

    if (session) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(session));
        syncGlobalHeaders(session.access_token);

        // Atualizar headers do cliente global se existir
        if (onlifinClient) {
            onlifinClient.auth.setSession({
                access_token: session.access_token,
                refresh_token: session.refresh_token || ''
            });
        }
    } else {
        localStorage.removeItem(STORAGE_KEY);
        syncGlobalHeaders(null);
        if (onlifinClient) {
            onlifinClient.auth.signOut();
        }
    }
}

export async function getCurrentSession(): Promise<LocalSession | null> {
    return loadSession();
}

export async function getCurrentUser(): Promise<LocalUser | null> {
    if (cachedUser !== undefined) {
        return cachedUser;
    }

    const session = loadSession();
    return session?.user || null;
}

export async function requireCurrentUser(): Promise<LocalUser> {
    const user = await getCurrentUser();

    if (!user) {
        throw new Error('Usuário não autenticado');
    }

    return user;
}

// Decodificar JWT (simples, apenas para ler payload)
function parseJwt(token: string) {
    try {
        const parts = token.split('.');
        if (parts.length !== 3) return null;

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
    const url = typeof input === 'string' ? input : (input as any).url || input.toString();

    // Só injetamos token se for requisição para nossa API
    const isApiRequest = url.includes('/api/rest/v1/') || url.includes('/rpc/');

    if (isApiRequest) {
        if (session?.access_token) {
            const headers = new Headers(options.headers);
            headers.set('Authorization', `Bearer ${session.access_token}`);
            options.headers = headers;
            // console.debug(`🚀 [AuthFetch] Token injetado para: ${url}`);
        } else {
            console.warn(`⚠️ [AuthFetch] Requisição API sem token: ${url}`);
        }
    }

    return fetch(input, options);
};

// Criar cliente base
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
    syncGlobalHeaders(initialSession.access_token);
    onlifinClient.auth.setSession({
        access_token: initialSession.access_token,
        refresh_token: initialSession.refresh_token || ''
    });
}

// Listeners de autenticação
const authListeners: ((event: string, session: any) => void)[] = [];

// Sistema de Auth Customizado
const auth = {
    async signInWithPassword({ email, password }: { email: string; password: string }) {
        try {
            console.log('🔐 Tentando login:', email);

            const response = await fetch(`${apiUrl}/rpc/login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ p_email: email, p_password: password })
            });

            if (!response.ok) {
                const errorText = (await response.text()).replace(/^"/, '').replace(/"$/, '').trim();
                return {
                    data: { user: null, session: null },
                    error: new Error(errorText || 'Credenciais inválidas ou erro no servidor')
                };
            }

            let token = await response.text();
            token = token.replace(/^"/, '').replace(/"$/, '');

            if (!token || token.length < 20) {
                return { data: { user: null, session: null }, error: new Error('Resposta inválida do servidor') };
            }

            const session = buildSessionFromToken(token);
            if (!session) {
                return { data: { user: null, session: null }, error: new Error('Token inválido ou corrompido') };
            }

            saveSession(session);
            authListeners.forEach(l => l('SIGNED_IN', session));

            return { data: { user: session.user, session }, error: null };
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

export function persistSessionFromToken(token: string) {
    const session = buildSessionFromToken(token);
    if (!session) {
        throw new Error('Token inválido ou corrompido');
    }

    saveSession(session);
    authListeners.forEach((listener) => listener('SIGNED_IN', session));
    return session;
}

// Objeto de exportação unificado com suporte a tudo que a aplicação usa
// Usamos Proxy para garantir que todos os métodos do onlifinClient (incluindo prototype) sejam acessíveis
export const api = new Proxy(onlifinClient, {
    get(target, prop, receiver) {
        // Interceptar 'auth' para usar a nossa implementação customizada
        if (prop === 'auth') {
            return {
                ...target.auth,
                ...auth
            };
        }

        // Interceptar 'functions'
        if (prop === 'functions') {
            return {
                invoke: async (name: string, _options?: any) => {
                    console.warn(`🚀 Edge Function '${name}' redirecionada para local/ignorada.`);
                    return { data: null, error: null };
                }
            };
        }

        // Delegar tudo o mais para o cliente supabase original
        const value = Reflect.get(target, prop, receiver);
        if (typeof value === 'function') {
            return value.bind(target);
        }
        return value;
    }
}) as any;

// Export padrão como 'supabase' para compatibilidade total com o código existente
export const supabase = api;
