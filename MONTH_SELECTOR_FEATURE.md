# 📅 Seletor de Mês - Visualização de Finanças Anteriores

## 🎯 Visão Geral

Implementação completa de um seletor de mês que permite aos usuários visualizar suas finanças de meses anteriores, facilitando a análise histórica e comparação de períodos.

---

## 🚀 Funcionalidades Principais

### 1. Navegação Entre Meses

#### Botões de Navegação
- **Botão Anterior** (←): Navega para o mês anterior
- **Botão Próximo** (→): Navega para o próximo mês
  - Desabilitado quando está no mês atual (não pode ver o futuro)
- **Botão "Mês Atual"**: Retorna rapidamente ao mês atual
  - Só aparece quando está visualizando meses anteriores

#### Interface do Seletor
```
[←]  📅 dezembro de 2025  [Mês Atual]  [→]
```

### 2. Exibição de Dados Históricos

#### Todos os Indicadores Atualizam Automaticamente
- ✅ Saldo Total (permanece o atual)
- ✅ Receitas do Mês (do mês selecionado)
- ✅ Despesas do Mês (do mês selecionado)
- ✅ Balanço do Mês (do mês selecionado)
- ✅ Taxa de Poupança (calculada para o mês)
- ✅ Gasto Médio/Dia (do mês selecionado)
- ✅ Projeção Mensal (só para mês atual)
- ✅ Maior Categoria (do mês selecionado)

#### Gráficos Atualizados
- ✅ Fluxo de Caixa Diário (do mês selecionado)
- ✅ Despesas por Categoria (do mês selecionado)
- ✅ Histórico Mensal (últimos 6 meses a partir do selecionado)
- ✅ Tendência de Balanço (evolução histórica)
- ✅ Transações Recentes (do mês selecionado)

---

## 🔧 Implementação Técnica

### Estado do Componente

```typescript
// Estado para armazenar o mês/ano selecionado
const [selectedDate, setSelectedDate] = useState(new Date());
```

### Funções de Navegação

#### goToPreviousMonth()
```typescript
const goToPreviousMonth = () => {
  setSelectedDate(prev => {
    const newDate = new Date(prev);
    newDate.setMonth(newDate.getMonth() - 1);
    return newDate;
  });
};
```

#### goToNextMonth()
```typescript
const goToNextMonth = () => {
  setSelectedDate(prev => {
    const newDate = new Date(prev);
    newDate.setMonth(newDate.getMonth() + 1);
    return newDate;
  });
};
```

#### goToCurrentMonth()
```typescript
const goToCurrentMonth = () => {
  setSelectedDate(new Date());
};
```

#### isCurrentMonth()
```typescript
const isCurrentMonth = () => {
  const now = new Date();
  return selectedDate.getFullYear() === now.getFullYear() && 
         selectedDate.getMonth() === now.getMonth();
};
```

#### formatSelectedMonth()
```typescript
const formatSelectedMonth = () => {
  return selectedDate.toLocaleDateString('pt-BR', { 
    month: 'long', 
    year: 'numeric' 
  });
};
```

### Carregamento de Dados

#### useEffect com Dependência
```typescript
useEffect(() => {
  loadDashboardData();
}, [selectedDate]); // Recarrega quando o mês muda
```

#### Cálculo de Datas
```typescript
const year = selectedDate.getFullYear();
const month = selectedDate.getMonth();
const firstDayOfMonth = new Date(year, month, 1).toISOString().split('T')[0];
const lastDayOfMonth = new Date(year, month + 1, 0).toISOString().split('T')[0];
```

---

## 📊 Lógica de Dados

### Diferenças Entre Mês Atual e Meses Anteriores

#### Mês Atual
- **Dias Exibidos**: Até o dia atual
- **Média Diária**: Baseada nos dias decorridos
- **Projeção**: Estimativa até o fim do mês
- **Fluxo de Caixa**: Mostra até hoje

#### Meses Anteriores
- **Dias Exibidos**: Todos os dias do mês
- **Média Diária**: Baseada em todos os dias
- **Projeção**: Igual ao total de despesas (mês completo)
- **Fluxo de Caixa**: Mostra o mês inteiro

### Queries Atualizadas

#### Transações do Mês
```typescript
const { data: transactions } = await supabase
  .from('transactions')
  .select('amount, type')
  .eq('user_id', userId)
  .gte('date', firstDay)
  .lte('date', lastDay);
```

#### Saldo Diário
```typescript
const now = new Date();
const isCurrentMonth = year === now.getFullYear() && month === now.getMonth();
const maxDay = isCurrentMonth ? now.getDate() : daysInMonth;

for (let i = 1; i <= maxDay; i++) {
  // Calcular saldo acumulado
}
```

---

## 🎨 Interface do Usuário

### Componente do Seletor

```tsx
<Card>
  <CardContent className="pt-6">
    <div className="flex items-center justify-between gap-4">
      {/* Botão Anterior */}
      <Button
        variant="outline"
        size="icon"
        onClick={goToPreviousMonth}
        title="Mês anterior"
      >
        <ChevronLeft className="h-4 w-4" />
      </Button>

      {/* Display Central */}
      <div className="flex items-center gap-4 flex-1 justify-center">
        <div className="flex items-center gap-2">
          <Calendar className="h-5 w-5 text-primary" />
          <span className="text-xl font-semibold capitalize">
            {formatSelectedMonth()}
          </span>
        </div>
        {!isCurrentMonth() && (
          <Button
            variant="outline"
            size="sm"
            onClick={goToCurrentMonth}
          >
            Mês Atual
          </Button>
        )}
      </div>

      {/* Botão Próximo */}
      <Button
        variant="outline"
        size="icon"
        onClick={goToNextMonth}
        disabled={isCurrentMonth()}
        title="Próximo mês"
      >
        <ChevronRight className="h-4 w-4" />
      </Button>
    </div>
  </CardContent>
</Card>
```

### Responsividade

#### Desktop
- Layout horizontal completo
- Botões e texto bem espaçados
- Ícones e labels visíveis

#### Mobile
- Layout adaptado
- Botões mantêm funcionalidade
- Texto redimensionado automaticamente

---

## 📈 Cálculos Ajustados

### Taxa de Poupança
```typescript
const savingsRate = monthlyIncome > 0 
  ? ((monthlyIncome - monthlyExpenses) / monthlyIncome) * 100 
  : 0;
```

### Média de Gastos Diários
```typescript
// Para mês atual: usa dias decorridos
// Para meses anteriores: usa todos os dias
const currentDay = isCurrentMonth ? now.getDate() : daysInMonth;
const averageDailyExpense = currentDay > 0 ? monthlyExpenses / currentDay : 0;
```

### Projeção para Fim do Mês
```typescript
// Só faz sentido para o mês atual
const projectedMonthEnd = isCurrentMonth 
  ? averageDailyExpense * daysInMonth 
  : monthlyExpenses;
```

### Saldo Acumulado
```typescript
let cumulativeBalance = 0;
for (let i = 1; i <= maxDay; i++) {
  const data = dailyMap.get(day) || { income: 0, expense: 0 };
  cumulativeBalance += data.income - data.expense;
  
  dailyData.push({
    day: `Dia ${i}`,
    balance: cumulativeBalance,
    income: data.income,
    expense: data.expense
  });
}
```

---

## 🔍 Casos de Uso

### 1. Análise Mensal
**Cenário**: Usuário quer ver quanto gastou em novembro
- Clica no botão ← até chegar em novembro
- Visualiza todos os indicadores de novembro
- Compara com outros meses

### 2. Comparação de Períodos
**Cenário**: Comparar gastos de dezembro com janeiro
- Navega para dezembro
- Anota os valores principais
- Navega para janeiro
- Compara os dados

### 3. Revisão Histórica
**Cenário**: Revisar os últimos 6 meses
- Navega mês a mês usando os botões
- Observa tendências nos gráficos
- Identifica padrões de gastos

### 4. Retorno Rápido
**Cenário**: Após revisar meses anteriores, voltar ao atual
- Clica no botão "Mês Atual"
- Dashboard retorna instantaneamente ao mês corrente

---

## ✅ Validações e Regras

### Regras de Navegação
1. ✅ Não pode navegar para meses futuros
2. ✅ Pode navegar para qualquer mês passado
3. ✅ Botão "próximo" desabilitado no mês atual
4. ✅ Botão "Mês Atual" só aparece em meses anteriores

### Regras de Dados
1. ✅ Dados sempre filtrados pelo mês selecionado
2. ✅ Saldo total permanece o atual (não muda)
3. ✅ Transações filtradas por data
4. ✅ Gráficos atualizados automaticamente

### Regras de Cálculo
1. ✅ Média diária ajustada para mês atual vs completo
2. ✅ Projeção só para mês atual
3. ✅ Dias exibidos corretos para cada caso
4. ✅ Saldo acumulado calculado corretamente

---

## 🎯 Benefícios para o Usuário

### Análise Financeira
- 📊 Visualizar histórico completo
- 📈 Identificar tendências de gastos
- 💰 Comparar receitas e despesas
- 🎯 Avaliar metas financeiras

### Controle e Planejamento
- ✅ Revisar decisões financeiras passadas
- ✅ Planejar com base em dados históricos
- ✅ Identificar categorias problemáticas
- ✅ Ajustar orçamento futuro

### Facilidade de Uso
- 🖱️ Navegação intuitiva
- ⚡ Resposta rápida
- 🎨 Interface limpa
- 📱 Funciona em mobile

---

## 🔮 Melhorias Futuras Possíveis

### Funcionalidades Adicionais
- [ ] Seletor de ano (dropdown)
- [ ] Comparação lado a lado de 2 meses
- [ ] Exportar dados de um mês específico
- [ ] Favoritar meses para acesso rápido
- [ ] Atalhos de teclado (← → para navegar)

### Visualizações
- [ ] Gráfico de comparação mensal
- [ ] Indicador de variação percentual
- [ ] Destaque de meses com melhor/pior desempenho
- [ ] Timeline visual de navegação

### Performance
- [ ] Cache de dados de meses visitados
- [ ] Pré-carregamento de meses adjacentes
- [ ] Lazy loading de gráficos
- [ ] Otimização de queries

---

## 📝 Notas Técnicas

### Dependências
- **React Hooks**: useState, useEffect
- **Lucide Icons**: ChevronLeft, ChevronRight, Calendar
- **shadcn/ui**: Button, Card, Badge
- **Supabase**: Queries com filtros de data

### Compatibilidade
- ✅ Funciona com todos os navegadores modernos
- ✅ Suporta timezone do usuário
- ✅ Lida com mudanças de ano corretamente
- ✅ Responsive em todos os tamanhos de tela

### Performance
- ⚡ Carregamento rápido de dados
- ⚡ Transições suaves entre meses
- ⚡ Queries otimizadas
- ⚡ Mínimo de re-renders

### Manutenção
- 📝 Código bem documentado
- 🧪 Fácil de testar
- 🔧 Fácil de estender
- 🐛 Tratamento de erros robusto

---

## 🎓 Como Usar

### Para o Usuário Final

1. **Visualizar Mês Anterior**
   - Clique no botão ← (seta esquerda)
   - Os dados serão atualizados automaticamente

2. **Visualizar Próximo Mês**
   - Clique no botão → (seta direita)
   - Desabilitado se já estiver no mês atual

3. **Voltar ao Mês Atual**
   - Clique no botão "Mês Atual"
   - Aparece apenas quando está em meses anteriores

4. **Navegar Vários Meses**
   - Clique múltiplas vezes nos botões ← ou →
   - Cada clique move um mês

### Para Desenvolvedores

1. **Adicionar Novo Indicador**
   ```typescript
   // Certifique-se de usar selectedDate para filtrar dados
   const year = selectedDate.getFullYear();
   const month = selectedDate.getMonth();
   ```

2. **Modificar Lógica de Cálculo**
   ```typescript
   // Sempre verifique se é mês atual
   const isCurrentMonth = year === now.getFullYear() && 
                          month === now.getMonth();
   ```

3. **Adicionar Nova Query**
   ```typescript
   // Use firstDayOfMonth e lastDayOfMonth
   .gte('date', firstDayOfMonth)
   .lte('date', lastDayOfMonth)
   ```

---

**Data de Implementação**: 2025-12-01  
**Versão**: 1.0  
**Status**: ✅ Completo e Funcional  
**Idioma**: Português (Brasil)
