/**
 * OnliFin Dashboard Enhancement - Contas a Pagar/Receber
 * Criando elementos programaticamente para garantir estilos
 */

(function () {
    'use strict';

    const API_BASE = '/api/rest/v1';

    function waitForDashboard() {
        return new Promise((resolve) => {
            const checkInterval = setInterval(() => {
                const isDashboard = window.location.pathname === '/' ||
                    window.location.pathname === '/dashboard' ||
                    window.location.pathname.includes('dashboard');

                const existingCards = document.querySelectorAll('[class*="grid"]');
                let targetContainer = null;

                for (const grid of existingCards) {
                    if (grid.textContent.includes('Saldo') ||
                        grid.textContent.includes('Receitas') ||
                        grid.textContent.includes('Despesas')) {
                        targetContainer = grid.parentElement;
                        break;
                    }
                }

                if (!targetContainer) {
                    targetContainer = document.querySelector('main') ||
                        document.querySelector('[class*="dashboard"]') ||
                        document.querySelector('#root > div > div:nth-child(2)');
                }

                if (isDashboard && targetContainer && !document.getElementById('onlifin-financial-section')) {
                    clearInterval(checkInterval);
                    resolve(targetContainer);
                }
            }, 500);
        });
    }

    function getUserId() {
        try {
            const authData = localStorage.getItem('onlifin-auth');
            if (authData) {
                const parsed = JSON.parse(authData);
                return parsed.user?.id || parsed.userId;
            }
        } catch (e) { }
        return null;
    }

    function formatCurrency(value) {
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
    }

    function formatDate(dateStr) {
        if (!dateStr) return '-';
        return new Date(dateStr + 'T00:00:00').toLocaleDateString('pt-BR');
    }

    async function fetchData(endpoint, params = {}) {
        const url = new URL(API_BASE + endpoint, window.location.origin);
        Object.entries(params).forEach(([k, v]) => url.searchParams.append(k, v));
        try {
            const res = await fetch(url);
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return await res.json();
        } catch (e) {
            console.error('OnliFin Enhancement - API Error:', e);
            return [];
        }
    }

    // Criar elemento com estilos forçados
    function createElement(tag, styles, content = '') {
        const el = document.createElement(tag);
        Object.entries(styles).forEach(([k, v]) => el.style.setProperty(k.replace(/([A-Z])/g, '-$1').toLowerCase(), v, 'important'));
        if (content) el.innerHTML = content;
        return el;
    }

    // Criar um card
    function createCard(type, label, icon, valueId, countId) {
        const colors = {
            pay: { border: 'linear-gradient(180deg, #ef4444, #f97316)', value: '#dc2626' },
            receive: { border: 'linear-gradient(180deg, #10b981, #34d399)', value: '#059669' },
            overdue: { border: 'linear-gradient(180deg, #f59e0b, #fbbf24)', value: '#d97706' },
            balance: { border: 'linear-gradient(180deg, #3b82f6, #8b5cf6)', value: '#2563eb' }
        };

        const card = createElement('div', {
            background: '#ffffff',
            backgroundColor: '#ffffff',
            borderRadius: '12px',
            padding: '20px',
            border: '1px solid #e2e8f0',
            boxShadow: '0 1px 3px rgba(0,0,0,0.08)',
            position: 'relative',
            overflow: 'hidden',
            minHeight: '100px'
        });

        // Barra lateral colorida
        const bar = createElement('div', {
            position: 'absolute',
            top: '0',
            left: '0',
            width: '4px',
            height: '100%',
            background: colors[type].border
        });
        card.appendChild(bar);

        // Header
        const header = createElement('div', {
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'flex-start',
            marginBottom: '12px',
            paddingLeft: '8px'
        });

        const labelEl = createElement('div', {
            color: '#64748b',
            fontSize: '13px',
            fontWeight: '500'
        }, label);

        const iconEl = createElement('div', { fontSize: '20px' }, icon);

        header.appendChild(labelEl);
        header.appendChild(iconEl);
        card.appendChild(header);

        // Valor
        const valueEl = createElement('div', {
            fontSize: '24px',
            fontWeight: '700',
            marginBottom: '4px',
            color: colors[type].value,
            paddingLeft: '8px'
        }, 'R$ 0,00');
        valueEl.id = valueId;
        card.appendChild(valueEl);

        // Contagem
        const countEl = createElement('div', {
            fontSize: '12px',
            color: '#94a3b8',
            paddingLeft: '8px'
        }, 'Carregando...');
        if (countId) countEl.id = countId;
        card.appendChild(countEl);

        return card;
    }

    // Criar seção financeira
    function createFinancialSection() {
        const section = createElement('div', {
            marginTop: '24px',
            padding: '0',
            width: '100%'
        });
        section.id = 'onlifin-financial-section';

        // Título
        const title = createElement('h2', {
            fontSize: '18px',
            fontWeight: '600',
            color: '#1e293b',
            marginBottom: '16px',
            display: 'flex',
            alignItems: 'center',
            gap: '10px',
            paddingLeft: '4px'
        });

        const titleBar = createElement('span', {
            width: '4px',
            height: '20px',
            background: 'linear-gradient(135deg, #3b82f6, #8b5cf6)',
            borderRadius: '2px',
            display: 'inline-block'
        });
        title.appendChild(titleBar);
        title.appendChild(document.createTextNode(' Contas a Pagar e Receber'));
        section.appendChild(title);

        // Grid de cards
        const cardsGrid = createElement('div', {
            display: 'grid',
            gridTemplateColumns: 'repeat(4, 1fr)',
            gap: '16px',
            marginBottom: '24px'
        });
        cardsGrid.id = 'onlifin-cards-grid';

        cardsGrid.appendChild(createCard('pay', 'Total a Pagar', '💸', 'onlifin-total-pay', 'onlifin-count-pay'));
        cardsGrid.appendChild(createCard('receive', 'Total a Receber', '💰', 'onlifin-total-receive', 'onlifin-count-receive'));
        cardsGrid.appendChild(createCard('overdue', 'Vencidas', '⚠️', 'onlifin-total-overdue', 'onlifin-count-overdue'));
        cardsGrid.appendChild(createCard('balance', 'Balanço Previsto', '📊', 'onlifin-balance', null));

        section.appendChild(cardsGrid);

        // Gráficos
        const chartsRow = createElement('div', {
            display: 'grid',
            gridTemplateColumns: 'repeat(2, 1fr)',
            gap: '16px',
            marginBottom: '24px'
        });

        const createChartCard = (title, canvasId) => {
            const card = createElement('div', {
                background: '#ffffff',
                backgroundColor: '#ffffff',
                borderRadius: '12px',
                padding: '20px',
                border: '1px solid #e2e8f0',
                boxShadow: '0 1px 3px rgba(0,0,0,0.08)'
            });

            const titleEl = createElement('div', {
                fontSize: '14px',
                fontWeight: '600',
                color: '#1e293b',
                marginBottom: '16px',
                display: 'flex',
                alignItems: 'center',
                gap: '8px'
            }, title);
            card.appendChild(titleEl);

            const container = createElement('div', {
                height: '220px',
                position: 'relative'
            });
            const canvas = document.createElement('canvas');
            canvas.id = canvasId;
            container.appendChild(canvas);
            card.appendChild(container);

            return card;
        };

        chartsRow.appendChild(createChartCard('📅 Vencimentos por Mês', 'onlifin-monthly-chart'));
        chartsRow.appendChild(createChartCard('📈 Fluxo de Caixa Previsto', 'onlifin-cashflow-chart'));
        section.appendChild(chartsRow);

        // Tabelas
        const tablesRow = createElement('div', {
            display: 'grid',
            gridTemplateColumns: 'repeat(2, 1fr)',
            gap: '16px'
        });

        const createTableCard = (title, tableId, color) => {
            const card = createElement('div', {
                background: '#ffffff',
                backgroundColor: '#ffffff',
                borderRadius: '12px',
                padding: '20px',
                border: '1px solid #e2e8f0',
                boxShadow: '0 1px 3px rgba(0,0,0,0.08)'
            });

            const titleEl = createElement('div', {
                fontSize: '14px',
                fontWeight: '600',
                marginBottom: '16px',
                display: 'flex',
                alignItems: 'center',
                gap: '8px',
                color: color
            }, title);
            card.appendChild(titleEl);

            const tableContainer = createElement('div', {});
            tableContainer.id = tableId;
            tableContainer.innerHTML = '<div style="text-align:center;padding:30px;color:#94a3b8;">Carregando...</div>';
            card.appendChild(tableContainer);

            return card;
        };

        tablesRow.appendChild(createTableCard('📋 Próximas Contas a Pagar', 'onlifin-pay-table', '#dc2626'));
        tablesRow.appendChild(createTableCard('📋 Próximas Contas a Receber', 'onlifin-receive-table', '#059669'));
        section.appendChild(tablesRow);

        // Adicionar responsividade
        const style = document.createElement('style');
        style.textContent = `
            @media (max-width: 1200px) { #onlifin-cards-grid { grid-template-columns: repeat(2, 1fr) !important; } }
            @media (max-width: 640px) { #onlifin-cards-grid { grid-template-columns: 1fr !important; } }
            @media (max-width: 900px) { 
                #onlifin-financial-section > div:nth-child(3),
                #onlifin-financial-section > div:nth-child(4) { grid-template-columns: 1fr !important; } 
            }
        `;
        document.head.appendChild(style);

        return section;
    }

    async function loadChartJS() {
        if (window.Chart) return Promise.resolve();
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js';
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    function renderTable(containerId, bills, type) {
        const container = document.getElementById(containerId);
        if (!container) return;

        const today = new Date();
        today.setHours(0, 0, 0, 0);

        if (!bills || bills.length === 0) {
            container.innerHTML = '<div style="text-align:center;padding:30px;color:#94a3b8;font-size:14px;">Nenhuma conta encontrada</div>';
            return;
        }

        const pendingBills = bills.filter(b => b.status !== 'paid').slice(0, 5);
        if (pendingBills.length === 0) {
            container.innerHTML = '<div style="text-align:center;padding:30px;color:#94a3b8;font-size:14px;">Todas as contas estão pagas! 🎉</div>';
            return;
        }

        const getStatus = (status, dueDate) => {
            const due = new Date(dueDate + 'T00:00:00');
            if (status === 'paid') return { label: 'Pago', bg: '#ecfdf5', color: '#059669' };
            if (due < today) return { label: 'Vencido', bg: '#fef2f2', color: '#dc2626' };
            return { label: 'Pendente', bg: '#eff6ff', color: '#2563eb' };
        };

        const table = document.createElement('table');
        table.style.cssText = 'width:100%;border-collapse:collapse;';

        const thead = document.createElement('thead');
        thead.innerHTML = `<tr>
            <th style="text-align:left;padding:10px 8px;border-bottom:1px solid #f1f5f9;font-size:11px;color:#64748b;font-weight:500;text-transform:uppercase;background:#f8fafc;">Descrição</th>
            <th style="text-align:left;padding:10px 8px;border-bottom:1px solid #f1f5f9;font-size:11px;color:#64748b;font-weight:500;text-transform:uppercase;background:#f8fafc;">Valor</th>
            <th style="text-align:left;padding:10px 8px;border-bottom:1px solid #f1f5f9;font-size:11px;color:#64748b;font-weight:500;text-transform:uppercase;background:#f8fafc;">Vencimento</th>
            <th style="text-align:left;padding:10px 8px;border-bottom:1px solid #f1f5f9;font-size:11px;color:#64748b;font-weight:500;text-transform:uppercase;background:#f8fafc;">Status</th>
        </tr>`;
        table.appendChild(thead);

        const tbody = document.createElement('tbody');
        pendingBills.forEach(bill => {
            const status = getStatus(bill.status, bill.due_date);
            const amountColor = type === 'receive' ? '#059669' : '#dc2626';
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="text-align:left;padding:10px 8px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#334155;">${bill.description || '-'}</td>
                <td style="text-align:left;padding:10px 8px;border-bottom:1px solid #f1f5f9;font-size:13px;color:${amountColor};font-weight:600;">${formatCurrency(bill.amount)}</td>
                <td style="text-align:left;padding:10px 8px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#334155;">${formatDate(bill.due_date)}</td>
                <td style="text-align:left;padding:10px 8px;border-bottom:1px solid #f1f5f9;font-size:13px;"><span style="display:inline-block;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:500;background:${status.bg};color:${status.color};">${status.label}</span></td>
            `;
            tbody.appendChild(tr);
        });
        table.appendChild(tbody);

        container.innerHTML = '';
        container.appendChild(table);
    }

    function calculateStats(billsToPay, billsToReceive) {
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const pendingToPay = billsToPay.filter(b => b.status !== 'paid');
        const pendingToReceive = billsToReceive.filter(b => b.status !== 'paid');

        const totalPay = pendingToPay.reduce((sum, b) => sum + parseFloat(b.amount || 0), 0);
        const totalReceive = pendingToReceive.reduce((sum, b) => sum + parseFloat(b.amount || 0), 0);

        const overduePay = pendingToPay.filter(b => new Date(b.due_date + 'T00:00:00') < today);
        const overdueReceive = pendingToReceive.filter(b => new Date(b.due_date + 'T00:00:00') < today);
        const totalOverdue = [...overduePay, ...overdueReceive].reduce((sum, b) => sum + parseFloat(b.amount || 0), 0);
        const overdueCount = overduePay.length + overdueReceive.length;

        const setEl = (id, text, color) => {
            const el = document.getElementById(id);
            if (el) {
                el.textContent = text;
                if (color) el.style.color = color;
            }
        };

        setEl('onlifin-total-pay', formatCurrency(totalPay));
        setEl('onlifin-count-pay', `${pendingToPay.length} conta${pendingToPay.length !== 1 ? 's' : ''} pendente${pendingToPay.length !== 1 ? 's' : ''}`);
        setEl('onlifin-total-receive', formatCurrency(totalReceive));
        setEl('onlifin-count-receive', `${pendingToReceive.length} conta${pendingToReceive.length !== 1 ? 's' : ''} pendente${pendingToReceive.length !== 1 ? 's' : ''}`);
        setEl('onlifin-total-overdue', formatCurrency(totalOverdue));
        setEl('onlifin-count-overdue', `${overdueCount} conta${overdueCount !== 1 ? 's' : ''} vencida${overdueCount !== 1 ? 's' : ''}`);

        const balance = totalReceive - totalPay;
        setEl('onlifin-balance', formatCurrency(balance), balance >= 0 ? '#059669' : '#dc2626');
    }

    function renderCharts(billsToPay, billsToReceive) {
        if (!window.Chart) return;

        const pendingToPay = billsToPay.filter(b => b.status !== 'paid');
        const pendingToReceive = billsToReceive.filter(b => b.status !== 'paid');

        const months = {};
        [...pendingToPay.map(b => ({ ...b, type: 'pay' })), ...pendingToReceive.map(b => ({ ...b, type: 'receive' }))]
            .forEach(bill => {
                const date = new Date(bill.due_date + 'T00:00:00');
                const monthKey = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
                if (!months[monthKey]) months[monthKey] = { pay: 0, receive: 0 };
                months[monthKey][bill.type] += parseFloat(bill.amount || 0);
            });

        const sortedMonths = Object.keys(months).sort().slice(0, 6);
        const monthLabels = sortedMonths.map(m => {
            const [year, month] = m.split('-');
            return new Date(year, month - 1).toLocaleDateString('pt-BR', { month: 'short' });
        });

        const monthlyCtx = document.getElementById('onlifin-monthly-chart');
        if (monthlyCtx) {
            new Chart(monthlyCtx, {
                type: 'bar',
                data: {
                    labels: monthLabels.length ? monthLabels : ['Sem dados'],
                    datasets: [
                        { label: 'A Pagar', data: sortedMonths.map(m => months[m]?.pay || 0), backgroundColor: 'rgba(220, 38, 38, 0.8)', borderRadius: 6 },
                        { label: 'A Receber', data: sortedMonths.map(m => months[m]?.receive || 0), backgroundColor: 'rgba(5, 150, 105, 0.8)', borderRadius: 6 }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { labels: { color: '#475569', font: { size: 11 } } } },
                    scales: { x: { ticks: { color: '#64748b' }, grid: { color: '#f1f5f9' } }, y: { ticks: { color: '#64748b' }, grid: { color: '#f1f5f9' } } }
                }
            });
        }

        const cashflowCtx = document.getElementById('onlifin-cashflow-chart');
        if (cashflowCtx) {
            const cashflowData = sortedMonths.map(m => (months[m]?.receive || 0) - (months[m]?.pay || 0));
            new Chart(cashflowCtx, {
                type: 'line',
                data: {
                    labels: monthLabels.length ? monthLabels : ['Sem dados'],
                    datasets: [{
                        label: 'Fluxo de Caixa', data: cashflowData, borderColor: '#3b82f6', backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true, tension: 0.4,
                        pointBackgroundColor: cashflowData.map(v => v >= 0 ? '#059669' : '#dc2626'),
                        pointBorderColor: cashflowData.map(v => v >= 0 ? '#059669' : '#dc2626'), pointRadius: 6
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { labels: { color: '#475569', font: { size: 11 } } } },
                    scales: { x: { ticks: { color: '#64748b' }, grid: { color: '#f1f5f9' } }, y: { ticks: { color: '#64748b' }, grid: { color: '#f1f5f9' }, beginAtZero: true } }
                }
            });
        }
    }

    function findInsertionPoint(container) {
        const grids = container.querySelectorAll('[class*="grid"]');
        for (const grid of grids) {
            if (grid.textContent.includes('Saldo') || grid.textContent.includes('Receitas') || grid.textContent.includes('Despesas') || grid.textContent.includes('Poupança')) {
                return { element: grid, position: 'after' };
            }
        }
        return { element: container, position: 'append' };
    }

    async function init() {
        console.log('🚀 OnliFin Dashboard Enhancement v2 - Iniciando...');

        try {
            const container = await waitForDashboard();
            await loadChartJS();

            const section = createFinancialSection();
            const insertPoint = findInsertionPoint(container);

            if (insertPoint.position === 'after') {
                insertPoint.element.insertAdjacentElement('afterend', section);
            } else {
                container.appendChild(section);
            }

            console.log('✅ OnliFin Enhancement - Seção adicionada');

            const userId = getUserId();
            const params = userId ? { 'user_id': `eq.${userId}` } : {};

            const [billsToPay, billsToReceive] = await Promise.all([
                fetchData('/bills_to_pay', { ...params, 'order': 'due_date.asc' }),
                fetchData('/bills_to_receive', { ...params, 'order': 'due_date.asc' })
            ]);

            calculateStats(billsToPay, billsToReceive);
            renderTable('onlifin-pay-table', billsToPay, 'pay');
            renderTable('onlifin-receive-table', billsToReceive, 'receive');
            renderCharts(billsToPay, billsToReceive);

            console.log('✅ OnliFin Enhancement - Dados carregados');

        } catch (error) {
            console.error('❌ OnliFin Enhancement - Erro:', error);
        }
    }

    let lastPath = window.location.pathname;
    const routeObserver = new MutationObserver(() => {
        if (window.location.pathname !== lastPath) {
            lastPath = window.location.pathname;
            const existing = document.getElementById('onlifin-financial-section');
            if (existing) existing.remove();
            if (lastPath === '/' || lastPath === '/dashboard' || lastPath.includes('dashboard')) {
                setTimeout(init, 500);
            }
        }
    });

    routeObserver.observe(document.body, { childList: true, subtree: true });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        setTimeout(init, 100);
    }

})();
