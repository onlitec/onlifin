# Onlifin - Docker Deployment

## Arquitetura Multi-Container

```
┌─────────────┐     ┌─────────────┐
│  onlifin-app│     │onlifin-ollama│
│   (Nginx)   │     │    (IA)     │
│    :80      │     │   :11434    │
└──────┬──────┘     └─────────────┘
       │
┌──────▼──────┐     ┌─────────────┐
│ onlifin-api │────▶│ onlifin-db  │
│ (PostgREST) │     │ (PostgreSQL)│
│   :3000     │     │   :5432     │
└─────────────┘     └─────────────┘
```

## Início Rápido

```bash
# Copiar variáveis de ambiente
cp .env.docker .env

# Iniciar todos os containers
docker-compose up -d

# Aguardar e configurar Ollama (primeira vez)
./docker/setup-ollama.sh

# Verificar status
docker-compose ps
```

## Containers

| Container | Porta | Função |
|-----------|-------|--------|
| `onlifin-app` | 80 | Frontend React |
| `onlifin-db` | 5432 | PostgreSQL |
| `onlifin-api` | 3000 | API REST |
| `onlifin-ollama` | 11434 | IA Local |

## Credenciais

- **Usuário**: `admin`
- **Senha**: `*M3a74g20M`
- **Email**: `admin@onlifin.com`

## Comandos Úteis

```bash
# Logs de todos os containers
docker-compose logs -f

# Reiniciar
docker-compose restart

# Parar
docker-compose down

# Rebuild após mudanças
docker-compose up -d --build

# Limpar tudo (inclui dados)
docker-compose down -v
```

## Volumes

- `postgres_data` - Dados do banco
- `ollama_data` - Modelos de IA

## Acesso

- **Aplicação**: http://localhost/
- **API**: http://localhost:3000/
- **Ollama**: http://localhost:11434/
