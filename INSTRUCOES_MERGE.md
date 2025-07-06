# Instruções para Resolver o Merge Conflict

## Status Atual
- Você está na branch `beta`
- Há um conflito de merge no arquivo `commit_to_beta_v2.sh`
- O conflito já foi resolvido no arquivo (não há mais marcadores <<<<<<< HEAD, =======, >>>>>>> main)

## Comandos para Executar

Execute os seguintes comandos no terminal:

```bash
# 1. Navegar para o diretório do projeto
cd /var/www/html/onlifin

# 2. Verificar o status atual
git status

# 3. Adicionar o arquivo resolvido
git add commit_to_beta_v2.sh

# 4. Verificar se há outros conflitos
git status

# 5. Se não houver mais conflitos, fazer o commit do merge
git commit -m "Merge branch 'main' into beta - Release v2.0.0

Resolução de conflitos:
- commit_to_beta_v2.sh: conflito resolvido

Alterações incluídas:
- Novos comandos console para categorias
- Melhorias nos controllers
- Atualizações nos models
- Limpeza de migrations obsoletas"

# 6. Verificar o status final
git status
```

## Alternativa (se ainda houver problemas)

Se ainda houver problemas, você pode abortar o merge e tentar novamente:

```bash
# Abortar o merge atual
git merge --abort

# Tentar o merge novamente
git merge main

# Resolver conflitos manualmente se necessário
# Depois repetir os passos 3-6 acima
```

## Verificação Final

Após resolver o merge, você deve estar na branch `beta` com todas as alterações mescladas da `main`. 