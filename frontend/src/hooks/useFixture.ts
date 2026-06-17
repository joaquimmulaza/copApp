import { useQuery } from "@tanstack/react-query"
import { api } from "@/lib/axios"
import { QUERY_KEYS } from "@/lib/constants"
import type { Fixture } from "@/types/fixture"

// ─── useFixture (singular) ─────────────────────────────────────
// Fetches the full Fixture object for a given internal DB ID.
// Distinct from useFixtures (list) — this is for the detail page.

export const useFixture = (fixtureId: number | undefined) =>
  useQuery({
    queryKey: QUERY_KEYS.fixture(fixtureId ?? 0),
    queryFn: async (): Promise<Fixture> => {
      const { data } = await api.get<{ data: Fixture }>(
        `/fixtures/${fixtureId ?? 0}`,
      )
      return data.data
    },
    enabled: fixtureId !== undefined && fixtureId > 0,
    staleTime: 30_000,
  })
