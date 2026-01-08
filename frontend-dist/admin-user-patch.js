/**
 * OnliFin Advanced Administrative Management
 * 
 * Este módulo provê ferramentas avançadas para gestão de usuários,
 * roles e auditoria do sistema OnliFin.
 */

(function () {
    'use strict';

    console.log('👷 OnliFin Admin Tools v3.0 - Ativo');

    // --- Core Methods ---
    function getAuthToken() {
        try {
            const token = localStorage.getItem('onlifin_auth_token');
            if (token) return token;

            const session = localStorage.getItem('onlifin_auth_session');
            if (session) return JSON.parse(session).access_token;
            return null;
        } catch (e) { return null; }
    }

    async function execAdminAction(rpcName, payload = {}) {
        const token = getAuthToken();
        const response = await fetch(`${window.location.origin}/api/rpc/${rpcName}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify(payload)
        });
        const result = await response.json();
        if (result.code || result.error) throw new Error(result.message || result.error || 'Erro operacional');
        return result;
    }

    // --- Admin Actions ---

    window.onlifinAdmin = {
        resetUserPassword: async (uid) => {
            const pwd = prompt('Nova senha (mínimo 6 caracteres):');
            if (!pwd) return;
            try {
                await execAdminAction('admin_reset_password', { p_user_id: uid, p_new_password: pwd });
                alert('✅ Senha atualizada.');
            } catch (e) { alert('❌ ' + e.message); }
        },
        removeUser: async (uid, email) => {
            if (!confirm(`⚠️ Confirmar exclusão definitiva do usuário: ${email}?`)) return;
            try {
                await execAdminAction('admin_delete_user', { p_user_id: uid });
                alert('✅ Usuário removido.');
                window.location.reload();
            } catch (e) { alert('❌ ' + e.message); }
        }
    };

    // --- UI Logic ---

    function updateManagementPage() {
        if (window.location.pathname !== '/user-management') return;

        // Corrigir formulários de criação
        const modal = document.querySelector('[role="dialog"]');
        if (modal) {
            modal.querySelectorAll('label').forEach(l => {
                if (l.textContent.includes('Nome de Usuário')) l.textContent = 'Email de Acesso';
            });
            modal.querySelectorAll('input').forEach(i => {
                if (i.placeholder.includes('usuário')) i.placeholder = 'exemplo@onlifin.com';
            });
        }

        // Adicionar ferramenta de gestão rápida se não houver
        if (!document.getElementById('onlifin-mgmt-panel')) {
            const qBtn = document.createElement('button');
            qBtn.id = 'onlifin-mgmt-panel';
            qBtn.textContent = '⚙️ Administração';
            qBtn.style.position = 'fixed';
            qBtn.style.bottom = '20px';
            qBtn.style.right = '20px';
            qBtn.style.zIndex = '9999';
            qBtn.className = 'px-5 py-3 bg-indigo-600 text-white rounded-full shadow-2xl font-bold hover:bg-indigo-700 transition transform hover:scale-105';

            qBtn.onclick = async () => {
                try {
                    const users = await execAdminAction('admin_list_users');
                    const userList = users.map((u, i) => `${i + 1}. ${u.email} [${u.role}]`).join('\n');
                    const selection = prompt(`Selecione um usuário para gerenciar:\n\n${userList}\n\nDigite o número correspondente:`);

                    if (selection && users[selection - 1]) {
                        const user = users[selection - 1];
                        const cmd = prompt(`Gerenciar: ${user.email}\n1. Alterar Senha\n2. Promover a ADMIN\n3. Rebaixar para USER\n4. REMOVER USUÁRIO`);

                        if (cmd === '1') window.onlifinAdmin.resetUserPassword(user.id);
                        if (cmd === '2') {
                            await execAdminAction('admin_update_user', { p_user_id: user.id, p_email: user.email, p_full_name: user.full_name, p_role: 'admin' });
                            alert('Promovido!');
                        }
                        if (cmd === '3') {
                            await execAdminAction('admin_update_user', { p_user_id: user.id, p_email: user.email, p_full_name: user.full_name, p_role: 'user' });
                            alert('Rebaixado!');
                        }
                        if (cmd === '4') window.onlifinAdmin.removeUser(user.id, user.email);
                    }
                } catch (e) { alert(e.message); }
            };
            document.body.appendChild(qBtn);
        }
    }

    setInterval(updateManagementPage, 1000);

})();
