// Este script sobrepõe Alpine.start para evitar inicializações múltiplas
console.log('Alpine override carregado');

// Aguarde até que o Alpine esteja disponível
document.addEventListener('DOMContentLoaded', () => {
    if (window.Alpine) {
        // Salve a função original
        const originalStart = window.Alpine.start;
        let started = false;
        
        // Substitua com nossa versão segura
        window.Alpine.start = function() {
            if (started) {
                console.warn('Tentativa de iniciar Alpine novamente impedida');
                return;
            }
            
            started = true;
            console.log('Alpine iniciado com controle de sobrecarga');
            return originalStart.apply(this, arguments);
        };
    }
}); 