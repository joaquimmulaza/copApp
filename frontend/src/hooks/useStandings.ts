import { useQuery } from '@tanstack/react-query'
import { api } from '@/lib/axios'
import { QUERY_KEYS } from '@/lib/constants'
import type { StandingsResponse, GroupStandings } from '@/types/standing'

export const useStandings = () =>
  useQuery({
    queryKey: QUERY_KEYS.standings(),
    queryFn: async (): Promise<GroupStandings[]> => {
      const { data } = await api.get<StandingsResponse>('/standings')
      return data.data
    },
    staleTime: 5 * 60_000,
  })
