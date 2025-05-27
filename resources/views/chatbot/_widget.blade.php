<div id="chatbot-widget" style="position: fixed; bottom: 20px; right: 20px; width: 350px; max-height: 500px; border: 1px solid #ccc; border-radius: 10px; background-color: #fff; box-shadow: 0 0 10px rgba(0,0,0,0.1); display: none; flex-direction: column; z-index: 1000;">
    <div id="chatbot-header" style="padding: 10px; background-color: #f1f1f1; border-bottom: 1px solid #ccc; border-top-left-radius: 10px; border-top-right-radius: 10px; display: flex; justify-content: space-between; align-items: center;">
        <h5 style="margin: 0;">Chatbot</h5>
        <button id="close-chatbot" style="border: none; background: none; font-size: 1.2em; cursor: pointer;">&times;</button>
    </div>
    <div id="chatbot-messages" style="flex-grow: 1; padding: 10px; overflow-y: auto; min-height: 200px; max-height: 350px; border-bottom: 1px solid #ccc;">
        <!-- As mensagens do chat aparecerão aqui -->
    </div>
    <div id="chatbot-input-area" style="padding: 10px; display: flex; border-bottom-left-radius: 10px; border-bottom-right-radius: 10px;">
        <input type="text" id="chatbot-input" style="flex-grow: 1; padding: 8px; border: 1px solid #ccc; border-radius: 5px;" placeholder="Digite sua mensagem...">
        <input type="file" id="chatbot-file-input" style="display: none;">
        <button id="chatbot-attach-file" style="margin-left: 5px; padding: 8px 12px; border: none; background-color: #007bff; color: white; border-radius: 5px; cursor: pointer;">📎</button>
        <button id="chatbot-send" style="margin-left: 5px; padding: 8px 12px; border: none; background-color: #007bff; color: white; border-radius: 5px; cursor: pointer;">Enviar</button>
    </div>
</div>

<button id="open-chatbot-button" style="position: fixed; bottom: 20px; right: 20px; padding: 15px 20px; background-color: #007bff; color: white; border: none; border-radius: 50%; cursor: pointer; box-shadow: 0 0 10px rgba(0,0,0,0.2); font-size: 1.5em; z-index: 999;">💬</button>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatbotWidget = document.getElementById('chatbot-widget');
    const openChatbotButton = document.getElementById('open-chatbot-button');
    const closeChatbotButton = document.getElementById('close-chatbot');
    const sendButton = document.getElementById('chatbot-send');
    const attachButton = document.getElementById('chatbot-attach-file');
    const fileInput = document.getElementById('chatbot-file-input');
    const messageInput = document.getElementById('chatbot-input');
    const messagesContainer = document.getElementById('chatbot-messages');

    openChatbotButton.addEventListener('click', () => {
        chatbotWidget.style.display = 'flex';
        openChatbotButton.style.display = 'none';
    });

    closeChatbotButton.addEventListener('click', () => {
        chatbotWidget.style.display = 'none';
        openChatbotButton.style.display = 'block';
    });

    attachButton.addEventListener('click', () => {
        fileInput.click();
    });

    fileInput.addEventListener('change', function(event) {
        const fileName = event.target.files.length > 0 ? event.target.files[0].name : 'Nenhum arquivo selecionado';
        // Poderíamos exibir o nome do arquivo selecionado em algum lugar, se desejado.
        console.log('Arquivo selecionado:', fileName);
        // Adicionar uma mensagem visual de que um arquivo foi anexado, ou prepará-lo para envio.
        if (event.target.files.length > 0) {
            appendMessage('Arquivo anexado: ' + fileName, 'system');
        }
    });

    sendButton.addEventListener('click', sendMessage);
    messageInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });

    function sendMessage() {
        const messageText = messageInput.value.trim();
        const file = fileInput.files[0];

        if (messageText === '' && !file) {
            return; // Não envia mensagem vazia sem arquivo
        }

        if (messageText !== '') {
            appendMessage(messageText, 'user');
        }
        
        const formData = new FormData();
        formData.append('message', messageText);
        if (file) {
            formData.append('file', file);
        }
        // Adicionar CSRF token para Laravel
        formData.append('_token', '{{ csrf_token() }}');

        // Lógica de envio para o backend (AJAX)
        fetch('{{ route("chatbot.ask") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest' // Importante para Laravel identificar como AJAX
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.reply) {
                appendMessage(data.reply, 'bot');
            } else if (data.error) {
                appendMessage('Erro: ' + data.error, 'system');
            }
        })
        .catch(error => {
            console.error('Erro no chatbot:', error);
            appendMessage('Erro ao conectar com o servidor.', 'system');
        })
        .finally(() => {
             // Limpa o input de texto e o de arquivo após o envio
            messageInput.value = '';
            fileInput.value = ''; // Limpa a seleção do arquivo
        });
    }

    function appendMessage(text, sender) {
        const messageElement = document.createElement('div');
        messageElement.style.padding = '8px';
        messageElement.style.marginBottom = '5px';
        messageElement.style.borderRadius = '5px';
        messageElement.style.wordWrap = 'break-word';

        if (sender === 'user') {
            messageElement.style.backgroundColor = '#d1eaff';
            messageElement.style.textAlign = 'right';
            messageElement.style.marginLeft = 'auto';
            messageElement.style.maxWidth = '80%';
        } else if (sender === 'bot') {
            messageElement.style.backgroundColor = '#f1f1f1';
            messageElement.style.textAlign = 'left';
            messageElement.style.marginRight = 'auto';
            messageElement.style.maxWidth = '80%';
        } else { // system messages
            messageElement.style.backgroundColor = '#fffacd'; // Amarelo claro para mensagens do sistema
            messageElement.style.textAlign = 'center';
            messageElement.style.fontStyle = 'italic';
            messageElement.style.fontSize = '0.9em';
            messageElement.style.maxWidth = '100%';
        }
        messageElement.textContent = text;
        messagesContainer.appendChild(messageElement);
        messagesContainer.scrollTop = messagesContainer.scrollHeight; // Auto-scroll para a última mensagem
    }
});
</script> 