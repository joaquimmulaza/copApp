import { useQuery } from '@tanstack/react-query'
import { api } from '@/lib/axios'
import { QUERY_KEYS } from '@/lib/constants'
import type { FixtureLineups } from '@/types/lineup'

export const useLineup = (fixtureId: number | undefined) =>
  useQuery({
    queryKey: QUERY_KEYS.lineups(fixtureId ?? 0),
    queryFn: async (): Promise<FixtureLineups> => {
      const { data } = await api.get<{ data: FixtureLineups }>(
        `/fixtures/${fixtureId}/lineups`
      )
      return data.data
    },
    enabled: fixtureId !== undefined && fixtureId > 0,
    staleTime: 60_000,
  })
