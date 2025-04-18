/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';

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
        window.confirmDelete(data.transactionId, data.type);
    });
});
