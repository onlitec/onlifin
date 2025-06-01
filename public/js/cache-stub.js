// Stub para API de Cache no browser para evitar ReferenceError em extensões
if (typeof caches === 'undefined') {
    window.caches = {
        open: async () => Promise.reject('Cache API não disponível'),
        match: async () => null,
        delete: async () => false,
        keys: async () => [],
        has: async () => false,
        set: async () => false
    };
    console.log('Stub de caches definido');
} 