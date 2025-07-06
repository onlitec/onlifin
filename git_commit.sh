#!/bin/bash
cd /var/www/html/onlifin
git add -A
git commit -m "Release v2.0.0 - Atualizações e melhorias

- Atualizações nos controllers: Category, FixedStatementImport, StatementImport, TempStatementImport
- Melhorias no Job ProcessUploadedFinancialFile
- Atualizações no Livewire FormModal para transações
- Melhorias nos models Category e Transaction
- Remoção de migrations obsoletas de cache e jobs
- Preparação para release v2.0.0"
git checkout beta
git push origin beta 