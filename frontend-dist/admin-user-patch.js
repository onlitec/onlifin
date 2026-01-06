/**
 * OnliFin Admin User Creation Patch
 * Este script corrige a criação de usuários no painel admin,
 * interceptando a chamada e usando a função RPC admin_create_user
 * 
 * Versão: 1.1
 */

(function () {
    'use strict';

    console.log('[Admin Patch v1.1] Carregando patch de criação de usuários...');

    // Função para obter o token JWT do localStorage
    function getAuthToken() {
        try {
            const session = localStorage.getItem('onlifin_auth_session');
            if (session) {
                const parsed = JSON.parse(session);
                return parsed.access_token;
            }
        } catch (e) {
            console.error('[Admin Patch] Erro ao obter token:', e);
        }
        return null;
    }

    // Função para criar usuário via RPC
    async function createUserViaRPC(username, password, fullName, role) {
        const token = getAuthToken();
        if (!token) {
            throw new Error('Não autenticado. Faça login novamente.');
        }

        const apiUrl = window.location.origin + '/api/rpc/admin_create_user';

        console.log('[Admin Patch] Chamando API:', apiUrl);
        console.log('[Admin Patch] Dados:', { username, fullName, role });

        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                p_username: username,
                p_password: password,
                p_full_name: fullName || username,
                p_role: role || 'user'
            })
        });

        const responseText = await response.text();
        console.log('[Admin Patch] Response status:', response.status);
        console.log('[Admin Patch] Response:', responseText);

        let result;
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            // Se não for JSON, pode ser o resultado direto
            if (response.ok) {
                result = { success: true, message: 'Usuário criado' };
            } else {
                throw new Error(`Erro na API: ${response.status} - ${responseText}`);
            }
        }

        if (result && result.success === false) {
            throw new Error(result.error || 'Erro desconhecido ao criar usuário');
        }

        return {
            userId: result.user_id,
            username: result.username || username,
            message: result.message || 'Usuário criado com sucesso!'
        };
    }

    // Expor função globalmente
    window.onlifinAdminCreateUser = createUserViaRPC;

    // Interceptar o botão de criar usuário no modal
    function setupFormInterception() {
        document.addEventListener('click', async function (e) {
            const target = e.target;

            // Verificar se é o botão de criar usuário
            const isCreateButton =
                (target.tagName === 'BUTTON' && target.textContent.includes('Criar Usuário')) ||
                (target.closest && target.closest('button') && target.closest('button').textContent.includes('Criar Usuário'));

            if (isCreateButton) {
                console.log('[Admin Patch] Botão Criar Usuário clicado!');

                // Encontrar o formulário/modal
                const modal = target.closest('[role="dialog"]') || target.closest('.modal') || target.closest('form');

                if (modal) {
                    e.preventDefault();
                    e.stopPropagation();

                    // Buscar os campos do formulário
                    const usernameInput = modal.querySelector('input[name="username"], input[placeholder*="usuário"], input[id*="username"]');
                    const passwordInput = modal.querySelector('input[type="password"], input[name="password"]');
                    const fullNameInput = modal.querySelector('input[name="full_name"], input[name="fullName"], input[placeholder*="nome completo"], input[placeholder*="Nome Completo"]');
                    const roleSelect = modal.querySelector('select[name="role"], select[name="papel"], [data-role-select]');

                    // Tentar campos alternativos
                    const allInputs = modal.querySelectorAll('input');
                    let username = usernameInput?.value || allInputs[0]?.value;
                    let password = passwordInput?.value || Array.from(allInputs).find(i => i.type === 'password')?.value;
                    let fullName = fullNameInput?.value || Array.from(allInputs).find(i => i.placeholder?.toLowerCase().includes('nome'))?.value;
                    let role = roleSelect?.value || 'user';

                    console.log('[Admin Patch] Dados do formulário:', { username, fullName, role });

                    if (!username) {
                        alert('Por favor, preencha o nome de usuário.');
                        return false;
                    }

                    if (!password || password.length < 6) {
                        alert('A senha deve ter no mínimo 6 caracteres.');
                        return false;
                    }

                    try {
                        const result = await createUserViaRPC(username, password, fullName, role);
                        alert(result.message || 'Usuário criado com sucesso!');

                        // Fechar o modal e recarregar
                        const closeBtn = modal.querySelector('[aria-label="Close"], button[data-dismiss], .close-button');
                        if (closeBtn) closeBtn.click();

                        // Recarregar após um pequeno delay
                        setTimeout(() => window.location.reload(), 500);
                    } catch (error) {
                        console.error('[Admin Patch] Erro:', error);
                        alert('Erro ao criar usuário: ' + error.message);
                    }

                    return false;
                }
            }
        }, true);  // Use capture phase para interceptar antes

        console.log('[Admin Patch] Interceptação de formulário configurada!');
    }

    // Também monkeypatch o objeto H.auth.signUp se existir
    function patchSupabaseClient() {
        if (typeof H !== 'undefined' && H.auth) {
            console.log('[Admin Patch] Patcheando H.auth.signUp...');

            const originalSignUp = H.auth.signUp;

            H.auth.signUp = async function (params) {
                console.log('[Admin Patch] H.auth.signUp interceptado!', params);

                try {
                    const email = params.email || '';
                    const password = params.password || '';

                    // Extrair username do email
                    const username = email.split('@')[0];

                    const result = await createUserViaRPC(username, password, username, 'user');

                    return {
                        data: {
                            user: { id: result.userId, email: email },
                            session: null
                        },
                        error: null
                    };
                } catch (error) {
                    console.error('[Admin Patch] Erro no signUp:', error);
                    return {
                        data: { user: null, session: null },
                        error: error
                    };
                }
            };

            console.log('[Admin Patch] H.auth.signUp patcheado!');
        } else {
            // Tentar novamente após um delay
            setTimeout(patchSupabaseClient, 500);
        }
    }

    // Inicializar
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setupFormInterception();
            patchSupabaseClient();
        });
    } else {
        setupFormInterception();
        patchSupabaseClient();
    }

    // Retry após delays para garantir que o app React carregou
    setTimeout(patchSupabaseClient, 1000);
    setTimeout(patchSupabaseClient, 2000);
    setTimeout(patchSupabaseClient, 5000);

    console.log('[Admin Patch v1.1] Patch carregado com sucesso!');
})();
