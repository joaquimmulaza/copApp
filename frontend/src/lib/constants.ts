// ─── Project-wide constants ───────────────────────────────────
// Keep all magic numbers and strings here.
// Components import from @/lib/constants — never hard-code values.

// ─── API Football ─────────────────────────────────────────────
export const LEAGUE_ID = 1 as const; // FIFA World Cup
export const SEASON_YEAR = 2026 as const;

// ─── API Base URL ─────────────────────────────────────────────
export const API_BASE_URL =
  import.meta.env.VITE_API_BASE_URL ?? "http://localhost:8000";

// ─── Fixture status codes ─────────────────────────────────────
export const LIVE_STATUSES = [
  "1H",
  "HT",
  "2H",
  "ET",
  "P",
  "BT",
  "INT",
] as const;
export const FINISHED_STATUSES = ["FT", "AET", "PEN"] as const;
export const PENDING_STATUSES = ["NS", "PST"] as const;

// ─── Polling intervals (ms) ───────────────────────────────────
export const LIVE_POLL_INTERVAL = 60_000; // 1 min — WebSocket handles real-time
export const STANDINGS_POLL_INTERVAL = 5 * 60_000; // 5 min

// ─── Player positions ─────────────────────────────────────────
export const POSITIONS = {
  GOALKEEPER: "Goalkeeper",
  DEFENDER: "Defender",
  MIDFIELDER: "Midfielder",
  ATTACKER: "Attacker",
} as const;

export type Position = (typeof POSITIONS)[keyof typeof POSITIONS];

// ─── AI Chip types ────────────────────────────────────────────
export const GEMINI_CHIPS = [
  { id: "tactical_flash", label: "⚡ Flash Tático" },
  { id: "injury_impact", label: "🏥 Impacto das Ausências" },
  { id: "head2head", label: "📊 Raio-X do Confronto" },
  { id: "guided_bet", label: "🎯 Palpite Guiado" },
  { id: "recent_form", label: "📈 Forma Recente" },
] as const;

export type GeminiChipId = (typeof GEMINI_CHIPS)[number]["id"];

// ─── Storage keys ─────────────────────────────────────────────
export const STORAGE_KEYS = {
  THEME: "copapp-theme",
  FCM_TOKEN: "copapp-fcm-token",
} as const;

// ─── Query keys (TanStack Query) ──────────────────────────────
// Centralised to prevent typo mismatches across hooks
export const QUERY_KEYS = {
  fixtures: (params?: object) => ["fixtures", params] as const,
  fixture: (id: number) => ["fixture", id] as const,
  lineups: (fixtureId: number) => ["lineups", fixtureId] as const,
  standings: () => ["standings"] as const,
  injuries: (teamId?: number) => ["injuries", teamId] as const,
  players: (teamId: number) => ["players", teamId] as const,
  geminiCache: (fixtureId: number, chip: GeminiChipId) =>
    ["gemini", fixtureId, chip] as const,
} as const;
