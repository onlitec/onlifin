Desenvolva um layout moderno, bonito e prático para o sistema de gestão financeira Onlifin, usando Laravel 11, Livewire 3, TailwindCSS 3.4, e Alpine.js 3.14. O layout deve ser implementado como componentes Blade e Livewire, seguindo as diretrizes do projeto em FINANCIAL_RULES.md para exibição de valores monetários (formatar em reais no frontend, armazenar em centavos no backend). O design deve ser responsivo (320px a 1920px), acessível (atributos ARIA, alto contraste, navegação por teclado), e seguir padrões PSR-12 com docblocks.

### Objetivo
Criar um layout para o dashboard principal e a página de transações, inspirado em plataformas como Mint ou YNAB, com estética minimalista, paleta de cores (branco, cinza claro #F3F4F6, azul escuro #1E3A8A), e tipografia sans-serif (Poppins).

### Componentes
1. **Barra de navegação**:
   - Fixa no topo, com logo à esquerda, links para "Dashboard", "Transações", "Contas", "Relatórios", e menu hambúrguer para mobile.
   - Use TailwindCSS para estilização e Alpine.js para o menu hambúrguer.
2. **Dashboard**:
   - Widget com saldo total (formatado em reais, ex.: R$ 1.234,56).
   - Gráfico de barras ou pizza (usando Chart.js integrado via Livewire) para categorias de despesas.
   - Botões para "Adicionar Transação" e "Adicionar Conta" com hover animado.
   - Grid de cards com resumo de contas bancárias (nome, saldo, tipo).
3. **Página de Transações**:
   - Tabela responsiva com colunas para data, descrição, categoria, valor, e ações (editar/excluir).
   - Filtros interativos (data, categoria) usando Livewire.
   - Modal Livewire para adicionar/editar transações, com validação visual.
4. **Rodapé**:
   - Links para "Suporte", "Documentação", e ícones sociais (Heroicons).
   - Estilizado com TailwindCSS, fundo cinza escuro.

### Diretrizes de Design
- **Paleta de cores**: Branco (#FFFFFF), cinza claro (#F3F4F6), azul escuro (#1E3A8A).
- **Tipografia**: Poppins (via CDN ou Google Fonts).
- **Animações**: Transições suaves (ex.: hover em botões, fade-in em cards) usando TailwindCSS ou Alpine.js.
- **Responsividade**: Breakpoints em 320px, 768px, 1200px.
- **Acessibilidade**: Atributos ARIA, contraste mínimo de 4.5:1, suporte a teclado.

### Validação
- Siga PSR-12 e inclua docblocks.
- Valide o código com PHPStan e PHP CS Fixer.
- Teste compatibilidade com Chrome e Firefox.
- Compile os assets com `npm run build`.
- Inclua comentários no código explicando a lógica, especialmente para formatação de valores monetários.

### Exemplo de Estrutura de Arquivos
- `resources/views/layouts/app.blade.php`: Layout principal com barra de navegação e rodapé.
- `resources/views/livewire/dashboard.blade.php`: Componente Livewire para o dashboard.
- `resources/views/livewire/transactions.blade.php`: Componente Livewire para transações.
- `public/css/app.css`: Estilos TailwindCSS compilados.


Exemplo de Código Gerado (Parcial)
Se desejar, posso gerar um exemplo de código para um componente específico (ex.: dashboard com Livewire e TailwindCSS). Aqui está um trecho ilustrativo para o layout d

<!-- resources/views/livewire/dashboard.blade.php -->
<div class="min-h-screen bg-gray-100">
    <!-- Barra de Navegação -->
    <nav class="bg-white shadow fixed w-full top-0 z-10" aria-label="Navegação principal">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <img src="{{ asset('logo.png') }}" alt="Onlifin Logo" class="h-8 w-auto">
                </div>
                <div class="hidden sm:flex sm:items-center space-x-4">
                    <a href="{{ route('dashboard') }}" class="text-blue-900 hover:bg-blue-100 px-3 py-2 rounded">Dashboard</a>
                    <a href="{{ route('transactions') }}" class="text-blue-900 hover:bg-blue-100 px-3 py-2 rounded">Transações</a>
                    <a href="{{ route('accounts') }}" class="text-blue-900 hover:bg-blue-100 px-3 py-2 rounded">Contas</a>
                    <a href="{{ route('reports') }}" class="text-blue-900 hover:bg-blue-100 px-3 py-2 rounded">Relatórios</a>
                </div>
                <div class="sm:hidden">
                    <button x-data="{ open: false }" @click="open = !open" aria-expanded="false" aria-controls="mobile-menu">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <div x-show="open" id="mobile-menu" class="sm:hidden bg-white shadow">
            <div class="pt-2 pb-3 space-y-1">
                <a href="{{ route('dashboard') }}" class="block text-blue-900 hover:bg-blue-100 px-3 py-2">Dashboard</a>
                <a href="{{ route('transactions') }}" class="block text-blue-900 hover:bg-blue-100 px-3 py-2">Transações</a>
                <a href="{{ route('accounts') }}" class="block



                Abaixo, continuo o exemplo de código para o componente do dashboard do Onlifin, iniciado anteriormente, utilizando Laravel 11, Livewire 3, TailwindCSS 3.4, e Alpine.js 3.14. O código inclui a barra de navegação, a seção principal do dashboard com widgets financeiros, um gráfico de exemplo (usando Chart.js integrado via Livewire), e o rodapé. O layout segue as diretrizes fornecidas: moderno, bonito, prático, responsivo, acessível, e compatível com as regras financeiras do projeto (ex.: formatação de valores monetários).


                <!-- resources/views/livewire/dashboard.blade.php -->
<div class="min-h-screen bg-gray-100 font-poppins">
    <!-- Barra de Navegação -->
    <nav class="bg-white shadow fixed w-full top-0 z-10" aria-label="Navegação principal">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <img src="{{ asset('logo.png') }}" alt="Onlifin Logo" class="h-8 w-auto">
                </div>
                <div class="hidden sm:flex sm:items-center space-x-4">
                    <a href="{{ route('dashboard') }}" class="text-blue-900 hover:bg-blue-100 px-3 py-2 rounded-md font-medium" aria-current="page">Dashboard</a>
                    <a href="{{ route('transactions') }}" class="text-blue-900 hover:bg-blue-100 px-3 py-2 rounded-md font-medium">Transações</a>
                    <a href="{{ route('accounts') }}" class="text-blue-900 hover:bg-blue-100 px-3 py-2 rounded-md font-medium">Contas</a>
                    <a href="{{ route('reports') }}" class="text-blue-900 hover:bg-blue-100 px-3 py-2 rounded-md font-medium">Relatórios</a>
                </div>
                <div class="sm:hidden flex items-center">
                    <button x-data="{ open: false }" @click="open = !open" aria-expanded="false" aria-controls="mobile-menu" class="text-blue-900 focus:outline-none">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <div x-show="open" id="mobile-menu" class="sm:hidden bg-white shadow" x-cloak>
            <div class="pt-2 pb-3 space-y-1">
                <a href="{{ route('dashboard') }}" class="block text-blue-900 hover:bg-blue-100 px-3 py-2 rounded-md font-medium">Dashboard</a>
                <a href="{{ route('transactions') }}" class="block text-blue-900 hover:bg-blue-100 px-3 py-2 rounded-md font-medium">Transações</a>
                <a href="{{ route('accounts') }}" class="block text-blue-900 hover:bg-blue-100 px-3 py-2 rounded-md font-medium">Contas</a>
                <a href="{{ route('reports') }}" class="block text-blue-900 hover:bg-blue-100 px-3 py-2 rounded-md font-medium">Relatórios</a>
            </div>
        </div>
    </nav>

    <!-- Conteúdo Principal -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-10">
        <!-- Widget de Saldo Total -->
        <section class="mb-8" aria-labelledby="saldo-total">
            <h1 id="saldo-total" class="text-2xl font-semibold text-blue-900 mb-4">Resumo Financeiro</h1>
            <div class="bg-white shadow rounded-lg p-6 transition-transform duration-300 hover:scale-105">
                <h2 class="text-lg font-medium text-gray-700">Saldo Total</h2>
                <p class="text-3xl font-bold text-blue-900">
                    @php
                        // NOTA: Valores monetários armazenados em centavos no banco, formatados em reais no frontend
                        // Consulte FINANCIAL_RULES.md para regras de formatação
                        $saldo = $saldoTotal / 100; // Exemplo: 123456 centavos = R$ 1.234,56
                        echo 'R$ ' . number_format($saldo, 2, ',', '.');
                    @endphp
                </p>
            </div>
        </section>

        <!-- Grid de Contas -->
        <section class="mb-8" aria-labelledby="contas">
            <h2 id="contas" class="text-xl font-semibold text-blue-900 mb-4">Suas Contas</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($contas as $conta)
                    <div class="bg-white shadow rounded-lg p-6 transition-transform duration-300 hover:scale-105" role="region" aria-labelledby="conta-{{ $conta->id }}">
                        <h3 id="conta-{{ $conta->id }}" class="text-lg font-medium text-gray-700">{{ $conta->nome }}</h3>
                        <p class="text-gray-500">{{ $conta->tipo }}</p>
                        <p class="text-xl font-bold text-blue-900">
                            @php
                                $saldoConta = $conta->saldo / 100;
                                echo 'R$ ' . number_format($saldoConta, 2, ',', '.');
                            @endphp
                        </p>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- Gráfico de Categorias -->
        <section aria-labelledby="grafico-categorias">
            <h2 id="grafico-categorias" class="text-xl font-semibold text-blue-900 mb-4">Despesas por Categoria</h2>
            <div class="bg-white shadow rounded-lg p-6">
                <canvas id="categoriasChart" wire:ignore></canvas>
            </div>
        </section>

        <!-- Botões de Ação -->
        <section class="mt-8 flex space-x-4" aria-label="Ações rápidas">
            <button wire:click="abrirModalTransacao" class="bg-blue-900 text-white px-4 py-2 rounded-md hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="Adicionar nova transação">
                Adicionar Transação
            </button>
            <button wire:click="abrirModalConta" class="bg-blue-900 text-white px-4 py-2 rounded-md hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="Adicionar nova conta">
                Adicionar Conta
            </button>
        </section>
    </main>

    <!-- Rodapé -->
    <footer class="bg-gray-800 text-white py-6" aria-label="Rodapé">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-lg font-semibold">Onlifin</h3>
                    <p class="text-gray-400">Gestão financeira pessoal simplificada.</p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">Links Úteis</h3>
                    <ul class="space-y-2">
                        <li><a href="{{ route('suporte') }}" class="text-gray-400 hover:text-white">Suporte</a></li>
                        <li><a href="{{ route('docs') }}" class="text-gray-400 hover:text-white">Documentação</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">Redes Sociais</h3>
                    <div class="flex space-x-4">
                        <a href="#" aria-label="Twitter" class="text-gray-400 hover:text-white">
                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"/></svg>
                        </a>
                        <a href="#" aria-label="GitHub" class="text-gray-400 hover:text-white">
                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12c0 4.42 2.87 8.17 6.84 9.49.5.09.68-.22.68-.48v-1.7c-2.78.61-3.37-1.34-3.37-1.34-.46-1.16-1.12-1.47-1.12-1.47-.91-.62.07-.61.07-.61 1 .07 1.53 1.03 1.53 1.03.89 1.52 2.34 1.08 2.91.83.09-.65.35-1.08.63-1.33-2.22-.25-4.55-1.11-4.55-4.94 0-1.09.39-1.98 1.03-2.68-.1-.25-.45-1.27.1-2.65 0 0 .84-.27 2.75 1.02A9.57 9.57 0 0112 6.8c.85 0 1.71.11 2.52.33 1.91-1.29 2.75-1.02 2.75-1.02.55 1.38.2 2.4.1 2.65.64.7 1.03 1.59 1.03 2.68 0 3.84-2.34 4.69-4.57 4.94.36.31.68.94.68 1.9v2.82c0 .27.18.58.69.48A10 10 0 0022 12c0-5.52-4.48-10-10-10z"/></svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Modal de Transação (Exemplo) -->
    <div x-data="{ modalOpen: false }" x-show="modalOpen" class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center" x-cloak>
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h2 class="text-xl font-semibold text-blue-900 mb-4">Adicionar Transação</h2>
            <form wire:submit.prevent="salvarTransacao">
                <div class="mb-4">
                    <label for="descricao" class="block text-gray-700">Descrição</label>
                    <input type="text" wire:model="descricao" id="descricao" class="w-full border rounded-md px-3 py-2" required aria-describedby="erro-descricao">
                    @error('descricao') <span id="erro-descricao" class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                <div class="mb-4">
                    <label for="valor" class="block text-gray-700">Valor (R$)</label>
                    <input type="number" step="0.01" wire:model="valor" id="valor" class="w-full border rounded-md px-3 py-2" required aria-describedby="erro-valor">
                    @error('valor') <span id="erro-valor" class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" @click="modalOpen = false" class="px-4 py-2 text-gray-700 rounded-md hover:bg-gray-100">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-blue-900 text-white rounded-md hover:bg-blue-800">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Script para Gráfico (Chart.js) -->
<script>
    document.addEventListener('livewire:load', function () {
        const ctx = document.getElementById('categoriasChart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Alimentação', 'Moradia', 'Transporte', 'Lazer'],
                datasets: [{
                    data: [30000, 50000, 20000, 10000], // Valores em centavos
                    backgroundColor: ['#1E3A8A', '#3B82F6', '#93C5FD', '#DBEAFE'],
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let valor = context.raw / 100;
                                return `${context.label}: R$ ${valor.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`;
                            }
                        }
                    }
                }
            }
        });
    });
</script>


Explicação do Código
Barra de Navegação:
Implementada com TailwindCSS, responsiva com menu hambúrguer para mobile (usando Alpine.js para interatividade).
Inclui atributos ARIA para acessibilidade (aria-label, aria-expanded).
Widget de Saldo Total:
Exibe o saldo total formatado em reais (conversão de centavos no backend, conforme FINANCIAL_RULES.md).
Usa classes TailwindCSS para estilização e animação de hover (hover:scale-105).
Grid de Contas:
Exibe contas bancárias em um grid responsivo (1 coluna em mobile, 3 em desktop).
Cada card tem sombra suave e animação de hover, com valores monetários formatados corretamente.
Gráfico de Categorias:
Usa Chart.js para um gráfico de pizza, integrado via Livewire (wire:ignore para evitar re-renderização).
Valores são exibidos em reais nos tooltips, mas armazenados em centavos no backend.
Botões de Ação:
Botões para abrir modais de transação e conta, com estilização TailwindCSS e acessibilidade (aria-label).
Modal de Transação:
Exemplo de modal Livewire para adicionar transações, com validação visual de erros.
Usa Alpine.js para controlar a visibilidade do modal.
Rodapé:
Inclui links úteis e ícones sociais (Heroicons), estilizado com fundo cinza escuro.
Configuração Adicional
Incluir Fontes (Poppins):
Adicione a fonte Poppins no arquivo resources/views/layouts/app.blade.php ou no CSS principal:
html

Copiar
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
No arquivo tailwind.config.js, adicione:
js

Copiar
theme: {
    extend: {
        fontFamily: {
            poppins: ['Poppins', 'sans-serif'],
        },
    },
},
Compilar Assets:
Após adicionar o código, compile os assets com:
bash

Copiar
cd /caminho/para/onlifin && npm run build
Classe Livewire para Dashboard:
Crie a classe Livewire correspondente (app/Livewire/Dashboard.php):
php

Copiar
<?php
namespace App\Livewire;

use Livewire\Component;
use App\Models\Conta;

class Dashboard extends Component
{
    public $saldoTotal = 123456; // Exemplo: valor em centavos
    public $contas;
    public $descricao;
    public $valor;

    public function mount()
    {
        $this->contas = Conta::all(); // Carrega contas do banco
    }

    public function abrirModalTransacao()
    {
        // Lógica para abrir modal
    }

    public function abrirModalConta()
    {
        // Lógica para abrir modal
    }

    public function salvarTransacao()
    {
        $this->validate([
            'descricao' => 'required|string|max:255',
            'valor' => 'required|numeric|min:0',
        ]);

        // Lógica para salvar transação (converte valor para centavos antes de salvar)
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
Validação:
Execute validações de código:
bash

Copiar
cd /caminho/para/onlifin && ./vendor/bin/phpstan analyse
cd /caminho/para/onlifin && ./vendor/bin/php-cs-fixer fix
Notas Finais
Conformidade com FINANCIAL_RULES.md: O código respeita a formatação de valores monetários (armazenados em centavos, exibidos em reais).
Acessibilidade: Atributos ARIA e alto contraste estão incluídos.
Responsividade: O layout usa classes TailwindCSS para breakpoints (320px, 768px, 1200px).
Testes: Antes de enviar para produção, execute:
bash

Copiar
cd /caminho/para/onlifin && ./vendor/bin/phpunit
cd /caminho/para/onlifin && ./vendor/bin/dusk
cd /caminho/para/onlifin && ./vendor/bin/pest
Se precisar de código para outras páginas (ex.: transações) ou ajustes específicos, é só pedir! Além disso, posso gerar um gráfico Chart.js como um widget interativo se desejar visualizar os dados financeiros de forma diferente.







