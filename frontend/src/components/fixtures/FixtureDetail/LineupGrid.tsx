// LineupGrid — tactical football pitch for both team lineups
// Aesthetic: Quiet Luxury Dark — a refined, monochromatic glass rectangle
// with hairline pitch markings. No green tones whatsoever.
//
// Grid coordinate system from API-Football:
//   grid: "row:col" where row 1 = goalkeeper row, higher = attackers
//   Columns are 1-indexed; max columns depend on formation (3–5 typically)
//
// Layout strategy:
//   - Each team occupies one half of the pitch (vertical split on desktop,
//     stacked vertically on mobile — home on top, away flipped 180°)
//   - The `grid` string is parsed into { row, col } and used to place
//     players in a CSS grid where rows = formation lines.

import { motion, AnimatePresence } from "framer-motion"
import { Activity, Ban } from "lucide-react"
import { cn } from "@/lib/utils"
import {
  playerCardVariant,
  playerCardTransition,
  stagger,
} from "@/lib/animations"
import type { FixtureLineup, LineupPlayer } from "@/types/lineup"
import type { InjuryWithPlayer } from "@/types/injury"

// ─── Types ────────────────────────────────────────────────────

interface GridPosition {
  readonly row: number
  readonly col: number
}

interface PlayerWithInjury {
  readonly player: LineupPlayer
  readonly injury: InjuryWithPlayer | null
}

// ─── Helpers ──────────────────────────────────────────────────

function parseGrid(grid: string | null): GridPosition | null {
  if (!grid) return null
  const parts = grid.split(":")
  if (parts.length !== 2) return null
  const row = parseInt(parts[0] ?? "0", 10)
  const col = parseInt(parts[1] ?? "0", 10)
  if (isNaN(row) || isNaN(col)) return null
  return { row, col }
}

// Group players by their row (formation line), sorted
function groupByRow(players: LineupPlayer[]): Map<number, LineupPlayer[]> {
  const map = new Map<number, LineupPlayer[]>()
  for (const p of players) {
    const pos = parseGrid(p.grid)
    const row = pos?.row ?? 0
    const existing = map.get(row) ?? []
    map.set(row, [...existing, p])
  }
  // Sort each row by column
  for (const [key, row] of map.entries()) {
    map.set(
      key,
      [...row].sort((a, b) => {
        const aCol = parseGrid(a.grid)?.col ?? 0
        const bCol = parseGrid(b.grid)?.col ?? 0
        return aCol - bCol
      }),
    )
  }
  return map
}

// Build a lookup map: player_id → active injury/suspension
function buildInjuryMap(
  injuries: InjuryWithPlayer[],
): Map<number, InjuryWithPlayer> {
  const map = new Map<number, InjuryWithPlayer>()
  for (const inj of injuries) {
    if (inj.is_active) {
      map.set(inj.player.id, inj)
    }
  }
  return map
}

// ─── Sub-components ───────────────────────────────────────────

interface PitchPlayerProps {
  readonly pw: PlayerWithInjury
  readonly flipped: boolean
  readonly isConfirmed: boolean
}

function PitchPlayer({ pw, flipped, isConfirmed }: PitchPlayerProps) {
  const { player, injury } = pw
  const hasInjury = injury !== null
  const isInjury = injury?.type === "injury"

  return (
    <motion.div
      key={player.player_id}
      // layoutId enables smooth transition from probable → confirmed positions
      layoutId={`player-${player.player_id}`}
      variants={playerCardVariant}
      initial="initial"
      animate="animate"
      transition={playerCardTransition}
      className={cn(
        "flex flex-col items-center gap-0.5 cursor-default select-none",
        "transition-opacity duration-300",
        hasInjury && "opacity-40",
      )}
      aria-label={`${player.name}${hasInjury ? (isInjury ? " — lesionado" : " — suspenso") : ""}`}
    >
      {/* Avatar circle */}
      <div className="relative">
        <div
          className={cn(
            "size-7 sm:size-8 rounded-full flex items-center justify-center",
            "bg-surface-elevated border border-border",
            "font-mono text-[10px] font-semibold text-foreground",
            isConfirmed && "border-[var(--gold)] border-opacity-40",
          )}
        >
          {player.number}
        </div>

        {/* Injury / suspension micro-icon overlay */}
        {hasInjury && (
          <span
            className={cn(
              "absolute -top-1 -right-1 size-3.5 rounded-full flex items-center justify-center",
              isInjury ? "bg-warning/90" : "bg-danger/90",
            )}
            aria-hidden
          >
            {isInjury ? (
              <Activity className="size-2" />
            ) : (
              <Ban className="size-2" />
            )}
          </span>
        )}
      </div>

      {/* Surname */}
      <span
        className={cn(
          "text-[9px] sm:text-[10px] text-muted-foreground truncate max-w-[52px] sm:max-w-[60px] text-center leading-tight",
          flipped && "rotate-180",
        )}
      >
        {player.name.split(" ").pop()}
      </span>
    </motion.div>
  )
}

// Formation half — one side of the pitch
interface FormationHalfProps {
  readonly players: LineupPlayer[]
  readonly flipped: boolean // away team is displayed flipped
  readonly injuryMap: Map<number, InjuryWithPlayer>
  readonly isConfirmed: boolean
}

function FormationHalf({
  players,
  flipped,
  injuryMap,
  isConfirmed,
}: FormationHalfProps) {
  const rowMap = groupByRow(players)

  // Sort rows: row 1 = GK (bottom of home half, top of away half)
  // For home: ascending row order (GK at bottom visually)
  // For away: descending row order (GK at bottom = highest row number)
  const sortedRows = Array.from(rowMap.keys()).sort((a, b) =>
    flipped ? b - a : a - b,
  )

  return (
    <motion.div
      variants={stagger}
      initial="initial"
      animate="animate"
      className={cn(
        "flex flex-col justify-around h-full w-full px-2",
        flipped && "flex-col-reverse",
      )}
      style={{ gap: "clamp(4px, 1.5vh, 12px)" }}
    >
      {sortedRows.map((rowNum) => {
        const rowPlayers = rowMap.get(rowNum) ?? []
        return (
          <div
            key={rowNum}
            className="flex justify-around items-center w-full"
          >
            {rowPlayers.map((player) => (
              <PitchPlayer
                key={player.player_id}
                pw={{ player, injury: injuryMap.get(player.player_id) ?? null }}
                flipped={flipped}
                isConfirmed={isConfirmed}
              />
            ))}
          </div>
        )
      })}
    </motion.div>
  )
}

// ─── Pitch Markings ───────────────────────────────────────────
// Extremely fine lines, low opacity — Quiet Luxury aesthetic

function PitchMarkings({ horizontal = false }: { horizontal?: boolean }) {
  return (
    <svg
      aria-hidden
      className="absolute inset-0 w-full h-full pointer-events-none"
      xmlns="http://www.w3.org/2000/svg"
      preserveAspectRatio="none"
    >
      {horizontal ? (
        // Horizontal layout (desktop): vertical centre line + circles + boxes
        <>
          {/* Centre line */}
          <line
            x1="50%"
            y1="0"
            x2="50%"
            y2="100%"
            stroke="var(--border)"
            strokeWidth="0.5"
            strokeOpacity="0.35"
          />
          {/* Centre circle */}
          <ellipse
            cx="50%"
            cy="50%"
            rx="12%"
            ry="18%"
            fill="none"
            stroke="var(--border)"
            strokeWidth="0.5"
            strokeOpacity="0.25"
          />
          {/* Centre spot */}
          <circle
            cx="50%"
            cy="50%"
            r="1.5"
            fill="var(--border)"
            fillOpacity="0.3"
          />
          {/* Home penalty area */}
          <rect
            x="0"
            y="22%"
            width="15%"
            height="56%"
            fill="none"
            stroke="var(--border)"
            strokeWidth="0.5"
            strokeOpacity="0.25"
          />
          {/* Away penalty area */}
          <rect
            x="85%"
            y="22%"
            width="15%"
            height="56%"
            fill="none"
            stroke="var(--border)"
            strokeWidth="0.5"
            strokeOpacity="0.25"
          />
          {/* Home goal area */}
          <rect
            x="0"
            y="37%"
            width="5.5%"
            height="26%"
            fill="none"
            stroke="var(--border)"
            strokeWidth="0.5"
            strokeOpacity="0.2"
          />
          {/* Away goal area */}
          <rect
            x="94.5%"
            y="37%"
            width="5.5%"
            height="26%"
            fill="none"
            stroke="var(--border)"
            strokeWidth="0.5"
            strokeOpacity="0.2"
          />
          {/* Outer border */}
          <rect
            x="0.5"
            y="0.5"
            width="calc(100% - 1px)"
            height="calc(100% - 1px)"
            fill="none"
            stroke="var(--border)"
            strokeWidth="0.5"
            strokeOpacity="0.3"
            rx="4"
          />
        </>
      ) : (
        // Vertical layout (mobile): horizontal centre line + boxes
        <>
          {/* Centre line */}
          <line
            x1="0"
            y1="50%"
            x2="100%"
            y2="50%"
            stroke="var(--border)"
            strokeWidth="0.5"
            strokeOpacity="0.35"
          />
          {/* Centre circle */}
          <ellipse
            cx="50%"
            cy="50%"
            rx="18%"
            ry="10%"
            fill="none"
            stroke="var(--border)"
            strokeWidth="0.5"
            strokeOpacity="0.25"
          />
          {/* Centre spot */}
          <circle
            cx="50%"
            cy="50%"
            r="1.5"
            fill="var(--border)"
            fillOpacity="0.3"
          />
          {/* Top penalty area (away GK) */}
          <rect
            x="22%"
            y="0"
            width="56%"
            height="15%"
            fill="none"
            stroke="var(--border)"
            strokeWidth="0.5"
            strokeOpacity="0.25"
          />
          {/* Bottom penalty area (home GK) */}
          <rect
            x="22%"
            y="85%"
            width="56%"
            height="15%"
            fill="none"
            stroke="var(--border)"
            strokeWidth="0.5"
            strokeOpacity="0.25"
          />
          {/* Outer border */}
          <rect
            x="0.5"
            y="0.5"
            width="calc(100% - 1px)"
            height="calc(100% - 1px)"
            fill="none"
            stroke="var(--border)"
            strokeWidth="0.5"
            strokeOpacity="0.3"
            rx="4"
          />
        </>
      )}
    </svg>
  )
}

// ─── Formation label ──────────────────────────────────────────

interface FormationLabelProps {
  readonly teamName: string
  readonly formation: string | null
  readonly side: "home" | "away"
}

function FormationLabel({ teamName, formation, side }: FormationLabelProps) {
  return (
    <div
      className={cn(
        "flex items-center gap-2 px-1",
        side === "away" && "flex-row-reverse",
      )}
    >
      <span className="font-display font-semibold text-xs text-foreground truncate max-w-[100px] sm:max-w-[140px]">
        {teamName}
      </span>
      {formation && (
        <span className="font-mono text-[10px] text-muted-foreground shrink-0">
          {formation}
        </span>
      )}
    </div>
  )
}

// ─── Main LineupGrid ──────────────────────────────────────────

export interface LineupGridProps {
  readonly home: FixtureLineup
  readonly away: FixtureLineup
  readonly homeTeamName: string
  readonly awayTeamName: string
  readonly activeInjuries?: InjuryWithPlayer[]
}

export function LineupGrid({
  home,
  away,
  homeTeamName,
  awayTeamName,
  activeInjuries = [],
}: LineupGridProps) {
  const injuryMap = buildInjuryMap(activeInjuries)

  return (
    <section aria-label="Campo tático — escalação oficial" className="w-full">
      {/* ── Formation labels (shown above the pitch) ── */}
      <div className="flex items-center justify-between mb-2 px-1">
        <FormationLabel
          teamName={homeTeamName}
          formation={home.formation}
          side="home"
        />
        <FormationLabel
          teamName={awayTeamName}
          formation={away.formation}
          side="away"
        />
      </div>

      {/* ── Pitch container ── */}
      <div
        className={cn(
          "card-glass relative overflow-hidden",
          // Desktop: horizontal pitch — two halves side by side
          // Mobile: vertical pitch — home on top, away below
          "flex flex-col md:flex-row",
          "w-full",
        )}
        style={{
          // Maintain pitch aspect ratio: 105m × 68m ≈ 1.544
          aspectRatio: "1 / 1.6",
          // On md+ override to horizontal
        }}
      >
        {/* Pitch markings — vertical layout (mobile) */}
        <div className="absolute inset-0 block md:hidden">
          <PitchMarkings horizontal={false} />
        </div>
        {/* Pitch markings — horizontal layout (desktop) */}
        <div className="absolute inset-0 hidden md:block">
          <PitchMarkings horizontal={true} />
        </div>

        {/* ── Home half ── */}
        <div
          className={cn(
            "relative flex-1 flex items-center justify-center",
            "min-h-[50%] md:min-h-0 md:min-w-[50%]",
          )}
        >
          <AnimatePresence mode="wait">
            <FormationHalf
              key={`home-${home.updated_at}`}
              players={home.starting_xi}
              flipped={false}
              injuryMap={injuryMap}
              isConfirmed={home.is_confirmed}
            />
          </AnimatePresence>
        </div>

        {/* ── Centre line — only visible on mobile as a divider ── */}
        <div
          aria-hidden
          className="relative md:hidden w-full flex items-center justify-center pointer-events-none"
          style={{ height: 0 }}
        />

        {/* ── Away half ── */}
        <div
          className={cn(
            "relative flex-1 flex items-center justify-center",
            "min-h-[50%] md:min-h-0 md:min-w-[50%]",
          )}
        >
          <AnimatePresence mode="wait">
            <FormationHalf
              key={`away-${away.updated_at}`}
              players={away.starting_xi}
              flipped={true}
              injuryMap={injuryMap}
              isConfirmed={away.is_confirmed}
            />
          </AnimatePresence>
        </div>
      </div>

      {/* ── Legend ── */}
      <div className="flex items-center gap-4 mt-2 px-1 text-[10px] text-muted-foreground">
        <span className="flex items-center gap-1">
          <Activity className="size-2.5 text-warning" />
          Lesionado
        </span>
        <span className="flex items-center gap-1">
          <Ban className="size-2.5 text-danger" />
          Suspenso
        </span>
        <span className="flex items-center gap-1 opacity-50">
          <span
            className="size-2 rounded-full border"
            style={{ borderColor: "var(--gold)" }}
          />
          Confirmado
        </span>
      </div>
    </section>
  )
}
