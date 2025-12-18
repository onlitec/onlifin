# 🔄 Fluxo de Deploy - Onlifin

Este documento descreve o processo de CI/CD para atualizar a aplicação em produção.

## 📋 Visão Geral

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│  1. Desenvolve  │────▶│  2. Commit/Push │────▶│  3. Build/Push  │────▶│  4. Redeploy    │
│  localmente     │     │  para GitHub    │     │  para DockerHub │     │  no Coolify     │
└─────────────────┘     └─────────────────┘     └─────────────────┘     └─────────────────┘
```

---

## 🚀 Método 1: Script Automatizado (Recomendado)

Use o script `release.sh` para fazer tudo de uma vez:

```bash
./release.sh 4.0.1.0 "feat: nova funcionalidade"
```

O script vai:
1. ✅ Commit e push para GitHub
2. ✅ Build das imagens Docker (app e db)
3. ✅ Push para DockerHub
4. ✅ Criar tag de release no GitHub

Depois, no Coolify:
- Atualize a tag da imagem para a nova versão
- Clique em **Redeploy**

---

## 🛠️ Método 2: Passo a Passo Manual

### 1️⃣ Desenvolver e Testar Localmente

```bash
# Iniciar ambiente de desenvolvimento
pnpm dev

# Testar alterações...
```

### 2️⃣ Commit e Push para GitHub

```bash
# Adicionar arquivos
git add .

# Commit com mensagem descritiva
git commit -m "feat: descrição da funcionalidade"

# Push para o repositório
git push origin master
```

### 3️⃣ Build das Imagens Docker

```bash
# Build de todas as imagens
./docker-build-all.sh 4.0.1.0

# Ou manualmente:
docker build -t onlitec/onlifin:4.0.1.0 -f Dockerfile .
docker build -t onlitec/onlifin-db:4.0.1.0 -f docker/Dockerfile.postgres .
```

### 4️⃣ Push para DockerHub

```bash
# Login (se necessário)
docker login

# Push das imagens
docker push onlitec/onlifin:4.0.1.0
docker push onlitec/onlifin:latest
docker push onlitec/onlifin-db:4.0.1.0
docker push onlitec/onlifin-db:latest
```

### 5️⃣ Criar Tag de Release

```bash
git tag -a v4.0.1.0 -m "Release 4.0.1.0"
git push origin v4.0.1.0
```

### 6️⃣ Atualizar Produção (Coolify)

**Opção A: Via Interface do Coolify**
1. Acesse o Coolify
2. Vá ao serviço Onlifin
3. Edite o docker-compose
4. Atualize as tags das imagens:
   - `onlitec/onlifin:4.0.1.0`
   - `onlitec/onlifin-db:4.0.1.0`
5. Clique em **Deploy**

**Opção B: Via SSH no Servidor**
```bash
# Atualizar imagens
docker pull onlitec/onlifin:4.0.1.0
docker pull onlitec/onlifin-db:4.0.1.0

# Reiniciar containers (via Coolify ou docker-compose)
```

---

## 📝 Convenção de Versionamento

Usamos o formato **MAJOR.MINOR.PATCH.BUILD**:

| Componente | Quando Incrementar |
|------------|-------------------|
| **MAJOR** | Mudanças incompatíveis na API |
| **MINOR** | Novas funcionalidades retrocompatíveis |
| **PATCH** | Correções de bugs |
| **BUILD** | Builds de teste/hotfix |

Exemplos:
- `4.0.0.0` → `4.0.1.0` (nova feature)
- `4.0.1.0` → `4.0.1.1` (hotfix)
- `4.0.1.1` → `4.1.0.0` (várias features novas)

---

## 🔧 Convenção de Commits

Use o padrão [Conventional Commits](https://www.conventionalcommits.org/):

| Prefixo | Uso |
|---------|-----|
| `feat:` | Nova funcionalidade |
| `fix:` | Correção de bug |
| `docs:` | Apenas documentação |
| `style:` | Formatação, sem mudança de código |
| `refactor:` | Refatoração de código |
| `perf:` | Melhoria de performance |
| `test:` | Adição de testes |
| `chore:` | Tarefas de manutenção |

Exemplos:
```bash
git commit -m "feat: adicionar filtro por categoria"
git commit -m "fix: corrigir cálculo de saldo"
git commit -m "docs: atualizar README"
```

---

## 🔄 Rollback

Se precisar voltar para uma versão anterior:

### Via Coolify
1. Edite o docker-compose
2. Altere a tag para a versão anterior (ex: `4.0.0.0`)
3. Redeploy

### Via SSH
```bash
docker pull onlitec/onlifin:4.0.0.0
docker pull onlitec/onlifin-db:4.0.0.0
# Reiniciar containers
```

---

## 📂 Estrutura de Arquivos de Deploy

```
/opt/onlifin/
├── release.sh              # Script de release automatizado
├── docker-build-all.sh     # Build de todas as imagens
├── docker-build.sh         # Build apenas do app
├── docker-push.sh          # Push para DockerHub
├── docker-compose.yml      # Compose local
├── docker-compose.coolify.yml  # Compose para Coolify
├── Dockerfile              # Imagem do app
└── docker/
    └── Dockerfile.postgres # Imagem do banco
```

---

## ✅ Checklist de Deploy

- [ ] Código testado localmente
- [ ] Commit feito com mensagem descritiva
- [ ] Push para GitHub
- [ ] Build das imagens Docker
- [ ] Push para DockerHub
- [ ] Tag de release criada
- [ ] Produção atualizada no Coolify
- [ ] Verificar se a aplicação está funcionando
- [ ] Verificar logs de erro
