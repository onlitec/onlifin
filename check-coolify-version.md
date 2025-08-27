# 🔍 DIAGNÓSTICO: Versão Antiga no Coolify

## ❌ **Problemas Identificados:**
1. **Assets não carregam** (ERR_CONNECTION_REFUSED)
2. **Login não funciona** (usuários não existem)
3. **Versão desatualizada** no Coolify

## ✅ **SOLUÇÃO: Atualizar Deploy no Coolify**

### **Passos para Atualizar:**

#### **1. Acessar Coolify:**
- URL do painel Coolify
- Fazer login no painel

#### **2. Localizar Projeto Onlifin:**
- Ir para o projeto/aplicação Onlifin
- Verificar configurações atuais

#### **3. Atualizar Imagem Docker:**
- **Imagem atual**: Provavelmente uma versão antiga
- **Nova imagem**: `onlitec/onlifin:latest`
- **Tag específica**: `onlitec/onlifin:20250725-034214`

#### **4. Configurações Necessárias:**
```env
# Variáveis de ambiente essenciais
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/database.sqlite
FORCE_HTTPS=false
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

#### **5. Fazer Redeploy:**
- Salvar configurações
- Executar novo deploy
- Aguardar conclusão (pode demorar alguns minutos)

## 🎯 **Versão Corrigida Inclui:**
- ✅ **Mixed Content** resolvido
- ✅ **Assets carregam** corretamente
- ✅ **Usuários criados** automaticamente
- ✅ **Chave de criptografia** válida
- ✅ **Redis desabilitado** (sem dependências)
- ✅ **Permissões corretas**

## 🔐 **Após Atualização - Credenciais:**
- **Email**: `admin@onlifin.com`
- **Senha**: `admin123`

## 📋 **Como Verificar se Funcionou:**
1. **Assets carregam**: Não há erros ERR_CONNECTION_REFUSED
2. **Página estilizada**: CSS e JS funcionam
3. **Login funciona**: Credenciais são aceitas
4. **Dashboard carrega**: Após login, mostra interface completa

## 🚨 **IMPORTANTE:**
A versão atual no Coolify é antiga e tem vários problemas corrigidos. 
A atualização é ESSENCIAL para funcionamento correto.

## 📞 **Se Precisar de Ajuda:**
- Verificar logs do deploy no Coolify
- Confirmar se a nova imagem foi baixada
- Testar após alguns minutos da conclusão do deploy
