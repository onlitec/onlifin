/**
 * Sistema de Lazy Loading para componentes pesados
 * 
 * Este módulo implementa lazy loading para:
 * - Gráficos e charts
 * - Componentes de relatórios
 * - Imagens e assets
 * - Componentes Livewire pesados
 */

class LazyLoader {
    constructor() {
        this.observer = null;
        this.loadedComponents = new Set();
        this.init();
    }

    init() {
        // Configurar Intersection Observer
        this.observer = new IntersectionObserver(
            this.handleIntersection.bind(this),
            {
                root: null,
                rootMargin: '50px',
                threshold: 0.1
            }
        );

        // Observar elementos com lazy loading
        this.observeLazyElements();
        
        // Configurar lazy loading para componentes Livewire
        this.setupLivewireLazyLoading();
    }

    /**
     * Observar elementos com atributo data-lazy
     */
    observeLazyElements() {
        const lazyElements = document.querySelectorAll('[data-lazy]');
        lazyElements.forEach(element => {
            this.observer.observe(element);
        });
    }

    /**
     * Configurar lazy loading para componentes Livewire
     */
    setupLivewireLazyLoading() {
        // Interceptar carregamento de componentes Livewire
        document.addEventListener('livewire:load', () => {
            this.observeLazyElements();
        });

        // Lazy load para gráficos
        document.addEventListener('livewire:update', () => {
            this.lazyLoadCharts();
        });
    }

    /**
     * Manipular interseção de elementos
     */
    handleIntersection(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const element = entry.target;
                const componentType = element.dataset.lazy;

                switch (componentType) {
                    case 'chart':
                        this.loadChart(element);
                        break;
                    case 'report':
                        this.loadReport(element);
                        break;
                    case 'image':
                        this.loadImage(element);
                        break;
                    case 'component':
                        this.loadComponent(element);
                        break;
                    default:
                        this.loadGeneric(element);
                }

                // Parar de observar após carregar
                this.observer.unobserve(element);
            }
        });
    }

    /**
     * Carregar gráfico
     */
    loadChart(element) {
        const chartId = element.dataset.chartId;
        const chartType = element.dataset.chartType;
        const chartData = element.dataset.chartData;

        if (this.loadedComponents.has(chartId)) {
            return;
        }

        try {
            // Carregar Chart.js dinamicamente se não estiver carregado
            if (typeof Chart === 'undefined') {
                this.loadScript('/js/chart.min.js').then(() => {
                    this.renderChart(element, chartType, chartData);
                });
            } else {
                this.renderChart(element, chartType, chartData);
            }

            this.loadedComponents.add(chartId);
        } catch (error) {
            console.error('Erro ao carregar gráfico:', error);
        }
    }

    /**
     * Renderizar gráfico
     */
    renderChart(element, type, data) {
        const ctx = element.getContext('2d');
        const config = JSON.parse(data);
        
        new Chart(ctx, {
            type: type,
            data: config.data,
            options: {
                ...config.options,
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1000,
                    easing: 'easeInOutQuart'
                }
            }
        });
    }

    /**
     * Carregar relatório
     */
    loadReport(element) {
        const reportId = element.dataset.reportId;
        const reportUrl = element.dataset.reportUrl;

        if (this.loadedComponents.has(reportId)) {
            return;
        }

        // Mostrar loading
        element.innerHTML = '<div class="flex justify-center items-center h-32"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div></div>';

        // Carregar dados do relatório
        fetch(reportUrl)
            .then(response => response.text())
            .then(html => {
                element.innerHTML = html;
                this.loadedComponents.add(reportId);
                
                // Re-observar elementos lazy dentro do relatório
                this.observeLazyElements();
            })
            .catch(error => {
                console.error('Erro ao carregar relatório:', error);
                element.innerHTML = '<div class="text-red-500 text-center">Erro ao carregar relatório</div>';
            });
    }

    /**
     * Carregar imagem
     */
    loadImage(element) {
        const src = element.dataset.src;
        const alt = element.dataset.alt || '';

        if (src) {
            element.src = src;
            element.alt = alt;
            element.classList.remove('lazy-placeholder');
            element.classList.add('loaded');
        }
    }

    /**
     * Carregar componente genérico
     */
    loadComponent(element) {
        const componentUrl = element.dataset.componentUrl;
        const componentName = element.dataset.componentName;

        if (this.loadedComponents.has(componentName)) {
            return;
        }

        // Mostrar loading
        element.innerHTML = '<div class="flex justify-center items-center h-32"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div></div>';

        // Carregar componente
        fetch(componentUrl)
            .then(response => response.text())
            .then(html => {
                element.innerHTML = html;
                this.loadedComponents.add(componentName);
                
                // Re-observar elementos lazy dentro do componente
                this.observeLazyElements();
            })
            .catch(error => {
                console.error('Erro ao carregar componente:', error);
                element.innerHTML = '<div class="text-red-500 text-center">Erro ao carregar componente</div>';
            });
    }

    /**
     * Carregar elemento genérico
     */
    loadGeneric(element) {
        const content = element.dataset.content;
        if (content) {
            element.innerHTML = content;
        }
    }

    /**
     * Lazy load para gráficos existentes
     */
    lazyLoadCharts() {
        const chartElements = document.querySelectorAll('[data-lazy="chart"]:not(.loaded)');
        chartElements.forEach(element => {
            if (this.isElementInViewport(element)) {
                this.loadChart(element);
            }
        });
    }

    /**
     * Verificar se elemento está na viewport
     */
    isElementInViewport(element) {
        const rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }

    /**
     * Carregar script dinamicamente
     */
    loadScript(src) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    /**
     * Destruir observer
     */
    destroy() {
        if (this.observer) {
            this.observer.disconnect();
        }
    }
}

// Inicializar lazy loader quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    window.lazyLoader = new LazyLoader();
});

// Exportar para uso global
window.LazyLoader = LazyLoader;
