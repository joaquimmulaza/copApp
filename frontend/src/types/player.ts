// ─── Player domain types ─────────────────────────────────────
import type { TeamSummary } from "./team";

export type Position = "Goalkeeper" | "Defender" | "Midfielder" | "Attacker";

export interface Player {
  readonly id: number;
  readonly api_football_id: number;
  readonly team_id: number;
  readonly team: TeamSummary | null;
  readonly name: string;
  readonly firstname: string | null;
  readonly lastname: string | null;
  readonly birth_date: string | null;
  readonly nationality: string | null;
  readonly age: number | null;
  readonly height: number | null; // cm
  readonly weight: number | null; // kg
  readonly photo_url: string | null;
  readonly position: Position | null;
  readonly number: string | null;
  readonly created_at: string;
  readonly updated_at: string;
}

export interface PlayerStats {
  readonly id: number;
  readonly player_id: number;
  readonly team_id: number;
  readonly appearances: number;
  readonly goals: number;
  readonly assists: number;
  readonly yellow_cards: number;
  readonly red_cards: number;
  readonly minutes_played: number;
  readonly rating: number | null;
  readonly shots_total: number;
  readonly shots_on: number;
  readonly passes_total: number;
  readonly passes_accuracy: number | null;
  readonly tackles: number;
  readonly dribbles_success: number;
  readonly synced_at: string | null;
}

// Player with status info (injury/suspension)
export interface PlayerWithStatus extends Player {
  readonly status: import("./injury").PlayerStatus | null;
}
