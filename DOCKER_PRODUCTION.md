# 🚀 Onlifin - Deploy em Produção com Docker

Este guia descreve como fazer o deploy do Onlifin em produção usando Docker.

## 📋 Pré-requisitos para Produção

- Servidor Linux (Ubuntu 20.04+ recomendado)
- Docker 20.10+
- Docker Compose 2.0+
- 4GB+ de RAM
- 20GB+ de espaço em disco
- Domínio configurado (opcional)
- Certificado SSL (recomendado)

## 🔧 Configuração do Servidor

### 1. Instalar Docker
```bash
# Ubuntu/Debian
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# Instalar Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
```

### 2. Configurar Firewall
```bash
# UFW (Ubuntu)
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS
sudo ufw enable

# Ou iptables
sudo iptables -A INPUT -p tcp --dport 22 -j ACCEPT
sudo iptables -A INPUT -p tcp --dport 80 -j ACCEPT
sudo iptables -A INPUT -p tcp --dport 443 -j ACCEPT
```

## 🚀 Deploy da Aplicação

### 1. Clonar o Repositório
```bash
cd /opt
sudo git clone https://github.com/onlitec/onlifin.git
sudo chown -R $USER:$USER onlifin
cd onlifin
```

### 2. Configurar Ambiente
```bash
# Copiar configuração de produção
cp .env.docker .env

# Editar configurações
nano .env
```

### Configurações Importantes para Produção:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seu-dominio.com

# Gerar chave forte
APP_KEY=base64:sua-chave-aqui

# Banco de dados (recomendado PostgreSQL ou MySQL)
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_DATABASE=onlifin_prod
DB_USERNAME=onlifin_user
DB_PASSWORD=senha-forte-aqui

# Cache e sessões (recomendado Redis)
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=redis

# Email (configure um provedor real)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=seu-email@gmail.com
MAIL_PASSWORD=sua-senha-app
MAIL_ENCRYPTION=tls

# Google Cloud (se usar)
GOOGLE_CLOUD_PROJECT_ID=seu-projeto
GOOGLE_APPLICATION_CREDENTIALS=/var/www/html/storage/app/google-credentials.json
```

### 3. Configurar Docker Compose para Produção
```yaml
# docker-compose.prod.yml
version: '3.8'

services:
  onlifin:
    build: .
    container_name: onlifin-prod
    restart: always
    environment:
      - APP_ENV=production
    volumes:
      - ./database:/var/www/html/database
      - ./storage:/var/www/html/storage
      - ./.env:/var/www/html/.env:ro
    depends_on:
      - postgres
      - redis
    networks:
      - onlifin-network

  postgres:
    image: postgres:15
    container_name: onlifin-postgres-prod
    restart: always
    environment:
      POSTGRES_DB: onlifin_prod
      POSTGRES_USER: onlifin_user
      POSTGRES_PASSWORD: senha-forte-aqui
    volumes:
      - postgres_data:/var/lib/postgresql/data
    networks:
      - onlifin-network

  redis:
    image: redis:7-alpine
    container_name: onlifin-redis-prod
    restart: always
    command: redis-server --requirepass senha-redis-aqui
    volumes:
      - redis_data:/data
    networks:
      - onlifin-network

  nginx-proxy:
    image: nginxproxy/nginx-proxy
    container_name: nginx-proxy
    restart: always
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - /var/run/docker.sock:/tmp/docker.sock:ro
      - ./certs:/etc/nginx/certs
      - ./vhost:/etc/nginx/vhost.d
      - ./html:/usr/share/nginx/html
    environment:
      - VIRTUAL_HOST=seu-dominio.com
      - LETSENCRYPT_HOST=seu-dominio.com
      - LETSENCRYPT_EMAIL=seu-email@gmail.com
    networks:
      - onlifin-network

  letsencrypt:
    image: nginxproxy/acme-companion
    container_name: nginx-proxy-acme
    restart: always
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock:ro
      - ./certs:/etc/nginx/certs
      - ./vhost:/etc/nginx/vhost.d
      - ./html:/usr/share/nginx/html
      - ./acme:/etc/acme.sh
    environment:
      - NGINX_PROXY_CONTAINER=nginx-proxy

volumes:
  postgres_data:
  redis_data:

networks:
  onlifin-network:
    driver: bridge
```

### 4. Deploy
```bash
# Build e start
docker-compose -f docker-compose.prod.yml up -d --build

# Verificar status
docker-compose -f docker-compose.prod.yml ps

# Ver logs
docker-compose -f docker-compose.prod.yml logs -f
```

## 🔒 Segurança em Produção

### 1. Configurações de Segurança
```bash
# Criar usuário específico para a aplicação
sudo useradd -r -s /bin/false onlifin
sudo chown -R onlifin:onlifin /opt/onlifin

# Configurar permissões restritivas
chmod 600 .env
chmod -R 755 /opt/onlifin
chmod -R 775 storage
```

### 2. Backup Automatizado
```bash
# Criar script de backup
cat > /opt/onlifin/backup-prod.sh << 'EOF'
#!/bin/bash
BACKUP_DIR="/opt/backups/onlifin"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR

# Backup do banco PostgreSQL
docker-compose -f /opt/onlifin/docker-compose.prod.yml exec -T postgres pg_dump -U onlifin_user onlifin_prod > $BACKUP_DIR/db_$DATE.sql

# Backup dos arquivos
tar -czf $BACKUP_DIR/files_$DATE.tar.gz -C /opt/onlifin storage database

# Manter apenas os últimos 7 backups
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete
EOF

chmod +x /opt/onlifin/backup-prod.sh

# Configurar cron para backup diário
echo "0 2 * * * /opt/onlifin/backup-prod.sh" | sudo crontab -
```

### 3. Monitoramento
```bash
# Script de monitoramento
cat > /opt/onlifin/monitor.sh << 'EOF'
#!/bin/bash
cd /opt/onlifin

# Verificar se containers estão rodando
if ! docker-compose -f docker-compose.prod.yml ps | grep -q "Up"; then
    echo "ALERTA: Containers não estão rodando!" | mail -s "Onlifin Down" admin@exemplo.com
    docker-compose -f docker-compose.prod.yml up -d
fi

# Verificar se aplicação responde
if ! curl -f -s http://localhost/ > /dev/null; then
    echo "ALERTA: Aplicação não está respondendo!" | mail -s "Onlifin Not Responding" admin@exemplo.com
fi
EOF

chmod +x /opt/onlifin/monitor.sh

# Executar a cada 5 minutos
echo "*/5 * * * * /opt/onlifin/monitor.sh" | sudo crontab -
```

## 📊 Otimização de Performance

### 1. Configurações do PHP
```ini
# docker/php.ini (produção)
memory_limit = 1G
max_execution_time = 300
opcache.enable = 1
opcache.memory_consumption = 256
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 0
opcache.validate_timestamps = 0
```

### 2. Configurações do Nginx
```nginx
# docker/nginx.conf (produção)
worker_processes auto;
worker_connections 2048;

gzip on;
gzip_comp_level 6;
gzip_min_length 1000;

client_max_body_size 100M;
client_body_timeout 60s;
client_header_timeout 60s;
```

### 3. Configurações do Banco
```yaml
# Para PostgreSQL
postgres:
  command: postgres -c shared_preload_libraries=pg_stat_statements -c pg_stat_statements.track=all -c max_connections=200
```

## 🔄 Atualizações

### Script de Atualização
```bash
cat > /opt/onlifin/update-prod.sh << 'EOF'
#!/bin/bash
cd /opt/onlifin

echo "Fazendo backup antes da atualização..."
./backup-prod.sh

echo "Baixando atualizações..."
git pull origin main

echo "Parando aplicação..."
docker-compose -f docker-compose.prod.yml down

echo "Fazendo rebuild..."
docker-compose -f docker-compose.prod.yml build --no-cache

echo "Iniciando aplicação..."
docker-compose -f docker-compose.prod.yml up -d

echo "Executando migrações..."
docker-compose -f docker-compose.prod.yml exec onlifin php artisan migrate --force

echo "Limpando cache..."
docker-compose -f docker-compose.prod.yml exec onlifin php artisan config:cache
docker-compose -f docker-compose.prod.yml exec onlifin php artisan route:cache
docker-compose -f docker-compose.prod.yml exec onlifin php artisan view:cache

echo "Atualização concluída!"
EOF

chmod +x /opt/onlifin/update-prod.sh
```

## 📞 Suporte e Troubleshooting

### Logs Importantes
```bash
# Logs da aplicação
docker-compose -f docker-compose.prod.yml logs onlifin

# Logs do banco
docker-compose -f docker-compose.prod.yml logs postgres

# Logs do sistema
sudo journalctl -u docker
```

### Comandos de Diagnóstico
```bash
# Status dos containers
docker-compose -f docker-compose.prod.yml ps

# Uso de recursos
docker stats

# Espaço em disco
df -h
docker system df
```

### Problemas Comuns

1. **Container não inicia**: Verificar logs e configurações
2. **Banco não conecta**: Verificar credenciais e rede
3. **SSL não funciona**: Verificar configuração do Let's Encrypt
4. **Performance baixa**: Verificar recursos e otimizações

Para suporte adicional, consulte a documentação ou abra uma issue no repositório.
