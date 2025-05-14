# Onlifin - Sistema de Gestão Financeira Pessoal

[![License](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![Build Status](https://github.com/onlitec/onlifin/actions/workflows/php.yml/badge.svg)](https://github.com/onlitec/onlifin/actions)

Sistema web moderno e completo para gestão financeira pessoal, desenvolvido com Laravel 11 e Livewire 3.

## Tecnologias

- **Backend**
  - PHP 8.2
  - Laravel 11.0
  - Livewire 3.6
  - Sanctum (Autenticação)
  - RoadRunner (Servidor de alta performance)

- **Frontend**
  - JavaScript/Alpine.js 3.14
  - TailwindCSS 3.4
  - Vite (Build System)
  - Livewire Elements (Componentes)

- **Banco de Dados**
  - MariaDB
  - MySQL

## Funcionalidades Principais

- Dashboard completo com visualização de finanças
- Gestão de transações (receitas e despesas)
- Categorização de transações
- Gestão de contas bancárias
- Relatórios e análises financeiras
- Sistema de autenticação robusto
- Gestão de usuários e permissões
- Interface responsiva
- Sistema de backup automático
- Importação de extratos bancários
- Integração com IA para categorização automática

## Regras Financeiras e Diretrizes Críticas

O sistema segue regras específicas para cálculos financeiros e manipulação de valores monetários:

- **Valores Monetários**: Armazenados em centavos no banco de dados para evitar erros de arredondamento
- **Cálculo de Saldos**: Implementação rigorosa para garantir consistência nos dados financeiros
- **Modificações em Código Financeiro**: Requer aprovação explícita e documentação
- **Documentação Detalhada**: Consulte [FINANCIAL_RULES.md](FINANCIAL_RULES.md) para informações completas

**IMPORTANTE**: Qualquer alteração em código que manipula valores monetários ou cálculos de saldo deve seguir as diretrizes em FINANCIAL_RULES.md. Os arquivos contendo lógica financeira crítica estão claramente marcados com comentários de aviso.

## Requisitos do Sistema

- PHP 8.2 ou superior
- Composer
- Node.js e NPM
- MariaDB 10.4 ou MySQL 8.0
- Nginx ou Apache
- Servidor de email configurado

## Instalação

1. Clone o repositório:
   ```bash
   git clone https://github.com/onlitec/onlifin.git
   cd onlifin
   ```

2. Instale as dependências:
   ```bash
   composer install
   npm install
   ```

3. Configure o ambiente:
   - Copie `.env.example` para `.env`
   - Configure as variáveis de ambiente
   - Execute: `php artisan key:generate`

4. Configure o banco de dados:
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

5. Compile os assets:
   ```bash
   npm run build
   ```

6. Inicie o servidor:
   ```bash
   php artisan serve
   ```

## Regras de Execução de Comandos

Sempre que for instruir comandos de terminal no projeto, crie-os de forma que possam ser copiados e executados diretamente:

- Forneça o comando completo em uma só linha, sem quebras de linha.
- Caso use ferramentas que paginam a saída (git, less, head, etc.), acrescente `| cat` ao final.
- Se for necessário trocar de diretório, inclua o `cd /caminho/para/projeto` antes do comando.
- Não inclua interações adicionais (como prompts) a menos que explicitamente solicitado.

## Estrutura de Branches

- `main`: Branch principal com código estável
- `Beta1`: Branch de desenvolvimento ativo
- `release/*`: Branches para releases
- `feature/*`: Branches para novas funcionalidades
- `fix/*`: Branches para correções
- `hotfix/*`: Branches para correções urgentes

## Padrões de Código

- Segue as PSR-12 standards
- Utiliza PHPStan para análise estática
- Emprega PHP CS Fixer para formatação
- Adota padrões Laravel
- Utiliza docblocks completos

## Testes

- Testes unitários com PHPUnit
- Testes de feature com Laravel Dusk
- Testes de API com Pest

## CI/CD

O projeto utiliza GitHub Actions para CI/CD:

### Testes Automatizados

- Testes PHP com PHPUnit
- Análise estática com PHPStan
- Formatação de código com PHP CS Fixer
- Testes JavaScript com ESLint
- Formatação JavaScript com Prettier
- Verificações de segurança

### Deploy Automatizado

O deploy é automatizado para a branch `Beta1`:

1. Testes automatizados
2. Build dos assets frontend
3. Deploy para ambiente de produção

### Status do Build

[![CI Status](https://github.com/onlitec/onlifin/workflows/CI/badge.svg)](https://github.com/onlitec/onlifin/actions)
[![Deploy Status](https://github.com/onlitec/onlifin/workflows/Deploy/badge.svg)](https://github.com/onlitec/onlifin/actions)

## Documentação

- Documentação completa em [/docs](docs)
- API documentation em [/docs/api](docs/api)
- Guia de contribuição em [CONTRIBUTING.md](CONTRIBUTING.md)
- Código de conduta em [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md)

## Documentação da API

A API do Onlifin está documentada usando Swagger/OpenAPI. Você pode acessar a documentação em:

- [Documentação da API](https://onlifin.com/api/docs)

### Endpoints Principais

- `GET /api/transactions` - Listar transações
- `POST /api/transactions` - Criar transação
- `GET /api/transactions/{id}` - Obter transação
- `PUT /api/transactions/{id}` - Atualizar transação
- `DELETE /api/transactions/{id}` - Excluir transação
- `GET /api/categories` - Listar categorias
- `POST /api/categories` - Criar categoria
- `GET /api/accounts` - Listar contas
- `POST /api/accounts` - Criar conta

### Autenticação

A API usa autenticação via token JWT. Para fazer requisições, inclua o token no header:

```
Authorization: Bearer seu-token-aqui
```

### Respostas

Todos os endpoints retornam respostas no formato JSON. Os códigos de status HTTP são:

- 200: Sucesso
- 201: Criado
- 204: Sem conteúdo (ex: DELETE)
- 400: Requisição inválida
- 401: Não autorizado
- 404: Não encontrado
- 422: Validação falhou

## Licença

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

## Contribuição

1. Faça um fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## Suporte e Comunidade

- Issues: [https://github.com/onlitec/onlifin/issues](https://github.com/onlitec/onlifin/issues)
- Discussões: [https://github.com/onlitec/onlifin/discussions](https://github.com/onlitec/onlifin/discussions)
- Documentação: [/docs](docs)

## Versionamento

Este projeto segue o Semantic Versioning 2.0.0. As versões são marcadas com tags no formato `vX.Y.Z`.