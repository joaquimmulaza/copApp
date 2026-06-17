import { useEffect, useRef } from 'react'
import { useQueryClient } from '@tanstack/react-query'
import { getEchoInstance } from '@/lib/echo'
import { QUERY_KEYS } from '@/lib/constants'

/**
 * Subscribes to a Laravel Echo (Reverb) channel and invalidates
 * relevant TanStack Query caches when events arrive.
 *
 * @param fixtureApiId - The API-Football fixture ID to listen for
 */
export const useEcho = (fixtureApiId: number | null) => {
  const queryClient = useQueryClient()
  // Using `any` here because Laravel Echo's channel type is not exported
  // and the generic ReturnType chain fails through the null-union type.
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const channelRef = useRef<any>(null)

  useEffect(() => {
    if (!fixtureApiId) return

    const echo = getEchoInstance()
    if (!echo) return

    const channelName = `fixture.${fixtureApiId}`
    // eslint-disable-next-line @typescript-eslint/no-unsafe-assignment
    const channel = echo.channel(channelName)
    channelRef.current = channel

    // ─── LineupConfirmed — invalidate lineup cache ──────────
    // eslint-disable-next-line @typescript-eslint/no-unsafe-call, @typescript-eslint/no-unsafe-member-access
    channel.listen('.LineupConfirmed', () => {
      void queryClient.invalidateQueries({
        queryKey: QUERY_KEYS.lineups(fixtureApiId),
      })
    })

    // ─── LiveScoreUpdated — invalidate fixture cache ────────
    // eslint-disable-next-line @typescript-eslint/no-unsafe-call, @typescript-eslint/no-unsafe-member-access
    channel.listen('.LiveScoreUpdated', () => {
      void queryClient.invalidateQueries({
        queryKey: QUERY_KEYS.fixture(fixtureApiId),
      })
    })

    return () => {
      echo.leave(channelName)
      channelRef.current = null
    }
  }, [fixtureApiId, queryClient])
}
