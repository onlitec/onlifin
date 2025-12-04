# 📄 Importação de Arquivos OFX

## 🎯 O que é OFX?

**OFX (Open Financial Exchange)** é um formato padrão usado por bancos e instituições financeiras para troca de dados financeiros. É o formato mais comum para exportar extratos bancários e de cartão de crédito.

### Vantagens do OFX

- ✅ **Formato Padrão**: Usado pela maioria dos bancos brasileiros
- ✅ **Dados Estruturados**: Informações organizadas e completas
- ✅ **Mais Preciso**: Menos erros de parsing que CSV/TXT
- ✅ **Informações Ricas**: Inclui tipo de transação, merchant, memo
- ✅ **Suporte Universal**: Funciona com qualquer banco

## 🏦 Como Obter Arquivo OFX do Seu Banco

### Bancos Brasileiros Comuns

#### **Banco do Brasil**
1. Acesse o Internet Banking
2. Vá em "Extrato"
3. Selecione o período
4. Clique em "Exportar"
5. Escolha formato "OFX"

#### **Itaú**
1. Acesse o app ou site
2. Entre em "Extrato"
3. Toque em "Compartilhar"
4. Selecione "Exportar OFX"

#### **Bradesco**
1. Acesse o Internet Banking
2. Menu "Conta Corrente" → "Extrato"
3. Clique em "Exportar"
4. Formato "OFX"

#### **Santander**
1. Acesse o Internet Banking
2. "Conta Corrente" → "Extrato"
3. "Exportar" → "OFX"

#### **Caixa Econômica**
1. Acesse o Internet Banking
2. "Extrato" → "Exportar"
3. Selecione "OFX"

#### **Nubank**
1. Abra o app
2. Vá em "Extrato"
3. Toque nos três pontos (⋮)
4. "Exportar extrato"
5. Escolha "OFX"

#### **Inter**
1. App → "Extrato"
2. Ícone de compartilhar
3. "Exportar OFX"

## 📥 Como Importar OFX

### Método 1: Importação Completa

1. **Acesse** `/import-statements`
2. **Clique** na aba "Arquivo"
3. **Selecione** seu arquivo `.ofx`
4. **Clique** em "Analisar com IA"
5. **Revise** as transações categorizadas
6. **Ajuste** categorias se necessário
7. **Marque** novas categorias desejadas
8. **Clique** em "Cadastrar Transações"

### Método 2: Chat Rápido

1. **Acesse** `/chat`
2. **Clique** no ícone de anexo 📎
3. **Selecione** seu arquivo `.ofx`
4. **Envie** a mensagem
5. **Veja** o resumo instantâneo
6. **Clique** no link para importar (opcional)

## 📋 Formato do Arquivo OFX

### Estrutura Básica

```xml
<OFX>
  <BANKMSGSRSV1>
    <STMTTRNRS>
      <STMTRS>
        <BANKTRANLIST>
          <STMTTRN>
            <TRNTYPE>DEBIT</TRNTYPE>
            <DTPOSTED>20241201</DTPOSTED>
            <TRNAMT>-150.00</TRNAMT>
            <FITID>12345</FITID>
            <NAME>Supermercado ABC</NAME>
            <MEMO>Compra alimentação</MEMO>
          </STMTTRN>
          <STMTTRN>
            <TRNTYPE>CREDIT</TRNTYPE>
            <DTPOSTED>20241205</DTPOSTED>
            <TRNAMT>3000.00</TRNAMT>
            <FITID>12346</FITID>
            <NAME>Empresa XYZ</NAME>
            <MEMO>Salário</MEMO>
          </STMTTRN>
        </BANKTRANLIST>
      </STMTRS>
    </STMTTRNRS>
  </BANKMSGSRSV1>
</OFX>
```

### Campos Suportados

| Campo | Descrição | Uso |
|-------|-----------|-----|
| `TRNTYPE` | Tipo da transação | Determina se é receita ou despesa |
| `DTPOSTED` | Data da transação | Convertida para DD/MM/YYYY |
| `TRNAMT` | Valor da transação | Positivo = receita, Negativo = despesa |
| `NAME` | Nome do estabelecimento | Usado na descrição |
| `MEMO` | Observação adicional | Complementa a descrição |
| `FITID` | ID único da transação | Para referência |

### Tipos de Transação

| Tipo OFX | Significado | Categoria |
|----------|-------------|-----------|
| `CREDIT` | Crédito | Receita |
| `DEBIT` | Débito | Despesa |
| `DEP` | Depósito | Receita |
| `DEPOSIT` | Depósito | Receita |
| `PAYMENT` | Pagamento | Despesa |
| `CHECK` | Cheque | Despesa |

## 🔄 Processo de Importação

### 1. Detecção Automática

O sistema detecta automaticamente se o arquivo é OFX verificando:
- Presença da tag `<OFX>`
- Header `OFXHEADER:`
- Tags de transação `<STMTTRN>` ou `<CCSTMTTRN>`

### 2. Conversão SGML → XML

Arquivos OFX antigos usam formato SGML (sem tags de fechamento):
```sgml
<OFX>
<BANKMSGSRSV1>
<STMTTRNRS>
<TRNTYPE>DEBIT
<DTPOSTED>20241201
<TRNAMT>-150.00
```

O sistema converte automaticamente para XML válido:
```xml
<OFX>
  <BANKMSGSRSV1>
    <STMTTRNRS>
      <TRNTYPE>DEBIT</TRNTYPE>
      <DTPOSTED>20241201</DTPOSTED>
      <TRNAMT>-150.00</TRNAMT>
    </STMTTRNRS>
  </BANKMSGSRSV1>
</OFX>
```

### 3. Extração de Dados

Para cada transação (`<STMTTRN>` ou `<CCSTMTTRN>`):

1. **Data**: `DTPOSTED` → `DD/MM/YYYY`
2. **Descrição**: `NAME` + `MEMO`
3. **Valor**: `TRNAMT` (valor absoluto)
4. **Tipo**: Baseado em `TRNTYPE` ou sinal do valor
5. **Merchant**: Primeira palavra da descrição

### 4. Categorização com IA

Após extração, as transações são enviadas para a IA:
- Análise do merchant e descrição
- Sugestão de categorias existentes
- Criação de novas categorias se necessário
- Nível de confiança da sugestão

## ✅ Vantagens vs CSV/TXT

| Aspecto | OFX | CSV/TXT |
|---------|-----|---------|
| **Estrutura** | Padronizada | Varia por banco |
| **Parsing** | Mais confiável | Pode ter erros |
| **Informações** | Completas | Limitadas |
| **Tipo de Transação** | Explícito | Inferido |
| **Data** | Formato padrão | Vários formatos |
| **Merchant** | Identificado | Pode faltar |
| **Compatibilidade** | Universal | Específico |

## 🔍 Exemplos Práticos

### Exemplo 1: Extrato Bancário

**Arquivo OFX:**
```xml
<STMTTRN>
  <TRNTYPE>DEBIT</TRNTYPE>
  <DTPOSTED>20241215</DTPOSTED>
  <TRNAMT>-85.50</TRNAMT>
  <NAME>RESTAURANTE BOM SABOR</NAME>
  <MEMO>Almoço</MEMO>
</STMTTRN>
```

**Resultado:**
- Data: 15/12/2024
- Descrição: RESTAURANTE BOM SABOR - Almoço
- Valor: R$ 85,50
- Tipo: Despesa
- Categoria Sugerida: Alimentação

### Exemplo 2: Cartão de Crédito

**Arquivo OFX:**
```xml
<CCSTMTTRN>
  <TRNTYPE>DEBIT</TRNTYPE>
  <DTPOSTED>20241220</DTPOSTED>
  <TRNAMT>-299.90</TRNAMT>
  <NAME>AMAZON.COM.BR</NAME>
  <MEMO>Compra online</MEMO>
</CCSTMTTRN>
```

**Resultado:**
- Data: 20/12/2024
- Descrição: AMAZON.COM.BR - Compra online
- Valor: R$ 299,90
- Tipo: Despesa
- Categoria Sugerida: Compras Online

### Exemplo 3: Salário

**Arquivo OFX:**
```xml
<STMTTRN>
  <TRNTYPE>CREDIT</TRNTYPE>
  <DTPOSTED>20241205</DTPOSTED>
  <TRNAMT>5000.00</TRNAMT>
  <NAME>EMPRESA ABC LTDA</NAME>
  <MEMO>Salário Dezembro</MEMO>
</STMTTRN>
```

**Resultado:**
- Data: 05/12/2024
- Descrição: EMPRESA ABC LTDA - Salário Dezembro
- Valor: R$ 5.000,00
- Tipo: Receita
- Categoria Sugerida: Salário

## 🆘 Solução de Problemas

### "Não foi possível fazer parse do arquivo OFX"

**Causas:**
- Arquivo corrompido
- Formato inválido
- Encoding incorreto

**Soluções:**
1. Baixe o arquivo novamente do banco
2. Verifique se é realmente um arquivo OFX
3. Abra em editor de texto para verificar conteúdo
4. Tente exportar em outro formato (CSV)

### "Nenhuma transação encontrada no arquivo OFX"

**Causas:**
- Arquivo vazio
- Período sem transações
- Tags incorretas

**Soluções:**
1. Verifique se há transações no período
2. Exporte um período com transações
3. Verifique se o arquivo contém `<STMTTRN>` ou `<CCSTMTTRN>`

### "Erro ao processar transação OFX"

**Causas:**
- Campos obrigatórios faltando
- Formato de data inválido
- Valor não numérico

**Soluções:**
1. Verifique o console do navegador (F12)
2. Veja qual transação causou erro
3. Reporte o problema com exemplo do arquivo

### Datas Incorretas

**Problema:**
Datas aparecem erradas após importação

**Solução:**
- OFX usa formato YYYYMMDD
- Sistema converte para DD/MM/YYYY
- Se houver erro, verifique o formato no arquivo original

### Valores Negativos/Positivos Invertidos

**Problema:**
Receitas aparecem como despesas ou vice-versa

**Solução:**
- Verifique o campo `TRNTYPE`
- Valores negativos = despesas
- Valores positivos = receitas
- Ajuste manualmente se necessário

## 📊 Comparação de Formatos

### Quando Usar OFX

✅ **Use OFX quando:**
- Seu banco oferece exportação OFX
- Quer máxima precisão
- Precisa de informações completas
- Importa regularmente
- Quer menos erros de parsing

### Quando Usar CSV

✅ **Use CSV quando:**
- Banco não oferece OFX
- Arquivo é simples
- Quer editar antes de importar
- Tem dados de outras fontes

### Quando Usar TXT

✅ **Use TXT quando:**
- Copiou extrato da tela
- Formato não estruturado
- Teste rápido
- Poucas transações

## 🎓 Dicas Avançadas

### 1. Importação em Lote

Para importar múltiplos meses:
1. Exporte OFX de cada mês
2. Importe um por vez
3. Sistema detecta duplicatas automaticamente

### 2. Múltiplas Contas

Para gerenciar várias contas:
1. Exporte OFX de cada conta
2. Importe separadamente
3. Categorias são compartilhadas

### 3. Cartão de Crédito

Arquivos de cartão usam `<CCSTMTTRN>`:
- Mesmo processo de importação
- Detectado automaticamente
- Categorização idêntica

### 4. Validação Antes de Importar

Use o Chat para validar:
1. Envie OFX no chat
2. Veja resumo rápido
3. Confirme se está correto
4. Depois importe completo

### 5. Backup dos Arquivos

Mantenha os arquivos OFX:
- Backup dos dados originais
- Reimportar se necessário
- Auditoria futura

## 🔐 Segurança

### Dados Sensíveis

Arquivos OFX contêm:
- ✅ Transações
- ✅ Valores
- ✅ Datas
- ❌ Não contém senhas
- ❌ Não contém dados de acesso

### Boas Práticas

1. **Não compartilhe** arquivos OFX
2. **Delete** após importar
3. **Use conexão segura** (HTTPS)
4. **Verifique** origem do arquivo
5. **Mantenha** antivírus atualizado

## 📈 Estatísticas de Uso

### Performance

- **Parsing**: ~100ms para 100 transações
- **Categorização IA**: ~5-10s
- **Importação**: ~2-3s
- **Total**: ~10-15s para processo completo

### Limites

- **Tamanho máximo**: 5MB
- **Transações**: Ilimitadas
- **Formatos**: OFX 1.x e 2.x
- **Encoding**: UTF-8, ISO-8859-1

## 🚀 Próximos Passos

Após importar OFX:

1. **Revise** as categorias sugeridas
2. **Ajuste** se necessário
3. **Crie** novas categorias
4. **Confirme** a importação
5. **Verifique** os saldos atualizados
6. **Analise** seus gastos

## 📚 Recursos Adicionais

- **IMPORTACAO_EXTRATOS_IA.md** - Guia completo de importação
- **CHAT_IA_UPLOAD.md** - Guia do chat com upload
- **GUIA_RAPIDO_IMPORTACAO.md** - Referência rápida
- **RESUMO_ATUALIZACOES.md** - Detalhes técnicos

## ✅ Checklist OFX

**Antes de importar:**
- [ ] Arquivo baixado do banco
- [ ] Extensão .ofx
- [ ] Tamanho < 5MB
- [ ] Período correto
- [ ] Conta correta

**Durante a importação:**
- [ ] Arquivo detectado como OFX
- [ ] Transações extraídas
- [ ] Categorias sugeridas
- [ ] Revisão completa
- [ ] Ajustes feitos

**Após a importação:**
- [ ] Transações cadastradas
- [ ] Saldos atualizados
- [ ] Categorias criadas
- [ ] Dados corretos
- [ ] Arquivo deletado (segurança)

---

**Última atualização:** 01/12/2024  
**Versão:** 1.0.0  
**Status:** ✅ FUNCIONAL  
**Formatos:** OFX 1.x, OFX 2.x, SGML, XML
