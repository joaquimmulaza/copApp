import { useQuery } from '@tanstack/react-query'
import { api } from '@/lib/axios'
import { QUERY_KEYS } from '@/lib/constants'
import type { FixtureListResponse, FixtureSummary } from '@/types/fixture'

interface UseFixturesParams {
  readonly date?: string         // "YYYY-MM-DD" — filter by day
  readonly status?: string       // "NS,1H,2H" — comma-separated statuses
  readonly group?: string        // "A" … "L"
}

export const useFixtures = (params?: UseFixturesParams) =>
  useQuery({
    queryKey: QUERY_KEYS.fixtures(params),
    queryFn: async (): Promise<FixtureSummary[]> => {
      const { data } = await api.get<FixtureListResponse>('/fixtures', {
        params,
      })
      return data.data
    },
    staleTime: 30_000,
  })
