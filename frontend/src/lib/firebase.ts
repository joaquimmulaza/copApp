import { initializeApp, type FirebaseApp } from 'firebase/app'
import { getMessaging, type Messaging } from 'firebase/messaging'

// ─── Firebase configuration from Vite env vars ───────────────
const firebaseConfig = {
  apiKey:            import.meta.env.VITE_FIREBASE_API_KEY,
  authDomain:        import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
  projectId:         import.meta.env.VITE_FIREBASE_PROJECT_ID,
  storageBucket:     import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
  messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
  appId:             import.meta.env.VITE_FIREBASE_APP_ID,
}

// ─── Singletons — initialise once ────────────────────────────
let app: FirebaseApp | null = null
let messaging: Messaging | null = null

export const getFirebaseApp = (): FirebaseApp => {
  if (!app) {
    app = initializeApp(firebaseConfig)
  }
  return app
}

/**
 * Returns the FCM Messaging instance.
 * Returns null if running in a context without service-worker support
 * (e.g. server-side, iOS Safari without PWA permission).
 */
export const getFirebaseMessaging = (): Messaging | null => {
  if (!('serviceWorker' in navigator)) return null

  if (!messaging) {
    messaging = getMessaging(getFirebaseApp())
  }
  return messaging
}
