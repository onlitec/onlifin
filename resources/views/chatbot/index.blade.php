<x-app-layout>
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
</script>
</x-app-layout> 