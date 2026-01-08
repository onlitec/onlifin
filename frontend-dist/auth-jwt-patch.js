/**
 * OnliFin JWT Authentication Patch v2.0
 * 
 * Este patch gerencia a autenticação JWT, sessões por inatividade
 * e garante que o usuário consiga deslogar se houver erros.
 */

(function () {
    'use strict';

    console.log('🔐 OnliFin JWT Auth Patch v2.0 - Carregando...');

    // Configurações
    const TOKEN_KEY = 'onlifin_jwt_token';
    const USER_DATA_KEY = 'onlifin_user_data';
    const LAST_ACTIVITY_KEY = 'onlifin_last_activity';
    const SESSION_TIMEOUT = 30 * 60 * 1000; // 30 minutos de inatividade
    const AUTH_SESSION_KEY = 'onlifin_auth_session'; // Chave usada pelo Supabase Client do app

    // Armazenar fetch original
    const originalFetch = window.fetch;

    /**
     * Limpa completamente o estado de login
     */
    function clearAuth() {
        localStorage.removeItem(TOKEN_KEY);
        localStorage.removeItem(USER_DATA_KEY);
        localStorage.removeItem(LAST_ACTIVITY_KEY);
        localStorage.removeItem(AUTH_SESSION_KEY);
        console.log('🚪 Sessão encerrada e dados limpos.');
    }

    /**
     * Redireciona para o login
     */
    function redirectToLogin() {
        if (window.location.pathname !== '/login') {
            console.log('🔄 Redirecionando para login...');
            window.location.href = '/login';
        }
    }

    /**
     * Atualiza o timestamp de última atividade
     */
    function updateActivity() {
        localStorage.setItem(LAST_ACTIVITY_KEY, Date.now().toString());
    }

    /**
     * Verifica inatividade
     */
    function checkInactivity() {
        const lastActivity = localStorage.getItem(LAST_ACTIVITY_KEY);
        if (lastActivity) {
            const inactiveTime = Date.now() - parseInt(lastActivity);
            if (inactiveTime > SESSION_TIMEOUT) {
                console.warn('⚠️ Sessão expirada por inatividade.');
                clearAuth();
                redirectToLogin();
            }
        }
    }

    /**
     * Decodifica o payload do JWT
     */
    function decodeJWT(token) {
        try {
            const parts = token.split('.');
            if (parts.length !== 3) return null;
            return JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')));
        } catch (e) {
            return null;
        }
    }

    /**
     * Interceptação do Fetch
     */
    window.fetch = function (url, options = {}) {
        const urlStr = typeof url === 'string' ? url : (url.url || '');
        const isApiRequest = urlStr.includes('/api/rest/') || urlStr.includes('/api/rpc/');

        if (isApiRequest) {
            updateActivity();

            // Garantir que temos o token
            let token = localStorage.getItem(TOKEN_KEY);

            // Se não temos no nosso local, mas tem na sessão do app, tenta migrar
            if (!token) {
                const appSession = localStorage.getItem(AUTH_SESSION_KEY);
                if (appSession) {
                    try {
                        const parsed = JSON.parse(appSession);
                        token = parsed.access_token;
                        if (token) localStorage.setItem(TOKEN_KEY, token);
                    } catch (e) { }
                }
            }

            // Injetar Header de Autorização
            if (token) {
                const payload = decodeJWT(token);
                const now = Math.floor(Date.now() / 1000);

                if (payload && payload.exp && payload.exp < now) {
                    console.warn('⚠️ Token expirado.');
                    clearAuth();
                    redirectToLogin();
                    return Promise.reject(new Error('Sessão expirada'));
                }

                options.headers = options.headers || {};
                if (options.headers instanceof Headers) {
                    options.headers.set('Authorization', `Bearer ${token}`);
                } else {
                    options.headers['Authorization'] = `Bearer ${token}`;
                }
            }
        }

        return originalFetch(url, options)
            .then(async response => {
                // Se houver erro de permissão ou autenticação na API
                if (isApiRequest && (response.status === 401 || response.status === 403)) {
                    console.error(`❌ Erro ${response.status} na API. Deslogando...`);
                    clearAuth();
                    redirectToLogin();
                }

                // Interceptar resposta de login
                if (urlStr.includes('/rpc/login')) {
                    const cloned = response.clone();
                    try {
                        const data = await cloned.text();
                        // O login agora retorna o token limpo entre aspas
                        const token = data.replace(/^"/, "").replace(/"$/, "");
                        if (token && token.length > 50) {
                            localStorage.setItem(TOKEN_KEY, token);
                            updateActivity();
                        }
                    } catch (e) { }
                }

                return response;
            })
            .catch(error => {
                console.error('Fetch error:', error);
                throw error;
            });
    };

    // Monitorar eventos do usuário para resetar o timer de inatividade
    ['mousedown', 'keydown', 'touchstart', 'scroll'].forEach(event => {
        window.addEventListener(event, updateActivity);
    });

    // Verificar inatividade a cada minuto
    setInterval(checkInactivity, 60000);

    // Botão de Logout de Emergência se o app travar
    window.forceLogout = function () {
        clearAuth();
        redirectToLogin();
    };

    // Verificação inicial
    updateActivity();
    checkInactivity();

    console.log('✅ OnliFin JWT Auth Patch v2.0 Ativo');
    console.log('💡 DICA: Use forceLogout() no console se o estado estiver travado.');

})();
