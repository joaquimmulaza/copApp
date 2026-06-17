import { useQuery } from "@tanstack/react-query";
import { api } from "@/lib/axios";
import { QUERY_KEYS, LIVE_POLL_INTERVAL } from "@/lib/constants";
import type {
  FixtureListResponse,
  FixtureStage,
  FixtureSummary,
} from "@/types/fixture";

// ─── Filter parameters accepted by useFixtures ───────────────
export interface UseFixturesParams {
  /** ISO date string "YYYY-MM-DD" — filter fixtures to a specific day */
  readonly date?: string;
  /** Comma-separated status codes e.g. "NS,1H,2H" */
  readonly status?: string;
  /** Group letter "A" … "L" — filter by World Cup group */
  readonly group?: string;
  /** Tournament stage — "group" | "r32" | "r16" | "qf" | "sf" | "f" */
  readonly stage?: FixtureStage;
}

// ─── Hook ────────────────────────────────────────────────────
export const useFixtures = (params?: UseFixturesParams) =>
  useQuery({
    queryKey: QUERY_KEYS.fixtures(params),
    queryFn: async (): Promise<FixtureSummary[]> => {
      const { data } = await api.get<FixtureListResponse>("/fixtures", {
        params,
      });
      return data.data;
    },
    // Cache fixtures for 30 s by default.
    // When a status filter includes live statuses the component should
    // pass a shorter staleTime (LIVE_POLL_INTERVAL) — keep the default
    // conservative so we don't hammer the backend for static data.
    staleTime: 30_000,
    // Refetch every minute in the background when tab is active so the
    // user gets fresh data without manual reload.
    refetchInterval: LIVE_POLL_INTERVAL,
    refetchIntervalInBackground: false,
  });
