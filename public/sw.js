/**
 * Onlifin Service Worker
 * 
 * Este service worker gerencia notificações em segundo plano para o sistema Onlifin.
 */

// Nome do cache
const CACHE_NAME = 'onlifin-cache-v1';

// Arquivos para cache offline
const urlsToCache = [
  '/',
  '/css/app.css',
  '/js/app.js',
  '/images/logo.png',
  '/images/badge.png'
];

// Instalação do service worker
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Cache aberto');
        return cache.addAll(urlsToCache);
      })
  );
});

// Ativação do service worker
self.addEventListener('activate', event => {
  const cacheWhitelist = [CACHE_NAME];
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// Interceptação de requisições
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        if (response) {
          return response;
        }
        return fetch(event.request);
      })
  );
});

// Gerenciamento de notificações push
self.addEventListener('push', event => {
  let data = {};
  if (event.data) {
    data = event.data.json();
  }

  const options = {
    body: data.body || 'Nova notificação do Onlifin',
    icon: data.icon || '/images/logo.png',
    badge: data.badge || '/images/badge.png',
    data: {
      url: data.url || '/'
    },
    vibrate: [200, 100, 200]
  };

  event.waitUntil(
    self.registration.showNotification(data.title || 'Onlifin', options)
  );
});

// Clique na notificação
self.addEventListener('notificationclick', event => {
  event.notification.close();
  
  event.waitUntil(
    clients.matchAll({type: 'window'})
      .then(windowClients => {
        // Se já tiver uma janela aberta, focar nela
        for (let i = 0; i < windowClients.length; i++) {
          const client = windowClients[i];
          if (client.url === event.notification.data.url && 'focus' in client) {
            return client.focus();
          }
        }
        
        // Se não tiver janela aberta, abrir uma nova
        if (clients.openWindow) {
          return clients.openWindow(event.notification.data.url);
        }
      })
  );
});

// Recebimento de mensagens
self.addEventListener('message', event => {
  if (event.data && event.data.type === 'INIT') {
    console.log('Service Worker inicializado com URL:', event.data.url);
  }
});
