import './bootstrap';
import Alpine from 'alpinejs';
import mask from '@alpinejs/mask';
import focus from '@alpinejs/focus';
import 'sweetalert2/dist/sweetalert2.min.css';
import Swal from 'sweetalert2';
import './notification';

// Configuração do Alpine
window.Alpine = window.Alpine || Alpine;

// Configuração do SweetAlert2
window.confirmDelete = async (data) => {
    const showPopup = localStorage.getItem('showDeletePopup') !== 'false';
    
    if (!showPopup) {
        Livewire.emit('deleteTransaction', data.transactionId);
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
    }
};

// Inicializando eventos do SweetAlert2
window.addEventListener('DOMContentLoaded', () => {
    window.Swal = Swal;
    
    // Evento para mostrar mensagens de sucesso
    window.addEventListener('swal:success', (event) => {
        Swal.fire({
            icon: 'success',
            title: event.detail.title,
            text: event.detail.text,
            timer: event.detail.timer,
            toast: true,
            position: event.detail.position,
            showConfirmButton: event.detail.showConfirmButton,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });
    });

    // Evento para mostrar mensagens de erro
    window.addEventListener('swal:error', (event) => {
        Swal.fire({
            icon: 'error',
            title: event.detail.title,
            text: event.detail.text,
            timer: event.detail.timer,
            toast: true,
            position: event.detail.position,
            showConfirmButton: event.detail.showConfirmButton,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });
    });

    // Inicializando Alpine apenas se não estiver inicializado
    if (!document.querySelector('[x-data]')) {
        Alpine.start();
    }
});

// Inicializando Flowbite
window.addEventListener('DOMContentLoaded', () => {
    window.flowbite = window.flowbite || {};
    window.flowbite.initializeComponents();
});

// Ouvinte para eventos de confirmação do SweetAlert2
Livewire.on('swal:confirm', (data) => {
    window.confirmDelete(data);
});

Alpine.plugin(mask);
Alpine.plugin(focus);

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
