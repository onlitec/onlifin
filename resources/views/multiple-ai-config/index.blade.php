<x-app-layout>
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                <i class="fas fa-robot me-2"></i>
                Configurações Múltiplas de IA
            </h2>
            <button class="btn btn-primary" id="refreshStats">
                <i class="fas fa-sync-alt me-1"></i>
                Atualizar
            </button>
        </div>
    </x-slot>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <!-- Estatísticas Gerais -->
                    <div class="row mb-4" id="statsContainer">
                        <div class="col-12 col-md-3">
                            <div class="card stats-card text-center">
                                <div class="card-body">
                                    <h4 class="mb-1">0</h4>
                                    <small>Provedores Disponíveis</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="card stats-card text-center">
                                <div class="card-body">
                                    <h4 class="mb-1">0</h4>
                                    <small>Total de Configurações</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="card stats-card text-center">
                                <div class="card-body">
                                    <h4 class="mb-1">0</h4>
                                    <small>Configurações Ativas</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="card stats-card text-center">
                                <div class="card-body">
                                    <h4 class="mb-1">0</h4>
                                    <small>Configurações Inativas</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de Provedores -->
                    <div class="horizontal-scroll-container" id="providersContainer">
                        <div class="col-12">
                            <div class="d-flex justify-content-center">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Carregando provedores...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Configurar Múltiplas IAs -->
<div class="modal fade" id="configureModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-cogs me-2"></i>
                    Configurar Múltiplas IAs - <span id="modalProviderName"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="multipleConfigForm">
                    <input type="hidden" id="modalProvider" name="provider">
                    
                    <div class="mb-3">
                        <label class="form-label">Configurações de IA</label>
                        <div id="configurationsContainer">
                            <!-- Configurações serão adicionadas dinamicamente -->
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="addConfiguration">
                            <i class="fas fa-plus me-1"></i>
                            Adicionar Configuração
                        </button>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="saveConfigurations">
                    <i class="fas fa-save me-1"></i>
                    Salvar Configurações
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.provider-container {
    flex: 0 0 auto;
    width: 300px;
    padding: 5px;
}

.provider-card {
    transition: transform 0.2s, box-shadow 0.2s;
    cursor: pointer;
    margin-bottom: 20px;
    border-radius: 12px;
    overflow: hidden;
    border: none;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    background: linear-gradient(145deg, #ffffff 0%, #f5f7fa 100%);
    height: 100%;
}

.provider-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transform: translateY(-5px);
    box-shadow: 0 8px 15px rgba(0,0,0,0.15);
}

.gemini-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    cursor: pointer;
    margin-bottom: 20px;
    border-radius: 12px;
    overflow: hidden;
    border: none;
    box-shadow: 0 4px 8px rgba(30, 64, 175, 0.2);
    background: linear-gradient(145deg, #f0f9ff 0%, #e0f2fe 100%);
    border-left: 4px solid #2563eb;
    height: 100%;
}

.gemini-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 20px rgba(30, 64, 175, 0.25);
}

.gemini-card .card-title {
    color: #1e40af;
    font-weight: 600;
}

.stats-row {
    background-color: rgba(255, 255, 255, 0.7);
    border-radius: 8px;
    padding: 10px 5px;
    margin: 0 -5px 15px -5px;
}

.stat-value {
    font-size: 1.8rem;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 5px;
}

.stat-label {
    text-transform: uppercase;
    font-size: 0.7rem;
    opacity: 0.8;
    letter-spacing: 0.5px;
}

.btn-config {
    background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
    color: white;
    border: none;
    font-weight: 500;
    padding: 10px 20px;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.btn-config:hover {
    background: linear-gradient(135deg, #4338ca 0%, #312e81 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(79, 70, 229, 0.3);
    color: white;
}

.btn-view {
    background-color: transparent;
    color: #4f46e5;
    border: 1px solid #4f46e5;
    font-weight: 500;
    padding: 10px 20px;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.btn-view:hover {
    background-color: rgba(79, 70, 229, 0.1);
    transform: translateY(-2px);
    box-shadow: 0 2px 4px rgba(79, 70, 229, 0.15);
    color: #3730a3;
}

.button-container {
    margin-top: 15px;
}

.card-title {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 15px;
}

.badge {
    padding: 5px 10px;
    font-weight: 500;
    letter-spacing: 0.3px;
    border-radius: 20px;
}

.bg-primary {
    background-color: #4f46e5 !important;
}

/* Responsividade para os cards de provedores */
@media (max-width: 992px) {
    .col-md-6.col-lg-4 {
        flex: 0 0 50%;
        max-width: 50%;
    }
}

@media (max-width: 767px) {
    .col-md-6.col-lg-4 {
        flex: 0 0 100%;
        max-width: 100%;
    }
    
    .stat-value {
        font-size: 1.5rem;
    }
    
    .button-container {
        display: flex;
        gap: 10px;
    }
    
    .btn-config, .btn-view {
        flex: 1;
        padding: 8px 12px;
    }
}

.stats-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    margin-bottom: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    padding: 10px;
    min-height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stats-card .card-body {
    padding: 15px 10px;
    text-align: center;
    width: 100%;
}

.stats-card h4 {
    font-size: 2.2rem;
    font-weight: 700;
    margin-bottom: 5px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stats-card small {
    font-size: 0.85rem;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: block;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 15px rgba(0,0,0,0.2);
}

.stats-card:nth-child(1) {
    background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);
}

.stats-card:nth-child(2) {
    background: linear-gradient(135deg, #10B981 0%, #059669 100%);
}

.stats-card:nth-child(3) {
    background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
}

.stats-card:nth-child(4) {
    background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
}

.row#statsContainer {
    margin: 0 -10px 20px -10px;
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
}

.row#statsContainer > div {
    padding: 0 8px;
    margin-bottom: 15px;
}

@media (max-width: 767px) {
    .row#statsContainer > div {
        width: 48%;
        flex: 0 0 48%;
        max-width: 48%;
        margin: 0 1% 15px 1%;
    }
    
    .stats-card {
        min-height: 100px;
    }
    
    .stats-card h4 {
        font-size: 1.8rem;
    }
    
    .stats-card small {
        font-size: 0.7rem;
    }
}

@media (max-width: 480px) {
    .row#statsContainer > div {
        width: 98%;
        flex: 0 0 98%;
        max-width: 98%;
    }
}

.config-item {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    background-color: #f8f9fa;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.config-item:last-child {
    margin-bottom: 0;
}

.remove-config {
    position: absolute;
    top: 10px;
    right: 10px;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #ff6b6b;
    color: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: background-color 0.2s;
}

.remove-config:hover {
    background-color: #ff4c4c;
}

.badge-status {
    font-size: 0.75rem;
}

.container-fluid {
    padding: 0 30px;
}

.card {
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    border: none;
}

.modal-content {
    border-radius: 12px;
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
    border: none;
}

.modal-header, .modal-footer {
    border: none;
    padding: 20px 30px;
}

.modal-body {
    padding: 30px;
}

.btn {
    border-radius: 8px;
    padding: 10px 20px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-primary {
    background-color: #4361ee;
    border-color: #4361ee;
}

.btn-primary:hover {
    background-color: #3a56d4;
    border-color: #3a56d4;
    box-shadow: 0 2px 5px rgba(67, 97, 238, 0.3);
}

.btn-outline-primary {
    border-color: #4361ee;
    color: #4361ee;
}

.btn-outline-primary:hover {
    background-color: #4361ee;
    color: white;
    box-shadow: 0 2px 5px rgba(67, 97, 238, 0.3);
}

.form-control, .form-select {
    border-radius: 8px;
    padding: 12px 15px;
    border-color: #ced4da;
    box-shadow: inset 0 1px 2px rgba(0,0,0,0.075);
}

.form-control:focus, .form-select:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.form-label {
    font-weight: 500;
    margin-bottom: 8px;
    color: #495057;
}

.horizontal-scroll-container {
    display: flex;
    flex-wrap: nowrap;
    overflow-x: auto;
    padding-bottom: 15px;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    scrollbar-color: #4f46e5 #f5f7fa;
    gap: 15px;
    padding: 10px 5px;
    margin: 0 -5px;
}

.horizontal-scroll-container::-webkit-scrollbar {
    height: 6px;
}

.horizontal-scroll-container::-webkit-scrollbar-track {
    background: #f5f7fa;
    border-radius: 10px;
}

.horizontal-scroll-container::-webkit-scrollbar-thumb {
    background-color: #4f46e5;
    border-radius: 10px;
}
</style>

<script>
class MultipleAIConfigManager {
    constructor() {
        this.providers = [];
        this.currentProvider = null;
        this.configurationCounter = 0;
        this.init();
    }

    init() {
        this.loadProviders();
        this.loadStats();
        this.bindEvents();
    }

    bindEvents() {
        // Refresh stats
        $('#refreshStats').on('click', () => {
            this.loadProviders();
            this.loadStats();
        });

        // Add configuration (evento delegado)
        $(document).on('click', '#addConfiguration', () => {
            this.addConfigurationItem();
        });

        // Save configurations
        $('#saveConfigurations').on('click', () => {
            this.saveConfigurations();
        });

        // Remove configuration (delegated event)
        $(document).on('click', '.remove-config', (e) => {
            $(e.target).closest('.config-item').remove();
        });
    }

    async loadStats() {
        try {
            const response = await fetch('/api/multiple-ai-config/stats');
            const data = await response.json();
            
            if (data.success) {
                this.renderStats(data.data);
            }
        } catch (error) {
            console.error('Erro ao carregar estatísticas:', error);
        }
    }

    async loadProviders() {
        try {
            const response = await fetch('/api/multiple-ai-config/providers');
            const data = await response.json();
            
            if (data.success) {
                this.providers = data.data;
                console.log('Provedores carregados:', this.providers);
                
                // Debug para modelos do OpenRouter
                if (this.providers.openrouter) {
                    console.log('Modelos OpenRouter:', this.providers.openrouter.models);
                    console.log('Estatísticas OpenRouter:', this.providers.openrouter.stats);
                }
                
                this.renderProviders();
            }
        } catch (error) {
            console.error('Erro ao carregar provedores:', error);
            $('#providersContainer').html('<div class="alert alert-danger">Erro ao carregar provedores</div>');
        }
    }

    renderStats(stats) {
        let totalConfigs = 0;
        let totalActive = 0;
        let totalProviders = Object.keys(stats).length;

        Object.values(stats).forEach(providerStats => {
            totalConfigs += providerStats.total_configurations;
            totalActive += providerStats.active_configurations;
        });

        const html = `
            <div class="col-md-3">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <h4 class="mb-1">${totalProviders}</h4>
                        <small>Provedores Disponíveis</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <h4 class="mb-1">${totalConfigs}</h4>
                        <small>Total de Configurações</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <h4 class="mb-1">${totalActive}</h4>
                        <small>Configurações Ativas</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <h4 class="mb-1">${totalConfigs - totalActive}</h4>
                        <small>Configurações Inativas</small>
                    </div>
                </div>
            </div>
        `;

        $('#statsContainer').html(html);
    }

    renderProviders() {
        const html = Object.values(this.providers).map(provider => {
            const stats = provider.stats;
            const isGemini = provider.key === 'gemini' || provider.name.toLowerCase().includes('gemini');
            const isOpenRouter = provider.key === 'openrouter';
            const cardClass = isGemini ? 'gemini-card' : 'provider-card';
            
            // Verificar se tem modelos externos configurados (para OpenRouter)
            let externalModelsHtml = '';
            if (stats.has_external_models && stats.external_models) {
                const externalProviders = Object.keys(stats.external_models);
                if (externalProviders.length > 0) {
                    externalModelsHtml = `
                        <div class="mt-2 border-t border-gray-200 pt-2">
                            <div class="text-sm font-medium mb-1">Modelos Configurados:</div>
                            <div class="flex flex-wrap gap-1">
                                ${externalProviders.map(provName => {
                                    const models = stats.external_models[provName];
                                    return `
                                        <div class="px-2 py-1 bg-blue-50 rounded-lg text-xs">
                                            <span class="font-medium">${provName}</span>: 
                                            ${models.length} ${models.length > 1 ? 'modelos' : 'modelo'}
                                        </div>
                                    `;
                                }).join('')}
                            </div>
                        </div>
                    `;
                }
            }
            
            return `
                <div class="provider-container">
                    <div class="card ${cardClass} h-100" data-provider="${provider.key}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h5 class="card-title mb-0">${provider.name}</h5>
                                <span class="badge bg-primary">${stats.available_models} modelos</span>
                            </div>
                            
                            <div class="row text-center mb-3 stats-row">
                                <div class="col-4">
                                    <div class="stat-value text-success">${stats.active_configurations}</div>
                                    <small class="stat-label">Ativas</small>
                                </div>
                                <div class="col-4">
                                    <div class="stat-value text-warning">${stats.inactive_configurations}</div>
                                    <small class="stat-label">Inativas</small>
                                </div>
                                <div class="col-4">
                                    <div class="stat-value text-info">${stats.total_configurations}</div>
                                    <small class="stat-label">Total</small>
                                </div>
                            </div>
                            
                            ${externalModelsHtml}
                            
                            <div class="d-grid gap-2 mt-3 button-container">
                                <button class="btn btn-config configure-provider" data-provider="${provider.key}">
                                    <i class="fas fa-cogs me-1"></i>
                                    Configurar
                                </button>
                                <button class="btn btn-view view-configurations" data-provider="${provider.key}">
                                    <i class="fas fa-eye me-1"></i>
                                    Ver Configurações
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        $('#providersContainer').html(html);

        // Bind provider-specific events
        $('.configure-provider').on('click', (e) => {
            const provider = $(e.currentTarget).data('provider');
            this.openConfigureModal(provider);
        });

        $('.view-configurations').on('click', (e) => {
            const provider = $(e.currentTarget).data('provider');
            window.location.href = `/multiple-ai-config/provider/${provider}`;
        });
    }

    openConfigureModal(providerKey) {
        this.currentProvider = this.providers[providerKey];
        $('#modalProvider').val(providerKey);
        $('#modalProviderName').text(this.currentProvider.name);
        
        // Clear previous configurations
        $('#configurationsContainer').empty();
        this.configurationCounter = 0;
        
        // Add one initial configuration
        this.addConfigurationItem();
        
        $('#configureModal').modal('show');
    }

    addConfigurationItem() {
        if (!this.currentProvider) {
            alert('Por favor, selecione um provedor primeiro clicando em "Configurar" em um dos cards de provedor.');
            return;
        }
        
        const configId = ++this.configurationCounter;
        const modelsObj = this.currentProvider.models;
        const modelsOptions = Object.entries(modelsObj).map(([modelKey, modelLabel]) =>
            `<option value="${modelKey}">${modelLabel}</option>`
        ).join('');

        const html = `
            <div class="config-item position-relative" data-config-id="${configId}">
                <button type="button" class="btn btn-sm btn-outline-danger remove-config">
                    <i class="fas fa-times"></i>
                </button>
                
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Modelo</label>
                        <select class="form-select" name="configurations[${configId}][model]" required>
                            <option value="">Selecione um modelo</option>
                            ${modelsOptions}
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Token da API</label>
                        <input type="text" class="form-control" name="configurations[${configId}][api_token]" required>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-4">
                        <label class="form-label">Prompt do Sistema</label>
                        <textarea class="form-control" name="configurations[${configId}][system_prompt]" rows="3"></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Prompt de Chat</label>
                        <textarea class="form-control" name="configurations[${configId}][chat_prompt]" rows="3"></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Prompt de Importação</label>
                        <textarea class="form-control" name="configurations[${configId}][import_prompt]" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="configurations[${configId}][is_active]" value="1" checked>
                            <label class="form-check-label">Configuração Ativa</label>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('#configurationsContainer').append(html);
    }

    async saveConfigurations() {
        const formData = new FormData($('#multipleConfigForm')[0]);
        const provider = $('#modalProvider').val();
        
        // Convert FormData to JSON
        const configurations = [];
        const configItems = $('.config-item');
        
        configItems.each((index, item) => {
            const configId = $(item).data('config-id');
            const config = {
                model: $(`[name="configurations[${configId}][model]"]`).val(),
                api_token: $(`[name="configurations[${configId}][api_token]"]`).val(),
                system_prompt: $(`[name="configurations[${configId}][system_prompt]"]`).val(),
                chat_prompt: $(`[name="configurations[${configId}][chat_prompt]"]`).val(),
                import_prompt: $(`[name="configurations[${configId}][import_prompt]"]`).val(),
                is_active: $(`[name="configurations[${configId}][is_active]"]`).is(':checked')
            };
            
            if (config.model && config.api_token) {
                configurations.push(config);
            }
        });

        if (configurations.length === 0) {
            alert('Adicione pelo menos uma configuração válida.');
            return;
        }

        try {
            // Obter o token CSRF da meta tag
            const csrfToken = $('meta[name="csrf-token"]').attr('content');
            
            if (!csrfToken) {
                console.error('CSRF token não encontrado. Verifique se há uma meta tag csrf-token.');
            }
            
            console.log('Enviando configurações:', configurations);
            console.log('Provider:', provider);
            console.log('CSRF Token:', csrfToken ? 'Presente' : 'Ausente');
            
            const response = await fetch(`/api/multiple-ai-config/provider/${provider}/configure`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ configurations })
            });

            // Verificar se a resposta está OK
            if (!response.ok) {
                const errorText = await response.text();
                console.error('Resposta de erro do servidor:', errorText);
                console.error('Status da resposta:', response.status, response.statusText);
                throw new Error(`Erro do servidor: ${response.status} ${response.statusText}`);
            }

            const responseText = await response.text();
            console.log('Resposta do servidor (texto):', responseText);
            
            let data;
            try {
                data = JSON.parse(responseText);
                console.log('Resposta do servidor (parseada):', data);
            } catch (e) {
                console.error('Erro ao fazer parse da resposta JSON:', e);
                throw new Error('A resposta do servidor não é um JSON válido');
            }

            if (data.success) {
                alert('Configurações salvas com sucesso!');
                $('#configureModal').modal('hide');
                this.loadProviders();
                this.loadStats();
            } else {
                alert('Erro ao salvar configurações: ' + data.message);
            }
        } catch (error) {
            console.error('Erro ao salvar configurações:', error);
            alert('Erro ao salvar configurações. Verifique o console para mais detalhes.');
        }
    }
}

// Initialize when document is ready
$(document).ready(() => {
    new MultipleAIConfigManager();
});
</script>
</x-app-layout>