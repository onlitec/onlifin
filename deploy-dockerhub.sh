#!/bin/bash

# 🐳 Script de Deploy via DockerHub - Onlifin API
# Este script constrói, publica e atualiza a versão de produção via DockerHub

set -e  # Parar execução em caso de erro

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configurações
DOCKER_IMAGE="onlitec/onlifin"
VERSION="2.0.0-api"
LATEST_TAG="latest"
PRODUCTION_SERVER="seu-servidor.com"
PRODUCTION_USER="root"

# Função para logging
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

# Verificar pré-requisitos
check_prerequisites() {
    log "Verificando pré-requisitos..."
    
    if ! command -v docker &> /dev/null; then
        error "Docker não encontrado"
        exit 1
    fi
    
    if ! command -v git &> /dev/null; then
        error "Git não encontrado"
        exit 1
    fi
    
    # Verificar se está logado no Docker Hub
    if ! docker info | grep -q "Username"; then
        warning "Não está logado no Docker Hub"
        info "Execute: docker login"
        read -p "Deseja fazer login agora? (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            docker login
        else
            exit 1
        fi
    fi
    
    log "Pré-requisitos verificados!"
}

# Verificar mudanças no código
check_changes() {
    log "Verificando mudanças no código..."
    
    # Verificar se há mudanças não commitadas
    if ! git diff-index --quiet HEAD --; then
        warning "Há mudanças não commitadas"
        git status --porcelain
        read -p "Deseja continuar mesmo assim? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 1
        fi
    fi
    
    # Mostrar últimos commits
    info "Últimos commits:"
    git log --oneline -5
    
    log "Verificação de mudanças concluída!"
}

# Construir imagem Docker
build_image() {
    log "Construindo imagem Docker..."
    
    # Usar Dockerfile de produção se existir
    if [ -f "Dockerfile.production" ]; then
        DOCKERFILE="Dockerfile.production"
        info "Usando Dockerfile.production"
    else
        DOCKERFILE="Dockerfile"
        info "Usando Dockerfile padrão"
    fi
    
    # Construir imagem
    docker build -f $DOCKERFILE -t $DOCKER_IMAGE:$VERSION . || {
        error "Falha ao construir imagem Docker"
        exit 1
    }
    
    # Taggar como latest
    docker tag $DOCKER_IMAGE:$VERSION $DOCKER_IMAGE:$LATEST_TAG
    
    log "Imagem construída com sucesso!"
    docker images | grep $DOCKER_IMAGE
}

# Testar imagem localmente
test_image() {
    log "Testando imagem localmente..."
    
    # Parar container de teste se estiver rodando
    docker stop onlifin-test 2>/dev/null || true
    docker rm onlifin-test 2>/dev/null || true
    
    # Executar container de teste
    docker run -d --name onlifin-test \
        -p 8888:80 \
        -e APP_ENV=testing \
        -e DB_CONNECTION=sqlite \
        -e DB_DATABASE=/tmp/database.sqlite \
        $DOCKER_IMAGE:$VERSION || {
        error "Falha ao executar container de teste"
        exit 1
    }
    
    # Aguardar inicialização
    info "Aguardando inicialização do container..."
    sleep 30
    
    # Testar se a aplicação está respondendo
    if curl -f http://localhost:8888/api/docs > /dev/null 2>&1; then
        log "Teste local passou! API respondendo corretamente"
    else
        error "Teste local falhou! API não está respondendo"
        docker logs onlifin-test
        docker stop onlifin-test
        docker rm onlifin-test
        exit 1
    fi
    
    # Limpar container de teste
    docker stop onlifin-test
    docker rm onlifin-test
    
    log "Teste local concluído com sucesso!"
}

# Publicar no DockerHub
publish_image() {
    log "Publicando imagem no DockerHub..."
    
    # Push da versão específica
    docker push $DOCKER_IMAGE:$VERSION || {
        error "Falha ao fazer push da versão $VERSION"
        exit 1
    }
    
    # Push da tag latest
    docker push $DOCKER_IMAGE:$LATEST_TAG || {
        error "Falha ao fazer push da tag latest"
        exit 1
    }
    
    log "Imagem publicada com sucesso no DockerHub!"
    info "Imagem disponível em: https://hub.docker.com/r/$DOCKER_IMAGE"
}

# Atualizar produção
update_production() {
    log "Atualizando produção..."
    
    if [ -z "$PRODUCTION_SERVER" ] || [ "$PRODUCTION_SERVER" = "seu-servidor.com" ]; then
        warning "Servidor de produção não configurado"
        info "Configure as variáveis PRODUCTION_SERVER e PRODUCTION_USER no script"
        read -p "Deseja continuar com deploy local? (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            update_local_production
        fi
        return
    fi
    
    info "Conectando ao servidor de produção: $PRODUCTION_SERVER"
    
    # Script para executar no servidor de produção
    REMOTE_SCRIPT="
        set -e
        echo '🐳 Atualizando Onlifin em produção...'
        
        # Ir para diretório da aplicação
        cd /var/www/html/onlifin || cd /opt/onlifin || cd ~/onlifin
        
        # Fazer backup do container atual
        echo '💾 Fazendo backup do container atual...'
        docker commit onlifin-prod onlifin-backup-\$(date +%Y%m%d_%H%M%S) || true
        
        # Parar aplicação
        echo '⏹️ Parando aplicação...'
        docker-compose -f docker-compose.production.yml down
        
        # Baixar nova imagem
        echo '📥 Baixando nova imagem...'
        docker pull $DOCKER_IMAGE:$LATEST_TAG
        
        # Iniciar aplicação
        echo '🚀 Iniciando aplicação...'
        docker-compose -f docker-compose.production.yml up -d
        
        # Aguardar inicialização
        echo '⏳ Aguardando inicialização...'
        sleep 60
        
        # Verificar se está funcionando
        echo '🔍 Verificando funcionamento...'
        if curl -f http://localhost/api/docs > /dev/null 2>&1; then
            echo '✅ Aplicação funcionando corretamente!'
        else
            echo '❌ Aplicação não está respondendo'
            echo '📋 Logs do container:'
            docker logs onlifin-prod --tail 50
            exit 1
        fi
        
        echo '🎉 Deploy concluído com sucesso!'
    "
    
    # Executar script no servidor remoto
    ssh $PRODUCTION_USER@$PRODUCTION_SERVER "$REMOTE_SCRIPT" || {
        error "Falha no deploy remoto"
        exit 1
    }
    
    log "Produção atualizada com sucesso!"
}

# Atualizar produção local
update_local_production() {
    log "Atualizando produção local..."
    
    # Verificar se docker-compose.production.yml existe
    if [ ! -f "docker-compose.production.yml" ]; then
        error "Arquivo docker-compose.production.yml não encontrado"
        exit 1
    fi
    
    # Fazer backup do container atual
    info "Fazendo backup do container atual..."
    docker commit onlifin-prod onlifin-backup-$(date +%Y%m%d_%H%M%S) 2>/dev/null || true
    
    # Parar aplicação
    info "Parando aplicação..."
    docker-compose -f docker-compose.production.yml down
    
    # Baixar nova imagem
    info "Baixando nova imagem..."
    docker pull $DOCKER_IMAGE:$LATEST_TAG
    
    # Iniciar aplicação
    info "Iniciando aplicação..."
    docker-compose -f docker-compose.production.yml up -d
    
    # Aguardar inicialização
    info "Aguardando inicialização..."
    sleep 60
    
    # Verificar se está funcionando
    info "Verificando funcionamento..."
    if curl -f http://localhost/api/docs > /dev/null 2>&1; then
        log "Aplicação funcionando corretamente!"
    else
        error "Aplicação não está respondendo"
        info "Logs do container:"
        docker logs onlifin-prod --tail 50
        exit 1
    fi
    
    log "Produção local atualizada com sucesso!"
}

# Limpeza
cleanup() {
    log "Executando limpeza..."
    
    # Remover imagens antigas (manter últimas 3 versões)
    docker images $DOCKER_IMAGE --format "table {{.Tag}}\t{{.ID}}" | \
        grep -v "latest\|$VERSION" | \
        tail -n +4 | \
        awk '{print $2}' | \
        xargs -r docker rmi 2>/dev/null || true
    
    # Limpar containers parados
    docker container prune -f
    
    # Limpar imagens não utilizadas
    docker image prune -f
    
    log "Limpeza concluída!"
}

# Função principal
main() {
    echo -e "${BLUE}🐳 Deploy Onlifin via DockerHub${NC}"
    echo "Versão: $VERSION"
    echo "Imagem: $DOCKER_IMAGE"
    echo ""
    
    # Menu de opções
    echo "Escolha uma opção:"
    echo "1) Deploy completo (build + publish + update production)"
    echo "2) Apenas build e test local"
    echo "3) Apenas publish no DockerHub"
    echo "4) Apenas update production"
    echo "5) Sair"
    
    read -p "Opção [1-5]: " -n 1 -r
    echo
    
    case $REPLY in
        1)
            check_prerequisites
            check_changes
            build_image
            test_image
            publish_image
            update_production
            cleanup
            ;;
        2)
            check_prerequisites
            build_image
            test_image
            ;;
        3)
            check_prerequisites
            publish_image
            ;;
        4)
            update_production
            ;;
        5)
            info "Saindo..."
            exit 0
            ;;
        *)
            error "Opção inválida"
            exit 1
            ;;
    esac
    
    echo ""
    log "🎉 Processo concluído com sucesso!"
    info "📱 API disponível em: http://seu-dominio.com/api"
    info "📚 Documentação: http://seu-dominio.com/api/docs"
}

# Executar função principal
main "$@"
