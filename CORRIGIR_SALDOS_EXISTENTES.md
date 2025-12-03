# Guia Rápido: Corrigir Saldos de Transações Já Importadas

## Problema Identificado

Você importou transações de extratos bancários, mas os saldos das contas não foram atualizados. Isso aconteceu porque o sistema anterior não tinha atualização automática de saldos.

## Solução Implementada ✅

O sistema agora possui:
- ✅ Atualização automática de saldos via trigger do banco de dados
- ✅ Recalculação manual de saldos
- ✅ Integração completa com dashboards e relatórios

## Como Corrigir os Saldos Existentes

### Opção 1: Recalcular Todos os Saldos (RECOMENDADO)

**Passo a Passo:**

1. **Acesse a página de Contas Bancárias**
   - No menu lateral, clique em "Contas"

2. **Clique no botão "Recalcular Saldos"**
   - Localizado no canto superior direito da página
   - Ao lado do botão "Nova Conta"

3. **Aguarde a confirmação**
   - O sistema irá processar todas as suas contas
   - Uma mensagem de sucesso será exibida
   - Exemplo: "3 conta(s) atualizada(s) com sucesso"

4. **Verifique os saldos**
   - Os saldos das contas serão atualizados imediatamente
   - Confira se os valores estão corretos

### Opção 2: Verificar Cada Conta Individualmente

Se preferir verificar conta por conta:

1. **Anote o saldo atual de cada conta**
   - Vá para a página "Contas"
   - Anote os saldos exibidos

2. **Clique em "Recalcular Saldos"**
   - O sistema recalculará baseado em todas as transações

3. **Compare os valores**
   - Veja a diferença entre o saldo antigo e o novo
   - Verifique se faz sentido com suas transações

## O Que o Sistema Faz ao Recalcular

O sistema executa os seguintes cálculos para cada conta:

```
Saldo Final = (Soma de todas as Receitas) - (Soma de todas as Despesas)
```

**Exemplo:**
- Receitas totais: R$ 5.000,00
- Despesas totais: R$ 3.200,00
- **Saldo calculado: R$ 1.800,00**

## Verificação de Integridade

Após recalcular, verifique:

### 1. Dashboard Principal
- [ ] O "Saldo Total" está correto?
- [ ] As "Receitas do Mês" fazem sentido?
- [ ] As "Despesas do Mês" estão corretas?

### 2. Página de Contas
- [ ] Cada conta mostra o saldo esperado?
- [ ] Os valores batem com seus extratos bancários?

### 3. Relatórios
- [ ] O gráfico de "Despesas por Categoria" está correto?
- [ ] O "Histórico Mensal" reflete suas transações?
- [ ] A "Projeção de Fluxo de Caixa" faz sentido?

## Próximas Importações

**Boa notícia!** 🎉

A partir de agora, todas as novas importações de extratos irão:
1. Criar as transações automaticamente
2. Atualizar os saldos das contas em tempo real
3. Refletir imediatamente nos dashboards e relatórios

Você **não precisará** recalcular manualmente após cada importação!

## Quando Usar a Recalculação Manual

Use o botão "Recalcular Saldos" apenas quando:
- Suspeitar de inconsistências nos saldos
- Após corrigir ou excluir muitas transações de uma vez
- Como verificação periódica (ex: uma vez por mês)
- Após importar dados históricos antigos

## Resolução de Problemas

### Problema: O saldo ainda está errado após recalcular

**Possíveis causas:**
1. **Transações duplicadas**: Verifique se você importou o mesmo extrato duas vezes
2. **Tipo incorreto**: Algumas transações podem estar marcadas como "Receita" quando deveriam ser "Despesa" (ou vice-versa)
3. **Conta errada**: Transações podem estar associadas à conta incorreta

**Como verificar:**
1. Vá para "Transações"
2. Filtre por conta
3. Ordene por data
4. Procure por:
   - Transações duplicadas (mesma data, valor e descrição)
   - Tipos incorretos (receitas que deveriam ser despesas)
   - Valores muito altos ou suspeitos

### Problema: Algumas transações não aparecem no dashboard

**Solução:**
1. Verifique se as transações têm uma conta associada
2. Confirme se a data está no período correto
3. Recarregue a página (F5)

### Problema: O botão "Recalcular Saldos" não funciona

**Solução:**
1. Verifique sua conexão com a internet
2. Abra o console do navegador (F12) e procure por erros
3. Tente fazer logout e login novamente
4. Limpe o cache do navegador

## Suporte Adicional

Se após seguir este guia você ainda tiver problemas:

1. **Documente o problema:**
   - Anote qual conta está com saldo incorreto
   - Liste as transações dessa conta
   - Calcule manualmente o saldo esperado

2. **Verifique os logs:**
   - Abra o console do navegador (F12)
   - Procure por mensagens de erro em vermelho
   - Anote as mensagens de erro

3. **Exporte seus dados:**
   - Vá para "Relatórios"
   - Exporte as transações em CSV
   - Guarde como backup

## Resumo Rápido

✅ **O que foi corrigido:**
- Sistema agora atualiza saldos automaticamente
- Trigger do banco de dados garante consistência
- Botão de recalculação manual disponível

🔧 **O que você precisa fazer:**
1. Acessar "Contas"
2. Clicar em "Recalcular Saldos"
3. Verificar se os valores estão corretos

🎯 **Resultado esperado:**
- Saldos corretos em todas as contas
- Dashboards mostrando dados precisos
- Relatórios refletindo a realidade financeira

---

**Última atualização:** 2025-12-01
**Versão do sistema:** 1.0.3 (com atualização automática de saldos)
