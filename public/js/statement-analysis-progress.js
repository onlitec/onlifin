/**
 * Gerenciador de barra de progresso para análise de extratos
 * Este script pode ser incluído na página de mapeamento para mostrar o progresso da análise pela IA
 */
class StatementAnalysisProgress {
    /**
     * Inicializa o monitoramento de progresso
     * 
     * @param {string} progressContainerId - ID do elemento container da barra de progresso
     * @param {string} progressBarId - ID do elemento da barra de progresso
     * @param {string} statusMessageId - ID do elemento que mostrará as mensagens de status
     * @param {string} analysisKey - Chave única da análise atual
     * @param {string} progressUrl - URL do endpoint para verificar o progresso
     */
    constructor(progressContainerId, progressBarId, statusMessageId, analysisKey, progressUrl) {
        // Elementos do DOM
        this.progressContainer = document.getElementById(progressContainerId);
        this.progressBar = document.getElementById(progressBarId);
        this.statusMessage = document.getElementById(statusMessageId);
        
        // Dados de configuração
        this.analysisKey = analysisKey;
        this.progressUrl = progressUrl;
        this.intervalId = null;
        this.checkFrequency = 1000; // 1 segundo
    }
    
    /**
     * Inicia o monitoramento de progresso
     */
    startMonitoring() {
        // Verificar se a chave de análise existe
        if (!this.analysisKey) {
            console.log('Nenhuma chave de análise fornecida');
            return;
        }
        
        // Verificar se os elementos DOM existem
        if (!this.progressContainer || !this.progressBar || !this.statusMessage) {
            console.error('Um ou mais elementos DOM não foram encontrados');
            return;
        }
        
        // Mostrar o container da barra de progresso
        this.progressContainer.style.display = 'block';
        this.progressContainer.classList.remove('hidden');
        
        // Verificar progresso imediatamente
        this.checkProgress();
        
        // Configurar intervalo para verificações periódicas
        this.intervalId = setInterval(() => this.checkProgress(), this.checkFrequency);
    }
    
    /**
     * Para o monitoramento de progresso
     */
    stopMonitoring() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
    }
    
    /**
     * Verifica o progresso atual da análise
     */
    checkProgress() {
        // Montar a URL com a chave de análise
        const url = `${this.progressUrl}?key=${encodeURIComponent(this.analysisKey)}`;
        
        // Fazer a requisição para verificar o progresso
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Erro HTTP: ${response.status}`);
                }
                // Verificar o tipo de conteúdo da resposta
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.indexOf('application/json') !== -1) {
                    return response.json().catch(e => {
                        console.error('Erro ao parsear JSON:', e);
                        // Retornar um objeto padrão para evitar erros
                        return {
                            progress: 0,
                            message: 'Erro ao processar resposta',
                            completed: false
                        };
                    });
                } else {
                    console.warn('Resposta não é JSON');
                    // Retornar um objeto padrão para evitar erros
                    return {
                        progress: 0,
                        message: 'Formato de resposta inválido',
                        completed: false
                    };
                }
            })
            .then(data => {
                // Atualizar a barra de progresso
                this.updateProgressBar(data.progress, data.message);
                
                // Se a análise estiver concluída, parar o monitoramento
                if (data.completed) {
                    this.stopMonitoring();
                    
                    // Se foi completada com sucesso, esconder a barra após alguns segundos
                    if (data.progress === 100) {
                        setTimeout(() => {
                            this.progressContainer.style.display = 'none';
                            this.progressContainer.classList.add('hidden');
                        }, 3000);
                    }
                }
            })
            .catch(error => {
                console.error('Erro ao verificar progresso:', error);
                
                // Atualizar a mensagem de status com o erro
                if (this.statusMessage) {
                    this.statusMessage.textContent = 'Erro ao verificar progresso. Tentando novamente...';
                }
                
                // Em caso de erro, reduzir a frequência de verificação
                this.stopMonitoring();
                this.checkFrequency = 3000; // 3 segundos
                this.intervalId = setInterval(() => this.checkProgress(), this.checkFrequency);
            });
    }
    
    /**
     * Atualiza a interface da barra de progresso
     * 
     * @param {number} progress - Porcentagem de progresso (0-100)
     * @param {string} message - Mensagem de status
     */
    updateProgressBar(progress, message) {
        // Atualizar a largura da barra
        this.progressBar.style.width = `${progress}%`;
        
        // Atualizar a mensagem de status
        if (message) {
            this.statusMessage.textContent = message;
        }
        
        // Mudar a cor da barra conforme o progresso
        if (progress < 25) {
            this.progressBar.className = 'bg-blue-500 h-4 rounded-full transition-all duration-500 ease-out';
        } else if (progress < 75) {
            this.progressBar.className = 'bg-purple-600 h-4 rounded-full transition-all duration-500 ease-out';
        } else {
            this.progressBar.className = 'bg-green-500 h-4 rounded-full transition-all duration-500 ease-out';
        }
    }
} 