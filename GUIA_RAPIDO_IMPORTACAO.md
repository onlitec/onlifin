# 🚀 Guia Rápido - Importação de Extratos

## 📌 Duas Formas de Importar

### 1️⃣ Chat IA (Análise Rápida)
**Acesse:** `/chat`

**Quando usar:**
- ✅ Quer ver um resumo rápido
- ✅ Validar se o arquivo está correto
- ✅ Não tem certeza se quer importar
- ✅ Fazer perguntas sobre o extrato

**Como usar:**
1. Clique no ícone 📎
2. Selecione seu arquivo CSV/TXT
3. Clique em Enviar ✈
4. Veja o resumo em segundos

**Resultado:**
```
✅ Análise concluída!
📊 15 transações encontradas
💡 2 novas categorias sugeridas
📋 Resumo por categoria
🔗 Link para importar
```

---

### 2️⃣ Importação Completa (Revisão Detalhada)
**Acesse:** `/import-statements`

**Quando usar:**
- ✅ Quer importar definitivamente
- ✅ Revisar cada transação
- ✅ Ajustar categorias
- ✅ Criar novas categorias

**Como usar:**
1. Faça upload do CSV ou cole o texto
2. Clique em "Analisar com IA"
3. Revise a tabela de transações
4. Ajuste categorias se necessário
5. Marque/desmarque novas categorias
6. Clique em "Cadastrar Transações"

**Resultado:**
- Transações importadas
- Categorias criadas
- Saldos atualizados

---

## 🔍 Qual Escolher?

### Use o Chat quando:
- 🏃 Quer rapidez
- 👀 Só quer ver o resumo
- ❓ Tem dúvidas sobre o arquivo
- 💬 Quer fazer perguntas

### Use a Importação quando:
- ✅ Vai importar com certeza
- 🔍 Quer revisar tudo
- ✏️ Precisa ajustar categorias
- 📊 Quer controle total

---

## 📝 Formato do Arquivo

### CSV (Recomendado)
```csv
Data,Descrição,Valor
01/12/2024,Supermercado ABC,-150.00
05/12/2024,Salário,3000.00
10/12/2024,Restaurante XYZ,-85.50
```

### TXT (Alternativo)
```
01/12/2024 Supermercado ABC R$ 150,00 Débito
05/12/2024 Salário R$ 3.000,00 Crédito
10/12/2024 Restaurante XYZ R$ 85,50 Débito
```

**Regras:**
- ✅ Valores negativos = Despesas
- ✅ Valores positivos = Receitas
- ✅ Máximo 5MB
- ✅ Formatos: CSV ou TXT

---

## ⚡ Fluxo Recomendado

### Primeira Vez
```
1. Chat (/chat)
   ↓ Anexar arquivo
   ↓ Ver resumo
   ↓ Validar dados
   
2. Importação (/import-statements)
   ↓ Upload do mesmo arquivo
   ↓ Revisar transações
   ↓ Ajustar categorias
   ↓ Importar
```

### Próximas Vezes
```
Importação (/import-statements)
↓ Upload direto
↓ Revisão rápida
↓ Importar
```

---

## 🆘 Problemas Comuns

### "Nenhuma transação encontrada"
**Solução:**
- Verifique o formato do arquivo
- Certifique-se de ter 3 colunas: Data, Descrição, Valor
- Remova linhas vazias

### "Erro ao analisar transações"
**Solução:**
- Abra o Console (F12)
- Veja os logs de erro
- Verifique sua conexão
- Tente com arquivo menor

### "Arquivo inválido"
**Solução:**
- Use apenas CSV ou TXT
- Verifique o tamanho (máximo 5MB)
- Salve novamente o arquivo

---

## 📚 Documentação Completa

- **IMPORTACAO_EXTRATOS_IA.md** - Guia completo de importação
- **CHAT_IA_UPLOAD.md** - Guia completo do chat
- **RESUMO_ATUALIZACOES.md** - Detalhes técnicos

---

## ✅ Checklist Rápido

**Antes de importar:**
- [ ] Tenho uma conta cadastrada
- [ ] Tenho categorias básicas criadas
- [ ] Meu arquivo está em CSV ou TXT
- [ ] O arquivo tem menos de 5MB
- [ ] Revisei o formato do arquivo

**Durante a importação:**
- [ ] Upload realizado com sucesso
- [ ] Análise da IA concluída
- [ ] Revisei as categorias sugeridas
- [ ] Ajustei categorias se necessário
- [ ] Marquei novas categorias desejadas

**Após a importação:**
- [ ] Transações aparecem na lista
- [ ] Saldos foram atualizados
- [ ] Categorias foram criadas
- [ ] Tudo está correto

---

## 🎯 Dicas Rápidas

1. **Use o Chat primeiro** para validar o arquivo
2. **Crie categorias básicas** antes de importar
3. **Importe regularmente** (semanal ou mensal)
4. **Revise sempre** antes de confirmar
5. **Use nomes consistentes** para categorias

---

**Última atualização:** 01/12/2024  
**Versão:** 1.0.0  
**Status:** ✅ OPERACIONAL
