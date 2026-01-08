/**
 * OnliFin Authentication System v3.0
 * 
 * Este módulo gerencia a autenticação, sessões por inatividade
 * e garante a integridade dos dados de acesso à API.
 */

(function () {
    'use strict';

    console.log('🔐 OnliFin Auth System v3.0 - Carregando...');

    // Configurações Globais
    const AUTH_TOKEN_KEY = 'onlifin_auth_token';
    const AUTH_USER_KEY = 'onlifin_user_data';
    const AUTH_ACTIVITY_KEY = 'onlifin_last_activity';
    const AUTH_SESSION_ID = 'onlifin_auth_session'; // Chave de compatibilidade interna do App
    const SESSION_TIMEOUT = 30 * 60 * 1000; // 30 minutos

    const originalFetch = window.fetch;

    /**
     * Limpa completamente o estado de login
     */
    function clearLogin() {
        localStorage.removeItem(AUTH_TOKEN_KEY);
        localStorage.removeItem(AUTH_USER_KEY);
        localStorage.removeItem(AUTH_ACTIVITY_KEY);
        localStorage.removeItem(AUTH_SESSION_ID);
        console.log('🚪 Sessão encerrada.');
    }

    /**
     * Redireciona para o login
     */
    function goToLogin() {
        if (window.location.pathname !== '/login') {
            window.location.href = '/login';
        }
    }

    /**
     * Atualiza o timestamp de última atividade
     */
    function updateActivity() {
        localStorage.setItem(AUTH_ACTIVITY_KEY, Date.now().toString());
    }

    /**
     * Verifica inatividade
     */
    function checkInactivity() {
        const lastActivity = localStorage.getItem(AUTH_ACTIVITY_KEY);
        if (lastActivity) {
            const inactiveTime = Date.now() - parseInt(lastActivity);
            if (inactiveTime > SESSION_TIMEOUT) {
                console.warn('⚠️ Sessão expirada por inatividade.');
                clearLogin();
                goToLogin();
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
     * Interceptação das chamadas de API
     */
    window.fetch = function (url, options = {}) {
        const urlStr = typeof url === 'string' ? url : (url.url || '');
        const isApiRequest = urlStr.includes('/api/rest/') || urlStr.includes('/api/rpc/');

        if (isApiRequest) {
            updateActivity();

            // Obter token atual
            let token = localStorage.getItem(AUTH_TOKEN_KEY);

            // Migração de Fallback
            if (!token) {
                const legacySession = localStorage.getItem(AUTH_SESSION_ID);
                if (legacySession) {
                    try {
                        const parsed = JSON.parse(legacySession);
                        token = parsed.access_token;
                        if (token) localStorage.setItem(AUTH_TOKEN_KEY, token);
                    } catch (e) { }
                }
            }

            // Injeção de Segurança em Requisições API
            if (token) {
                const payload = decodeJWT(token);
                const now = Math.floor(Date.now() / 1000);

                if (payload && payload.exp && payload.exp < now) {
                    console.warn('⚠️ Credencial expirada.');
                    clearLogin();
                    goToLogin();
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
                // Erros de autorização forçam logout
                if (isApiRequest && (response.status === 401 || response.status === 403)) {
                    console.error('❌ Falha na autorização da API.');
                    clearLogin();
                    goToLogin();
                }

                // Interceptar resposta de login para capturar credenciais
                if (urlStr.includes('/rpc/login')) {
                    const cloned = response.clone();
                    try {
                        const rawContent = await cloned.text();
                        const token = rawContent.replace(/^"/, "").replace(/"$/, "");

                        if (token && token.split('.').length === 3) {
                            const payload = decodeJWT(token);
                            if (payload) {
                                localStorage.setItem(AUTH_TOKEN_KEY, token);

                                // Construir sessão compatível com a lógica do App
                                const sessionData = {
                                    access_token: token,
                                    token_type: 'bearer',
                                    expires_in: 86400,
                                    refresh_token: token,
                                    user: {
                                        id: payload.user_id || payload.sub,
                                        email: payload.email,
                                        role: payload.app_role || 'user',
                                        app_metadata: { provider: 'onlifin', role: payload.app_role || 'user' },
                                        user_metadata: { full_name: payload.email.split('@')[0] },
                                        aud: 'authenticated',
                                        created_at: new Date().toISOString()
                                    },
                                    expires_at: payload.exp
                                };

                                localStorage.setItem(AUTH_SESSION_ID, JSON.stringify(sessionData));
                                localStorage.setItem(AUTH_USER_KEY, JSON.stringify(sessionData.user));

                                console.log('✅ Credenciais sincronizadas com sucesso');
                                updateActivity();
                            }
                        }
                    } catch (e) { }
                }

                return response;
            })
            .catch(error => {
                console.error('API Error:', error);
                throw error;
            });
    };

    // UI Patches na Tela de Login
    function fixLoginUI() {
        if (window.location.pathname !== '/login') return;

        const labels = document.querySelectorAll('label');
        labels.forEach(l => {
            if (l.textContent.includes('Nome de Usuário')) l.textContent = 'Email ou Usuário';
        });

        const inputs = document.querySelectorAll('input');
        inputs.forEach(i => {
            if (i.placeholder === 'Nome de Usuário' || i.name === 'username') {
                i.placeholder = 'Digite seu email ou usuário';
                i.removeAttribute('pattern');
                i.removeAttribute('title');
            }
        });

        const errorMsgs = document.querySelectorAll('p, span, div');
        errorMsgs.forEach(t => {
            if (t.textContent.includes('Apenas letras, números e underscore')) t.style.display = 'none';
        });

        // Interceptar submit
        const forms = document.querySelectorAll('form');
        forms.forEach(f => {
            if (!f.dataset.onlifinPatched) {
                f.addEventListener('submit', () => console.log('🚀 Processando acesso...'), true);
                f.dataset.onlifinPatched = 'true';
            }
        });
    }

    // Monitorar atividade
    ['mousedown', 'keydown', 'touchstart'].forEach(ev => window.addEventListener(ev, updateActivity));
    setInterval(checkInactivity, 60000);
    setInterval(fixLoginUI, 500);

    // Logout global
    window.onlifinSignOut = function () {
        clearLogin();
        goToLogin();
    };

    /**
     * Validação de Integridade da Sessão (Startup)
     */
    function validateSessionIntegrity() {
        const token = localStorage.getItem(AUTH_TOKEN_KEY);
        const session = localStorage.getItem(AUTH_SESSION_ID);

        if (session) {
            try {
                const parsed = JSON.parse(session);
                // Se a sessão existe mas não tem ID de usuário, está corrompida
                if (!parsed.user || !parsed.user.id || parsed.user.id === 'undefined') {
                    console.error('🚨 Sessão corrompida detectada (ID undefined). Limpando...');
                    clearLogin();
                    // Só redireciona se não estiver já no login
                    if (window.location.pathname !== '/login') window.location.reload();
                }
            } catch (e) {
                clearLogin();
            }
        }
    }

    updateActivity();
    checkInactivity();
    validateSessionIntegrity();

    console.log('✅ OnliFin Auth v3.1 Pronto (Integridade Ativa)');
})();
