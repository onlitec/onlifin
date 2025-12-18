# 🚀 GitHub Actions - Deploy Automático

Este guia explica como configurar o deploy automático via GitHub Actions.

## 📋 Como Funciona

Quando você faz **push** para a branch `master`:

1. ✅ **Build** das imagens Docker (app e db)
2. ✅ **Push** para DockerHub
3. ✅ **Deploy** no servidor via SSH

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   git push  │────▶│    Build    │────▶│    Push     │────▶│   Deploy    │
│   master    │     │   Docker    │     │  DockerHub  │     │   Server    │
└─────────────┘     └─────────────┘     └─────────────┘     └─────────────┘
```

---

## 🔐 Configurar Secrets no GitHub

Vá para: **GitHub** → **Settings** → **Secrets and variables** → **Actions**

### Secrets Obrigatórios

| Secret | Descrição | Exemplo |
|--------|-----------|---------|
| `DOCKERHUB_USERNAME` | Usuário do DockerHub | `onlitec` |
| `DOCKERHUB_TOKEN` | Token do DockerHub | `dckr_pat_xxxxx` |
| `SERVER_HOST` | IP do servidor VPS | `65.109.14.53` |
| `SERVER_USER` | Usuário SSH | `root` |
| `SERVER_SSH_KEY` | Chave SSH privada | (ver abaixo) |
| `COOLIFY_SERVICE_ID` | UUID do serviço | `mosgg0s4w04g048wko4g0cw4` |

### Secrets Opcionais

| Secret | Descrição | Valor Padrão |
|--------|-----------|--------------|
| `SERVER_PORT` | Porta SSH | `22` |
| `VITE_SUPABASE_URL` | URL do Supabase | - |
| `VITE_SUPABASE_ANON_KEY` | Chave anon | - |
| `VITE_APP_ID` | ID do app | `app-7xkeeoe4bsap` |

---

## 🔑 Gerar Token do DockerHub

1. Acesse: https://hub.docker.com/settings/security
2. Clique em **New Access Token**
3. Nome: `GitHub Actions Onlifin`
4. Permissões: **Read & Write**
5. Copie o token gerado

---

## 🔑 Configurar Chave SSH

### 1. Gerar chave SSH (se não existir)

No seu computador local:

```bash
ssh-keygen -t ed25519 -C "github-actions-onlifin" -f ~/.ssh/github_deploy_key
```

### 2. Adicionar chave pública no servidor

```bash
# Copie a chave pública
cat ~/.ssh/github_deploy_key.pub

# No servidor, adicione a chave ao authorized_keys
ssh root@65.109.14.53
echo "SUA_CHAVE_PUBLICA_AQUI" >> ~/.ssh/authorized_keys
```

### 3. Adicionar chave privada no GitHub

```bash
# Copie a chave privada
cat ~/.ssh/github_deploy_key

# Cole no GitHub Secrets como SERVER_SSH_KEY
```

**IMPORTANTE**: Cole a chave **completa**, incluindo:
```
-----BEGIN OPENSSH PRIVATE KEY-----
...conteúdo...
-----END OPENSSH PRIVATE KEY-----
```

---

## 📝 Passo a Passo Completo

### 1. Criar Token do DockerHub
- Vá em https://hub.docker.com/settings/security
- Crie um token de acesso

### 2. Configurar Secrets no GitHub

1. Vá para: https://github.com/onlitec/onlifin/settings/secrets/actions
2. Clique em **New repository secret**
3. Adicione cada secret:

| Nome | Valor |
|------|-------|
| `DOCKERHUB_USERNAME` | `onlitec` |
| `DOCKERHUB_TOKEN` | `dckr_pat_xxxxx` |
| `SERVER_HOST` | `65.109.14.53` |
| `SERVER_USER` | `root` |
| `SERVER_SSH_KEY` | `-----BEGIN OPENSSH...` |
| `COOLIFY_SERVICE_ID` | `mosgg0s4w04g048wko4g0cw4` |

### 3. Testar

Faça um push para o repositório:

```bash
git add .
git commit -m "test: testar GitHub Actions deploy"
git push origin master
```

### 4. Verificar

- Vá em: https://github.com/onlitec/onlifin/actions
- Veja o status do workflow

---

## 🔄 Disparar Deploy Manual

Você pode disparar o deploy manualmente:

1. Vá em: **Actions** → **🚀 Deploy Onlifin**
2. Clique em **Run workflow**
3. Selecione a branch `master`
4. Clique em **Run workflow**

---

## 🐛 Troubleshooting

### Erro de autenticação no DockerHub

```
Error: denied: requested access to the resource is denied
```

**Solução**: Verifique se `DOCKERHUB_USERNAME` e `DOCKERHUB_TOKEN` estão corretos.

### Erro de conexão SSH

```
ssh: connect to host ... port 22: Connection refused
```

**Soluções**:
- Verifique se `SERVER_HOST` está correto
- Verifique se a porta SSH está correta (`SERVER_PORT`)
- Verifique se a chave SSH está no `authorized_keys` do servidor

### Container não reinicia

Se o container não reiniciar automaticamente, verifique o `COOLIFY_SERVICE_ID`:

```bash
# No servidor, liste os containers
docker ps -a | grep onlifin
```

---

## 📊 Status do Workflow

Você pode ver o status do último deploy na página do repositório.

Badge: ![Deploy Status](https://github.com/onlitec/onlifin/actions/workflows/deploy.yml/badge.svg)

---

## 🔧 Arquivos Relacionados

- `.github/workflows/deploy.yml` - Workflow do GitHub Actions
- `Dockerfile` - Imagem do app
- `docker/Dockerfile.postgres` - Imagem do banco
