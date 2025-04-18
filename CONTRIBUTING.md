# Guia de Contribuição para Onlifin

Agradecemos por seu interesse em contribuir com o Onlifin! Este documento guia você sobre como contribuir de forma efetiva.

## 📋 Pré-requisitos

Antes de começar, certifique-se de ter:

- PHP 8.2 ou superior
- Composer
- Node.js e NPM
- Git
- MariaDB 10.4 ou MySQL 8.0
- Nginx ou Apache

## 📝 Como Contribuir

### 1. Reportando Issues

- Verifique se o problema já foi reportado
- Seja específico sobre o problema
- Inclua passos para reproduzir o problema
- Adicione logs de erro, se houver
- Indique qual versão do Onlifin você está usando

### 2. Fazendo Pull Requests

1. Faça um fork do repositório
2. Crie uma branch para sua feature (`git checkout -b feature/NomeDaFeature`)
3. Faça commits menores e específicos
4. Faça testes locais
5. Atualize a documentação
6. Abra um Pull Request

### 3. Formato dos Commits

Use o formato convencional:
```
tipo: descrição do commit

Exemplos:
- feat: adicionar nova funcionalidade
- fix: corrigir bug específico
- docs: atualizar documentação
- style: alterações de formatação
- refactor: refatoração de código
- test: adicionar testes
- chore: tarefas de manutenção
```

## 🛠️ Padrões de Código

- Segue as PSR-12 standards
- Utiliza PHPStan para análise estática
- Emprega PHP CS Fixer para formatação
- Adota padrões Laravel
- Utiliza docblocks completos
- Mantém consistência com o código existente

### PHP

- Use 4 espaços para indentação
- Adicione docblocks completos para classes e métodos
- Use nomes descritivos para variáveis e funções
- Mantenha funções curtas e focadas
- Use type hints sempre que possível

### JavaScript

- Use 2 espaços para indentação
- Adicione comentários explicativos
- Use nomes descritivos para variáveis
- Mantenha consistência com o código existente

## 📝 Documentação

- Mantenha a documentação atualizada
- Adicione exemplos práticos
- Mantenha a consistência nos termos
- Documente todas as APIs públicas

## 🧪 Testes

- Adicione testes para novas funcionalidades
- Mantenha os testes existentes passando
- Use testes unitários para funcionalidades específicas
- Use testes de feature para fluxos de usuário

## 🤝 Comunidade

- Seja respeitoso e profissional
- Ajude outros contribuidores
- Mantenha discussões construtivas
- Reporte problemas de forma clara

## 📢 Suporte

- Issues: [https://github.com/onlitec/onlifin/issues](https://github.com/onlitec/onlifin/issues)
- Discussões: [https://github.com/onlitec/onlifin/discussions](https://github.com/onlitec/onlifin/discussions)
- Documentação: [/docs](docs)

## 📄 Código de Conduta

Este projeto segue o [Código de Conduta](CODE_OF_CONDUCT.md). Por favor, leia e siga as diretrizes.
