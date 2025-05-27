
# OnliFin - Guia de Implementação do Layout Moderno

## Visão Geral
Este documento fornece um guia completo para implementar o layout moderno do OnliFin em todas as páginas da plataforma, garantindo consistência visual e uma experiência de usuário fluida.

## 🎨 Sistema de Design

### Paleta de Cores
```css
/* Gradientes Principais */
.gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%)
.gradient-success: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)
.gradient-warning: linear-gradient(135deg, #f093fb 0%, #f5576c 100%)

/* Cores por Categoria */
- Receita: text-green-600
- Despesas: text-red-600
- Investimentos: text-blue-600
- Economia: text-purple-600
```

### Animações Disponíveis
```css
/* Classes de Animação */
.animate-fade-in - Entrada suave com fade
.hover-scale - Escala no hover
.card-hover - Efeito hover para cards
```

## 🏗️ Estrutura Base das Páginas

### Template Padrão
```jsx
const PageTemplate = ({ title, subtitle, children }) => {
  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 p-6">
      <div className="max-w-7xl mx-auto space-y-8">
        {/* Header */}
        <div className="text-center space-y-4 animate-fade-in">
          <h1 className="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
            {title}
          </h1>
          <p className="text-xl text-gray-600">
            {subtitle}
          </p>
        </div>
        
        {/* Conteúdo */}
        {children}
      </div>
    </div>
  );
};
```

## 📊 Componentes Reutilizáveis

### 1. Card de Métrica
```jsx
const MetricCard = ({ title, value, change, icon: Icon, color = "green" }) => {
  return (
    <Card className="hover-scale transition-all duration-300 hover:shadow-lg">
      <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
        <CardTitle className="text-sm font-medium">{title}</CardTitle>
        <Icon className="h-4 w-4 text-muted-foreground" />
      </CardHeader>
      <CardContent>
        <div className={`text-2xl font-bold text-${color}-600`}>{value}</div>
        <p className="text-xs text-muted-foreground flex items-center">
          <ArrowUp className="h-3 w-3 text-green-500 mr-1" />
          {change}
        </p>
        <Progress value={75} className="mt-2" />
      </CardContent>
    </Card>
  );
};
```

### 2. Card de Gráfico
```jsx
const ChartCard = ({ title, description, icon: Icon, children }) => {
  return (
    <Card className="hover-scale transition-all duration-300 hover:shadow-lg">
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Icon className="h-5 w-5 text-blue-500" />
          {title}
        </CardTitle>
        <CardDescription>{description}</CardDescription>
      </CardHeader>
      <CardContent>
        {children}
      </CardContent>
    </Card>
  );
};
```

### 3. Card de Meta
```jsx
const GoalCard = ({ title, description, progress, progressText }) => {
  return (
    <Card className="hover-scale transition-all duration-300 hover:shadow-lg">
      <CardHeader>
        <CardTitle className="text-lg">{title}</CardTitle>
        <CardDescription>{description}</CardDescription>
      </CardHeader>
      <CardContent>
        <Progress value={progress} className="mb-2" />
        <p className="text-sm text-gray-600">{progressText}</p>
      </CardContent>
    </Card>
  );
};
```

## 📱 Layouts Responsivos

### Grid System
```jsx
/* Métricas - 4 colunas em desktop, 2 em tablet, 1 em mobile */
<div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

/* Gráficos - 2 colunas em desktop, 1 em mobile */
<div className="grid grid-cols-1 lg:grid-cols-2 gap-6">

/* Metas - 3 colunas em desktop, 1 em mobile */
<div className="grid grid-cols-1 md:grid-cols-3 gap-6">
```

## 🎯 Páginas Sugeridas para Implementação

### 1. Dashboard (✅ Implementado)
- Métricas principais
- Gráficos de receita vs despesas
- Distribuição do portfolio
- Metas de progresso

### 2. Transações
```jsx
// Estrutura sugerida:
- Filtros e busca
- Lista de transações com cards
- Gráfico de gastos por categoria
- Resumo mensal
```

### 3. Investimentos
```jsx
// Estrutura sugerida:
- Portfolio overview
- Performance de ativos
- Histórico de investimentos
- Recomendações
```

### 4. Metas Financeiras
```jsx
// Estrutura sugerida:
- Cards de metas ativas
- Progresso visual
- Timeline de objetivos
- Simuladores
```

### 5. Relatórios
```jsx
// Estrutura sugerida:
- Filtros de período
- Gráficos comparativos
- Tabelas de dados
- Exportação de relatórios
```

## 🔧 Configurações de Implementação

### 1. CSS Customizado (src/index.css)
```css
@layer utilities {
  /* Animações personalizadas */
  .animate-fade-in {
    animation: fadeIn 0.6s ease-out;
  }

  .hover-scale {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
  }

  .hover-scale:hover {
    transform: scale(1.02);
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
      transform: translateY(20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  /* Efeitos de hover para cards */
  .card-hover {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .card-hover:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  }
}
```

### 2. Configuração de Gráficos
```jsx
// Configuração padrão para charts
const chartConfig = {
  revenue: {
    label: "Receita",
    color: "#2563eb",
  },
  expenses: {
    label: "Despesas",
    color: "#dc2626",
  },
};

// Wrapper para gráficos
<ChartContainer config={chartConfig} className="h-[300px]">
  {/* Seu gráfico aqui */}
</ChartContainer>
```

## 📚 Dependências Necessárias

### Já Instaladas
- @radix-ui/* (componentes UI)
- recharts (gráficos)
- lucide-react (ícones)
- tailwindcss (estilização)
- class-variance-authority (variantes)

### Ícones Recomendados
```jsx
import { 
  TrendingUp, TrendingDown, DollarSign, CreditCard, 
  Activity, Users, ArrowUp, ArrowDown, PieChart,
  BarChart3, LineChart, Target, Settings 
} from "lucide-react";
```

## 🚀 Implementação Passo a Passo

### Passo 1: Criar Componentes Base
1. Crie os componentes `MetricCard`, `ChartCard`, `GoalCard`
2. Implemente o `PageTemplate`
3. Configure as animações no CSS

### Passo 2: Estruturar Páginas
1. Use o `PageTemplate` como base
2. Implemente grids responsivos
3. Adicione animações de entrada

### Passo 3: Configurar Navegação
1. Adicione rotas no React Router
2. Crie menu de navegação
3. Implemente breadcrumbs

### Passo 4: Otimizar Performance
1. Use lazy loading para páginas
2. Otimize gráficos com React.memo
3. Implemente skeleton loading

## 💡 Boas Práticas

### Design
- ✅ Mantenha consistência visual
- ✅ Use espaçamento uniforme (space-y-8, gap-6)
- ✅ Aplique hover effects em elementos interativos
- ✅ Use cores semânticas (verde=positivo, vermelho=negativo)

### Performance
- ✅ Lazy load de gráficos pesados
- ✅ Memoização de componentes
- ✅ Debounce em filtros e buscas

### Acessibilidade
- ✅ Alt text em gráficos
- ✅ Navegação por teclado
- ✅ Contraste adequado
- ✅ ARIA labels

## 📝 Checklist de Implementação

### Para cada nova página:
- [ ] Usar PageTemplate como base
- [ ] Implementar animação animate-fade-in
- [ ] Aplicar hover-scale em cards
- [ ] Usar grid system responsivo
- [ ] Adicionar ícones relevantes
- [ ] Configurar gráficos com ChartContainer
- [ ] Testar responsividade
- [ ] Validar acessibilidade

## 🔗 Arquivos de Referência

### Principais
- `src/pages/Index.tsx` - Dashboard implementado
- `src/index.css` - Animações e estilos customizados
- `src/components/ui/*` - Componentes shadcn/ui

### Para Estudar
- Grid layouts responsivos
- Configuração de gráficos
- Sistema de cores e gradientes
- Animações e transições

---

## 📞 Suporte

Para dúvidas sobre implementação:
1. Consulte a documentação do shadcn/ui
2. Verifique exemplos no Index.tsx
3. Teste responsividade em diferentes telas
4. Valide acessibilidade com ferramentas adequadas

**Última atualização:** 2025-05-27
**Versão:** 1.0.0
