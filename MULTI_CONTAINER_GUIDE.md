# 🚀 Guia Multi-Container - Onlifin Produção

## 📋 Visão Geral

Este guia explica como configurar e usar a versão **multi-container** do Onlifin para produção, que inclui:

- **📱 Container da Aplicação** (Onlifin + API)
- **🗄️ Container MySQL** (Banco de dados dedicado)
- **🔴 Container Redis** (Cache e sessões)
- **💾 Container de Backup** (Backup automático)
- **🔍 Container Watchtower** (Atualizações automáticas)

## 🎯 Vantagens da Versão Multi-Container

### ✅ **Performance**
- MySQL dedicado com configurações otimizadas
- Redis para cache e sessões rápidas
- Recursos isolados por serviço

### ✅ **Escalabilidade**
- Cada serviço pode ser escalado independentemente
- Balanceamento de carga facilitado
- Recursos dedicados por função

### ✅ **Manutenibilidade**
- Backup independente de cada serviço
- Atualizações sem afetar dados
- Logs separados por serviço

### ✅ **Segurança**
- Rede interna isolada
- Senhas geradas automaticamente
- Containers com permissões mínimas

## 🚀 Setup Automático (Recomendado)

### **Passo 1: Executar Script de Setup**
```bash
# Clonar repositório (se ainda não tiver)
git clone https://github.com/onlitec/onlifin.git
cd onlifin

# Executar setup automático
./setup-production.sh
```

### **O Script Fará Automaticamente:**
1. ✅ Verificar pré-requisitos (Docker, Docker Compose)
2. ✅ Gerar senhas seguras
3. ✅ Configurar arquivo .env
4. ✅ Solicitar configurações (domínio, email, IA)
5. ✅ Baixar imagens Docker
6. ✅ Iniciar todos os containers
7. ✅ Executar migrações
8. ✅ Verificar funcionamento

### **Resultado:**
```bash
🎉 Setup concluído com sucesso!

📱 URLs disponíveis:
  - Aplicação: http://localhost
  - API: http://localhost/api
  - Documentação API: http://localhost/api/docs

🐳 Containers rodando:
  - onlifin-app-prod (Aplicação)
  - onlifin-db-prod (MySQL)
  - onlifin-redis-prod (Redis)
  - onlifin-backup (Backup)
  - onlifin-watchtower (Monitoramento)
```

## 🔧 Setup Manual (Avançado)

### **Passo 1: Preparar Ambiente**
```bash
# Clonar repositório
git clone https://github.com/onlitec/onlifin.git
cd onlifin

# Copiar configuração
cp .env.production .env
```

### **Passo 2: Configurar Variáveis**
```bash
# Editar arquivo .env
nano .env

# Configurar pelo menos:
DB_PASSWORD=sua_senha_mysql
MYSQL_ROOT_PASSWORD=sua_senha_root
REDIS_PASSWORD=sua_senha_redis
APP_KEY=base64:sua_chave_app
APP_URL=https://seu-dominio.com
```

### **Passo 3: Iniciar Serviços**
```bash
# Baixar imagens
docker-compose -f docker-compose.prod.yml pull

# Iniciar containers
docker-compose -f docker-compose.prod.yml up -d

# Verificar status
docker-compose -f docker-compose.prod.yml ps
```

### **Passo 4: Executar Migrações**
```bash
# Aguardar MySQL estar pronto (30-60 segundos)
sleep 60

# Executar migrações
docker-compose -f docker-compose.prod.yml exec onlifin-app php artisan migrate --force
```

## 🐳 Gerenciamento dos Containers

### **Comandos Básicos**
```bash
# Ver status de todos os containers
docker-compose -f docker-compose.prod.yml ps

# Ver logs de todos os serviços
docker-compose -f docker-compose.prod.yml logs -f

# Ver logs de um serviço específico
docker-compose -f docker-compose.prod.yml logs -f onlifin-app

# Parar todos os serviços
docker-compose -f docker-compose.prod.yml down

# Reiniciar todos os serviços
docker-compose -f docker-compose.prod.yml restart

# Reiniciar um serviço específico
docker-compose -f docker-compose.prod.yml restart onlifin-app
```

### **Comandos Avançados**
```bash
# Executar comando no container da aplicação
docker-compose -f docker-compose.prod.yml exec onlifin-app php artisan --version

# Acessar shell do container
docker-compose -f docker-compose.prod.yml exec onlifin-app sh

# Backup manual do banco
docker-compose -f docker-compose.prod.yml exec onlifin-db mysqldump -u onlifin_user -p onlifin_production > backup.sql

# Restaurar backup
docker-compose -f docker-compose.prod.yml exec -T onlifin-db mysql -u onlifin_user -p onlifin_production < backup.sql
```

## 💾 Sistema de Backup

### **Backup Automático**
- ✅ **Frequência**: Diário às 2h da manhã
- ✅ **Retenção**: 7 dias (configurável)
- ✅ **Localização**: `./backups/`
- ✅ **Formato**: SQL comprimido (.gz)

### **Backup Manual**
```bash
# Executar backup imediato
docker-compose -f docker-compose.prod.yml exec onlifin-backup /backup.sh

# Listar backups
ls -la backups/

# Restaurar backup específico
gunzip backups/onlifin_backup_20240122_020000.sql.gz
docker-compose -f docker-compose.prod.yml exec -T onlifin-db mysql -u onlifin_user -p onlifin_production < backups/onlifin_backup_20240122_020000.sql
```

## 🔄 Atualizações

### **Atualização Automática (Watchtower)**
- ✅ Verifica atualizações a cada hora
- ✅ Atualiza automaticamente se nova versão disponível
- ✅ Notifica via Slack (se configurado)

### **Atualização Manual**
```bash
# Baixar nova versão
docker-compose -f docker-compose.prod.yml pull

# Reiniciar com nova versão
docker-compose -f docker-compose.prod.yml up -d

# Verificar se atualizou
docker-compose -f docker-compose.prod.yml ps
```

## 📊 Monitoramento

### **Health Checks**
Todos os containers têm health checks automáticos:
```bash
# Ver status de saúde
docker-compose -f docker-compose.prod.yml ps

# Status detalhado
docker inspect onlifin-app-prod | grep -A 10 Health
```

### **Logs Estruturados**
```bash
# Logs da aplicação
docker-compose -f docker-compose.prod.yml logs -f onlifin-app

# Logs do MySQL
docker-compose -f docker-compose.prod.yml logs -f onlifin-db

# Logs do Redis
docker-compose -f docker-compose.prod.yml logs -f onlifin-redis

# Logs do backup
docker-compose -f docker-compose.prod.yml logs -f onlifin-backup
```

### **Métricas de Recursos**
```bash
# Uso de recursos por container
docker stats

# Espaço em disco dos volumes
docker system df -v

# Informações detalhadas dos containers
docker-compose -f docker-compose.prod.yml top
```

## 🔒 Segurança

### **Rede Isolada**
- ✅ Containers se comunicam via rede interna
- ✅ Apenas portas necessárias expostas
- ✅ Subnet dedicada (172.20.0.0/16)

### **Senhas Seguras**
- ✅ Geradas automaticamente (25 caracteres)
- ✅ Diferentes para cada serviço
- ✅ Armazenadas apenas no .env

### **Configurações de Segurança**
```bash
# Verificar configurações de segurança
docker-compose -f docker-compose.prod.yml config

# Verificar rede
docker network ls
docker network inspect onlifin_onlifin-network
```

## 🚨 Troubleshooting

### **Container não inicia**
```bash
# Ver logs de erro
docker-compose -f docker-compose.prod.yml logs container-name

# Verificar configuração
docker-compose -f docker-compose.prod.yml config

# Reiniciar container específico
docker-compose -f docker-compose.prod.yml restart container-name
```

### **Banco de dados não conecta**
```bash
# Verificar se MySQL está rodando
docker-compose -f docker-compose.prod.yml ps onlifin-db

# Testar conexão
docker-compose -f docker-compose.prod.yml exec onlifin-db mysql -u onlifin_user -p

# Ver logs do MySQL
docker-compose -f docker-compose.prod.yml logs onlifin-db
```

### **API não responde**
```bash
# Verificar logs da aplicação
docker-compose -f docker-compose.prod.yml logs onlifin-app

# Testar health check
curl -f http://localhost/api/docs

# Reiniciar aplicação
docker-compose -f docker-compose.prod.yml restart onlifin-app
```

## 📱 Para o App Android

### **URLs de Produção**
- **API Base**: `http://seu-dominio.com/api`
- **Documentação**: `http://seu-dominio.com/api/docs`

### **Configurações Específicas**
- **Rate Limiting**: 60 req/min (autenticado), 10 req/min (não autenticado)
- **CORS**: Configurado para aceitar requisições do app
- **Autenticação**: Laravel Sanctum com tokens Bearer

## 🎉 Resultado Final

Com a configuração multi-container você terá:

✅ **Sistema Robusto**: MySQL + Redis dedicados
✅ **Alta Performance**: Recursos otimizados por serviço
✅ **Backup Automático**: Proteção de dados garantida
✅ **Monitoramento**: Health checks e logs estruturados
✅ **Escalabilidade**: Pronto para crescer
✅ **Segurança**: Rede isolada e senhas seguras
✅ **Manutenção Fácil**: Scripts automatizados

**🚀 Sua plataforma Onlifin estará pronta para produção com arquitetura profissional!**
