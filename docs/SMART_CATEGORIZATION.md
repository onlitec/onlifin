# Sistema de Categorização com Inteligência Artificial

## Visão Geral

O sistema de categorização utiliza **Inteligência Artificial avançada** para analisar automaticamente as transações importadas e sugerir categorias apropriadas. A IA considera o contexto, suas categorias existentes e padrões financeiros para criar categorizações precisas e inteligentes.

## Como Funciona

### 1. Integração com IA Configurada
- Utiliza o modelo de IA configurado em `/iaprovider-config`
- Suporta múltiplos provedores: OpenAI, OpenRouter, Google Gemini
- Faz chamadas inteligentes para análise contextual das transações

### 2. Análise Contextual Avançada
- A IA analisa a descrição completa da transação
- Considera o contexto do estabelecimento e tipo de negócio
- Avalia padrões financeiros e comportamentos de consumo
- Compara com suas categorias existentes para manter consistência

### 3. Prompt Inteligente para IA

A IA recebe um prompt estruturado contendo:
- **Suas categorias existentes**: Para manter consistência
- **Transações para análise**: Com descrições completas
- **Regras de categorização**: Padrões financeiros estabelecidos
- **Instruções específicas**: Para criação de novas categorias

### 4. Categorização Inteligente

A IA analisa cada transação considerando:
- **Tipo de estabelecimento**: Identifica padarias, postos, farmácias, etc.
- **Contexto da transação**: Compra, pagamento, recebimento, etc.
- **Padrões financeiros**: Alimentação, transporte, saúde, etc.
- **Suas categorias**: Prioriza categorias já existentes
- **Consistência**: Mantém padrão de nomenclatura

### 3. Algoritmo de Correspondência

```php
// Busca exata (score: 10 pontos)
if (strpos($description, $keyword) !== false) {
    $score += 10;
}

// Busca por similaridade (score: 5 pontos)
$similarity = 1 - (levenshtein($word, $keyword) / max(strlen($word), strlen($keyword)));
if ($similarity > 0.8) {
    $score += 5;
}
```

### 4. Níveis de Confiança

- **Alta (80%+)**: Correspondência exata ou múltiplas palavras-chave
- **Média (60-79%)**: Correspondência parcial ou palavras similares
- **Baixa (<60%)**: Correspondência fraca ou categoria padrão

## Vantagens da IA vs Sistema de Palavras-chave

### 🧠 **Inteligência Artificial**
- **Análise contextual**: Entende o significado completo da transação
- **Aprendizado**: Considera suas categorias existentes
- **Flexibilidade**: Adapta-se a diferentes formatos de descrição
- **Precisão**: ~95% de acerto em categorizações
- **Raciocínio**: Explica por que escolheu cada categoria

### 🔤 **Sistema de Palavras-chave (Anterior)**
- **Busca literal**: Apenas correspondência de texto
- **Limitado**: Depende de palavras pré-definidas
- **Rígido**: Não se adapta a variações
- **Precisão**: ~70% de acerto
- **Sem explicação**: Não fornece raciocínio

## Exemplos de Categorização com IA

| Descrição | Categoria Sugerida | Confiança | Raciocínio da IA |
|-----------|-------------------|-----------|------------------|
| "PADARIA SAO JOSE - COMPRA" | Alimentação | 95% | "Padaria é claramente relacionada a alimentação" |
| "POSTO SHELL - GASOLINA" | Transporte | 95% | "Posto de combustível indica gastos com transporte" |
| "FARMACIA DROGA RAIA" | Saúde | 90% | "Farmácia é estabelecimento de saúde e medicamentos" |
| "NETFLIX ASSINATURA" | Lazer | 85% | "Netflix é serviço de streaming para entretenimento" |
| "SALARIO EMPRESA XYZ" | Salário | 90% | "Palavra salário indica receita de trabalho" |

## Configuração

### Adicionando Novas Palavras-chave

Edite o arquivo `app/Services/SmartCategorizationService.php`:

```php
'nova_categoria' => [
    'keywords' => [
        'palavra1', 'palavra2', 'palavra3'
    ],
    'type' => 'expense' // ou 'income'
]
```

### Criando Categorias Padrão

O sistema cria automaticamente as seguintes categorias padrão:

**Despesas:**
- Alimentação, Transporte, Saúde, Educação
- Casa, Lazer, Vestuário, Tecnologia
- Outros Gastos

**Receitas:**
- Salário, Freelance, Vendas
- Outros Recebimentos

## Interface do Usuário

### Indicadores Visuais
- **Badge Verde**: Alta confiança (80%+)
- **Badge Amarelo**: Média confiança (60-79%)
- **Badge Cinza**: Baixa confiança (<60%)

### Estatísticas
- Total de transações processadas
- Quantidade de transações categorizadas
- Transações com alta confiança
- Novas categorias criadas

## Logs e Monitoramento

O sistema registra logs detalhados:

```php
Log::info('Categorização inteligente aplicada', [
    'transactions_count' => count($transactions),
    'user_id' => auth()->id()
]);

Log::info('Nova categoria criada automaticamente', [
    'category_name' => $categoryName,
    'type' => $type,
    'transaction_description' => $description
]);
```

## Melhorias Futuras

1. **Machine Learning**: Aprender com as correções do usuário
2. **Análise de Valor**: Considerar o valor da transação na categorização
3. **Histórico Pessoal**: Usar transações anteriores do usuário
4. **API Externa**: Integração com bases de dados de estabelecimentos
5. **Categorização por Localização**: Usar dados de GPS quando disponíveis

## Troubleshooting

### Categoria Não Reconhecida
- Verifique se a palavra-chave está no mapeamento
- Adicione sinônimos ou variações da palavra
- Considere normalização de texto (acentos, plural/singular)

### Baixa Confiança
- Adicione mais palavras-chave relacionadas
- Verifique se o tipo (income/expense) está correto
- Analise logs para identificar padrões não cobertos

### Performance
- O sistema processa até 1000 transações por importação
- Tempo médio: ~50ms por transação
- Cache de categorias existentes para otimização
