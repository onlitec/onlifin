# Changelog

Todas as mudanças importantes do projeto serão documentadas neste arquivo.

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

## [3.0.0] - 2025-07-06

### 🚀 Novas Funcionalidades

#### Autenticação Social
- **Login com Google**: Implementado sistema completo de autenticação OAuth2 com Google
- **Autenticação Híbrida**: Suporte para login tradicional e social na mesma plataforma
- **Gestão de Contas Sociais**: Interface para gerenciar contas sociais vinculadas
- **Autenticação de Dois Fatores (2FA)**: Sistema completo de 2FA com códigos de recuperação

#### Sistema de Email SMTP
- **Configuração SMTP**: Interface completa para configuração de servidores SMTP
- **Email de Recuperação de Senha**: Template personalizado com design da marca
- **Notificações Personalizadas**: Sistema de notificações por email totalmente customizável
- **Teste de Conectividade**: Botões para testar conexão SMTP e envio de emails

#### Melhorias na Interface
- **Design Responsivo**: Interface otimizada para dispositivos móveis
- **Correções de Layout**: Eliminados problemas de overflow horizontal
- **Componentes Reutilizáveis**: Criados componentes Livewire para melhor organização

### 🔧 Melhorias Técnicas

#### Arquitetura
- **Livewire 3.x**: Atualização completa para a versão mais recente
- **Alpine.js**: Otimização e correção de conflitos de inicialização
- **Vite**: Sistema de build moderno implementado

#### Segurança
- **Validação de Domínios**: Sistema de whitelist para domínios Google autorizados
- **Sanitização de Dados**: Melhorias na validação e sanitização de entradas
- **Logs de Segurança**: Sistema de auditoria para tentativas de login

#### Performance
- **Cache Otimizado**: Implementação de cache inteligente para configurações
- **Lazy Loading**: Carregamento otimizado de componentes
- **Compressão de Assets**: Otimização de CSS e JavaScript

### 🐛 Correções

#### Problemas Críticos
- **Link Simbólico**: Corrigido problema com storage/public que causava erro 404
- **Alpine Override**: Removido código conflitante que causava erros de referência
- **Popups SweetAlert**: Eliminados popups indesejados em formulários de edição

#### Bugs Menores
- **Validação de Formulários**: Correções em validações de campos obrigatórios
- **Responsividade**: Ajustes em componentes para melhor visualização mobile
- **Compatibilidade**: Correções para melhor compatibilidade entre navegadores

### 🗑️ Removidos

#### Código Obsoleto
- **Alpine Override**: Removido arquivo desnecessário que causava conflitos
- **Código Duplicado**: Limpeza de código duplicado e comentários obsoletos
- **Dependências Não Utilizadas**: Remoção de pacotes não utilizados

### 📦 Dependências

#### Adicionadas
- `hybridauth/hybridauth`: ^3.0 - Biblioteca para autenticação social
- `pragmarx/google2fa`: ^8.0 - Biblioteca para autenticação de dois fatores

#### Atualizadas
- `livewire/livewire`: ^3.6 - Framework para componentes dinâmicos
- `laravel/framework`: ^11.0 - Framework principal

### 🔄 Migração

#### Configurações Necessárias
1. Executar migrações: `php artisan migrate`
2. Publicar assets: `php artisan storage:link`
3. Limpar cache: `php artisan config:clear`
4. Recompilar assets: `npm run build`

#### Variáveis de Ambiente
Adicionar ao `.env`:
```
# Google OAuth
GOOGLE_CLIENT_ID=seu_client_id
GOOGLE_CLIENT_SECRET=seu_client_secret

# SMTP
MAIL_MAILER=smtp
MAIL_HOST=seu_servidor_smtp
MAIL_PORT=587
MAIL_USERNAME=seu_usuario
MAIL_PASSWORD=sua_senha
MAIL_ENCRYPTION=tls
```

### 📝 Notas de Desenvolvimento

#### Arquivos Importantes
- `app/Notifications/ResetPasswordNotification.php`: Notificação personalizada
- `resources/views/mail/auth/reset-password.blade.php`: Template de email
- `app/Livewire/Settings/EmailConfig.php`: Configuração de email
- `app/Http/Controllers/Auth/GoogleAuthController.php`: Controlador Google Auth

#### Configurações
- `config/hybridauth.php`: Configurações de autenticação social
- `config/services.php`: Configurações de serviços externos

---

**Versão completa com todas as funcionalidades implementadas e testadas.**

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