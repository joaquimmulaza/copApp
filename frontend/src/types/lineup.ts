// ─── Lineup domain types ─────────────────────────────────────
import type { Position } from './player'

// Player entry within the lineup JSON array
export interface LineupPlayer {
  readonly player_id: number
  readonly name: string
  readonly number: string
  readonly pos: Position
  readonly grid: string | null  // "1:1", "2:3" — row:column in formation grid
  readonly photo_url: string | null
}

export interface Coach {
  readonly name: string
  readonly photo: string | null
}

export interface FixtureLineup {
  readonly id: number
  readonly fixture_id: number
  readonly team_id: number
  readonly formation: string | null   // "4-3-3", "3-5-2"
  readonly starting_xi: LineupPlayer[]
  readonly substitutes: LineupPlayer[]
  readonly coach: Coach | null
  readonly is_confirmed: boolean
  readonly confirmed_at: string | null
  readonly created_at: string
  readonly updated_at: string
}

// Both team lineups together
export interface FixtureLineups {
  readonly home: FixtureLineup | null
  readonly away: FixtureLineup | null
}
