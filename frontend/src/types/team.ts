// ─── Team domain types ───────────────────────────────────────

export interface Team {
  readonly id: number
  readonly api_football_id: number
  readonly name: string
  readonly code: string | null
  readonly country: string | null
  readonly logo_url: string | null
  readonly group_name: string | null
  readonly venue: TeamVenue | null
  readonly coach: TeamCoach | null
  readonly created_at: string
  readonly updated_at: string
}

export interface TeamVenue {
  readonly name: string
  readonly city: string
  readonly capacity: number | null
}

export interface TeamCoach {
  readonly name: string
  readonly nationality: string | null
  readonly photo: string | null
}

// Minimal team reference used inside other types (e.g. fixtures)
export interface TeamSummary {
  readonly id: number
  readonly name: string
  readonly logo_url: string | null
  readonly code: string | null
}
