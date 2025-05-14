document.addEventListener('DOMContentLoaded', function() {
    const inputMask = document.querySelector('.money-input');
    if (inputMask) {
        const maskOptions = {
            mask: 'R$ num',
            blocks: {
                num: {
                    mask: Number,
                    thousandsSeparator: '.',
                    radix: ',',
                    scale: 2,
                    padFractional: true,
                    signed: false,
                    normalizeZeros: true,
                    min: 0,
                    max: 999999999.99
                }
            }
        };
        IMask(inputMask, maskOptions);
    }
}); 