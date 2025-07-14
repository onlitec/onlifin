# Comando de Limpeza da Plataforma Onlifin

## 📋 Resumo

O comando `platform:clear-data` permite apagar transações e/ou categorias da plataforma de forma segura e controlada.

## 🚀 Uso do Comando

### **Sintaxe Básica**
```bash
php artisan platform:clear-data [opções]
```

### **Opções Disponíveis**

| Opção | Descrição |
|-------|-----------|
| `--transactions` | Apagar apenas transações |
| `--categories` | Apagar apenas categorias |
| `--all` | Apagar transações e categorias |
| `--user=ID` | Apagar dados de usuário específico |
| `--force` | Pular confirmação de segurança |

## 📊 **Exemplos de Uso**

### **1. Apagar Todas as Transações e Categorias**
```bash
php artisan platform:clear-data --all
```
**Resultado**: Remove todas as transações e categorias de todos os usuários

### **2. Apagar Apenas Transações**
```bash
php artisan platform:clear-data --transactions
```
**Resultado**: Remove apenas as transações, mantém categorias

### **3. Apagar Apenas Categorias**
```bash
php artisan platform:clear-data --categories
```
**Resultado**: Remove apenas as categorias, mantém transações

### **4. Apagar Dados de Usuário Específico**
```bash
php artisan platform:clear-data --all --user=2
```
**Resultado**: Remove transações e categorias apenas do usuário ID 2

### **5. Limpeza Forçada (Sem Confirmação)**
```bash
php artisan platform:clear-data --all --force
```
**Resultado**: Executa limpeza sem pedir confirmação

## 🛡️ **Recursos de Segurança**

### **1. Confirmação Obrigatória**
- Por padrão, o comando pede confirmação antes de executar
- Usuário deve digitar `CONFIRMAR` para prosseguir
- Use `--force` apenas em scripts automatizados

### **2. Estatísticas Antes e Depois**
- Mostra quantos registros existem antes da limpeza
- Mostra quantos registros foram apagados
- Mostra estatísticas finais após a limpeza

### **3. Transações de Banco**
- Usa transações de banco de dados para garantir consistência
- Se houver erro, todas as alterações são revertidas
- Logs detalhados de todas as operações

### **4. Processamento em Lotes**
- Transações são apagadas em lotes de 1000 para evitar problemas de memória
- Mostra progresso durante a operação
- Otimizado para grandes volumes de dados

## 📊 **Exemplo de Execução**

```bash
$ php artisan platform:clear-data --all

🗑️  Comando de Limpeza da Plataforma Onlifin
   Data/Hora: 13/07/2025 22:54:34
🌐 Escopo: Todos os usuários

📊 Estatísticas atuais:
   👥 Usuários: 7
   🏦 Contas: 8
   💰 Transações: 3
   📂 Categorias: 1
   💵 Valor total: R$ 1.700,00

⚠️  ATENÇÃO: Esta operação é IRREVERSÍVEL!
   • Todas as transações serão PERMANENTEMENTE apagadas
   • Todas as categorias serão PERMANENTEMENTE apagadas

Digite 'CONFIRMAR' para prosseguir (qualquer outra coisa cancela): CONFIRMAR

🚀 Iniciando limpeza...
🗑️  Apagando transações...
   Progresso: 3/3 transações apagadas
🗑️  Apagando categorias...
   Categorias apagadas: 1

✅ Limpeza concluída com sucesso!
   Transações apagadas: 3
   Categorias apagadas: 1

📊 Estatísticas após limpeza:
   👥 Usuários: 7
   🏦 Contas: 8
   💰 Transações: 0
   📂 Categorias: 0
```

## ⚠️ **Avisos Importantes**

### **1. Operação Irreversível**
- **NÃO HÁ COMO DESFAZER** a operação após executada
- Certifique-se de ter backup dos dados se necessário
- Teste primeiro em ambiente de desenvolvimento

### **2. Dados Preservados**
- **Usuários**: Nunca são apagados
- **Contas**: Nunca são apagadas
- **Configurações**: Mantidas intactas
- **Logs**: Preservados para auditoria

### **3. Impacto na Aplicação**
- Usuários verão contas vazias após a limpeza
- Relatórios e dashboards mostrarão dados zerados
- Histórico de transações será perdido permanentemente

## 🔧 **Casos de Uso Comuns**

### **1. Reset Completo para Testes**
```bash
php artisan platform:clear-data --all --force
```
**Quando usar**: Preparar ambiente para novos testes

### **2. Limpeza de Dados de Demonstração**
```bash
php artisan platform:clear-data --all --user=1
```
**Quando usar**: Remover dados de demo de usuário específico

### **3. Reset de Categorias**
```bash
php artisan platform:clear-data --categories
```
**Quando usar**: Recriar sistema de categorias do zero

### **4. Limpeza de Transações de Teste**
```bash
php artisan platform:clear-data --transactions --user=2
```
**Quando usar**: Remover transações de teste de usuário específico

## 📝 **Logs e Auditoria**

### **Logs Gerados**
- Todas as operações são registradas em `storage/logs/laravel.log`
- Inclui timestamp, usuário executor, e detalhes da operação
- Registra erros e exceções para debug

### **Exemplo de Log**
```
[2025-07-13 22:54:34] local.INFO: Limpeza da plataforma executada {
    "user_id": null,
    "target_user_id": null,
    "deleted_transactions": 3,
    "deleted_categories": 1,
    "timestamp": "2025-07-13T22:54:34.000000Z"
}
```

## 🛠️ **Troubleshooting**

### **Erro: Column 'system' not found**
**Solução**: O comando foi atualizado para detectar automaticamente se a coluna existe

### **Erro: Permission denied nos logs**
**Solução**: Verificar permissões da pasta `storage/logs/`
```bash
chmod -R 775 storage/logs/
chown -R www-data:www-data storage/logs/
```

### **Erro: Memory limit exceeded**
**Solução**: O comando processa em lotes de 1000 registros para evitar isso

## ✅ **Resultado da Limpeza Executada**

### **Antes da Limpeza**
- 👥 Usuários: 7
- 🏦 Contas: 8  
- 💰 Transações: 3
- 📂 Categorias: 1
- 💵 Valor total: R$ 1.700,00

### **Após a Limpeza**
- 👥 Usuários: 7 (mantidos)
- 🏦 Contas: 8 (mantidas)
- 💰 Transações: 0 ✅
- 📂 Categorias: 0 ✅
- 💵 Valor total: R$ 0,00

## 🎯 **Conclusão**

O comando `platform:clear-data` foi executado com sucesso, removendo:
- ✅ **3 transações** apagadas
- ✅ **1 categoria** apagada
- ✅ **Usuários e contas preservados**
- ✅ **Operação segura e auditada**

A plataforma agora está limpa e pronta para novos dados. Todos os usuários e contas foram preservados, permitindo que o sistema continue funcionando normalmente.
