# 🐳 Onlifin - Docker Hub Deployment

Este documento explica como enviar a imagem Docker da aplicação Onlifin para o Docker Hub.

## 📦 Repositório Docker Hub

**URL**: https://hub.docker.com/repository/docker/onlitec/onlifin/general  
**Namespace**: `onlitec/onlifin`

## 🚀 Processo de Deploy

### 1. **Login no Docker Hub**

```bash
# Login interativo (recomendado)
docker login -u onlitec

# Ou login via web
docker login
```

### 2. **Verificar Imagens Locais**

```bash
# Verificar imagem atual
docker images | grep onlifin

# Verificar imagens taggeadas para Docker Hub
docker images | grep onlitec/onlifin
```

### 3. **Push Manual das Imagens**

As imagens já estão taggeadas corretamente:

```bash
# Push da versão latest
docker push onlitec/onlifin:latest

# Push da versão beta
docker push onlitec/onlifin:beta

# Push da versão com hash do commit
docker push onlitec/onlifin:b3f5707
```

### 4. **Usar Scripts Automatizados**

#### Push das Imagens Existentes:
```bash
./docker-push.sh
```

#### Build Completo + Push:
```bash
./docker-build-and-push.sh
```

## 📋 Tags Disponíveis

| Tag | Descrição |
|-----|-----------|
| `latest` | Versão mais recente estável |
| `beta` | Versão da branch beta |
| `b3f5707` | Versão específica do commit |
| `YYYYMMDD-HHMMSS` | Versão com timestamp |

## 🔧 Uso da Imagem

### Pull da Imagem:
```bash
docker pull onlitec/onlifin:latest
```

### Executar Container:
```bash
# Execução simples
docker run -p 8080:80 onlitec/onlifin:latest

# Com variáveis de ambiente
docker run -p 8080:80 \
  -e DB_HOST=seu-mysql-host \
  -e DB_DATABASE=onlifin \
  -e DB_USERNAME=usuario \
  -e DB_PASSWORD=senha \
  onlitec/onlifin:latest
```

### Docker Compose:
```yaml
version: '3.8'
services:
  onlifin:
    image: onlitec/onlifin:latest
    ports:
      - "8080:80"
    environment:
      - DB_HOST=mysql
      - DB_DATABASE=onlifin
      - DB_USERNAME=root
      - DB_PASSWORD=password
    depends_on:
      - mysql
  
  mysql:
    image: mariadb:10.6
    environment:
      - MYSQL_ROOT_PASSWORD=password
      - MYSQL_DATABASE=onlifin
    volumes:
      - mysql_data:/var/lib/mysql

volumes:
  mysql_data:
```

## 📊 Status Atual

- ✅ **Imagem Local**: `onlifin_onlifin:latest` (1.41GB)
- ✅ **Tags Criadas**: `latest`, `beta`, `b3f5707`
- ✅ **Scripts**: `docker-push.sh`, `docker-build-and-push.sh`
- ⏳ **Docker Hub**: Aguardando push (requer login)

## 🔐 Credenciais Necessárias

Para fazer o push, você precisa:
1. **Username**: `onlitec`
2. **Password**: Senha da conta Docker Hub
3. **Repositório**: `onlitec/onlifin` (já configurado)

## 📝 Próximos Passos

1. Fazer login no Docker Hub: `docker login -u onlitec`
2. Executar push: `./docker-push.sh`
3. Verificar no Docker Hub: https://hub.docker.com/repository/docker/onlitec/onlifin/general
4. Testar pull da imagem: `docker pull onlitec/onlifin:latest`

## 🎯 Automação CI/CD

Para automação futura, considere:
- GitHub Actions para build automático
- Webhooks para deploy automático
- Tags automáticas baseadas em releases
- Testes automatizados antes do push
