/**
 * Onlifin - Sistema de Notificações Web
 * 
 * Este módulo gerencia as notificações web para o sistema Onlifin.
 * Funcionalidades:
 * - Solicita permissão para notificações
 * - Exibe notificações web
 * - Gerencia o service worker para notificações em segundo plano
 */

document.addEventListener("DOMContentLoaded", () => {
    // Inicializar listeners para eventos Livewire
    initNotificationListeners();
    
    // Verificar se o navegador suporta notificações
    checkNotificationSupport();
});

/**
 * Inicializa os listeners para eventos relacionados a notificações
 */
function initNotificationListeners() {
    // Listener para solicitação de permissão de notificação
    document.addEventListener("requestNotificationPermission", () => {
        requestNotificationPermission();
    });
    
    // Listener para exibir notificação web
    document.addEventListener("showWebNotification", (event) => {
        if (event.detail) {
            showNotification(event.detail.title, event.detail.body, event.detail.icon);
        }
    });
}

/**
 * Verifica se o navegador suporta notificações
 */
function checkNotificationSupport() {
    if (!("Notification" in window)) {
        console.log("Este navegador não suporta notificações web");
        return false;
    }
    return true;
}

/**
 * Solicita permissão para enviar notificações
 */
async function requestNotificationPermission() {
    if (!checkNotificationSupport()) return;
    
    try {
        const permission = await Notification.requestPermission();
        
        if (permission === "granted") {
            console.log("Permissão para notificações concedida");
            registerServiceWorker();
            return true;
        } else {
            console.log("Permissão para notificações negada");
            return false;
        }
    } catch (error) {
        console.error("Erro ao solicitar permissão para notificações:", error);
        return false;
    }
}

/**
 * Exibe uma notificação web
 * 
 * @param {string} title - Título da notificação
 * @param {string} body - Corpo da mensagem
 * @param {string} icon - URL do ícone da notificação
 */
function showNotification(title, body, icon = "/images/logo.png") {
    if (!checkNotificationSupport()) return;
    
    if (Notification.permission === "granted") {
        const notification = new Notification(title, {
            body: body,
            icon: icon,
            badge: "/images/badge.png",
            timestamp: Date.now(),
            vibrate: [200, 100, 200]
        });
        
        notification.onclick = function() {
            window.focus();
            notification.close();
        };
        
        return notification;
    } else if (Notification.permission !== "denied") {
        requestNotificationPermission().then(permission => {
            if (permission) {
                showNotification(title, body, icon);
            }
        });
    }
}

/**
 * Registra o service worker para notificações em segundo plano
 */
async function registerServiceWorker() {
    if ("serviceWorker" in navigator) {
        try {
            // Verificar se já existe um service worker ativo para evitar conflitos
            const existingRegistration = await navigator.serviceWorker.getRegistration();
            if (existingRegistration && existingRegistration.scope !== window.location.origin + '/') {
                console.log("Service Worker de outra origem detectado, evitando conflito");
                return;
            }
            
            const registration = await navigator.serviceWorker.register("/sw.js");
            console.log("Service Worker registrado com sucesso:", registration);
            
            // Enviar mensagem para o service worker
            if (registration.active) {
                registration.active.postMessage({
                    type: "INIT",
                    url: window.location.origin
                });
            }
        } catch (error) {
            console.error("Falha ao registrar o Service Worker:", error);
        }
    }
}
