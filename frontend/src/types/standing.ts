// ─── Standing domain types ───────────────────────────────────
import type { TeamSummary } from "./team";

export type StandingStatus = "same" | "up" | "down";

export interface Standing {
  readonly id: number;
  readonly team: TeamSummary;
  readonly group_name: string;
  readonly rank: number;
  readonly played: number;
  readonly won: number;
  readonly drawn: number;
  readonly lost: number;
  readonly goals_for: number;
  readonly goals_against: number;
  readonly goals_diff: number;
  readonly points: number;
  readonly form: string | null; // "WWDLW"
  readonly status: StandingStatus | null;
  readonly description: string | null; // "Promotion - Round of 32"
  readonly synced_at: string | null;
}

// Group standings sorted by rank
export interface GroupStandings {
  readonly group: string; // "A", "B" … "L"
  readonly standings: Standing[];
}

export type GroupStandingsMap = Record<string, Standing[]>;

export interface StandingsResponse {
  readonly data: GroupStandingsMap;
}
