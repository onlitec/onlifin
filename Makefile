# Makefile para Onlifin Docker
.PHONY: help build up down restart logs shell clean dev prod backup restore

# Configurações
COMPOSE_FILE = docker-compose.yml
DEV_COMPOSE_FILE = docker-compose.yml -f docker-compose.dev.yml
SERVICE_NAME = onlifin

# Cores para output
GREEN = \033[0;32m
YELLOW = \033[1;33m
RED = \033[0;31m
NC = \033[0m

help: ## Mostrar esta ajuda
	@echo "$(GREEN)Onlifin Docker - Comandos disponíveis:$(NC)"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-15s$(NC) %s\n", $$1, $$2}'
	@echo ""

build: ## Build da imagem Docker
	@echo "$(GREEN)🔨 Fazendo build da imagem...$(NC)"
	docker-compose -f $(COMPOSE_FILE) build --no-cache

up: ## Iniciar aplicação (produção)
	@echo "$(GREEN)🚀 Iniciando Onlifin (produção)...$(NC)"
	docker-compose -f $(COMPOSE_FILE) up -d
	@echo "$(GREEN)✅ Aplicação disponível em http://localhost:8080$(NC)"

down: ## Parar aplicação
	@echo "$(YELLOW)⏹️  Parando aplicação...$(NC)"
	docker-compose -f $(COMPOSE_FILE) down

restart: ## Reiniciar aplicação
	@echo "$(YELLOW)🔄 Reiniciando aplicação...$(NC)"
	docker-compose -f $(COMPOSE_FILE) restart

logs: ## Ver logs da aplicação
	docker-compose -f $(COMPOSE_FILE) logs -f $(SERVICE_NAME)

shell: ## Acessar shell do container
	docker-compose -f $(COMPOSE_FILE) exec $(SERVICE_NAME) sh

clean: ## Limpar containers, imagens e volumes
	@echo "$(RED)🧹 Limpando containers, imagens e volumes...$(NC)"
	docker-compose -f $(COMPOSE_FILE) down --rmi all --volumes --remove-orphans
	docker system prune -f

# Comandos de desenvolvimento
dev: ## Iniciar ambiente de desenvolvimento
	@echo "$(GREEN)🛠️  Iniciando ambiente de desenvolvimento...$(NC)"
	docker-compose -f $(DEV_COMPOSE_FILE) up -d
	@echo "$(GREEN)✅ Ambiente de desenvolvimento disponível:$(NC)"
	@echo "  🌐 App: http://localhost:8080"
	@echo "  📧 MailHog: http://localhost:8025"
	@echo "  🗄️  Adminer: http://localhost:8081"

dev-down: ## Parar ambiente de desenvolvimento
	@echo "$(YELLOW)⏹️  Parando ambiente de desenvolvimento...$(NC)"
	docker-compose -f $(DEV_COMPOSE_FILE) down

dev-logs: ## Ver logs do ambiente de desenvolvimento
	docker-compose -f $(DEV_COMPOSE_FILE) logs -f

# Comandos Laravel
migrate: ## Executar migrações
	@echo "$(GREEN)🔄 Executando migrações...$(NC)"
	docker-compose -f $(COMPOSE_FILE) exec $(SERVICE_NAME) php artisan migrate

seed: ## Executar seeders
	@echo "$(GREEN)🌱 Executando seeders...$(NC)"
	docker-compose -f $(COMPOSE_FILE) exec $(SERVICE_NAME) php artisan db:seed

fresh: ## Resetar banco e executar migrações + seeders
	@echo "$(YELLOW)🔄 Resetando banco de dados...$(NC)"
	docker-compose -f $(COMPOSE_FILE) exec $(SERVICE_NAME) php artisan migrate:fresh --seed

cache-clear: ## Limpar cache da aplicação
	@echo "$(GREEN)🧹 Limpando cache...$(NC)"
	docker-compose -f $(COMPOSE_FILE) exec $(SERVICE_NAME) php artisan config:clear
	docker-compose -f $(COMPOSE_FILE) exec $(SERVICE_NAME) php artisan route:clear
	docker-compose -f $(COMPOSE_FILE) exec $(SERVICE_NAME) php artisan view:clear
	docker-compose -f $(COMPOSE_FILE) exec $(SERVICE_NAME) php artisan cache:clear

optimize: ## Otimizar aplicação para produção
	@echo "$(GREEN)⚡ Otimizando aplicação...$(NC)"
	docker-compose -f $(COMPOSE_FILE) exec $(SERVICE_NAME) php artisan config:cache
	docker-compose -f $(COMPOSE_FILE) exec $(SERVICE_NAME) php artisan route:cache
	docker-compose -f $(COMPOSE_FILE) exec $(SERVICE_NAME) php artisan view:cache

# Comandos de backup
backup: ## Fazer backup do banco SQLite
	@echo "$(GREEN)💾 Fazendo backup do banco...$(NC)"
	@mkdir -p backups
	docker cp $(SERVICE_NAME)-app:/var/www/html/database/database.sqlite ./backups/backup-$(shell date +%Y%m%d-%H%M%S).sqlite
	@echo "$(GREEN)✅ Backup salvo em ./backups/$(NC)"

restore: ## Restaurar backup do banco (use: make restore FILE=backup.sqlite)
	@if [ -z "$(FILE)" ]; then \
		echo "$(RED)❌ Especifique o arquivo: make restore FILE=backup.sqlite$(NC)"; \
		exit 1; \
	fi
	@echo "$(YELLOW)🔄 Restaurando backup $(FILE)...$(NC)"
	docker cp ./backups/$(FILE) $(SERVICE_NAME)-app:/var/www/html/database/database.sqlite
	docker-compose -f $(COMPOSE_FILE) restart $(SERVICE_NAME)
	@echo "$(GREEN)✅ Backup restaurado$(NC)"

# Comandos de monitoramento
status: ## Ver status dos containers
	docker-compose -f $(COMPOSE_FILE) ps

health: ## Verificar saúde da aplicação
	@echo "$(GREEN)🏥 Verificando saúde da aplicação...$(NC)"
	@curl -f -s http://localhost:8080/ > /dev/null && echo "$(GREEN)✅ Aplicação está saudável$(NC)" || echo "$(RED)❌ Aplicação não está respondendo$(NC)"

# Comandos de produção
prod: build up optimize ## Deploy completo para produção
	@echo "$(GREEN)🎉 Deploy de produção concluído!$(NC)"

# Comandos de instalação
install: ## Instalação completa (primeira vez)
	@echo "$(GREEN)📦 Instalação completa do Onlifin...$(NC)"
	@if [ ! -f .env ]; then cp .env.example .env; echo "$(YELLOW)⚠️  Configure o arquivo .env antes de continuar$(NC)"; fi
	$(MAKE) build
	$(MAKE) up
	sleep 10
	$(MAKE) migrate
	$(MAKE) seed
	$(MAKE) optimize
	@echo "$(GREEN)🎉 Instalação concluída! Acesse http://localhost:8080$(NC)"

# Comando padrão
.DEFAULT_GOAL := help
