#!/bin/bash

# 🚀 Script de Setup - Onlifin Multi-Container Production
# Este script configura automaticamente o ambiente de produção

set -e

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log() {
    echo -e "${GREEN}[$(date +'%H:%M:%S')] ✅ $1${NC}"
}

error() {
    echo -e "${RED}[$(date +'%H:%M:%S')] ❌ $1${NC}"
}

warning() {
    echo -e "${YELLOW}[$(date +'%H:%M:%S')] ⚠️  $1${NC}"
}

info() {
    echo -e "${BLUE}[$(date +'%H:%M:%S')] ℹ️  $1${NC}"
}

# Função para gerar senha segura
generate_password() {
    openssl rand -base64 32 | tr -d "=+/" | cut -c1-25
}

# Função para gerar APP_KEY
generate_app_key() {
    echo "base64:$(openssl rand -base64 32)"
}

# Verificar pré-requisitos
check_prerequisites() {
    log "Verificando pré-requisitos..."
    
    # Verificar Docker
    if ! command -v docker &> /dev/null; then
        error "Docker não encontrado. Instale o Docker primeiro."
        exit 1
    fi
    
    # Verificar Docker Compose
    if ! command -v docker-compose &> /dev/null; then
        error "Docker Compose não encontrado. Instale o Docker Compose primeiro."
        exit 1
    fi
    
    # Verificar OpenSSL
    if ! command -v openssl &> /dev/null; then
        error "OpenSSL não encontrado. Instale o OpenSSL primeiro."
        exit 1
    fi
    
    log "Pré-requisitos verificados!"
}

# Configurar arquivo .env
setup_env_file() {
    log "Configurando arquivo de ambiente..."
    
    if [ -f ".env" ]; then
        warning "Arquivo .env já existe. Criando backup..."
        cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
    fi
    
    # Copiar template
    cp .env.production .env
    
    # Gerar senhas seguras
    DB_PASSWORD=$(generate_password)
    MYSQL_ROOT_PASSWORD=$(generate_password)
    REDIS_PASSWORD=$(generate_password)
    APP_KEY=$(generate_app_key)
    
    # Substituir valores no .env
    sed -i "s/DB_PASSWORD=SENHA_SEGURA_DO_BANCO_AQUI/DB_PASSWORD=$DB_PASSWORD/" .env
    sed -i "s/MYSQL_ROOT_PASSWORD=SENHA_ROOT_MYSQL_AQUI/MYSQL_ROOT_PASSWORD=$MYSQL_ROOT_PASSWORD/" .env
    sed -i "s/REDIS_PASSWORD=SENHA_REDIS_AQUI/REDIS_PASSWORD=$REDIS_PASSWORD/" .env
    sed -i "s/APP_KEY=base64:GERE_UMA_CHAVE_AQUI_COM_php_artisan_key:generate/$APP_KEY/" .env
    
    log "Arquivo .env configurado com senhas seguras!"
    
    # Mostrar informações importantes
    info "Senhas geradas:"
    info "  - Banco MySQL: $DB_PASSWORD"
    info "  - Root MySQL: $MYSQL_ROOT_PASSWORD"
    info "  - Redis: $REDIS_PASSWORD"
    info "  - APP_KEY: $APP_KEY"
    
    warning "IMPORTANTE: Salve essas senhas em local seguro!"
}

# Configurar domínio
configure_domain() {
    info "Configurando domínio..."
    
    read -p "Digite seu domínio (ex: onlifin.exemplo.com): " DOMAIN
    
    if [ -n "$DOMAIN" ]; then
        sed -i "s/onlifin.onlitec.com.br/$DOMAIN/g" .env
        log "Domínio configurado: $DOMAIN"
    else
        warning "Domínio não configurado. Usando padrão: onlifin.onlitec.com.br"
    fi
}

# Configurar email
configure_email() {
    info "Configurando email (opcional)..."
    
    read -p "Digite seu email SMTP (ou Enter para pular): " MAIL_USERNAME
    
    if [ -n "$MAIL_USERNAME" ]; then
        read -p "Digite a senha do email: " MAIL_PASSWORD
        read -p "Digite o host SMTP (padrão: smtp.gmail.com): " MAIL_HOST
        MAIL_HOST=${MAIL_HOST:-smtp.gmail.com}
        
        sed -i "s/MAIL_USERNAME=seu_email@gmail.com/MAIL_USERNAME=$MAIL_USERNAME/" .env
        sed -i "s/MAIL_PASSWORD=sua_senha_de_app/MAIL_PASSWORD=$MAIL_PASSWORD/" .env
        sed -i "s/MAIL_HOST=smtp.gmail.com/MAIL_HOST=$MAIL_HOST/" .env
        
        log "Email configurado: $MAIL_USERNAME"
    else
        warning "Email não configurado. Configure manualmente no .env se necessário."
    fi
}

# Configurar IA
configure_ai() {
    info "Configurando IA (opcional)..."
    
    read -p "Digite sua chave Groq API (ou Enter para pular): " GROQ_API_KEY
    
    if [ -n "$GROQ_API_KEY" ]; then
        sed -i "s/GROQ_API_KEY=sua_chave_groq_aqui/GROQ_API_KEY=$GROQ_API_KEY/" .env
        log "Groq API configurada"
    else
        warning "Groq API não configurada. Chat com IA não funcionará."
    fi
}

# Criar diretórios necessários
create_directories() {
    log "Criando diretórios necessários..."
    
    mkdir -p backups
    mkdir -p docker/mysql
    mkdir -p scripts
    
    # Configurar permissões
    chmod +x scripts/backup.sh
    chmod 755 backups
    
    log "Diretórios criados!"
}

# Baixar imagem Docker
pull_docker_image() {
    log "Baixando imagem Docker mais recente..."
    
    docker pull onlitec/onlifin:api
    
    log "Imagem Docker baixada!"
}

# Iniciar serviços
start_services() {
    log "Iniciando serviços..."
    
    # Parar serviços existentes se estiverem rodando
    docker-compose -f docker-compose.prod.yml down 2>/dev/null || true
    
    # Iniciar serviços
    docker-compose -f docker-compose.prod.yml up -d
    
    log "Serviços iniciados!"
    
    # Aguardar inicialização
    info "Aguardando inicialização dos serviços..."
    sleep 30
    
    # Verificar status
    docker-compose -f docker-compose.prod.yml ps
}

# Executar migrações
run_migrations() {
    log "Executando migrações do banco de dados..."
    
    # Aguardar MySQL estar pronto
    info "Aguardando MySQL estar pronto..."
    sleep 20
    
    # Executar migrações
    docker-compose -f docker-compose.prod.yml exec -T onlifin-app php artisan migrate --force
    
    log "Migrações executadas!"
}

# Verificar funcionamento
verify_installation() {
    log "Verificando instalação..."
    
    # Testar API
    if curl -f http://localhost/api/docs > /dev/null 2>&1; then
        log "✅ API funcionando corretamente!"
        info "Acesse: http://localhost/api/docs"
    else
        error "❌ API não está respondendo"
        info "Verifique os logs: docker-compose -f docker-compose.prod.yml logs"
    fi
    
    # Testar aplicação web
    if curl -f http://localhost > /dev/null 2>&1; then
        log "✅ Aplicação web funcionando!"
        info "Acesse: http://localhost"
    else
        warning "⚠️ Aplicação web pode não estar funcionando"
    fi
}

# Mostrar informações finais
show_final_info() {
    echo ""
    log "🎉 Setup concluído com sucesso!"
    echo ""
    info "📱 URLs disponíveis:"
    info "  - Aplicação: http://localhost"
    info "  - API: http://localhost/api"
    info "  - Documentação API: http://localhost/api/docs"
    echo ""
    info "🐳 Comandos úteis:"
    info "  - Ver logs: docker-compose -f docker-compose.prod.yml logs -f"
    info "  - Parar: docker-compose -f docker-compose.prod.yml down"
    info "  - Reiniciar: docker-compose -f docker-compose.prod.yml restart"
    info "  - Status: docker-compose -f docker-compose.prod.yml ps"
    echo ""
    info "💾 Backups automáticos configurados em: ./backups/"
    echo ""
    warning "🔒 IMPORTANTE:"
    warning "  - Configure SSL/HTTPS para produção"
    warning "  - Configure firewall adequadamente"
    warning "  - Monitore logs regularmente"
    warning "  - Faça backup das senhas geradas"
}

# Função principal
main() {
    echo -e "${BLUE}🚀 Setup Onlifin Multi-Container Production${NC}"
    echo ""
    
    check_prerequisites
    setup_env_file
    configure_domain
    configure_email
    configure_ai
    create_directories
    pull_docker_image
    start_services
    run_migrations
    verify_installation
    show_final_info
}

# Executar se chamado diretamente
if [ "${BASH_SOURCE[0]}" == "${0}" ]; then
    main "$@"
fi
