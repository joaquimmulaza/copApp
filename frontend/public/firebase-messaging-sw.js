/* eslint-disable no-undef */

// Importa os scripts de compatibilidade do Firebase diretamente da CDN da Google
importScripts('https://www.gstatic.com/firebasejs/10.8.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.8.0/firebase-messaging-compat.js');

// Configuração hardcoded (o Service Worker não lê o .env)
const firebaseConfig = {
  apiKey: "AIzaSyCLd14wa8PrxXRbCiKuBIqNp3n_EaousR0",
  authDomain: "copapp-7ecd7.firebaseapp.com",
  projectId: "copapp-7ecd7",
  storageBucket: "copapp-7ecd7.firebasestorage.app",
  messagingSenderId: "875777930191",
  appId: "1:875777930191:web:68a057155038764c0058ba"
};

// Inicializa o Firebase no Background
firebase.initializeApp(firebaseConfig);
const messaging = firebase.messaging();

/**
 * Background message handler.
 * Fires when the app is NOT in the foreground.
 */
messaging.onBackgroundMessage((payload) => {
  console.log("[SW] Background message received:", payload);

  const notificationTitle = payload.notification?.title ?? "CopApp — Nova Notificação";

  const notificationOptions = {
    body: payload.notification?.body ?? "Clique para ver os detalhes do jogo.",
    icon: "/vite.svg", // Fallback seguro (o agente estava a apontar para /icons que pode não existir ainda)
    badge: "/vite.svg",
    tag: payload.data?.fixture_id ?? "copapp-notification",
    data: payload.data ?? {},
    requireInteraction: false,
    vibrate: [200, 100, 200],
    actions: [
      {
        action: "open",
        title: "Ver Jogo",
      },
      {
        action: "dismiss",
        title: "Ignorar",
      },
    ],
  };

  self.registration.showNotification(notificationTitle, notificationOptions);
});

// ─── Notification click handler ───────────────────────────────────────────────
self.addEventListener("notificationclick", (event) => {
  event.notification.close();

  if (event.action === "dismiss") return;

  const fixtureId = event.notification.data?.fixture_id;
  const url = fixtureId ? `/fixtures/${fixtureId}` : "/";

  event.waitUntil(
    clients
      .matchAll({ type: "window", includeUncontrolled: true })
      .then((clientList) => {
        // Focus existing tab if available
        for (const client of clientList) {
          if ("focus" in client) {
            client.navigate(url);
            return client.focus();
          }
        }
        // Open new tab if no existing tab
        if (clients.openWindow) {
          return clients.openWindow(url);
        }
      })
  );
});

// ─── SW lifecycle ─────────────────────────────────────────────────────────────
self.addEventListener("install", () => self.skipWaiting());
self.addEventListener("activate", (event) =>
  event.waitUntil(clients.claim())
);