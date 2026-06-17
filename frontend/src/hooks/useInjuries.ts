import { useQuery } from '@tanstack/react-query'
import { api } from '@/lib/axios'
import { QUERY_KEYS } from '@/lib/constants'
import type { InjuryListResponse, InjuryWithPlayer } from '@/types/injury'

interface UseInjuriesParams {
  readonly teamId?: number
}

export const useInjuries = (params?: UseInjuriesParams) =>
  useQuery({
    queryKey: QUERY_KEYS.injuries(params?.teamId),
    queryFn: async (): Promise<InjuryWithPlayer[]> => {
      const { data } = await api.get<InjuryListResponse>('/injuries', {
        params: params?.teamId ? { team_id: params.teamId } : undefined,
      })
      return data.data
    },
    staleTime: 5 * 60_000, // Injuries update infrequently — 5 min
  })
