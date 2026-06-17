// ─── Fixture domain types ────────────────────────────────────
import type { TeamSummary } from './team'

// All valid short status codes from API-Football
export type FixtureStatusShort =
  | 'NS'    // Not started
  | '1H'    // First half
  | 'HT'    // Half time
  | '2H'    // Second half
  | 'ET'    // Extra time
  | 'BT'    // Break time (extra time interval)
  | 'P'     // Penalties
  | 'SUSP'  // Suspended
  | 'INT'   // Interrupted
  | 'FT'    // Full time
  | 'AET'   // After extra time
  | 'PEN'   // After penalties
  | 'PST'   // Postponed
  | 'CANC'  // Cancelled
  | 'ABD'   // Abandoned
  | 'AWD'   // Award
  | 'WO'    // Walk over

export type FixtureStage =
  | 'group' | 'r32' | 'r16' | 'qf' | 'sf' | 'f'

export interface Fixture {
  readonly id: number
  readonly api_football_id: number
  readonly home_team: TeamSummary
  readonly away_team: TeamSummary
  readonly round: string | null
  readonly stage: FixtureStage | null
  readonly group_name: string | null
  readonly venue_name: string | null
  readonly venue_city: string | null
  readonly kickoff_utc: string
  readonly status_short: FixtureStatusShort
  readonly status_long: string | null
  readonly home_score: number | null
  readonly away_score: number | null
  readonly home_score_ht: number | null
  readonly away_score_ht: number | null
  readonly home_score_et: number | null
  readonly away_score_et: number | null
  readonly home_score_pen: number | null
  readonly away_score_pen: number | null
  readonly elapsed_minutes: number | null
  readonly lineup_confirmed: boolean
  readonly lineup_confirmed_at: string | null
  readonly created_at: string
  readonly updated_at: string
}

// Compact shape for list views
export type FixtureSummary = Pick<
  Fixture,
  | 'id'
  | 'api_football_id'
  | 'home_team'
  | 'away_team'
  | 'kickoff_utc'
  | 'status_short'
  | 'home_score'
  | 'away_score'
  | 'elapsed_minutes'
  | 'lineup_confirmed'
  | 'round'
  | 'group_name'
>

// API list response wrapper
export interface FixtureListResponse {
  readonly data: FixtureSummary[]
  readonly meta?: {
    readonly total: number
    readonly page: number
  }
}
