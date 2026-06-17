import { create } from 'zustand'
import { persist } from 'zustand/middleware'
import { STORAGE_KEYS } from '@/lib/constants'

interface NotificationStore {
  readonly fcmToken: string | null
  readonly isPermissionGranted: boolean
  readonly subscribedFixtures: number[]
  readonly subscribedTeams: number[]

  readonly setFcmToken: (token: string) => void
  readonly setPermissionGranted: (granted: boolean) => void
  readonly subscribeToFixture: (fixtureApiId: number) => void
  readonly unsubscribeFromFixture: (fixtureApiId: number) => void
  readonly subscribeToTeam: (teamApiId: number) => void
  readonly unsubscribeFromTeam: (teamApiId: number) => void
}

export const useNotificationStore = create<NotificationStore>()(
  persist(
    (set) => ({
      fcmToken: null,
      isPermissionGranted: false,
      subscribedFixtures: [],
      subscribedTeams: [],

      setFcmToken: (token) => set({ fcmToken: token }),

      setPermissionGranted: (granted) =>
        set({ isPermissionGranted: granted }),

      subscribeToFixture: (fixtureApiId) =>
        set((state) => ({
          subscribedFixtures: state.subscribedFixtures.includes(fixtureApiId)
            ? state.subscribedFixtures
            : [...state.subscribedFixtures, fixtureApiId],
        })),

      unsubscribeFromFixture: (fixtureApiId) =>
        set((state) => ({
          subscribedFixtures: state.subscribedFixtures.filter(
            (id) => id !== fixtureApiId
          ),
        })),

      subscribeToTeam: (teamApiId) =>
        set((state) => ({
          subscribedTeams: state.subscribedTeams.includes(teamApiId)
            ? state.subscribedTeams
            : [...state.subscribedTeams, teamApiId],
        })),

      unsubscribeFromTeam: (teamApiId) =>
        set((state) => ({
          subscribedTeams: state.subscribedTeams.filter(
            (id) => id !== teamApiId
          ),
        })),
    }),
    {
      name: STORAGE_KEYS.FCM_TOKEN,
      partialize: (state) => ({
        fcmToken:            state.fcmToken,
        isPermissionGranted: state.isPermissionGranted,
        subscribedFixtures:  state.subscribedFixtures,
        subscribedTeams:     state.subscribedTeams,
      }),
    }
  )
)
