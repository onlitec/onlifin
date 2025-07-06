# Onlifin - Sistema de Gestão Financeira

[![Versão](https://img.shields.io/badge/version-3.0.0-blue.svg)](https://github.com/onlitec/onlifin/releases/tag/v3.0.0)
[![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-purple.svg)](https://php.net)
[![Licença](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

Sistema completo de gestão financeira pessoal e empresarial com autenticação social, notificações por email e interface moderna.

## 🚀 Funcionalidades Principais

### 💳 Gestão Financeira
- **Transações**: Controle completo de receitas e despesas
- **Contas**: Gerenciamento de múltiplas contas bancárias
- **Categorias**: Organização inteligente por categorias personalizáveis
- **Relatórios**: Dashboards e relatórios detalhados
- **Importação**: Importação automática de extratos bancários

### 🔐 Autenticação e Segurança
- **Login Tradicional**: Sistema de login com email e senha
- **Login Social**: Autenticação com Google OAuth2
- **Autenticação de Dois Fatores (2FA)**: Segurança adicional com códigos TOTP
- **Recuperação de Senha**: Sistema completo com emails personalizados
- **Gestão de Sessões**: Controle avançado de sessões de usuário

### 📧 Sistema de Notificações
- **Email SMTP**: Configuração completa de servidores SMTP
- **Templates Personalizados**: Emails com design da marca
- **Notificações Inteligentes**: Sistema de notificações contextuais
- **Teste de Conectividade**: Ferramentas para testar configurações

### 🎨 Interface Moderna
- **Design Responsivo**: Interface otimizada para todos os dispositivos
- **Tema Escuro/Claro**: Alternância entre temas
- **Componentes Dinâmicos**: Interface reativa com Livewire
- **Acessibilidade**: Seguindo padrões de acessibilidade web

## 🛠️ Tecnologias Utilizadas

### Backend
- **Laravel 11.x**: Framework PHP moderno
- **Livewire 3.x**: Componentes dinâmicos
- **MySQL**: Banco de dados relacional
- **Redis**: Cache e sessões (opcional)

### Frontend
- **Alpine.js**: Framework JavaScript reativo
- **Tailwind CSS**: Framework CSS utilitário
- **Vite**: Build tool moderno
- **Chart.js**: Gráficos e visualizações

### Integrações
- **Google OAuth2**: Autenticação social
- **SMTP**: Envio de emails
- **2FA**: Autenticação de dois fatores
- **Backup**: Sistema de backup automático

## 📋 Requisitos do Sistema

### Mínimos
- **PHP**: 8.2 ou superior
- **Composer**: 2.0 ou superior
- **Node.js**: 18.0 ou superior
- **MySQL**: 8.0 ou superior
- **Nginx/Apache**: Servidor web

### Recomendados
- **PHP**: 8.3
- **MySQL**: 8.0
- **Redis**: 7.0 (para cache)
- **SSL**: Certificado SSL válido

## 🚀 Instalação

### 1. Clone o Repositório
```bash
git clone https://github.com/onlitec/onlifin.git
cd onlifin
```

### 2. Instale as Dependências
```bash
# Dependências PHP
composer install

# Dependências Node.js
npm install
```

### 3. Configure o Ambiente
```bash
# Copie o arquivo de configuração
cp .env.example .env

# Gere a chave da aplicação
php artisan key:generate
```

### 4. Configure o Banco de Dados
```bash
# Execute as migrações
php artisan migrate

# Execute os seeders (opcional)
php artisan db:seed
```

### 5. Configure o Storage
```bash
# Crie o link simbólico
php artisan storage:link
```

### 6. Compile os Assets
```bash
# Para desenvolvimento
npm run dev

# Para produção
npm run build
```

## ⚙️ Configuração

### Variáveis de Ambiente

#### Banco de Dados
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=onlifin
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha
```

#### Google OAuth
```env
GOOGLE_CLIENT_ID=seu_client_id_google
GOOGLE_CLIENT_SECRET=seu_client_secret_google
GOOGLE_REDIRECT_URI=https://seudominio.com/auth/google/callback
```

#### Email SMTP
```env
MAIL_MAILER=smtp
MAIL_HOST=seu_servidor_smtp
MAIL_PORT=587
MAIL_USERNAME=seu_usuario_email
MAIL_PASSWORD=sua_senha_email
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@seudominio.com
MAIL_FROM_NAME="Onlifin"
```

#### Cache e Sessões
```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

## 📖 Uso

### Primeiro Acesso
1. Acesse o sistema através do navegador
2. Complete o processo de instalação
3. Crie sua conta de administrador
4. Configure as integrações necessárias

### Configuração de Autenticação Social
1. Acesse **Configurações → Autenticação Social**
2. Configure as credenciais do Google
3. Teste a conexão
4. Ative o provedor

### Configuração de Email
1. Acesse **Configurações → Email**
2. Configure o servidor SMTP
3. Teste o envio de email
4. Teste a recuperação de senha

## 🔧 Desenvolvimento

### Comandos Úteis
```bash
# Executar testes
php artisan test

# Limpar cache
php artisan config:clear
php artisan view:clear
php artisan route:clear

# Executar filas
php artisan queue:work

# Executar scheduler
php artisan schedule:work
```

### Estrutura do Projeto
```
onlifin/
├── app/
│   ├── Http/Controllers/     # Controladores
│   ├── Livewire/            # Componentes Livewire
│   ├── Models/              # Modelos Eloquent
│   ├── Notifications/       # Notificações
│   └── Services/            # Serviços
├── config/                  # Configurações
├── database/               # Migrações e seeders
├── resources/
│   ├── views/              # Templates Blade
│   ├── js/                 # JavaScript
│   └── css/                # Estilos
└── routes/                 # Rotas
```

## 📝 Contribuição

### Como Contribuir
1. Faça um fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/MinhaFeature`)
3. Commit suas mudanças (`git commit -m 'Adiciona MinhaFeature'`)
4. Push para a branch (`git push origin feature/MinhaFeature`)
5. Abra um Pull Request

### Padrões de Código
- Siga os padrões PSR-12 para PHP
- Use ESLint para JavaScript
- Documente novas funcionalidades
- Escreva testes para novas features

## 📄 Changelog

Veja o arquivo [CHANGELOG.md](CHANGELOG.md) para detalhes sobre as mudanças em cada versão.

## 🐛 Reportar Problemas

Se você encontrar algum problema, por favor:
1. Verifique se já não foi reportado
2. Crie uma issue detalhada
3. Inclua informações do ambiente
4. Forneça passos para reproduzir

## 📞 Suporte

- **Email**: galvatec@gmail.com
- **GitHub**: [Issues](https://github.com/onlitec/onlifin/issues)
- **Documentação**: [Wiki](https://github.com/onlitec/onlifin/wiki)

## 📜 Licença

Este projeto está licenciado sob a Licença MIT - veja o arquivo [LICENSE](LICENSE) para detalhes.

## 🙏 Agradecimentos

- **Laravel**: Framework PHP excepcional
- **Livewire**: Componentes dinâmicos fantásticos
- **Tailwind CSS**: Framework CSS incrível
- **Comunidade**: Todos os contribuidores e usuários

---

**Desenvolvido com ❤️ pela equipe Onlitec**