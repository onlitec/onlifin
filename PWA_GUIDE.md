# 📱 Guia PWA - OnliFin

## O que é PWA?

Progressive Web App (PWA) é uma tecnologia que permite que aplicações web funcionem como aplicativos nativos, oferecendo:

- ✅ Instalação no dispositivo
- ✅ Funcionamento offline
- ✅ Notificações push
- ✅ Acesso rápido
- ✅ Atualizações automáticas
- ✅ Experiência nativa

## Arquivos Implementados

### 1. Manifest (`/public/manifest.json`)
Arquivo de configuração principal do PWA que define:
- Nome e descrição da aplicação
- Ícones em múltiplos tamanhos
- Cores do tema
- Modo de exibição (standalone)
- Atalhos rápidos
- Screenshots

### 2. Service Worker (`/public/sw.js`)
Gerencia o cache e funcionalidades offline:
- **Cache-First**: Para assets estáticos (imagens, CSS, JS)
- **Network-First**: Para APIs e dados dinâmicos
- **Offline Fallback**: Página offline quando sem conexão
- **Sincronização em Background**: Para dados pendentes
- **Notificações Push**: Suporte para notificações

### 3. Página Offline (`/public/offline.html`)
Página exibida quando o usuário está offline e tenta acessar conteúdo não cacheado.

### 4. Componentes React

#### InstallPrompt (`/src/components/pwa/InstallPrompt.tsx`)
- Detecta se o app pode ser instalado
- Exibe prompt de instalação elegante
- Gerencia estado de instalação
- Respeita preferências do usuário (não mostra novamente por 7 dias se recusado)

#### UpdateNotification (`/src/components/pwa/UpdateNotification.tsx`)
- Detecta quando há nova versão disponível
- Notifica o usuário
- Permite atualização imediata ou posterior

#### PWAStatus (`/src/components/pwa/PWAStatus.tsx`)
- Monitora status da conexão
- Exibe banner quando offline
- Notifica quando conexão é restaurada

### 5. Utilitários

#### registerSW.ts (`/src/utils/registerSW.ts`)
Funções para gerenciar o Service Worker:
- `registerServiceWorker()`: Registra o SW com callbacks
- `unregisterServiceWorker()`: Remove o SW
- `checkForUpdates()`: Verifica atualizações manualmente
- `skipWaiting()`: Força atualização imediata
- `clearCache()`: Limpa todo o cache

### 6. Página de Informações (`/src/pages/PWAInfo.tsx`)
Página dedicada com:
- Status do PWA
- Instruções de instalação para cada plataforma
- Benefícios do PWA
- Funcionalidades disponíveis

## Como Usar

### Para Desenvolvedores

1. **Testar Localmente**
```bash
npm run dev
```
Acesse via HTTPS ou localhost para testar PWA.

2. **Verificar Service Worker**
- Abra DevTools → Application → Service Workers
- Verifique se está registrado e ativo

3. **Testar Offline**
- DevTools → Network → Offline
- Navegue pela aplicação

4. **Validar Manifest**
- DevTools → Application → Manifest
- Verifique todos os campos

5. **Lighthouse Audit**
```bash
# No Chrome DevTools
Lighthouse → Progressive Web App → Generate Report
```

### Para Usuários

#### Android (Chrome/Edge)
1. Abra o OnliFin no navegador
2. Toque no menu (⋮) → "Adicionar à tela inicial"
3. Confirme a instalação
4. O ícone aparecerá na tela inicial

#### iOS (Safari)
1. Abra o OnliFin no Safari
2. Toque no botão de compartilhar (□↑)
3. Role e toque em "Adicionar à Tela de Início"
4. Confirme

#### Desktop (Chrome/Edge)
1. Abra o OnliFin
2. Clique no ícone de instalação na barra de endereço
3. Ou Menu → "Instalar OnliFin"
4. O app abrirá em janela própria

## Funcionalidades Implementadas

### ✅ Instalação
- Prompt automático de instalação
- Suporte para Android, iOS e Desktop
- Ícones adaptativos (maskable)

### ✅ Offline
- Cache inteligente de assets
- Página offline personalizada
- Sincronização automática quando online

### ✅ Atualizações
- Detecção automática de novas versões
- Notificação ao usuário
- Atualização sem perda de dados

### ✅ Performance
- Cache-first para assets estáticos
- Network-first para dados dinâmicos
- Pré-cache de recursos críticos

### ✅ Experiência Nativa
- Tela de splash automática
- Barra de status personalizada
- Atalhos rápidos (shortcuts)
- Modo standalone (sem barra do navegador)

### ✅ Notificações (Preparado)
- Infraestrutura para push notifications
- Handlers de eventos configurados
- Pronto para integração com backend

## Estratégias de Cache

### Cache-First (Assets Estáticos)
```
Usuário → Cache → Rede (se não estiver em cache)
```
Usado para:
- Imagens
- CSS
- JavaScript
- Fontes

### Network-First (Dados Dinâmicos)
```
Usuário → Rede → Cache (fallback se offline)
```
Usado para:
- APIs do Supabase
- Dados de transações
- Informações em tempo real

### Offline Fallback
```
Usuário → Rede (falha) → Cache → Página Offline
```
Usado para:
- Páginas não cacheadas
- Quando completamente offline

## Configurações Importantes

### Manifest.json
```json
{
  "name": "OnliFin - Gestão Financeira Pessoal",
  "short_name": "OnliFin",
  "theme_color": "#3b82f6",
  "background_color": "#0f172a",
  "display": "standalone"
}
```

### Meta Tags (index.html)
- `theme-color`: Cor da barra de status
- `apple-mobile-web-app-capable`: Habilita modo standalone no iOS
- `viewport-fit=cover`: Suporte para notch/ilha dinâmica

## Ícones Necessários

Os ícones devem ser criados nos seguintes tamanhos:
- 72x72
- 96x96
- 128x128
- 144x144
- 152x152
- 192x192
- 384x384
- 512x512
- 192x192 (maskable)
- 512x512 (maskable)

**Ferramenta**: Use `/icons/generate-icons.html` para gerar todos os ícones automaticamente.

## Troubleshooting

### Service Worker não registra
1. Verifique se está usando HTTPS ou localhost
2. Limpe o cache do navegador
3. Verifique o console por erros

### Prompt de instalação não aparece
1. Verifique se já está instalado
2. Limpe o localStorage: `localStorage.removeItem('pwa-install-dismissed')`
3. Verifique se o manifest está válido

### Offline não funciona
1. Verifique se o SW está ativo
2. Navegue pela aplicação online primeiro (para cachear)
3. Verifique as estratégias de cache no sw.js

### Atualizações não aparecem
1. Force atualização: DevTools → Application → Service Workers → Update
2. Verifique se `skipWaiting()` está sendo chamado
3. Limpe o cache: `clearCache()`

## Manutenção

### Atualizar Versão do Cache
Edite `CACHE_NAME` em `/public/sw.js`:
```javascript
const CACHE_NAME = 'onlifin-v1.0.1'; // Incrementar versão
```

### Adicionar Novos Recursos ao Pré-Cache
Edite `PRECACHE_URLS` em `/public/sw.js`:
```javascript
const PRECACHE_URLS = [
  '/',
  '/offline.html',
  '/novo-recurso.html' // Adicionar aqui
];
```

### Modificar Estratégias de Cache
Edite as condições no event listener `fetch` em `/public/sw.js`.

## Recursos Adicionais

### Documentação
- [MDN - Progressive Web Apps](https://developer.mozilla.org/en-US/docs/Web/Progressive_web_apps)
- [web.dev - PWA](https://web.dev/progressive-web-apps/)
- [Google Workbox](https://developers.google.com/web/tools/workbox)

### Ferramentas
- [Lighthouse](https://developers.google.com/web/tools/lighthouse)
- [PWA Builder](https://www.pwabuilder.com/)
- [Manifest Generator](https://app-manifest.firebaseapp.com/)

### Testes
- [PWA Testing](https://web.dev/pwa-checklist/)
- Chrome DevTools → Application
- Firefox Developer Tools → Application

## Próximos Passos

### Melhorias Futuras
- [ ] Implementar notificações push reais
- [ ] Adicionar sincronização em background
- [ ] Implementar share target API
- [ ] Adicionar shortcuts dinâmicos
- [ ] Implementar badging API
- [ ] Adicionar file handling
- [ ] Implementar periodic background sync

### Otimizações
- [ ] Implementar estratégia de cache mais granular
- [ ] Adicionar analytics de uso offline
- [ ] Otimizar tamanho do cache
- [ ] Implementar cache de imagens otimizado
- [ ] Adicionar prefetch de recursos

## Suporte

Para dúvidas ou problemas relacionados ao PWA:
1. Verifique este guia
2. Consulte a página `/pwa-info` na aplicação
3. Verifique os logs do console
4. Use as ferramentas de desenvolvedor do navegador

---

**Versão**: 1.0.0  
**Última Atualização**: 2025-12-09  
**Compatibilidade**: Chrome 90+, Edge 90+, Safari 15+, Firefox 90+
