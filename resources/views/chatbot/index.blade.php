<x-app-layout>
    {{-- ATENÇÃO: CORREÇÃO CRÍTICA no Chatbot; NÃO ALTERAR SEM AUTORIZAÇÃO EXPLÍCITA. --}}
    @isset($config)
        <div class="container mx-auto px-4 py-2">
            <p class="text-sm text-gray-600">Provedor de IA: <strong>{{ ucfirst($config['provider'] ?? 'Desconhecido') }}</strong></p>
        </div>
    @endisset
    <div class="w-full min-h-screen bg-gradient-to-br from-gray-50 to-gray-200 py-10 px-0 flex flex-col items-center">
        <div class="w-full max-w-2xl bg-white rounded-xl shadow-lg p-8 flex flex-col h-[70vh]">
            <h1 class="text-3xl font-bold text-primary-700 mb-4">Assistente Financeiro</h1>
            <div id="chat-window" class="flex-1 overflow-y-auto mb-4 border rounded-lg p-4 bg-gray-50" style="min-height: 300px;">
                <div class="text-gray-400 text-center">Inicie uma conversa com o assistente financeiro. Pergunte sobre suas finanças, peça relatórios, dicas ou tire dúvidas!</div>
            </div>
            <form id="chat-form" class="flex gap-2">
                <input type="text" id="chat-input" class="form-input flex-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Digite sua pergunta..." autocomplete="off" required>
                <button type="submit" class="btn btn-primary px-6" id="send-button">Enviar</button>
            </form>
            <!-- Seleção de conta e anexar extrato -->
            <div class="mt-2 flex items-center space-x-2">
                <select id="statement-account" class="form-select block w-full md:w-auto">
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                    @endforeach
                </select>
                <label for="chat-attachment" class="btn btn-secondary">
                    <i class="ri-file-text-line mr-1"></i> Anexar Extrato
                </label>
                <input type="file" id="chat-attachment" class="hidden" accept=".csv,.ofx,.qfx,.qif,.pdf,.xls,.xlsx,.txt">
            </div>
        </div>
    </div>
    <script>
    const chatWindow = document.getElementById('chat-window');
    const chatForm = document.getElementById('chat-form');
    const chatInput = document.getElementById('chat-input');
    const sendButton = document.getElementById('send-button');

    function appendMessage(text, sender = 'user') {
        const msg = document.createElement('div');
        msg.className = sender === 'user' ? 'text-right mb-2' : 'text-left mb-2';
        msg.innerHTML = `<span class="inline-block px-4 py-2 rounded-lg ${sender === 'user' ? 'bg-primary-100 text-primary-800' : 'bg-gray-200 text-gray-800'}">${text}</span>`;
        chatWindow.appendChild(msg);
        chatWindow.scrollTop = chatWindow.scrollHeight;
    }

    function setLoading(isLoading) {
        sendButton.disabled = isLoading;
        chatInput.disabled = isLoading;
        if (isLoading) {
            sendButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        } else {
            sendButton.innerHTML = 'Enviar';
        }
    }

    chatForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const question = chatInput.value.trim();
        if (!question) return;

        appendMessage(question, 'user');
        chatInput.value = '';
        appendMessage('<span class="animate-pulse">Pensando...</span>', 'bot');
        setLoading(true);

        try {
            const response = await fetch('/chatbot/ask', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({ message: question })
            });

            const data = await response.json();
            
            // Remove o "Pensando..."
            chatWindow.removeChild(chatWindow.lastChild);

            if (response.ok) {
                appendMessage(data.answer, 'bot');
            } else {
                appendMessage(data.error || 'Erro ao obter resposta da IA.', 'bot');
            }
        } catch (error) {
            console.error('Erro:', error);
            chatWindow.removeChild(chatWindow.lastChild);
            appendMessage('Erro ao conectar com o servidor. Por favor, tente novamente mais tarde.', 'bot');
        } finally {
            setLoading(false);
        }
    });

    // Upload de extrato via chatbot
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const accountSelect = document.getElementById('statement-account');
    const chatAttachment = document.getElementById('chat-attachment');

    chatAttachment.addEventListener('change', async function(e) {
        const file = e.target.files[0];
        if (!file) return;
        const accountId = accountSelect.value;
        appendMessage(`Arquivo selecionado: ${file.name}`, 'user');
        appendMessage('<span class="animate-pulse">Enviando arquivo...</span>', 'bot');
        setLoading(true);
        try {
            const formData = new FormData();
            formData.append('statement_file', file);
            formData.append('account_id', accountId);

            const uploadRes = await fetch('/chatbot/upload-statement', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: formData
            });
            const uploadData = await uploadRes.json();
            // Remove indicador de envio
            chatWindow.removeChild(chatWindow.lastChild);

            if (!uploadRes.ok || !uploadData.success) {
                appendMessage(uploadData.message || 'Erro ao enviar arquivo.', 'bot');
                return;
            }
            appendMessage('Arquivo enviado com sucesso. Iniciando processamento...', 'bot');
            appendMessage('<span class="animate-pulse">Processando...</span>', 'bot');

            const processRes = await fetch('/chatbot/process-statement', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({
                    file_path: uploadData.file_path,
                    account_id: uploadData.account_id,
                    extension: uploadData.extension
                })
            });
            const processData = await processRes.json();
            // Remove indicador de processamento
            chatWindow.removeChild(chatWindow.lastChild);

            if (processRes.ok && processData.success) {
                appendMessage(processData.message, 'bot');
            } else {
                appendMessage(processData.message || 'Erro ao processar arquivo.', 'bot');
            }
        } catch (err) {
            console.error(err);
            appendMessage('Falha na comunicação com o servidor.', 'bot');
        } finally {
            setLoading(false);
            chatAttachment.value = '';
        }
    });
    </script>
</x-app-layout> 