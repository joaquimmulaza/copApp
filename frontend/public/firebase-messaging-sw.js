/**
 * firebase-messaging-sw.js
 * Service Worker for Firebase Cloud Messaging — handles background push messages.
 *
 * IMPORTANT: This file must live at /public/firebase-messaging-sw.js so the
 * browser registers it at the root scope (/). FCM requires root-scope registration.
 *
 * The Firebase SDK version below must match the one in package.json.
 * Update the version string when upgrading the firebase package.
 */

/* eslint-disable no-undef */

// ─── Import Firebase compat SDKs (required for SW context) ───────────────────
importScripts(
  "https://www.gstatic.com/firebasejs/10.12.4/firebase-app-compat.js"
);
importScripts(
  "https://www.gstatic.com/firebasejs/10.12.4/firebase-messaging-compat.js"
);

// ─── Firebase config (must duplicate env values here — SWs can't use Vite) ──
// These values are public/client-safe — never put secret keys here.
// In production, replace placeholders via a build step (e.g., sed in CI/CD).
const firebaseConfig = {
  apiKey: self.__FIREBASE_CONFIG_API_KEY__ || "",
  authDomain: self.__FIREBASE_CONFIG_AUTH_DOMAIN__ || "",
  projectId: self.__FIREBASE_CONFIG_PROJECT_ID__ || "",
  storageBucket: self.__FIREBASE_CONFIG_STORAGE_BUCKET__ || "",
  messagingSenderId: self.__FIREBASE_CONFIG_MESSAGING_SENDER_ID__ || "",
  appId: self.__FIREBASE_CONFIG_APP_ID__ || "",
};

// ─── Guard: only initialise if config is present ─────────────────────────────
if (firebaseConfig.apiKey) {
  firebase.initializeApp(firebaseConfig);

  const messaging = firebase.messaging();

  /**
   * Background message handler.
   * Fires when the app is NOT in the foreground.
   * FCM delivers the raw payload here — we construct and show the notification.
   */
  messaging.onBackgroundMessage((payload) => {
    console.log("[SW] Background message received:", payload);

    const notificationTitle =
      payload.notification?.title ?? "CopApp — Nova Notificação";

    const notificationOptions = {
      body:
        payload.notification?.body ?? "Clique para ver os detalhes do jogo.",
      icon: "/icons/icon-192x192.png",
      badge: "/icons/badge-72x72.png",
      tag: payload.data?.fixture_id ?? "copapp-notification",
      data: payload.data ?? {},
      requireInteraction: false,
      vibrate: [200, 100, 200],
      actions: [
        {
          action: "open",
          title: "Ver Jogo",
          icon: "/icons/icon-96x96.png",
        },
        {
          action: "dismiss",
          title: "Ignorar",
        },
      ],
    };

    self.registration.showNotification(notificationTitle, notificationOptions);
  });
}

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
