#!/bin/bash
cd /var/www/html/onlifin
git add -A
git commit -m "Recuperar funcionalidades SSL/HTTPS perdidas

- Recriar views: settings/ssl.blade.php e settings/diagnostics.blade.php  
- Adicionar rotas SSL: generate, renew, validate, diagnostics
- Implementar métodos SSL no SettingsController
- Adicionar import Symfony\Component\Process\Process
- Manter card SSL sempre visível na interface
- Funcionalidades completas de gerenciamento de certificados SSL"
git push origin main 