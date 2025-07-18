/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

// Axios e IMask já estão disponíveis globalmente via app.js

window.addEventListener('DOMContentLoaded', () => {
    // Configuração do Axios
    window.axios = window.axios || {};
    window.axios.defaults = window.axios.defaults || {};
    window.axios.defaults.headers = window.axios.defaults.headers || {};
    window.axios.defaults.headers.common = window.axios.defaults.headers.common || {};
    window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

    // Função para remover qualquer container SweetAlert2
    function removeSweetAlertContainers() {
        setTimeout(() => {
            const swalContainer = document.querySelector("body > div.swal2-container.swal2-backdrop-show");
            if (swalContainer) {
                swalContainer.remove();
                console.log('SweetAlert2 container removed via bootstrap.js');
            }
        }, 100);
    }

    // Configuração do Livewire (v3)
    document.addEventListener('livewire:init', () => {
        Livewire.on('swal:confirm', (data) => {
            // Garantir que os dados estejam no formato esperado pela função confirmDelete
            window.confirmDelete({
                transactionId: data.transactionId,
                type: data.type || 'transação'
            });
        });
    });
    
    // Remover containers SweetAlert2 após navegação
    window.addEventListener('popstate', removeSweetAlertContainers);
    
    // Remover containers SweetAlert2 após redirecionamento ou atualização de página
    document.addEventListener('livewire:navigated', removeSweetAlertContainers);
    document.addEventListener('livewire:updated', removeSweetAlertContainers);
});
