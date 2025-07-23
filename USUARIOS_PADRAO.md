# 👤 Usuários Padrão - Onlifin

## 📋 Visão Geral

Este documento lista os usuários padrão criados automaticamente na plataforma Onlifin após a instalação.

## 🔐 Usuários Criados Automaticamente

### **1. Usuário Administrador Principal**
```
📧 Email: admin@onlifin.com
🔑 Senha: admin123
👤 Nome: Administrador
🛡️ Tipo: Administrador
✅ Status: Ativo
```

### **2. Usuário Demonstração**
```
📧 Email: demo@onlifin.com
🔑 Senha: demo123
👤 Nome: Usuário Demo
🛡️ Tipo: Usuário Normal
✅ Status: Ativo
```

### **3. Usuário Desenvolvedor**
```
📧 Email: alfreire@onlifin.com
🔑 Senha: M3a74g20M
👤 Nome: Alfredo Freire
🛡️ Tipo: Administrador
✅ Status: Ativo
```

## 🚀 Como Acessar

### **Interface Web:**
1. Acesse: `https://sua-url/login`
2. Digite um dos emails e senhas acima
3. Clique em "Entrar"

### **API:**
```bash
# Fazer login via API
curl -X POST https://sua-url/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@onlifin.com",
    "password": "admin123",
    "device_name": "Web Browser"
  }'
```

## 🔧 Comandos Úteis

### **Criar Usuário Admin Manualmente:**
```bash
# No terminal do container
php artisan onlifin:create-admin

# Com parâmetros personalizados
php artisan onlifin:create-admin \
  --email=meu-admin@empresa.com \
  --password=minha-senha-segura \
  --name="Meu Administrador"
```

### **Executar Seeders:**
```bash
# Executar todos os seeders
php artisan db:seed

# Executar apenas seeder de usuários
php artisan db:seed --class=AdminUserSeeder
```

### **Verificar Usuários Existentes:**
```bash
# Listar usuários via tinker
php artisan tinker --execute="
\$users = \App\Models\User::all(['id', 'name', 'email', 'is_admin']);
foreach(\$users as \$user) {
    echo \$user->id . ' - ' . \$user->name . ' (' . \$user->email . ') - Admin: ' . (\$user->is_admin ? 'Sim' : 'Não') . PHP_EOL;
}
"
```

## ⚙️ Configuração via Variáveis de Ambiente

### **Para Habilitar Criação Automática:**
```env
CREATE_ADMIN_USER=true
ADMIN_EMAIL=admin@onlifin.com
ADMIN_PASSWORD=admin123
ADMIN_NAME=Administrador
```

### **Para Desabilitar:**
```env
CREATE_ADMIN_USER=false
```

## 🔒 Segurança

### **⚠️ IMPORTANTE - Produção:**
1. **Altere as senhas padrão** imediatamente após o primeiro login
2. **Desabilite usuários** que não serão utilizados
3. **Use senhas complexas** em produção
4. **Configure autenticação de dois fatores** se disponível

### **Alterar Senha via Interface:**
1. Faça login na plataforma
2. Vá em "Perfil" ou "Configurações"
3. Clique em "Alterar Senha"
4. Digite a nova senha

### **Alterar Senha via Comando:**
```bash
# No terminal do container
php artisan tinker --execute="
\$user = \App\Models\User::where('email', 'admin@onlifin.com')->first();
\$user->password = bcrypt('nova-senha-segura');
\$user->save();
echo 'Senha alterada com sucesso!';
"
```

## 🎯 Recomendações por Ambiente

### **Desenvolvimento:**
- ✅ Use as credenciais padrão
- ✅ Mantenha `CREATE_ADMIN_USER=true`
- ✅ Senhas simples são aceitáveis

### **Produção:**
- ⚠️ **ALTERE TODAS AS SENHAS** imediatamente
- ⚠️ Configure `CREATE_ADMIN_USER=false` após setup
- ⚠️ Use senhas complexas (mínimo 12 caracteres)
- ⚠️ Ative logs de auditoria

### **Exemplo de Senhas Seguras:**
```
❌ Fraca: admin123
❌ Fraca: password
❌ Fraca: 123456

✅ Forte: Onl1f1n@2024!Pr0d
✅ Forte: $3cur3P@ssw0rd#2024
✅ Forte: Adm1n!Onl1f1n$2024
```

## 🚨 Troubleshooting

### **Problema: Não consigo fazer login**
```bash
# Verificar se usuário existe
php artisan tinker --execute="
\$user = \App\Models\User::where('email', 'admin@onlifin.com')->first();
if(\$user) {
    echo 'Usuário encontrado: ' . \$user->name;
    echo ' - Ativo: ' . (\$user->is_active ? 'Sim' : 'Não');
    echo ' - Admin: ' . (\$user->is_admin ? 'Sim' : 'Não');
} else {
    echo 'Usuário não encontrado';
}
"
```

### **Problema: Esqueci a senha**
```bash
# Resetar senha para padrão
php artisan tinker --execute="
\$user = \App\Models\User::where('email', 'admin@onlifin.com')->first();
\$user->password = bcrypt('admin123');
\$user->save();
echo 'Senha resetada para: admin123';
"
```

### **Problema: Usuário não foi criado automaticamente**
```bash
# Criar manualmente
php artisan onlifin:create-admin

# Ou executar seeder
php artisan db:seed --class=AdminUserSeeder
```

## 📱 Para App Android

### **Credenciais de Teste:**
```kotlin
// Para desenvolvimento/testes
const val TEST_EMAIL = "demo@onlifin.com"
const val TEST_PASSWORD = "demo123"

// Para admin
const val ADMIN_EMAIL = "admin@onlifin.com"
const val ADMIN_PASSWORD = "admin123"
```

### **Endpoint de Login:**
```kotlin
POST /api/auth/login
{
  "email": "admin@onlifin.com",
  "password": "admin123",
  "device_name": "Android App"
}
```

## 🎉 Resumo

✅ **3 usuários** criados automaticamente
✅ **Comando personalizado** para criar admins
✅ **Seeders atualizados** sem erros
✅ **Documentação completa** de acesso
✅ **Segurança** configurável por ambiente
✅ **Troubleshooting** incluído

**🚀 Agora você tem um sistema completo de usuários padrão funcionando perfeitamente!**
