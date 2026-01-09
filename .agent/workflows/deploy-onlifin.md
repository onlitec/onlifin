---
description: Fluxo de deploy simplificado da plataforma Onlifin
---

Este workflow automatiza o processo de build, commit, push para as branches master/main/beta, geração de tag de release e atualização dos containers em produção.

// turbo-all
Para iniciar o deploy completo, siga estes passos:

1. Gere o build de produção:
```bash
npx vite build
```

2. Salve as alterações no Git e sincronize todas as branches:
```bash
git add .
git commit -m "deploy: release v6.0.0 hotfix"
git push origin beta
git checkout master
git merge beta
git push origin master
git checkout main
git merge master
git push origin main
git checkout beta
```

3. Atualize a Tag de Release:
```bash
git tag -f v6.0.0 HEAD
git push origin v6.0.0 --force
```

4. Reinicie o frontend em produção com a nova imagem:
```bash
docker compose up -d --force-recreate frontend
```

---
**Nota:** Este workflow utiliza a tag `v6.0.0` como padrão atual. Se desejar mudar a versão, altere manualmente os comandos acima.
