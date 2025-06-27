// Importações principais
import Alpine from 'alpinejs';
import mask from '@alpinejs/mask';
import focus from '@alpinejs/focus';
import axios from 'axios';
import IMask from 'imask';

// Disponibiliza globalmente antes de qualquer uso
window.axios = axios;
window.IMask = IMask;
// SweetAlert2 já está disponível globalmente via CDN

// Importa outros scripts
import './notification';
import './bootstrap';
import './alpine-override'; // Importa a sobrecarga do Alpine

// Abordagem alternativa para Alpine: verificar se já existe uma instância em execução
// e se não existir, inicializar plugins e disponibilizar globalmente
if (!window.Alpine) {
    // Plugins do Alpine
    Alpine.plugin(mask);
    Alpine.plugin(focus);
    
    // Componentes do Alpine
    Alpine.data('moneyInput', () => ({
        amount: '',
        init() {
            const input = this.$el;
            
            // Função para formatar o valor
            const formatValue = (value) => {
                // Converte para número
                let number = parseFloat(value);
                if (isNaN(number)) number = 0;
                
                // Formata para o padrão brasileiro
                return number.toLocaleString('pt-BR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            };
    
            // Função para limpar o valor
            const cleanValue = (value) => {
                return value.replace(/[^\d]/g, '');
            };
    
            // Inicializa com o valor atual
            if (input.value) {
                // O valor vem em centavos do banco, precisamos dividir por 100
                const valueInReais = parseFloat(input.value) / 100;
                this.amount = formatValue(valueInReais);
            }
    
            // Atualiza quando o usuário digita
            input.addEventListener('input', (e) => {
                let value = cleanValue(e.target.value);
                
                // Converte para decimal
                value = parseFloat(value) / 100;
                
                // Atualiza o valor formatado
                this.amount = formatValue(value);
            });
    
            // Antes do envio do formulário
            input.closest('form').addEventListener('submit', (e) => {
                e.preventDefault();
                
                // Pega o valor limpo
                const rawValue = cleanValue(this.amount);
                
                // Atualiza o input com o valor em centavos
                input.value = rawValue;
                
                // Envia o formulário
                e.target.submit();
            });
        }
    }));
    
    // Disponibiliza o Alpine globalmente
    window.Alpine = Alpine;
    
    // Inicializa o Alpine automaticamente quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            console.log('Alpine inicializado na estratégia de fallback');
            Alpine.start();
        });
    } else {
        console.log('Alpine inicializado imediatamente na estratégia de fallback');
        Alpine.start();
    }
}

/**
 * =====================================================================
 * REGRA DE REMOÇÃO DE POPUPS - CÓDIGO INALTERÁVEL
 * =====================================================================
 * ESTA SEÇÃO CONTÉM CÓDIGO PARA REMOÇÃO DE POPUPS SWEETALERT2
 * É ABSOLUTAMENTE PROIBIDO ALTERAR ESTE CÓDIGO SEM AUTORIZAÇÃO
 * EXPLÍCITA DO PROPRIETÁRIO DO SISTEMA
 * 
 * ESTE CÓDIGO ELIMINA POPUPS QUE CAUSAM PROBLEMAS DE INTERFACE
 * E ERROS 500 NA APLICAÇÃO
 * 
 * ALTERADO POR ÚLTIMO EM: 18/05/2023
 * NÃO ALTERAR SEM AUTORIZAÇÃO EXPLÍCITA DO CLIENTE
 * =====================================================================
 */

// SweetAlert2 é independente do Alpine
document.addEventListener('DOMContentLoaded', () => {
    // Inicializa o SweetAlert2
    window.Swal = Swal;
    
    // Salvar a referência original do método fire do SweetAlert
    const originalSwalFire = window.Swal.fire;
    
    // Substituir o método fire com nossa própria versão
    window.Swal.fire = function(...args) {
        // Se estamos em uma página de edição ou o argumento é um toast de sucesso, bloquear
        if (window.location.href.includes('/edit') || 
            document.querySelector('form[action*="update"]') ||
            (args[0] && args[0].toast && args[0].icon === 'success')) {
            console.log('SweetAlert2 popup blocked');
            // Retornar uma promessa resolvida para evitar erros
            return Promise.resolve({
                isConfirmed: false,
                isDenied: false,
                isDismissed: true,
                value: undefined
            });
        }
        
        // Caso contrário, usar o comportamento normal
        return originalSwalFire.apply(this, args);
    };
    
    // Adicionar interceptador para botões em tabelas
    document.addEventListener('click', function(event) {
        // Verificar se é um botão em uma tabela que poderia disparar um popup
        let target = event.target;
        
        // Se clicou no ícone, pegar o botão pai
        if (target.tagName === 'I' && target.parentElement.tagName === 'BUTTON') {
            target = target.parentElement;
        }
        
        // Se é um botão dentro de uma célula de tabela, mas não for um botão marcado para ignorar remoção de popup
        if (target.tagName === 'BUTTON' && 
            target.closest('td') && 
            target.closest('table') && 
            !target.classList.contains('no-remove-swal')) {
            // Limpar qualquer container SweetAlert2 após um pequeno atraso
            setTimeout(() => {
                const containers = document.querySelectorAll('.swal2-container');
                containers.forEach(container => {
                    container.remove();
                    console.log('SweetAlert2 container removed after table button click');
                });
            }, 100);
        }
    }, true); // Usar captura para interceptar antes do evento normal
    
    // Configurar eventos do SweetAlert2
    setupSweetAlertEvents();
    
    // Remover qualquer popup SweetAlert2 existente
    const containers = document.querySelectorAll('.swal2-container');
    containers.forEach(container => {
        container.remove();
        console.log('SweetAlert2 container removed on load');
    });
    
    // Adicionar um observador de mutações para remover popups
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.classList && node.classList.contains('swal2-container')) {
                        if (window.location.href.includes('/edit')) {
                            setTimeout(() => {
                                node.remove();
                                console.log('SweetAlert2 container removed by observer');
                            }, 100);
                        }
                    }
                });
            }
        });
    });
    
    observer.observe(document.body, { childList: true });
});

// Função para remover forçadamente todos os popups SweetAlert2
function removeAllSweetAlertPopups() {
    const containers = document.querySelectorAll('.swal2-container');
    if (containers.length > 0) {
        containers.forEach(container => {
            container.remove();
            console.log('SweetAlert2 container forcibly removed');
        });
    }
}

/**
 * =====================================================================
 * EVENTOS SWEETALERT2 - CÓDIGO INALTERÁVEL
 * =====================================================================
 * ESTA FUNÇÃO É NECESSÁRIA PARA REMOVER POPUPS QUE CAUSAM
 * ERRO 500 E PROBLEMAS DE INTERFACE
 * 
 * NÃO MODIFICAR OU REMOVER ESTE CÓDIGO SEM AUTORIZAÇÃO
 * EXPLÍCITA DO CLIENTE
 * =====================================================================
 */
function setupSweetAlertEvents() {
    // Remove SweetAlert2 containers that might be causing issues
    setTimeout(() => {
        const swalContainer = document.querySelector("body > div.swal2-container.swal2-backdrop-show");
        if (swalContainer) {
            swalContainer.remove();
            console.log('SweetAlert2 container removed to prevent errors');
        }
    }, 1000);
    
    // Captura eventos de sucesso de transação para remover containers pendentes
    window.addEventListener('transaction-confirmed', () => {
        const swalContainer = document.querySelector("body > div.swal2-container.swal2-backdrop-show");
        if (swalContainer) {
            swalContainer.remove();
            console.log('SweetAlert2 container removed after transaction confirmation');
        }
    });
    
    // Remover qualquer container SweetAlert2 após submissão de formulários
    document.addEventListener('submit', () => {
        setTimeout(() => {
            const swalContainer = document.querySelector("body > div.swal2-container.swal2-backdrop-show");
            if (swalContainer) {
                swalContainer.remove();
                console.log('SweetAlert2 container removed after form submission');
            }
        }, 500);
    });
}

// Configuração do SweetAlert2
window.confirmDelete = async (data) => {
    const showPopup = localStorage.getItem('showDeletePopup') !== 'false';
    
    if (!showPopup) {
        Livewire.emit('deleteTransaction', data.transactionId);
        document.dispatchEvent(new CustomEvent('transaction-confirmed'));
        return;
    }

    const { isConfirmed, isDenied } = await Swal.fire({
        title: `Tem certeza que deseja excluir esta ${data.type}?`,
        text: "Esta ação não pode ser desfeita!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
        customClass: {
            confirmButton: 'swal-confirm-button',
            cancelButton: 'swal-cancel-button'
        },
        showDenyButton: true,
        denyButtonText: 'Não mostrar mais',
        denyButtonColor: '#6b7280',
        denyButtonAriaLabel: 'Não mostrar mais este aviso',
        denyButtonAriaChecked: false,
        preConfirm: () => {
            return true;
        }
    });

    if (isDenied) {
        localStorage.setItem('showDeletePopup', 'false');
    }

    if (isConfirmed) {
        Livewire.emit('deleteTransaction', data.transactionId);
        document.dispatchEvent(new CustomEvent('transaction-confirmed'));
    }
};

// Ignorar erros de scripts de extensões do Chrome
window.addEventListener('error', event => {
    if (event.filename && event.filename.startsWith('chrome-extension://')) {
        console.log('Ignorando erro de extensão:', event.message);
        event.preventDefault();
        return true;
    }
});

// Ignorar promessas rejeitadas de extensões do Chrome
window.addEventListener('unhandledrejection', event => {
    const reason = event.reason;
    if (reason && typeof reason === 'object' && reason.stack && reason.stack.includes('chrome-extension://')) {
        console.log('Ignorando unhandledrejection de extensão:', reason);
        event.preventDefault();
        return true;
    }
});
