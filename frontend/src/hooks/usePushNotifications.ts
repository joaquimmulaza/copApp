/**
 * usePushNotifications.ts
 *
 * Custom hook for Firebase Cloud Messaging (FCM) push notifications.
 *
 * Responsibilities:
 *  1. Request browser notification permission via Notification.requestPermission()
 *  2. Register/retrieve the FCM token via getToken() from firebase/messaging
 *  3. POST the token to the Laravel backend at /api/push-subscriptions
 *  4. Persist state in notificationStore (Zustand + localStorage)
 *  5. Expose helpers to subscribe/unsubscribe from specific fixtures
 *
 * Error handling strategy:
 *  - "denied"  → permission blocked by user → return false, no retry
 *  - SW absent → browser not supported (Safari without PWA) → return false
 *  - Network   → backend POST failure → token still stored locally, retry next time
 *  - Any other → log + return false (never throw to the UI)
 */

import { useCallback, useEffect } from "react";
import { getToken, onMessage, type MessagePayload } from "firebase/messaging";
import { toast } from "sonner";
import {
  getFirebaseMessaging,
  getFcmSwRegistration,
  registerFcmServiceWorker,
} from "@/lib/firebase";
import { useNotificationStore } from "@/stores/notificationStore";
import { api } from "@/lib/axios";

// VAPID key from Firebase Console → Project Settings → Cloud Messaging → Web Push certificates
const VAPID_KEY = import.meta.env.VITE_FIREBASE_VAPID_KEY as string | undefined;

// ─── Types ────────────────────────────────────────────────────────────────────

export type PushPermissionStatus = "granted" | "denied" | "default" | "unsupported";

export interface UsePushNotificationsReturn {
  readonly fcmToken: string | null;
  readonly permissionStatus: PushPermissionStatus;
  readonly isPermissionGranted: boolean;
  readonly requestPermission: () => Promise<boolean>;
  readonly subscribeFixture: (fixtureApiId: number) => Promise<void>;
  readonly unsubscribeFixture: (fixtureApiId: number) => Promise<void>;
}

// ─── Hook ─────────────────────────────────────────────────────────────────────

export const usePushNotifications = (): UsePushNotificationsReturn => {
  const {
    fcmToken,
    isPermissionGranted,
    setFcmToken,
    setPermissionGranted,
    subscribeToFixture,
    unsubscribeFromFixture,
  } = useNotificationStore();

  // ── Resolve current permission status ──────────────────────────────────────
  const permissionStatus: PushPermissionStatus = (() => {
    if (typeof Notification === "undefined") return "unsupported";
    return Notification.permission as PushPermissionStatus;
  })();

  // ── Register foreground message listener once ───────────────────────────────
  useEffect(() => {
    if (!isPermissionGranted) return;

    const messaging = getFirebaseMessaging();
    if (!messaging) return;

    // onMessage fires when the app is foregrounded — FCM does NOT show a native
    // notification in this case, so we render one via Sonner.
    const unsubscribe = onMessage(messaging, (payload: MessagePayload) => {
      const { title, body } = payload.notification ?? {};
      toast(title ?? "CopApp", {
        description: body ?? "Nova actualização de jogo.",
        duration: 6000,
        position: "bottom-right",
      });
    });

    return unsubscribe;
  }, [isPermissionGranted]);

  // ── Request permission + get FCM token ─────────────────────────────────────
  const requestPermission = useCallback(async (): Promise<boolean> => {
    // Guard: API not available (e.g. Firefox without HTTPS, iOS Safari)
    if (typeof Notification === "undefined") {
      toast.error("Notificações push não são suportadas neste browser.", {
        duration: 4000,
      });
      return false;
    }

    // Guard: user already blocked
    if (Notification.permission === "denied") {
      toast.warning(
        "Notificações bloqueadas. Altere as permissões nas definições do browser.",
        { duration: 5000 }
      );
      setPermissionGranted(false);
      return false;
    }

    try {
      // 1. Request OS-level permission
      const permission = await Notification.requestPermission();

      if (permission !== "granted") {
        setPermissionGranted(false);
        toast.warning("Permissão de notificação não concedida.", {
          duration: 4000,
        });
        return false;
      }

      // 2. Get FCM messaging instance (requires SW support)
      const messaging = getFirebaseMessaging();
      if (!messaging) {
        toast.error(
          "Serviço de mensagens indisponível. Verifique a configuração do PWA.",
          { duration: 4000 }
        );
        return false;
      }

      // 3. Ensure SW is active — use the cached registration from bootstrap;
      //    fall back to on-demand registration if App.tsx bootstrap raced.
      let swRegistration = getFcmSwRegistration();
      if (!swRegistration) {
        swRegistration = await registerFcmServiceWorker();
      }

      if (!swRegistration?.active) {
        toast.error(
          "Serviço de mensagens indisponível. Verifique se o browser suporta PWA.",
          { duration: 4000 }
        );
        return false;
      }

      // 4. Retrieve or refresh the FCM token
      // VAPID_KEY must be set; getToken requires a string
      if (!VAPID_KEY) {
        console.error("[FCM] VITE_FIREBASE_VAPID_KEY is not set.");
        return false;
      }

      const token = await getToken(messaging, {
        vapidKey: VAPID_KEY,
        serviceWorkerRegistration: swRegistration,
      });

      if (!token) {
        toast.error("Não foi possível obter o token de notificação.", {
          duration: 4000,
        });
        return false;
      }

      // 4. Persist locally first (optimistic) — backend failure is non-fatal
      setFcmToken(token);
      setPermissionGranted(true);

      // 5. Register token with backend (best-effort — do not block UX on failure)
      try {
        await api.post("/push-subscriptions", {
          fcm_token: token,
          device_type: "web",
        });
      } catch (backendError) {
        // Log but don't fail — subscription will be retried on next enable
        console.warn(
          "[FCM] Failed to register token with backend:",
          backendError
        );
      }

      toast.success("Notificações activadas com sucesso.", {
        description: "Receberá alertas quando as escalações forem confirmadas.",
        duration: 4000,
      });

      return true;
    } catch (error) {
      // Catch-all: getToken failures, SW registration errors, etc.
      console.error("[FCM] requestPermission error:", error);
      setPermissionGranted(false);
      toast.error("Erro ao activar notificações. Tente novamente.", {
        duration: 4000,
      });
      return false;
    }
  }, [setFcmToken, setPermissionGranted]);

  // ── Subscribe to a specific fixture ────────────────────────────────────────
  const subscribeFixture = useCallback(
    async (fixtureApiId: number): Promise<void> => {
      if (!fcmToken) {
        console.warn("[FCM] Cannot subscribe — no FCM token available");
        return;
      }

      try {
        await api.patch("/push-subscriptions/subscribe", {
          fcm_token: fcmToken,
          fixture_id: fixtureApiId,
        });
        subscribeToFixture(fixtureApiId);
      } catch (error) {
        console.error("[FCM] subscribeFixture error:", error);
      }
    },
    [fcmToken, subscribeToFixture]
  );

  // ── Unsubscribe from a specific fixture ────────────────────────────────────
  const unsubscribeFixture = useCallback(
    async (fixtureApiId: number): Promise<void> => {
      if (!fcmToken) return;

      try {
        await api.patch("/push-subscriptions/unsubscribe", {
          fcm_token: fcmToken,
          fixture_id: fixtureApiId,
        });
        unsubscribeFromFixture(fixtureApiId);
      } catch (error) {
        console.error("[FCM] unsubscribeFixture error:", error);
      }
    },
    [fcmToken, unsubscribeFromFixture]
  );

  return {
    fcmToken,
    permissionStatus,
    isPermissionGranted,
    requestPermission,
    subscribeFixture,
    unsubscribeFixture,
  };
};
