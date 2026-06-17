import { useCallback } from "react";
import { getToken } from "firebase/messaging";
import { getFirebaseMessaging } from "@/lib/firebase";
import { useNotificationStore } from "@/stores/notificationStore";
import { api } from "@/lib/axios";

const VAPID_KEY = import.meta.env.VITE_FIREBASE_VAPID_KEY;

export const usePushNotifications = () => {
  const {
    fcmToken,
    isPermissionGranted,
    setFcmToken,
    setPermissionGranted,
    subscribeToFixture,
    unsubscribeFromFixture,
  } = useNotificationStore();

  /**
   * Requests notification permission and registers the FCM token
   * with the Laravel backend via POST /api/push-subscriptions.
   */
  const requestPermission = useCallback(async (): Promise<boolean> => {
    try {
      const permission = await Notification.requestPermission();

      if (permission !== "granted") {
        setPermissionGranted(false);
        return false;
      }

      const messaging = getFirebaseMessaging();
      if (!messaging) return false;

      const token = await getToken(messaging, { vapidKey: VAPID_KEY });

      if (!token) return false;

      await api.post("/push-subscriptions", {
        fcm_token: token,
        device_type: "web",
      });

      setFcmToken(token);
      setPermissionGranted(true);
      return true;
    } catch {
      setPermissionGranted(false);
      return false;
    }
  }, [setFcmToken, setPermissionGranted]);

  const subscribeFixture = useCallback(
    async (fixtureApiId: number) => {
      if (!fcmToken) return;

      await api.patch("/push-subscriptions/subscribe", {
        fcm_token: fcmToken,
        fixture_id: fixtureApiId,
      });

      subscribeToFixture(fixtureApiId);
    },
    [fcmToken, subscribeToFixture],
  );

  const unsubscribeFixture = useCallback(
    async (fixtureApiId: number) => {
      if (!fcmToken) return;

      await api.patch("/push-subscriptions/unsubscribe", {
        fcm_token: fcmToken,
        fixture_id: fixtureApiId,
      });

      unsubscribeFromFixture(fixtureApiId);
    },
    [fcmToken, unsubscribeFromFixture],
  );

  return {
    fcmToken,
    isPermissionGranted,
    requestPermission,
    subscribeFixture,
    unsubscribeFixture,
  };
};
