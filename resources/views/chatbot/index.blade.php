<x-app-layout>
    {{-- ATENÇÃO: CORREÇÃO CRÍTICA no Chatbot; NÃO ALTERAR SEM AUTORIZAÇÃO EXPLÍCITA. --}}
    @isset($config)
        <div class="container mx-auto px-4 py-2">
            <p class="text-sm text-gray-600">Provedor de IA: <strong>{{ ucfirst($config['provider'] ?? 'Desconhecido') }}</strong></p>
        </div>
    @endisset
    <div class="w-full min-h-screen bg-gradient-to-br from-blue-50 to-gray-100 py-10 flex flex-col items-center">
        <div class="w-full max-w-2xl bg-white rounded-2xl shadow-2xl p-0 flex flex-col h-[80vh] overflow-hidden">
            <!-- Header -->
            <div class="flex items-center justify-between px-8 py-4 border-b bg-gradient-to-r from-blue-600 to-blue-400">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-full shadow flex items-center justify-center bg-white/20">
                        <i class="ri-robot-line text-white text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-white">Assistente Financeiro IA</h2>
                        @php
                            $chatbotConfig = app('App\Services\AIConfigService')->getChatbotConfig();
                        @endphp
                        @if($chatbotConfig && $chatbotConfig['enabled'])
                            <p class="text-xs text-blue-100">{{ ucfirst($chatbotConfig['provider']) }} ({{ $chatbotConfig['model'] }})</p>
                        @else
                            <p class="text-xs text-blue-100">Google Cloud ({{ ucfirst($config['model'] ?? 'Desconhecido') }})</p>
                        @endif
                    </div>
                </div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-white/20 text-white animate-pulse">
                    <i class="ri-flashlight-line mr-1"></i> IA Ativa
                </span>
            </div>

            <!-- Chat Window -->
            <div id="chat-window" class="flex-1 overflow-y-auto px-8 py-4 bg-gray-50 flex flex-col gap-2" style="min-height: 300px;">
                <div class="text-gray-400 text-center">Inicie uma conversa ou use as opções abaixo para explorar as funções da IA financeira do Google.</div>
            </div>

            <!-- Quick Actions Bar -->
            <div class="flex flex-wrap gap-2 px-8 py-2 border-t bg-white/80">
                <button class="quick-action" data-action="import-extract"><i class="ri-file-upload-line mr-1"></i> Importar Extrato</button>
                <button class="quick-action" data-action="analyze-coupon"><i class="ri-receipt-2-line mr-1"></i> Analisar Cupom Fiscal</button>
                <button class="quick-action" data-action="generate-graph"><i class="ri-bar-chart-grouped-line mr-1"></i> Gerar Gráfico</button>
                <button class="quick-action" data-action="predict"><i class="ri-lightbulb-flash-line mr-1"></i> Previsão Financeira</button>
                <button class="quick-action" data-action="insight"><i class="ri-search-eye-line mr-1"></i> Análise de Gastos</button>
                <button class="quick-action" data-action="suggestion"><i class="ri-question-answer-line mr-1"></i> Sugestão de Pergunta</button>
            </div>

            <!-- File Upload Preview (hidden by default) -->
            <div id="file-preview" class="px-8 py-2 hidden bg-blue-50 border-t border-b flex items-center gap-3">
                <i class="ri-attachment-2 text-blue-600 text-xl"></i>
                <span id="file-preview-name" class="text-sm text-blue-800"></span>
                <button id="remove-file" class="ml-auto text-xs text-red-500 hover:underline">Remover</button>
            </div>

            <!-- Chat Form -->
            <form id="chat-form" class="flex gap-2 px-8 py-4 border-t bg-white">
                <input type="text" id="chat-input" class="form-input flex-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Digite sua pergunta ou escolha uma ação..." autocomplete="off">
                <input type="file" id="chat-attachment" class="hidden" accept=".csv,.ofx,.qfx,.qif,.pdf,.xls,.xlsx,.txt,.jpg,.jpeg,.png,.gif">
                <button type="button" id="attach-button" class="btn btn-secondary px-3"><i class="ri-attachment-2"></i></button>
                <button type="submit" class="btn btn-primary px-6" id="send-button">Enviar</button>
            </form>
        </div>
    </div>

    <!-- Chart Modal -->
    <div id="chart-modal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl p-6 w-full max-w-lg relative">
            <button id="close-chart-modal" class="absolute top-2 right-2 text-gray-400 hover:text-red-500"><i class="ri-close-line text-2xl"></i></button>
            <h3 class="text-lg font-bold mb-4">Gráfico Gerado pela IA</h3>
            <canvas id="chatbot-chart" class="w-full h-64"></canvas>
        </div>
    </div>

    <style>
        .quick-action {
            @apply bg-blue-100 hover:bg-blue-200 text-blue-700 px-3 py-1 rounded-lg text-xs font-medium flex items-center transition;
        }
        .bot-card {
            @apply bg-blue-50 border border-blue-100 rounded-lg p-4 mb-2 shadow-sm;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    const chatWindow = document.getElementById('chat-window');
    const chatForm = document.getElementById('chat-form');
    const chatInput = document.getElementById('chat-input');
    const sendButton = document.getElementById('send-button');
    const attachButton = document.getElementById('attach-button');
    const chatAttachment = document.getElementById('chat-attachment');
    const filePreview = document.getElementById('file-preview');
    const filePreviewName = document.getElementById('file-preview-name');
    const removeFileBtn = document.getElementById('remove-file');
    const chartModal = document.getElementById('chart-modal');
    const closeChartModal = document.getElementById('close-chart-modal');
    const chatbotChart = document.getElementById('chatbot-chart');

    let attachedFile = null;
    let chartInstance = null;

    // Quick Actions
    document.querySelectorAll('.quick-action').forEach(btn => {
        btn.addEventListener('click', function() {
            const action = this.dataset.action;
            switch(action) {
                case 'import-extract':
                    chatAttachment.click();
                    break;
                case 'analyze-coupon':
                    chatInput.value = 'Quero analisar um cupom fiscal.';
                    chatInput.focus();
                    break;
                case 'generate-graph':
                    chatInput.value = 'Gerar gráfico de gastos por categoria.';
                    chatInput.focus();
                    break;
                case 'predict':
                    chatInput.value = 'Me mostre uma previsão financeira para o próximo mês.';
                    chatInput.focus();
                    break;
                case 'insight':
                    chatInput.value = 'Análise de gastos e sugestões de economia.';
                    chatInput.focus();
                    break;
                case 'suggestion':
                    chatInput.value = 'Quais perguntas posso fazer?';
                    chatInput.focus();
                    break;
            }
        });
    });

    // File attachment logic
    attachButton.addEventListener('click', () => chatAttachment.click());
    chatAttachment.addEventListener('change', function(e) {
        attachedFile = this.files[0] || null;
        if (attachedFile) {
            filePreview.classList.remove('hidden');
            filePreviewName.textContent = attachedFile.name;
        } else {
            filePreview.classList.add('hidden');
            filePreviewName.textContent = '';
        }
    });
    if (removeFileBtn) {
        removeFileBtn.addEventListener('click', function() {
            chatAttachment.value = '';
            attachedFile = null;
            filePreview.classList.add('hidden');
            filePreviewName.textContent = '';
        });
    }

    // Chart modal logic
    if (closeChartModal) {
        closeChartModal.addEventListener('click', function() {
            chartModal.classList.add('hidden');
            if (chartInstance) { chartInstance.destroy(); }
        });
    }

    // Append message to chat
    function appendMessage(content, sender = 'user', type = 'text') {
        const msg = document.createElement('div');
        msg.className = sender === 'user' ? 'text-right mb-2' : 'text-left mb-2';
        if (type === 'card') {
            msg.innerHTML = `<div class='bot-card'>${content}</div>`;
        } else if (type === 'graph') {
            msg.innerHTML = `<button class='underline text-blue-600 text-sm' onclick='window.showChatbotChart(${JSON.stringify(content)})'>Clique para ver o gráfico gerado</button>`;
        } else {
            msg.innerHTML = `<span class="inline-block px-4 py-2 rounded-lg ${sender === 'user' ? 'bg-blue-100 text-blue-900' : 'bg-gray-200 text-gray-800'}">${content}</span>`;
        }
        chatWindow.appendChild(msg);
        chatWindow.scrollTop = chatWindow.scrollHeight;
    }

    // Expor função global para exibir gráfico
    window.showChatbotChart = function(chartData) {
        chartModal.classList.remove('hidden');
        if (chartInstance) chartInstance.destroy();
        chartInstance = new Chart(chatbotChart, chartData);
    };

    function setLoading(isLoading) {
        sendButton.disabled = isLoading;
        chatInput.disabled = isLoading;
        if (isLoading) {
            sendButton.innerHTML = '<i class="ri-loader-4-line animate-spin"></i>';
        } else {
            sendButton.innerHTML = 'Enviar';
        }
    }

    chatForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const question = chatInput.value.trim();
        if (!question && !attachedFile) return;
        appendMessage(question || `Arquivo: ${attachedFile?.name}`, 'user');
        chatInput.value = '';
        setLoading(true);
        // Remove file preview
        chatAttachment.value = '';
        attachedFile = null;
        filePreview.classList.add('hidden');
        filePreviewName.textContent = '';
        appendMessage('<span class="animate-pulse">Aguarde, processando...</span>', 'bot');
        try {
            const formData = new FormData();
            if (question) formData.append('message', question);
            if (attachedFile) formData.append('file', attachedFile);
            formData.append('account_id', document.getElementById('statement-account')?.value || '');
            const response = await fetch('/chatbot/ask', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
            });
            const data = await response.json();
            chatWindow.removeChild(chatWindow.lastChild); // Remove o "Aguarde..."
            // Lógica para resposta multimodal
            if (response.ok) {
                if (data.graph) {
                    appendMessage('Gráfico gerado! Clique para visualizar.', 'bot', 'graph');
                    window.lastChartData = data.graph; // Para debug
                }
                if (data.card) {
                    appendMessage(data.card, 'bot', 'card');
                }
                if (data.answer) {
                    appendMessage(data.answer, 'bot');
                }
                if (!data.answer && !data.graph && !data.card) {
                    appendMessage('Resposta recebida, mas sem conteúdo exibível.', 'bot');
                }
            } else {
                appendMessage(data.error || 'Erro ao obter resposta da IA.', 'bot');
            }
        } catch (error) {
            console.error('Erro:', error);
            chatWindow.removeChild(chatWindow.lastChild);
            appendMessage('Erro de comunicação com o servidor. Tente novamente mais tarde.', 'bot');
        } finally {
            setLoading(false);
        }
    });
    </script>
</x-app-layout> 