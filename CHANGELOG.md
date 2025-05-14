# Changelog

## [Não Lançado]

### Adicionado
- Documentação FINANCIAL_RULES.md com regras detalhadas sobre manipulação de valores financeiros
- Comentários de aviso "CONFIGURAÇÃO CRÍTICA" em todos os arquivos com lógica financeira crítica
- Implementação da coluna `current_balance` na tabela accounts para armazenar os saldos atuais calculados
- Observer `AccountObserver` para gerenciar atualizações de saldo automaticamente

### Corrigido
- Erro 500 ao acessar a página de contas causado pela falta da coluna `current_balance`
- Discrepância entre o saldo total exibido no dashboard e a soma dos saldos individuais das contas
- Tratamento de valores monetários para garantir armazenamento consistente em centavos
- Exibição correta de saldos negativos com formatação de cor apropriada

### Alterado
- Atualizado README.md para incluir informações sobre as regras financeiras
- Implementação do cálculo de saldo nas contas para considerar a existência ou não de transações
- Implementação mais robusta do atributo `current_balance` para prevenir erros 500

## [1.0.0] - 2025-04-12

### Adicionado
- Lançamento inicial do Onlifin
- Sistema completo de gestão financeira pessoal
- Dashboard com visualização de finanças
- Gestão de transações (receitas e despesas)
- Categorização de transações
- Gestão de contas bancárias
- Relatórios e análises financeiras
- Sistema de autenticação robusto
- Interface responsiva

## [4.0-beta1] - 2024-03-12

### Adicionado
- Integração com Replicate AI para processamento de IA
- Sistema de configuração e teste de conexão com API Replicate
- ReplicateService para processamento de IA
- Configurações do Replicate no painel administrativo
- Página de configurações acessível para todos os usuários
- Suporte a usuários administradores

### Modificado
- Padronização do tamanho da fonte do menu principal para 18px
- Melhorias gerais no layout da aplicação
- Aprimoramento no tratamento de erros e validação de token API
- Otimização nos formulários de transações

### Corrigido
- Processamento correto de valores monetários nos formulários
- Funcionalidades de edição e exclusão de categorias
- Filtragem dinâmica de categorias por tipo de transação

## [3.0.0] - 2024-02-28

### Adicionado
- Importação automática de extratos bancários com IA
- Melhorias significativas no sistema de contas e categorias

## [2.1.0] - 2024-02-25

### Adicionado
- Menu horizontal consistente
- Implementação de perfil de usuário

### Corrigido
- Alertas Livewire
- Remoção de informações de debug da tela de login

## [2.0.0] - 2024-02-23

### Adicionado
- Sistema completo de backup e restauração
- Backup do banco de dados e arquivos
- Download e restauração de backups
- Manifesto detalhado dos backups
- Dashboard com visualização de receitas e despesas
- Exibição de transações do dia atual e próximo dia
- Botões de edição rápida nas transações
- Interface melhorada para gerenciamento financeiro

### Modificado
- Layout do dashboard reorganizado
- Sistema de edição de transações aprimorado
- Melhorias na interface do usuário
- Otimização no tratamento de valores monetários

### Corrigido
- Problemas de permissões em arquivos de backup
- Tratamento de erros aprimorado
- Validações de formulários
- Formatação de valores monetários 