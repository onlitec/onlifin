export function initMoneyMask(el) {
    return IMask(el, {
        mask: Number,
        scale: 2,
        thousandsSeparator: '.',
        padFractionalZeros: true,
        radix: ',',
        normalizeZeros: true,
        min: 0,
        max: 999999999.99,
        
        // Evento quando o valor é alterado
        onAccept: function(value) {
            // Remove formatação para envio ao backend
            let numericValue = value.replace(/\./g, '').replace(',', '.');
            el.dataset.value = numericValue;
        }
    });
} 