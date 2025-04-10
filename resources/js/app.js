import './bootstrap';
import Alpine from 'alpinejs';
import mask from '@alpinejs/mask';
import focus from '@alpinejs/focus';
import 'flowbite';
import './notification';

Alpine.plugin(mask);
Alpine.plugin(focus);
window.Alpine = Alpine;

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

Alpine.start();
