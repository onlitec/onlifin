# 🔧 Solução de Problemas - Importação OFX

## 🐛 Erro: "Opening and ending tag mismatch"

### Descrição do Erro
```
Erro ao fazer parse do XML: This page contains the following errors:
error on line 5 at column 22: Opening and ending tag mismatch: STATUS line 4 and CODE
```

Este erro indica que o arquivo OFX tem um problema de formatação que impede o parser XML de processar corretamente.

---

## 🔍 Diagnóstico

### Passo 1: Verificar os Logs do Console

Abra o Console do Navegador (F12) e procure por estas mensagens:

```
Iniciando parse de arquivo OFX...
Tamanho do arquivo: XXXX bytes
Primeiras linhas do arquivo: ...
XML após conversão: ...
```

### Passo 2: Analisar o Conteúdo

As "Primeiras linhas do arquivo" mostram o formato original. Verifique:

1. **Tem header OFX?**
   ```
   OFXHEADER:100
   DATA:OFXSGML
   VERSION:102
   ```

2. **Tem tag `<OFX>`?**
   ```
   <OFX>
   ```

3. **As tags têm valores inline ou em linhas separadas?**
   
   **Formato SGML (sem fechamento):**
   ```
   <STATUS>
   <CODE>0
   <SEVERITY>INFO
   </STATUS>
   ```
   
   **Formato XML (com fechamento):**
   ```xml
   <STATUS>
     <CODE>0</CODE>
     <SEVERITY>INFO</SEVERITY>
   </STATUS>
   ```

---

## 🛠️ Soluções

### Solução 1: Exportar Novamente do Banco

O problema pode estar no arquivo exportado:

1. **Acesse** o Internet Banking
2. **Vá** em Extrato
3. **Exporte** novamente em formato OFX
4. **Tente** um período menor (ex: 1 mês)
5. **Verifique** se o download completou

### Solução 2: Verificar o Arquivo

Abra o arquivo OFX em um editor de texto (Notepad, VS Code):

1. **Verifique** se o arquivo não está corrompido
2. **Procure** por caracteres estranhos
3. **Confirme** que tem a tag `<OFX>` no início
4. **Verifique** se tem `</OFX>` no final

### Solução 3: Converter para CSV

Se o OFX continuar com problemas:

1. **Use** uma ferramenta online para converter OFX → CSV
2. **Ou** copie manualmente as transações para CSV
3. **Importe** o CSV no sistema

**Formato CSV:**
```csv
Data,Descrição,Valor
01/12/2024,Supermercado ABC,-150.00
05/12/2024,Salário,3000.00
```

### Solução 4: Testar com Arquivo Menor

1. **Exporte** apenas 1 semana de transações
2. **Teste** se o arquivo menor funciona
3. **Se funcionar**, importe em lotes menores

---

## 📋 Formatos OFX Suportados

### ✅ Formato SGML (Mais Comum)

```
OFXHEADER:100
DATA:OFXSGML
VERSION:102

<OFX>
<BANKMSGSRSV1>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<TRNTYPE>DEBIT
<DTPOSTED>20241201
<TRNAMT>-150.00
<NAME>Supermercado ABC
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</BANKMSGSRSV1>
</OFX>
```

### ✅ Formato XML

```xml
<?xml version="1.0" encoding="UTF-8"?>
<OFX>
  <BANKMSGSRSV1>
    <STMTTRNRS>
      <STMTRS>
        <BANKTRANLIST>
          <STMTTRN>
            <TRNTYPE>DEBIT</TRNTYPE>
            <DTPOSTED>20241201</DTPOSTED>
            <TRNAMT>-150.00</TRNAMT>
            <NAME>Supermercado ABC</NAME>
          </STMTTRN>
        </BANKTRANLIST>
      </STMTRS>
    </STMTTRNRS>
  </BANKMSGSRSV1>
</OFX>
```

---

## 🔬 Debug Avançado

### Compartilhar Logs para Análise

Se o problema persistir, compartilhe estas informações:

1. **Console do navegador** (F12):
   - Copie todas as mensagens que começam com "Iniciando parse"
   - Copie as "Primeiras linhas do arquivo"
   - Copie o "XML após conversão"

2. **Informações do banco**:
   - Qual banco exportou o OFX?
   - Qual o tamanho do arquivo?
   - Quantas transações aproximadamente?

3. **Primeiras linhas do arquivo**:
   - Abra o OFX em editor de texto
   - Copie as primeiras 20 linhas
   - **REMOVA** dados sensíveis (números de conta, valores)

### Exemplo de Log para Compartilhar

```
Banco: Banco do Brasil
Tamanho: 15KB
Transações: ~50

Console:
Iniciando parse de arquivo OFX...
Tamanho do arquivo: 15234 bytes
Primeiras linhas do arquivo:
OFXHEADER:100
DATA:OFXSGML
VERSION:102
<OFX>
<SIGNONMSGSRSV1>
<SONRS>
<STATUS>
<CODE>0
...

Primeiras linhas do arquivo (editor):
OFXHEADER:100
DATA:OFXSGML
VERSION:102
SECURITY:NONE
ENCODING:USASCII
...
```

---

## 🎯 Checklist de Verificação

**Antes de reportar o problema:**

- [ ] Tentei exportar o arquivo novamente
- [ ] Verifiquei que o arquivo não está corrompido
- [ ] Abri o arquivo em editor de texto
- [ ] Confirmei que tem `<OFX>` no início
- [ ] Confirmei que tem `</OFX>` no final
- [ ] Tentei com um período menor (1 semana)
- [ ] Verifiquei os logs do console (F12)
- [ ] Copiei as mensagens de erro completas
- [ ] Tentei converter para CSV como alternativa

**Informações para reportar:**

- [ ] Nome do banco
- [ ] Tamanho do arquivo
- [ ] Número aproximado de transações
- [ ] Logs do console
- [ ] Primeiras 20 linhas do arquivo (sem dados sensíveis)
- [ ] Mensagem de erro completa

---

## 💡 Dicas Importantes

### 1. Privacidade dos Dados

Ao compartilhar logs ou arquivos:
- ❌ **NÃO** compartilhe números de conta
- ❌ **NÃO** compartilhe valores reais
- ❌ **NÃO** compartilhe nomes de pessoas
- ✅ **PODE** compartilhar estrutura do arquivo
- ✅ **PODE** compartilhar nomes de estabelecimentos
- ✅ **PODE** compartilhar mensagens de erro

### 2. Alternativas ao OFX

Se o OFX não funcionar:

**Opção 1: CSV**
- Mais simples
- Fácil de editar
- Funciona sempre

**Opção 2: TXT**
- Copiar e colar do extrato
- Sem necessidade de arquivo
- Rápido para poucos lançamentos

**Opção 3: Manual**
- Cadastrar transações uma por uma
- Mais controle
- Melhor para poucos lançamentos

### 3. Bancos Testados

Formatos OFX testados e funcionando:

- ✅ Nubank (XML)
- ✅ Inter (XML)
- ⚠️ Banco do Brasil (SGML - pode ter problemas)
- ⚠️ Itaú (SGML - pode ter problemas)
- ⚠️ Bradesco (SGML - pode ter problemas)
- ⚠️ Santander (SGML - pode ter problemas)
- ⚠️ Caixa (SGML - pode ter problemas)

**Legenda:**
- ✅ Testado e funcionando
- ⚠️ Pode precisar de ajustes

---

## 🚀 Próximos Passos

### Se Conseguiu Importar

1. ✅ Revise as transações importadas
2. ✅ Verifique as categorias
3. ✅ Ajuste se necessário
4. ✅ Confirme a importação

### Se Não Conseguiu Importar

1. 📝 Use CSV como alternativa
2. 💬 Reporte o problema com os logs
3. 🔄 Aguarde correção do parser
4. ✋ Ou cadastre manualmente

---

## 📞 Suporte

### Informações Úteis para Suporte

Ao reportar um problema, inclua:

1. **Mensagem de erro completa**
2. **Logs do console** (primeiras linhas, XML convertido)
3. **Nome do banco**
4. **Tamanho do arquivo**
5. **Primeiras 20 linhas** do arquivo (sem dados sensíveis)
6. **Já tentou** as soluções deste guia?

### Formato de Reporte

```
PROBLEMA: Erro ao importar OFX

BANCO: Banco do Brasil
TAMANHO: 15KB
TRANSAÇÕES: ~50

ERRO:
Opening and ending tag mismatch: STATUS line 4 and CODE

LOGS DO CONSOLE:
Iniciando parse de arquivo OFX...
Tamanho do arquivo: 15234 bytes
Primeiras linhas: ...
XML após conversão: ...

PRIMEIRAS LINHAS DO ARQUIVO:
OFXHEADER:100
DATA:OFXSGML
...

JÁ TENTEI:
- [x] Exportar novamente
- [x] Arquivo menor
- [ ] Converter para CSV
```

---

## 📚 Documentação Relacionada

- **IMPORTACAO_OFX.md** - Guia completo de importação OFX
- **GUIA_RAPIDO_IMPORTACAO.md** - Guia rápido de importação
- **IMPORTACAO_EXTRATOS_IA.md** - Detalhes da categorização com IA

---

**Última atualização:** 01/12/2024  
**Versão:** 1.0.0  
**Status:** 🔧 TROUBLESHOOTING GUIDE
