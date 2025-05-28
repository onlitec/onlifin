# Regras para Gerenciamento do Repositório Git

## Arquivo .env

1. **IMPORTANTE: O arquivo .env NUNCA deve ser removido do ambiente local.**
2. O arquivo .env deve sempre estar incluído no .gitignore para evitar que seja adicionado ao repositório remoto.
3. Se for necessário limpar o histórico de commits (usando git filter-branch ou git-filter-repo), certifique-se de preservar o arquivo .env local, realizando um backup antes de executar qualquer operação.
4. Após operações de limpeza de histórico, restaure o arquivo .env para manter as configurações do ambiente local.
5. Para novas instalações, sempre utilize o arquivo .env.example como base para criar um novo arquivo .env.

## Arquivos de Configuração Sensíveis

1. Arquivos contendo credenciais, tokens, senhas ou outras informações sensíveis nunca devem ser commitados para o repositório.
2. Utilize o .gitignore para garantir que estes arquivos não sejam acidentalmente adicionados ao stage.
3. Para documentar arquivos de configuração, crie versões "example" ou modelos sem dados sensíveis.

## Branches

1. A branch `main` contém o código em produção.
2. A branch `beta` contém o código pronto para testes.
3. A branch `develop` contém as funcionalidades em desenvolvimento.
4. Novas funcionalidades devem ser desenvolvidas em branches específicas (`feature/nome-da-funcionalidade`) e depois mescladas para `develop`.

## Commits

1. Mensagens de commit devem ser claras e descritivas.
2. Evite fazer commits de muitos arquivos com alterações não relacionadas.
3. Assegure-se de revisar o código antes de fazer commit para evitar adicionar segredos ou dados sensíveis.

## Procedimento para Recuperar Arquivo .env

Se o arquivo .env for perdido, siga estes passos:

1. Copie o arquivo .env.example: `cp .env.example .env`
2. Edite o arquivo .env com as configurações específicas do seu ambiente
3. Execute `php artisan key:generate` para gerar uma nova chave de aplicação
4. Verifique se todas as configurações necessárias estão presentes no arquivo 