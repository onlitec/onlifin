# Guia de Atualização para Release v3.0.0

## Situação Atual
- Você está na branch `beta` 
- Há uma tag `v3.0.0` disponível no repositório remoto
- A tag v3.0.0 contém as seguintes funcionalidades:
  - Sistema completo de autenticação social com Google
  - Sistema de email SMTP com templates personalizados
  - Autenticação de dois fatores (2FA)
  - Melhorias gerais do sistema

## Passos para Atualização

### 1. Preparação
```bash
# Navegar para o diretório do projeto
cd /var/www/html/onlifin

# Verificar status atual
git status

# Limpar arquivos temporários (se houver)
git add -A
git commit -m "Cleanup: Remove temporary files before v3.0.0 update"
```

### 2. Backup da Branch Beta
```bash
# Criar backup da branch beta atual
git branch beta-backup-$(date +%Y%m%d-%H%M%S)

# Verificar se o backup foi criado
git branch | grep beta-backup
```

### 3. Merge da Release v3.0.0
```bash
# Fazer merge da tag v3.0.0 para a branch beta
git merge v3.0.0 -m "Merge release v3.0.0 into beta

- Sistema completo de autenticação social com Google
- Sistema de email SMTP com templates personalizados  
- Autenticação de dois fatores (2FA)
- Melhorias gerais do sistema
- Atualização para versão 3.0.0"
```

### 4. Verificar Resultado
```bash
# Verificar status após o merge
git status

# Se houver conflitos, listar arquivos em conflito
git diff --name-only --diff-filter=U
```

### 5. Resolução de Conflitos (se necessário)
Se houver conflitos, você precisará:
1. Abrir cada arquivo em conflito
2. Resolver manualmente os conflitos (remover marcadores <<<<<<< HEAD, =======, >>>>>>> v3.0.0)
3. Adicionar os arquivos resolvidos: `git add <arquivo>`
4. Fazer commit: `git commit -m "Resolve merge conflicts with v3.0.0"`

### 6. Verificação Final
```bash
# Verificar se a atualização foi aplicada
git log --oneline -10

# Verificar branch atual
git branch --show-current

# Verificar diferenças com a origem
git status
```

## Principais Funcionalidades da v3.0.0

### Autenticação Social
- Login com Google implementado
- Integração completa com OAuth2
- Gerenciamento de perfis sociais

### Sistema de Email SMTP
- Templates personalizados para emails
- Configuração SMTP completa
- Notificações por email automatizadas

### Autenticação de Dois Fatores (2FA)
- Implementação completa de 2FA
- Suporte a aplicativos autenticadores
- Códigos de backup

### Melhorias Gerais
- Otimizações de performance
- Correções de bugs
- Melhorias na interface do usuário

## Comandos de Verificação Pós-Atualização

```bash
# Verificar se todas as funcionalidades foram integradas
git log --grep="v3.0.0" --oneline

# Verificar arquivos modificados
git diff --name-only beta~1 beta

# Verificar se não há conflitos pendentes
git status --porcelain
```

## Próximos Passos
Após a atualização bem-sucedida:
1. Testar as novas funcionalidades
2. Verificar se não há quebras no sistema
3. Fazer push das alterações: `git push origin beta`
4. Considerar fazer deploy das atualizações

## Troubleshooting
- Se houver muitos conflitos, considere usar `git merge --abort` e tentar uma abordagem diferente
- Para reverter a atualização: `git reset --hard beta-backup-[timestamp]`
- Para ver detalhes dos conflitos: `git diff` 