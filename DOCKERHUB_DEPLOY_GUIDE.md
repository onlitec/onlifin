# 🐳 Guia de Deploy via DockerHub - Onlifin API

## 📋 Visão Geral

Este guia explica como atualizar a versão de produção da plataforma Onlifin com todas as implementações da API usando DockerHub. Este método é ideal quando você já tem uma versão em produção rodando via Docker.

## 🎯 Vantagens do Deploy via DockerHub

✅ **Consistência**: Mesma imagem em todos os ambientes
✅ **Rollback Rápido**: Voltar para versão anterior em segundos
✅ **Zero Downtime**: Atualizações sem interrupção
✅ **Automação**: Deploy automatizado e confiável
✅ **Backup Automático**: Container anterior mantido como backup

## 📁 Arquivos Criados

### **Docker**
```
├── Dockerfile.production          # Dockerfile otimizado para produção
├── docker-compose.production.yml  # Configuração de produção atualizada
├── docker/start-production.sh     # Script de inicialização com API
└── deploy-dockerhub.sh            # Script de deploy automatizado
```

## 🚀 Processo de Deploy

### **Passo 1: Preparação**

#### 1.1 Verificar Implementações
```bash
# Verificar se todos os arquivos da API foram criados
ls -la app/Http/Controllers/Api/
ls -la app/Http/Resources/Api/
ls -la app/Http/Middleware/Api*

# Verificar rotas da API
php artisan route:list --path=api
```

#### 1.2 Commit das Alterações
```bash
# Adicionar todos os arquivos
git add .

# Commit com mensagem descritiva
git commit -m "feat: API completa para app Android v2.0.0

- Implementar autenticação Laravel Sanctum
- Adicionar CRUD completo para transações, contas e categorias
- Integrar sistema de relatórios e IA
- Configurar middleware CORS e rate limiting
- Adicionar testes automatizados
- Criar documentação completa da API"

# Push para repositório
git push origin main
```

### **Passo 2: Deploy Automatizado**

#### 2.1 Executar Script de Deploy
```bash
# Tornar script executável
chmod +x deploy-dockerhub.sh

# Executar deploy completo
./deploy-dockerhub.sh
```

#### 2.2 Opções do Script
1. **Deploy completo** - Build + Publish + Update Production
2. **Apenas build e test** - Para testar localmente
3. **Apenas publish** - Enviar para DockerHub
4. **Apenas update production** - Atualizar produção
5. **Sair**

### **Passo 3: Deploy Manual (Alternativa)**

#### 3.1 Build da Imagem
```bash
# Construir imagem de produção
docker build -f Dockerfile.production -t onlitec/onlifin:2.0.0-api .

# Taggar como latest
docker tag onlitec/onlifin:2.0.0-api onlitec/onlifin:latest
```

#### 3.2 Testar Localmente
```bash
# Executar container de teste
docker run -d --name onlifin-test \
  -p 8888:80 \
  -e APP_ENV=testing \
  -e DB_CONNECTION=sqlite \
  onlitec/onlifin:latest

# Aguardar inicialização
sleep 30

# Testar API
curl http://localhost:8888/api/docs

# Limpar teste
docker stop onlifin-test && docker rm onlifin-test
```

#### 3.3 Publicar no DockerHub
```bash
# Login no DockerHub
docker login

# Push da imagem
docker push onlitec/onlifin:2.0.0-api
docker push onlitec/onlifin:latest
```

#### 3.4 Atualizar Produção
```bash
# No servidor de produção
cd /var/www/html/onlifin

# Fazer backup do container atual
docker commit onlifin-prod onlifin-backup-$(date +%Y%m%d_%H%M%S)

# Parar aplicação
docker-compose -f docker-compose.production.yml down

# Baixar nova imagem
docker pull onlitec/onlifin:latest

# Iniciar aplicação
docker-compose -f docker-compose.production.yml up -d

# Verificar funcionamento
curl http://localhost/api/docs
```

## ⚙️ Configurações de Produção

### **Variáveis de Ambiente (.env)**
```env
# Criar arquivo .env.production no servidor
APP_ENV=production
APP_DEBUG=false
APP_URL=https://onlifin.onlitec.com.br

# Banco de dados
DB_CONNECTION=mysql
DB_HOST=onlifin-db
DB_DATABASE=onlifin_production
DB_USERNAME=onlifin_user
DB_PASSWORD=sua_senha_segura

# API Sanctum
SANCTUM_STATEFUL_DOMAINS=onlifin.onlitec.com.br,www.onlifin.onlitec.com.br
SESSION_DOMAIN=onlifin.onlitec.com.br

# API Rate Limiting
API_RATE_LIMIT=60
API_RATE_LIMIT_UNAUTHENTICATED=10

# CORS para Android
CORS_ALLOWED_ORIGINS=*
CORS_ALLOWED_METHODS=GET,POST,PUT,DELETE,OPTIONS
CORS_ALLOWED_HEADERS=Content-Type,Authorization,X-Requested-With,Accept,Origin

# IA
GROQ_API_KEY=sua_chave_groq
GROQ_MODEL=llama3-8b-8192

# Email
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_USERNAME=seu_email@gmail.com
MAIL_PASSWORD=sua_senha_app
```

### **Docker Compose Atualizado**
O arquivo `docker-compose.production.yml` foi atualizado com:
- Configurações do Laravel Sanctum
- Variáveis de ambiente da API
- CORS para app Android
- Rate limiting configurado
- Health checks da API

## 🧪 Validação Pós-Deploy

### **Testes Automáticos**
```bash
# Executar no servidor de produção
curl -X GET "https://onlifin.onlitec.com.br/api/docs"

# Testar registro de usuário
curl -X POST "https://onlifin.onlitec.com.br/api/auth/register" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Teste Deploy",
    "email": "teste@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "device_name": "Deploy Test"
  }'
```

### **Verificar Logs**
```bash
# Logs da aplicação
docker logs onlifin-prod

# Logs do banco
docker logs onlifin-db-prod

# Logs do Redis
docker logs onlifin-redis-prod
```

## 🔄 Rollback (Se Necessário)

### **Rollback Rápido**
```bash
# Parar versão atual
docker-compose -f docker-compose.production.yml down

# Voltar para backup
docker tag onlifin-backup-YYYYMMDD_HHMMSS onlitec/onlifin:latest

# Iniciar versão anterior
docker-compose -f docker-compose.production.yml up -d
```

### **Rollback Completo**
```bash
# Restaurar imagem anterior do DockerHub
docker pull onlitec/onlifin:1.0.0  # versão anterior

# Atualizar docker-compose para usar versão específica
sed -i 's/onlitec\/onlifin:latest/onlitec\/onlifin:1.0.0/' docker-compose.production.yml

# Reiniciar
docker-compose -f docker-compose.production.yml up -d
```

## 📊 Monitoramento

### **Health Checks**
```bash
# Verificar status dos containers
docker ps

# Health check da API
curl -f http://localhost/api/docs

# Verificar recursos
docker stats
```

### **Logs em Tempo Real**
```bash
# Logs da aplicação
docker logs -f onlifin-prod

# Logs de todos os serviços
docker-compose -f docker-compose.production.yml logs -f
```

## 🔧 Automação Avançada

### **CI/CD com GitHub Actions**
```yaml
# .github/workflows/deploy.yml
name: Deploy to Production

on:
  push:
    branches: [ main ]
    tags: [ 'v*' ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Build and Push Docker Image
        run: |
          docker build -f Dockerfile.production -t onlitec/onlifin:${{ github.sha }} .
          docker tag onlitec/onlifin:${{ github.sha }} onlitec/onlifin:latest
          echo ${{ secrets.DOCKER_PASSWORD }} | docker login -u ${{ secrets.DOCKER_USERNAME }} --password-stdin
          docker push onlitec/onlifin:${{ github.sha }}
          docker push onlitec/onlifin:latest
      
      - name: Deploy to Production
        run: |
          ssh ${{ secrets.PRODUCTION_USER }}@${{ secrets.PRODUCTION_HOST }} '
            cd /var/www/html/onlifin &&
            docker-compose -f docker-compose.production.yml pull &&
            docker-compose -f docker-compose.production.yml up -d
          '
```

### **Watchtower (Auto-Update)**
```yaml
# Adicionar ao docker-compose.production.yml
watchtower:
  image: containrrr/watchtower
  volumes:
    - /var/run/docker.sock:/var/run/docker.sock
  environment:
    - WATCHTOWER_CLEANUP=true
    - WATCHTOWER_POLL_INTERVAL=3600
  command: onlifin-prod
```

## 📱 Para o Desenvolvedor Android

### **URLs de Produção**
- **API Base**: `https://onlifin.onlitec.com.br/api`
- **Documentação**: `https://onlifin.onlitec.com.br/api/docs`
- **Health Check**: `https://onlifin.onlitec.com.br/up`

### **Headers Obrigatórios**
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
User-Agent: OnlifinAndroid/1.0
```

### **Rate Limiting**
- **Autenticado**: 60 requisições/minuto
- **Não autenticado**: 10 requisições/minuto

## 🎉 Resultado Final

✅ **Deploy via DockerHub configurado**
✅ **Processo automatizado e seguro**
✅ **Rollback rápido disponível**
✅ **Monitoramento e logs configurados**
✅ **API 100% funcional em produção**

**🚀 A plataforma Onlifin agora pode ser atualizada facilmente via DockerHub, mantendo a versão de produção sempre atualizada com as últimas funcionalidades da API!** 🐳📱
