// Script para limpar caches antigos do Service Worker
// Execute no console do navegador: clearSWCaches()

function clearSWCaches() {
  console.log('🧹 Limpando caches do Service Worker...');
  
  // Limpar todos os caches
  caches.keys().then(cacheNames => {
    console.log('📦 Caches encontrados:', cacheNames);
    
    return Promise.all(
      cacheNames.map(cacheName => {
        console.log(`🗑️ Removendo cache: ${cacheName}`);
        return caches.delete(cacheName);
      })
    );
  }).then(() => {
    console.log('✅ Todos os caches removidos');
    
    // Limpar Service Workers registrados
    navigator.serviceWorker.getRegistrations().then(registrations => {
      console.log('🔧 Service Workers encontrados:', registrations.length);
      
      return Promise.all(
        registrations.map(registration => {
          console.log(`🗑️ Unregistering SW: ${registration.scope}`);
          return registration.unregister();
        })
      );
    }).then(() => {
      console.log('✅ Service Workers removidos');
      
      // Recarregar página
      console.log('🔄 Recarregando página em 2 segundos...');
      setTimeout(() => {
        window.location.reload();
      }, 2000);
    });
  }).catch(error => {
    console.error('❌ Erro ao limpar caches:', error);
  });
}

// Auto-executar se estiver no console
if (typeof window !== 'undefined') {
  window.clearSWCaches = clearSWCaches;
  console.log('💡 Para limpar caches, execute: clearSWCaches()');
}
