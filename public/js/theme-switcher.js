/**
 * Sistema de Troca de Tema para Onlifin
 * Melhora a experiência do usuário ao alternar entre temas claro e escuro
 */

class ThemeSwitcher {
    constructor() {
        this.init();
    }

    init() {
        // Detectar mudanças no select de tema
        const themeSelect = document.getElementById('site_theme');
        if (themeSelect) {
            themeSelect.addEventListener('change', (e) => {
                this.previewTheme(e.target.value);
            });
        }

        // Aplicar tema atual na inicialização
        this.applyCurrentTheme();
        
        // Detectar preferência do sistema
        this.detectSystemPreference();
    }

    /**
     * Aplica o tema atual baseado na classe do HTML
     */
    applyCurrentTheme() {
        const html = document.documentElement;
        const isDark = html.classList.contains('dark');
        
        if (isDark) {
            this.applyDarkTheme();
        } else {
            this.applyLightTheme();
        }
    }

    /**
     * Preview do tema sem salvar
     */
    previewTheme(theme) {
        const html = document.documentElement;
        
        if (theme === 'dark') {
            html.classList.add('dark');
            this.applyDarkTheme();
            this.showThemeNotification('Tema escuro aplicado', 'dark');
        } else {
            html.classList.remove('dark');
            this.applyLightTheme();
            this.showThemeNotification('Tema claro aplicado', 'light');
        }
    }

    /**
     * Aplica melhorias específicas para o tema escuro
     */
    applyDarkTheme() {
        // Ajustar meta theme-color para mobile
        this.updateMetaThemeColor('#1f2937');
        
        // Aplicar estilos específicos para elementos que podem não ter classes dark:
        this.applyDarkStyles();
        
        // Ajustar imagens que podem ficar com contraste ruim
        this.adjustImagesForDarkTheme();
    }

    /**
     * Aplica melhorias específicas para o tema claro
     */
    applyLightTheme() {
        // Ajustar meta theme-color para mobile
        this.updateMetaThemeColor('#ffffff');
        
        // Remover estilos específicos do tema escuro
        this.removeDarkStyles();
        
        // Restaurar imagens para tema claro
        this.adjustImagesForLightTheme();
    }

    /**
     * Atualiza a cor do tema para dispositivos móveis
     */
    updateMetaThemeColor(color) {
        let metaThemeColor = document.querySelector('meta[name="theme-color"]');
        if (!metaThemeColor) {
            metaThemeColor = document.createElement('meta');
            metaThemeColor.name = 'theme-color';
            document.head.appendChild(metaThemeColor);
        }
        metaThemeColor.content = color;
    }

    /**
     * Aplica estilos específicos para o tema escuro
     */
    applyDarkStyles() {
        // Elementos que podem não ter classes dark: adequadas
        const elementsToStyle = [
            'input[type="search"]',
            '.form-control',
            '.form-select',
            '.dropdown-menu',
            '.modal-content',
            '.popover',
            '.tooltip'
        ];

        elementsToStyle.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(element => {
                element.classList.add('dark-theme-element');
            });
        });
    }

    /**
     * Remove estilos específicos do tema escuro
     */
    removeDarkStyles() {
        const darkElements = document.querySelectorAll('.dark-theme-element');
        darkElements.forEach(element => {
            element.classList.remove('dark-theme-element');
        });
    }

    /**
     * Ajusta imagens para o tema escuro
     */
    adjustImagesForDarkTheme() {
        // Adicionar filtro sutil para imagens que podem ficar muito brilhantes
        const images = document.querySelectorAll('img:not(.logo):not(.avatar):not(.icon)');
        images.forEach(img => {
            if (!img.classList.contains('dark-adjusted')) {
                img.style.filter = 'brightness(0.9) contrast(1.1)';
                img.classList.add('dark-adjusted');
            }
        });
    }

    /**
     * Restaura imagens para o tema claro
     */
    adjustImagesForLightTheme() {
        const images = document.querySelectorAll('img.dark-adjusted');
        images.forEach(img => {
            img.style.filter = '';
            img.classList.remove('dark-adjusted');
        });
    }

    /**
     * Detecta preferência do sistema
     */
    detectSystemPreference() {
        if (window.matchMedia) {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            
            // Adicionar listener para mudanças na preferência do sistema
            mediaQuery.addEventListener('change', (e) => {
                this.onSystemPreferenceChange(e.matches);
            });
        }
    }

    /**
     * Callback para mudança na preferência do sistema
     */
    onSystemPreferenceChange(isDark) {
        // Mostrar notificação sobre mudança na preferência do sistema
        const message = isDark ? 
            'Sistema alterado para tema escuro' : 
            'Sistema alterado para tema claro';
        
        this.showThemeNotification(message, 'system');
    }

    /**
     * Mostra notificação sobre mudança de tema
     */
    showThemeNotification(message, type) {
        // Remover notificação anterior se existir
        const existingNotification = document.querySelector('.theme-notification');
        if (existingNotification) {
            existingNotification.remove();
        }

        // Criar nova notificação
        const notification = document.createElement('div');
        notification.className = 'theme-notification';
        notification.innerHTML = `
            <div class="flex items-center p-3 rounded-lg shadow-lg transition-all duration-300 transform translate-y-0">
                <div class="flex-shrink-0">
                    ${this.getThemeIcon(type)}
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium">${message}</p>
                </div>
            </div>
        `;

        // Aplicar estilos baseados no tema atual
        const isDark = document.documentElement.classList.contains('dark');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            background-color: ${isDark ? '#374151' : '#ffffff'};
            color: ${isDark ? '#f9fafb' : '#1f2937'};
            border: 1px solid ${isDark ? '#4b5563' : '#e5e7eb'};
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            transform: translateY(-100px);
            transition: transform 0.3s ease;
        `;

        document.body.appendChild(notification);

        // Animar entrada
        setTimeout(() => {
            notification.style.transform = 'translateY(0)';
        }, 100);

        // Remover após 3 segundos
        setTimeout(() => {
            notification.style.transform = 'translateY(-100px)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        }, 3000);
    }

    /**
     * Retorna ícone baseado no tipo de tema
     */
    getThemeIcon(type) {
        const icons = {
            dark: '🌙',
            light: '☀️',
            system: '💻'
        };
        return `<span style="font-size: 1.25rem;">${icons[type] || '🎨'}</span>`;
    }

    /**
     * Salva preferência do usuário no localStorage
     */
    saveThemePreference(theme) {
        localStorage.setItem('onlifin_theme_preference', theme);
    }

    /**
     * Carrega preferência do usuário do localStorage
     */
    loadThemePreference() {
        return localStorage.getItem('onlifin_theme_preference');
    }
}

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    window.themeSwitcher = new ThemeSwitcher();
});

// Disponibilizar globalmente para uso em outros scripts
window.ThemeSwitcher = ThemeSwitcher;
