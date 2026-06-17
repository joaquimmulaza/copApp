/**
 * firebase.ts — Firebase SDK initialisation & FCM setup
 *
 * Singleton pattern: initialise once, reuse everywhere.
 * All env vars are VITE_ prefixed (public/client-safe — never put server keys here).
 *
 * Service Worker note:
 *  The SW at /firebase-messaging-sw.js must be registered at root scope for FCM
 *  to work correctly. We register it explicitly here so Vite's dev server does
 *  not intercept it. In production (Vercel), the file is served from /public.
 */

import { initializeApp, type FirebaseApp } from "firebase/app";
import { getMessaging, type Messaging } from "firebase/messaging";

// ─── Raw env vars (may be undefined if .env.local is not configured) ─────────
const FIREBASE_API_KEY = import.meta.env.VITE_FIREBASE_API_KEY as string | undefined;
const FIREBASE_AUTH_DOMAIN = import.meta.env.VITE_FIREBASE_AUTH_DOMAIN as string | undefined;
const FIREBASE_PROJECT_ID = import.meta.env.VITE_FIREBASE_PROJECT_ID as string | undefined;
const FIREBASE_STORAGE_BUCKET = import.meta.env.VITE_FIREBASE_STORAGE_BUCKET as string | undefined;
const FIREBASE_MESSAGING_SENDER_ID = import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID as string | undefined;
const FIREBASE_APP_ID = import.meta.env.VITE_FIREBASE_APP_ID as string | undefined;

// ─── Singletons — initialise once, share across the app ──────────────────────
let _app: FirebaseApp | null = null;
let _messaging: Messaging | null = null;

/**
 * Returns (or creates) the Firebase app singleton.
 * Throws if required env vars are missing — fail fast during development.
 */
export const getFirebaseApp = (): FirebaseApp => {
  if (_app) return _app;

  if (!FIREBASE_API_KEY || !FIREBASE_PROJECT_ID) {
    throw new Error(
      "[Firebase] Missing required env vars: VITE_FIREBASE_API_KEY, VITE_FIREBASE_PROJECT_ID. " +
        "Copy .env.example to .env.local and fill in your Firebase project values."
    );
  }

  // Build a config object without optional undefined fields (exactOptionalPropertyTypes)
  // eslint-disable-next-line @typescript-eslint/consistent-type-assertions
  const config = {
    apiKey: FIREBASE_API_KEY,
    projectId: FIREBASE_PROJECT_ID,
    ...(FIREBASE_AUTH_DOMAIN ? { authDomain: FIREBASE_AUTH_DOMAIN } : {}),
    ...(FIREBASE_STORAGE_BUCKET ? { storageBucket: FIREBASE_STORAGE_BUCKET } : {}),
    ...(FIREBASE_MESSAGING_SENDER_ID ? { messagingSenderId: FIREBASE_MESSAGING_SENDER_ID } : {}),
    ...(FIREBASE_APP_ID ? { appId: FIREBASE_APP_ID } : {}),
  } as const;

  _app = initializeApp(config);
  return _app;
};

/**
 * Returns the FCM Messaging instance, or null if:
 *  - Running in SSR / non-browser context
 *  - ServiceWorker API is not available (e.g. insecure context, iOS Safari without PWA)
 *
 * Does NOT throw — callers should guard against null.
 */
export const getFirebaseMessaging = (): Messaging | null => {
  // ServiceWorker is required for FCM token generation
  if (typeof window === "undefined") return null;
  if (!("serviceWorker" in navigator)) return null;

  if (_messaging) return _messaging;

  try {
    _messaging = getMessaging(getFirebaseApp());
    return _messaging;
  } catch (error) {
    console.error("[Firebase] Failed to initialise Messaging:", error);
    return null;
  }
};

/**
 * Registers the Firebase Messaging service worker at root scope.
 *
 * Must be called early (e.g. in main.tsx or App.tsx) — before getToken() is called.
 * Returns the ServiceWorkerRegistration, or null on failure.
 *
 * The SW file is at /public/firebase-messaging-sw.js → served at /firebase-messaging-sw.js
 */
export const registerFcmServiceWorker =
  async (): Promise<ServiceWorkerRegistration | null> => {
    if (!("serviceWorker" in navigator)) return null;

    try {
      const registration = await navigator.serviceWorker.register(
        "/firebase-messaging-sw.js",
        { scope: "/" }
      );

      // Wait for the SW to be active before returning
      await navigator.serviceWorker.ready;

      console.info(
        "[FCM] Service worker registered successfully:",
        registration.scope
      );
      return registration;
    } catch (error) {
      console.error("[FCM] Service worker registration failed:", error);
      return null;
    }
  };
