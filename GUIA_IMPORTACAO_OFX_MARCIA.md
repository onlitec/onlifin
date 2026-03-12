# Guia de Importação OFX - Marcia Aparecida Domingos Freire

## 📋 Resumo da Análise

**Arquivo:** `34-565-338-marcia-aparecida-domingos-freire_01012026_a_04032026_97a7d4bd.ofx`  
**Conta:** CORA - Banco 0403 - Agência 1 - Conta 57022454  
**Período:** 01/01/2026 a 04/03/2026  
**Total de Transações:** 358

### Valores
- **Receitas:** 35 transações - R$ 18.812,53
- **Despesas:** 323 transações - R$ 18.812,70
- **Saldo Final:** R$ 0,15

## 📊 Análise Detalhada

Consulte o arquivo `analise_extrato_marcia.md` para análise completa com:
- Categorização de todas as transações
- Padrões de gastos identificados
- Receitas e despesas por categoria
- Despesas fixas mensais

## 🚀 Como Importar

### Opção 1: Usando o Script Python (Recomendado)

1. **Faça login na aplicação Onlifin primeiro**
   - Acesse: https://onlifin.onlitec.com.br
   - Faça login com suas credenciais
   - Isso garantirá que a pessoa Marcia existe no sistema

2. **Execute o script de importação**
   ```bash
   cd /home/alfreire/docker/apps/onlifin
   python3 import_ofx_marcia.py
   ```

3. **Forneça as credenciais quando solicitado**
   - O script pedirá email e senha
   - Ou você pode editar o arquivo e definir as credenciais

### Opção 2: Importação Manual via Interface

Se preferir importar manualmente:

1. Acesse a aplicação Onlifin
2. Vá para a seção de Importação de Extratos
3. Selecione a pessoa "Marcia Aparecida Domingos Freire"
4. Faça upload do arquivo OFX
5. Revise as transações categorizadas
6. Confirme a importação

## 🔧 Ajustes Necessários no Script

Se o login falhar, edite o arquivo `import_ofx_marcia.py`:

```python
# Linha 13-14: Atualize com suas credenciais
EMAIL = "seu_email@exemplo.com"
PASSWORD = "sua_senha"
```

Ou use variáveis de ambiente:

```bash
export ONLIFIN_EMAIL="seu_email@exemplo.com"
export ONLIFIN_PASSWORD="sua_senha"
python3 import_ofx_marcia.py
```

## 📝 Categorias Aplicadas

O script categoriza automaticamente as transações:

### Receitas
- **Salário**: Quallit, Hi Engenharia, Hi Comércio
- **Pagamento Recebido**: Helpseg, Gerencial, Grama Vale
- **Transferência Recebida**: Outras transferências
- **Devolução**: Devoluções de Pix

### Despesas
- **Supermercado**: Tenda, Sendas, Carrefour, Mercado Seven
- **Combustível**: Postos de gasolina
- **Restaurante**: iFood, restaurantes, açaí
- **Farmácia**: Raia, Drogasil
- **Telefone**: Claro, Vivo, Telefônica
- **Água/Luz**: Sabesp, contas de utilidades
- **Compras Online**: Marketplace, Shopee
- **Eletrônicos**: Kabum, Pichau
- **Varejo**: Havan, Leroy Merlin, Sodimac
- **Lazer**: Associações esportivas
- **Serviços Online**: Hostgator
- **Transferência Família**: Para Beatriz, Geovanna, Miguel, Alessandro
- **Transferência Entre Contas**: Para própria Marcia
- **Transferência Enviada**: Outras transferências
- **Serviços/Cobranças**: Cobranças recorrentes
- **Estacionamento**: Parking
- **Outros**: Demais transações

## ⚠️ Observações Importantes

1. **Duplicatas**: O script usa o `FITID` (ID único da transação) para evitar duplicatas
2. **Conta CORA**: Se não existir, será criada automaticamente
3. **Pessoa Marcia**: Deve existir no sistema antes da importação
4. **Categorias**: Podem ser ajustadas manualmente após a importação

## 🔍 Verificação Pós-Importação

Após a importação, verifique:

1. ✅ Total de transações importadas: 358
2. ✅ Saldo da conta CORA: R$ 0,15
3. ✅ Receitas totais: R$ 18.812,53
4. ✅ Despesas totais: R$ 18.812,70
5. ✅ Período: 01/01/2026 a 04/03/2026

## 📞 Suporte

Se encontrar problemas:

1. Verifique os logs do script
2. Confirme que a pessoa Marcia existe no sistema
3. Verifique as credenciais de acesso
4. Consulte a documentação da API em `/api/rest/v1`

## 📁 Arquivos Gerados

- `analise_extrato_marcia.md` - Análise detalhada do extrato
- `import_ofx_marcia.py` - Script de importação
- `GUIA_IMPORTACAO_OFX_MARCIA.md` - Este guia

---

**Criado em:** 05/03/2026 23:15  
**Última atualização:** 05/03/2026 23:15
