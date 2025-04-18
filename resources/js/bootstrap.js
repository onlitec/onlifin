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

    // Configuração do Livewire
    window.Livewire = window.Livewire || {};
    window.Livewire.on('swal:confirm', function (data) {
        // Garantir que os dados estejam no formato esperado pela função confirmDelete
        window.confirmDelete({
            transactionId: data.transactionId,
            type: data.type || 'transação'
        });
    });
});
