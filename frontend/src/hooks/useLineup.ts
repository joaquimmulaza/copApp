import { useQuery, useQueryClient } from "@tanstack/react-query"
import { useEffect, useRef } from "react"
import { api } from "@/lib/axios"
import { QUERY_KEYS } from "@/lib/constants"
import { getEchoInstance } from "@/lib/echo"
import type { FixtureLineups } from "@/types/lineup"

// ─── useLineup ────────────────────────────────────────────────
// Fetches both team lineups for a fixture and subscribes to the
// Laravel Reverb public channel `fixtures.{id}`. When the backend
// dispatches `LineupConfirmed`, the TanStack Query cache for this
// fixture's lineup is immediately invalidated and refetched,
// updating the 22 starters in real-time without a page refresh.
//
// Channel naming: `fixtures.{id}` (public, no auth required)
// Event name in Laravel: LineupConfirmed → broadcasted as `.LineupConfirmed`

export const useLineup = (fixtureId: number | undefined) => {
  const queryClient = useQueryClient()
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const channelRef = useRef<any>(null)
  const resolvedId = fixtureId ?? 0

  const query = useQuery({
    queryKey: QUERY_KEYS.lineups(resolvedId),
    queryFn: async (): Promise<FixtureLineups> => {
      const { data } = await api.get<{ data: FixtureLineups }>(
        `/fixtures/${resolvedId}/lineups`,
      )
      return data.data
    },
    enabled: fixtureId !== undefined && fixtureId > 0,
    // Lineups rarely change; 60 s stale keeps requests minimal.
    // When confirmed, the WebSocket event forces an instant refetch.
    staleTime: 60_000,
    refetchOnWindowFocus: false,
  })

  // ─── Real-time subscription via Laravel Echo (Reverb) ──────
  useEffect(() => {
    if (!fixtureId || fixtureId <= 0) return

    const echo = getEchoInstance()
    if (!echo) return

    // Public channel — no auth needed
    const channelName = `fixtures.${fixtureId}`
    // eslint-disable-next-line @typescript-eslint/no-unsafe-assignment
    const channel = echo.channel(channelName)
    channelRef.current = channel

    // LineupConfirmed → force immediate cache invalidation + refetch
    // eslint-disable-next-line @typescript-eslint/no-unsafe-call, @typescript-eslint/no-unsafe-member-access
    channel.listen(".LineupConfirmed", () => {
      void queryClient.invalidateQueries({
        queryKey: QUERY_KEYS.lineups(fixtureId),
        refetchType: "active",
      })
    })

    return () => {
      echo.leave(channelName)
      channelRef.current = null
    }
  }, [fixtureId, queryClient])

  return query
}
