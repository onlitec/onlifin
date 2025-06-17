<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                <i class="fas fa-robot me-2"></i>
                Configurações de IA - {{ ucfirst($provider) }}
            </h2>
            <a href="{{ route('multiple-ai-config.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>
                Voltar
            </a>
        </div>
    </x-slot>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0">
                            <i class="fas fa-robot me-2"></i>
                            Configurações de IA - <span id="providerName">{{ ucfirst($provider) }}</span>
                        </h3>
                        <small class="text-muted">Gerenciar configurações do provedor {{ $provider }}</small>
                    </div>
                    <div>
                        <a href="{{ route('multiple-ai-config.index') }}" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i>
                            Voltar
                        </a>
                        <button class="btn btn-primary" id="refreshConfigurations">
                            <i class="fas fa-sync-alt me-1"></i>
                            Atualizar
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Informações do Provedor -->
                    <div class="row mb-4" id="providerInfo">
                        <div class="col-12">
                            <div class="d-flex justify-content-center">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Carregando informações...</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ações Rápidas -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="d-flex gap-2 flex-wrap">
                                <button class="btn btn-success" id="addNewConfig">
                                    <i class="fas fa-plus me-1"></i>
                                    Nova Configuração
                                </button>
                                <button class="btn btn-warning" id="toggleAllConfigs">
                                    <i class="fas fa-toggle-on me-1"></i>
                                    Alternar Todas
                                </button>
                                <button class="btn btn-danger" id="removeAllConfigs">
                                    <i class="fas fa-trash me-1"></i>
                                    Remover Todas
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de Configurações -->
                    <div class="row" id="configurationsContainer">
                        <div class="col-12">
                            <div class="d-flex justify-content-center">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Carregando configurações...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Adicionar/Editar Configuração -->
<div class="modal fade" id="configModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-cog me-2"></i>
                    <span id="modalTitle">Nova Configuração</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="configForm">
                    <input type="hidden" id="configProvider" name="provider" value="{{ $provider }}">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Modelo *</label>
                            <select class="form-select" id="configModel" name="model" required>
                                <option value="">Selecione um modelo</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Token da API *</label>
                            <input type="text" class="form-control" id="configApiToken" name="api_token" required>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <label class="form-label">Prompt do Sistema</label>
                            <textarea class="form-control" id="configSystemPrompt" name="system_prompt" rows="4"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Prompt de Chat</label>
                            <textarea class="form-control" id="configChatPrompt" name="chat_prompt" rows="4"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Prompt de Importação</label>
                            <textarea class="form-control" id="configImportPrompt" name="import_prompt" rows="4"></textarea>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="configIsActive" name="is_active" value="1" checked>
                                <label class="form-check-label" for="configIsActive">
                                    Configuração Ativa
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <button type="button" class="btn btn-outline-info" id="validateConfig">
                                <i class="fas fa-check-circle me-1"></i>
                                Validar Configuração
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="saveConfig">
                    <i class="fas fa-save me-1"></i>
                    Salvar
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.provider-config {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.config-card {
    transition: all 0.3s ease;
    border: none;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.config-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.2);
}

.prompt-type-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 20px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.prompt-type-chat {
    background: linear-gradient(45deg, #4facfe, #00f2fe);
    color: white;
}

.prompt-type-import {
    background: linear-gradient(45deg, #43e97b, #38f9d7);
    color: white;
}

.prompt-type-system {
    background: linear-gradient(45deg, #fa709a, #fee140);
    color: white;
}

.stats-card {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    text-align: center;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.stats-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

.stats-number {
    font-size: 2rem;
    font-weight: bold;
    color: #667eea;
    margin-bottom: 0.5rem;
}

.stats-label {
    color: #6c757d;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn-gradient {
    background: linear-gradient(45deg, #667eea, #764ba2);
    border: none;
    color: white;
    transition: all 0.3s ease;
}

.btn-gradient:hover {
    background: linear-gradient(45deg, #764ba2, #667eea);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    color: white;
}

.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.table-hover tbody tr:hover {
    background-color: rgba(102, 126, 234, 0.1);
}

.badge-success {
    background: linear-gradient(45deg, #28a745, #20c997);
}

.badge-warning {
    background: linear-gradient(45deg, #ffc107, #fd7e14);
}

.badge-danger {
    background: linear-gradient(45deg, #dc3545, #e83e8c);
}

.loading-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255,255,255,.3);
    border-radius: 50%;
    border-top-color: #fff;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.fade-in {
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<script>
class ProviderConfigManager {
    constructor(provider) {
        this.provider = provider;
        this.configurations = [];
        this.availableModels = [];
        this.init();
    }

    init() {
        this.loadConfigurations();
        this.bindEvents();
    }

    bindEvents() {
        // Refresh configurations
        $('#refreshConfigurations').on('click', () => {
            this.loadConfigurations();
        });

        // Add new configuration
        $('#addNewConfig').on('click', () => {
            this.openConfigModal();
        });

        // Toggle all configurations
        $('#toggleAllConfigs').on('click', () => {
            this.toggleAllConfigurations();
        });

        // Remove all configurations
        $('#removeAllConfigs').on('click', () => {
            this.removeAllConfigurations();
        });

        // Save configuration
        $('#saveConfig').on('click', () => {
            this.saveConfiguration();
        });

        // Validate configuration
        $('#validateConfig').on('click', () => {
            this.validateConfiguration();
        });

        // Configuration actions (delegated events)
        $(document).on('click', '.toggle-config', (e) => {
            const model = $(e.target).data('model');
            const isActive = $(e.target).data('active');
            this.toggleConfiguration(model, !isActive);
        });

        $(document).on('click', '.remove-config', (e) => {
            const model = $(e.target).data('model');
            this.removeConfiguration(model);
        });
    }

    async loadConfigurations() {
        try {
            const response = await fetch(`/api/multiple-ai-config/provider/${this.provider}/configurations`);
            const data = await response.json();
            
            if (data.success) {
                this.configurations = data.data.configurations;
                this.availableModels = data.data.available_models;
                $('#providerName').text(data.data.provider_name);
                this.renderProviderInfo(data.data);
                this.renderConfigurations();
                this.populateModelSelect();
            }
        } catch (error) {
            console.error('Erro ao carregar configurações:', error);
            this.showError('Erro ao carregar configurações');
        }
    }

    renderProviderInfo(data) {
        const activeCount = this.configurations.filter(config => config.is_active).length;
        const inactiveCount = this.configurations.length - activeCount;
        
        const html = `
            <div class="col-12">
                <div class="provider-stats p-4 text-center">
                    <div class="row">
                        <div class="col-md-3">
                            <h4 class="mb-1">${data.available_models.length}</h4>
                            <small>Modelos Disponíveis</small>
                        </div>
                        <div class="col-md-3">
                            <h4 class="mb-1">${this.configurations.length}</h4>
                            <small>Total de Configurações</small>
                        </div>
                        <div class="col-md-3">
                            <h4 class="mb-1">${activeCount}</h4>
                            <small>Configurações Ativas</small>
                        </div>
                        <div class="col-md-3">
                            <h4 class="mb-1">${inactiveCount}</h4>
                            <small>Configurações Inativas</small>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('#providerInfo').html(html);
    }

    renderConfigurations() {
        if (this.configurations.length === 0) {
            $('#configurationsContainer').html(`
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle me-2"></i>
                        Nenhuma configuração encontrada para este provedor.
                        <br>
                        <button class="btn btn-primary mt-2" id="addFirstConfig">
                            <i class="fas fa-plus me-1"></i>
                            Adicionar Primeira Configuração
                        </button>
                    </div>
                </div>
            `);
            
            $('#addFirstConfig').on('click', () => {
                this.openConfigModal();
            });
            
            return;
        }

        const html = this.configurations.map(config => {
            const statusClass = config.is_active ? 'active' : 'inactive';
            const statusBadge = config.is_active ? 
                '<span class="badge bg-success">Ativa</span>' : 
                '<span class="badge bg-secondary">Inativa</span>';
            
            return `
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card config-card ${statusClass} h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h6 class="card-title mb-1">
                                        <span class="badge badge-model">${config.model}</span>
                                    </h6>
                                    ${statusBadge}
                                </div>
                                <div class="config-actions">
                                    <button class="btn btn-sm btn-outline-primary toggle-config" 
                                            data-model="${config.model}" 
                                            data-active="${config.is_active}"
                                            title="${config.is_active ? 'Desativar' : 'Ativar'}">
                                        <i class="fas fa-toggle-${config.is_active ? 'on' : 'off'}"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger remove-config" 
                                            data-model="${config.model}"
                                            title="Remover">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <small class="text-muted">Token:</small>
                                <div class="font-monospace small">${this.maskToken(config.api_token)}</div>
                            </div>
                            
                            ${config.system_prompt ? `
                                <div class="mb-2">
                                    <small class="text-muted">Prompt do Sistema:</small>
                                    <div class="small text-truncate">${config.system_prompt.substring(0, 50)}...</div>
                                </div>
                            ` : ''}
                            
                            ${config.chat_prompt ? `
                                <div class="mb-2">
                                    <small class="text-muted">Prompt de Chat:</small>
                                    <div class="small text-truncate">${config.chat_prompt.substring(0, 50)}...</div>
                                </div>
                            ` : ''}
                            
                            ${config.import_prompt ? `
                                <div class="mb-2">
                                    <small class="text-muted">Prompt de Importação:</small>
                                    <div class="small text-truncate">${config.import_prompt.substring(0, 50)}...</div>
                                </div>
                            ` : ''}
                            
                            <div class="text-muted small">
                                <i class="fas fa-clock me-1"></i>
                                Criado: ${new Date(config.created_at).toLocaleDateString('pt-BR')}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        $('#configurationsContainer').html(html);
    }

    populateModelSelect() {
        let options = '';
        
        // Verificar se availableModels é um objeto ou array
        if (this.availableModels) {
            if (Array.isArray(this.availableModels)) {
                // Se for um array (formato padrão para a maioria dos provedores)
                options = this.availableModels.map(model => 
                    `<option value="${model}">${model}</option>`
                ).join('');
            } else if (typeof this.availableModels === 'object') {
                // Se for um objeto (formato usado pelo OpenRouter)
                options = Object.entries(this.availableModels).map(([key, value]) => 
                    `<option value="${key}">${value}</option>`
                ).join('');
            }
        }
        
        $('#configModel').html(`
            <option value="">Selecione um modelo</option>
            ${options}
        `);
    }

    openConfigModal(config = null) {
        if (config) {
            // Edit mode
            $('#modalTitle').text('Editar Configuração');
            $('#configModel').val(config.model);
            $('#configApiToken').val(config.api_token);
            $('#configSystemPrompt').val(config.system_prompt || '');
            $('#configChatPrompt').val(config.chat_prompt || '');
            $('#configImportPrompt').val(config.import_prompt || '');
            $('#configIsActive').prop('checked', config.is_active);
        } else {
            // Add mode
            $('#modalTitle').text('Nova Configuração');
            $('#configForm')[0].reset();
            $('#configIsActive').prop('checked', true);
        }
        
        $('.validation-result').hide();
        $('#configModal').modal('show');
    }

    async saveConfiguration() {
        const formData = {
            configurations: [{
                model: $('#configModel').val(),
                api_token: $('#configApiToken').val(),
                system_prompt: $('#configSystemPrompt').val(),
                chat_prompt: $('#configChatPrompt').val(),
                import_prompt: $('#configImportPrompt').val(),
                is_active: $('#configIsActive').is(':checked')
            }]
        };

        if (!formData.configurations[0].model || !formData.configurations[0].api_token) {
            alert('Modelo e Token da API são obrigatórios.');
            return;
        }

        try {
            const response = await fetch(`/api/multiple-ai-config/provider/${this.provider}/configure`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();
            
            if (data.success) {
                this.showSuccess('Configuração salva com sucesso!');
                $('#configModal').modal('hide');
                this.loadConfigurations();
            } else {
                this.showError('Erro ao salvar configuração: ' + data.message);
            }
        } catch (error) {
            console.error('Erro ao salvar configuração:', error);
            this.showError('Erro ao salvar configuração.');
        }
    }

    async validateConfiguration() {
        const model = $('#configModel').val();
        const apiToken = $('#configApiToken').val();

        if (!model || !apiToken) {
            this.showValidationError('Modelo e Token da API são obrigatórios para validação.');
            return;
        }

        try {
            const response = await fetch(`/api/multiple-ai-config/provider/${this.provider}/validate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                body: JSON.stringify({ model, api_token: apiToken })
            });

            const data = await response.json();
            
            if (data.success && data.data.valid) {
                this.showValidationSuccess('Configuração válida!');
            } else {
                this.showValidationError('Configuração inválida: ' + (data.data.error || data.message));
            }
        } catch (error) {
            console.error('Erro ao validar configuração:', error);
            this.showValidationError('Erro ao validar configuração.');
        }
    }

    async toggleConfiguration(model, isActive) {
        try {
            // Remove o formato errôneo de URL que pode aparecer para o OpenRouter
            // onde 'openai' é incluído incorretamente no caminho
            const cleanModel = model.replace(/^openai\//, '');
            
            const response = await fetch(`/api/multiple-ai-config/provider/${this.provider}/model/${cleanModel}/toggle`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ is_active: isActive })
            });

            // Verificar se a resposta está OK antes de tentar fazer parse do JSON
            if (!response.ok) {
                const errorText = await response.text();
                console.error('Resposta de erro do servidor:', errorText);
                throw new Error(`Erro do servidor: ${response.status} ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.loadConfigurations();
            } else {
                this.showError('Erro ao alterar configuração: ' + data.message);
            }
        } catch (error) {
            console.error('Erro ao alterar configuração:', error);
            this.showError('Erro ao alterar configuração.');
        }
    }

    async removeConfiguration(model) {
        if (!confirm(`Tem certeza que deseja remover a configuração do modelo ${model}?`)) {
            return;
        }

        try {
            // Remove o formato errôneo de URL que pode aparecer para o OpenRouter
            // onde 'openai' é incluído incorretamente no caminho
            const cleanModel = model.replace(/^openai\//, '');
            
            const response = await fetch(`/api/multiple-ai-config/provider/${this.provider}/model/${cleanModel}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json'
                }
            });

            // Verificar se a resposta está OK antes de tentar fazer parse do JSON
            if (!response.ok) {
                const errorText = await response.text();
                console.error('Resposta de erro do servidor:', errorText);
                throw new Error(`Erro do servidor: ${response.status} ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.showSuccess('Configuração removida com sucesso!');
                this.loadConfigurations();
            } else {
                this.showError('Erro ao remover configuração: ' + data.message);
            }
        } catch (error) {
            console.error('Erro ao remover configuração:', error);
            this.showError('Erro ao remover configuração.');
        }
    }

    async toggleAllConfigurations() {
        const activeCount = this.configurations.filter(config => config.is_active).length;
        const shouldActivate = activeCount < this.configurations.length / 2;
        
        const action = shouldActivate ? 'ativar' : 'desativar';
        if (!confirm(`Tem certeza que deseja ${action} todas as configurações?`)) {
            return;
        }

        const promises = this.configurations.map(config => 
            this.toggleConfiguration(config.model, shouldActivate)
        );

        try {
            await Promise.all(promises);
            this.showSuccess(`Todas as configurações foram ${shouldActivate ? 'ativadas' : 'desativadas'}!`);
        } catch (error) {
            this.showError('Erro ao alterar configurações.');
        }
    }

    async removeAllConfigurations() {
        if (!confirm('Tem certeza que deseja remover TODAS as configurações deste provedor? Esta ação não pode ser desfeita.')) {
            return;
        }

        try {
            const response = await fetch(`/api/multiple-ai-config/provider/${this.provider}/configurations`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            const data = await response.json();
            
            if (data.success) {
                this.showSuccess(`${data.data.removed_count} configurações removidas com sucesso!`);
                this.loadConfigurations();
            } else {
                this.showError('Erro ao remover configurações: ' + data.message);
            }
        } catch (error) {
            console.error('Erro ao remover configurações:', error);
            this.showError('Erro ao remover configurações.');
        }
    }

    maskToken(token) {
        if (!token || token.length < 8) return token;
        return token.substring(0, 4) + '*'.repeat(token.length - 8) + token.substring(token.length - 4);
    }

    showValidationSuccess(message) {
        $('.validation-result').removeClass('validation-error').addClass('validation-success').text(message).show();
    }

    showValidationError(message) {
        $('.validation-result').removeClass('validation-success').addClass('validation-error').text(message).show();
    }

    showSuccess(message) {
        // You can implement a toast notification here
        alert(message);
    }

    showError(message) {
        // You can implement a toast notification here
        alert(message);
    }
}

// Initialize when document is ready
$(document).ready(() => {
    const provider = '{{ $provider }}';
    new ProviderConfigManager(provider);
});
</script>
</x-app-layout>