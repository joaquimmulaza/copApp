// ─── Injury & Suspension domain types ──────────────────────

export type StatusType = 'injury' | 'suspension'

export interface PlayerStatus {
  readonly id: number
  readonly api_football_id: number
  readonly player_id: number
  readonly team_id: number
  readonly type: StatusType
  readonly reason: string | null
  readonly start_date: string | null
  readonly expected_return: string | null
  readonly is_active: boolean
  readonly synced_at: string | null
  readonly created_at: string
  readonly updated_at: string
}

// Injury with denormalised player data (API resource shape)
export interface InjuryWithPlayer extends PlayerStatus {
  readonly player: {
    readonly id: number
    readonly name: string
    readonly photo_url: string | null
    readonly position: string | null
    readonly number: string | null
  }
  readonly team: {
    readonly id: number
    readonly name: string
    readonly logo_url: string | null
  }
}

export interface InjuryListResponse {
  readonly data: InjuryWithPlayer[]
  readonly meta?: {
    readonly total: number
    readonly total_injuries: number
    readonly total_suspensions: number
  }
}
