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

## Documentação

- Documentação completa em [/docs](docs)
- API documentation em [/docs/api](docs/api)
- Guia de contribuição em [CONTRIBUTING.md](CONTRIBUTING.md)
- Código de conduta em [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md)

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