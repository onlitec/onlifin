# 🐳 Onlifin - Docker Setup

Este documento descreve como executar o Onlifin usando Docker.

## 📋 Pré-requisitos

- Docker 20.10+
- Docker Compose 2.0+
- 2GB de RAM disponível
- 5GB de espaço em disco

## 🚀 Início Rápido

### 1. Clone o repositório
```bash
git clone https://github.com/onlitec/onlifin.git
cd onlifin
```

### 2. Configure o ambiente
```bash
# Copie o arquivo de exemplo
cp .env.example .env

# Edite as configurações necessárias
nano .env
```

### 3. Execute com Docker Compose
```bash
# Build e start
docker-compose up -d

# Ou apenas start se já foi buildado
docker-compose start
```

### 4. Acesse a aplicação
Abra seu navegador em: http://localhost:8080

## 🔧 Configuração

### Variáveis de Ambiente Importantes

```env
# Aplicação
APP_NAME=Onlifin
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost:8080

# Banco de dados (SQLite por padrão)
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/database.sqlite

# Cache e Sessões
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database

# Google Cloud (opcional)
GOOGLE_CLOUD_PROJECT_ID=seu-projeto
GOOGLE_APPLICATION_CREDENTIALS=/caminho/para/credenciais.json
```

### Usando MySQL ou PostgreSQL

Para usar MySQL ou PostgreSQL, descomente as seções correspondentes no `docker-compose.yml` e ajuste as variáveis de ambiente:

#### MySQL
```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=onlifin
DB_USERNAME=onlifin_user
DB_PASSWORD=onlifin_password
```

#### PostgreSQL
```env
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=onlifin
DB_USERNAME=onlifin_user
DB_PASSWORD=onlifin_password
```

## 📁 Estrutura do Docker

```
docker/
├── nginx.conf          # Configuração do Nginx
├── default.conf        # Virtual host do Nginx
├── php-fpm.conf        # Configuração do PHP-FPM
├── php.ini             # Configuração do PHP
├── supervisord.conf    # Configuração do Supervisor
└── start.sh            # Script de inicialização
```

## 🛠️ Comandos Úteis

### Gerenciamento do Container
```bash
# Ver logs
docker-compose logs -f

# Parar serviços
docker-compose stop

# Reiniciar serviços
docker-compose restart

# Remover containers
docker-compose down

# Rebuild da imagem
docker-compose build --no-cache
```

### Executar Comandos Laravel
```bash
# Artisan commands
docker-compose exec onlifin php artisan migrate
docker-compose exec onlifin php artisan db:seed
docker-compose exec onlifin php artisan queue:work

# Composer
docker-compose exec onlifin composer install
docker-compose exec onlifin composer update

# Shell no container
docker-compose exec onlifin sh
```

### Backup e Restore

#### Backup do SQLite
```bash
# Backup
docker cp onlifin-app:/var/www/html/database/database.sqlite ./backup-$(date +%Y%m%d).sqlite

# Restore
docker cp ./backup-20240101.sqlite onlifin-app:/var/www/html/database/database.sqlite
docker-compose restart onlifin
```

## 🔍 Troubleshooting

### Problemas Comuns

1. **Erro de permissão**
   ```bash
   docker-compose exec onlifin chown -R www:www /var/www/html/storage
   docker-compose exec onlifin chmod -R 775 /var/www/html/storage
   ```

2. **Cache corrompido**
   ```bash
   docker-compose exec onlifin php artisan config:clear
   docker-compose exec onlifin php artisan cache:clear
   docker-compose exec onlifin php artisan view:clear
   ```

3. **Banco de dados não inicializa**
   ```bash
   docker-compose exec onlifin php artisan migrate:fresh --seed
   ```

### Logs Importantes
```bash
# Logs da aplicação
docker-compose logs onlifin

# Logs do Nginx
docker-compose exec onlifin tail -f /var/log/nginx/error.log

# Logs do PHP-FPM
docker-compose exec onlifin tail -f /var/log/php-fpm/error.log

# Logs do Laravel
docker-compose exec onlifin tail -f storage/logs/laravel.log
```

## 🔒 Segurança

### Configurações de Produção

1. **Altere senhas padrão**
2. **Configure HTTPS** (use um proxy reverso como Traefik ou nginx-proxy)
3. **Limite acesso** usando firewall
4. **Backup regular** dos dados
5. **Monitore logs** regularmente

### Exemplo com Traefik
```yaml
# Adicione ao docker-compose.yml
labels:
  - "traefik.enable=true"
  - "traefik.http.routers.onlifin.rule=Host(`onlifin.exemplo.com`)"
  - "traefik.http.routers.onlifin.tls.certresolver=letsencrypt"
```

## 📊 Monitoramento

### Health Check
```bash
# Verificar status
docker-compose ps

# Health check manual
curl -f http://localhost:8080/
```

### Métricas
O container expõe métricas básicas através dos logs do Supervisor e pode ser integrado com ferramentas como Prometheus.

## 🤝 Contribuição

Para contribuir com melhorias no Docker setup:

1. Fork o projeto
2. Crie uma branch para sua feature
3. Teste as mudanças
4. Envie um Pull Request

## 📞 Suporte

- **Issues**: https://github.com/onlitec/onlifin/issues
- **Documentação**: Consulte os arquivos de documentação no repositório
- **Email**: galvatec@gmail.com
