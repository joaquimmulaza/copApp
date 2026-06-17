// FixturePage — match detail page with tabbed navigation
// Tabs: Escalações | Flash Tático | Informações
// The "Escalações" tab hosts LineupGrid with real-time WebSocket updates.

import { useParams } from "react-router-dom"
import { motion, AnimatePresence } from "framer-motion"
import { cn } from "@/lib/utils"
import {
  Tabs,
  TabsList,
  TabsTrigger,
  TabsContent,
} from "@/components/ui/tabs"
import { SkeletonCard } from "@/components/common/SkeletonCard"
import { LineupGrid } from "@/components/fixtures/FixtureDetail/LineupGrid"
import { TacticalFlash } from "@/components/fixtures/FixtureDetail/TacticalFlash"
import { useFixture } from "@/hooks/useFixture"
import { useLineup } from "@/hooks/useLineup"
import { useInjuries } from "@/hooks/useInjuries"
import { fadeIn, fadeInTransition } from "@/lib/animations"
import type { InjuryWithPlayer } from "@/types/injury"

// ─── Lineup status indicator ──────────────────────────────────
// A small pulsing dot next to a text label that tells the user
// whether the lineup shown is "Provável" (estimated) or
// "Oficial Confirmada" (confirmed from the API).

interface LineupStatusIndicatorProps {
  readonly isConfirmed: boolean
}

function LineupStatusIndicator({ isConfirmed }: LineupStatusIndicatorProps) {
  return (
    <div
      className={cn(
        "inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full",
        "border text-[10px] font-medium tracking-wide uppercase",
        isConfirmed
          ? "border-[var(--gold)]/30 bg-[var(--gold-glow)] text-[var(--gold)]"
          : "border-border bg-surface-elevated text-muted-foreground",
      )}
      aria-label={
        isConfirmed
          ? "Escalação oficial confirmada"
          : "Escalação provável — não confirmada"
      }
    >
      {/* Pulsing dot */}
      <span
        className={cn(
          "size-1.5 rounded-full shrink-0",
          isConfirmed
            ? "bg-[var(--gold)] animate-pulse"
            : "bg-muted-foreground",
        )}
        aria-hidden
      />
      {isConfirmed ? "Oficial Confirmada" : "Provável"}
    </div>
  )
}

// ─── Fixture header ───────────────────────────────────────────

interface FixtureHeaderProps {
  readonly homeTeamName: string
  readonly awayTeamName: string
  readonly round: string | null
  readonly homeScore: number | null
  readonly awayScore: number | null
  readonly statusShort: string
}

function FixtureHeader({
  homeTeamName,
  awayTeamName,
  round,
  homeScore,
  awayScore,
  statusShort,
}: FixtureHeaderProps) {
  const isLive = ["1H", "HT", "2H", "ET", "P", "BT", "INT"].includes(
    statusShort,
  )
  const isFinished = ["FT", "AET", "PEN"].includes(statusShort)
  const hasScore = homeScore !== null && awayScore !== null

  return (
    <header className="card-glass p-4 sm:p-6">
      <div className="flex items-center justify-between gap-4">
        {/* Home team */}
        <h2 className="font-display font-semibold text-base sm:text-xl text-right flex-1 truncate">
          {homeTeamName}
        </h2>

        {/* Score / Status */}
        <div className="flex flex-col items-center shrink-0 gap-0.5">
          {hasScore ? (
            <span className="font-mono tabular-nums font-bold text-2xl sm:text-3xl text-foreground">
              {homeScore}{" "}
              <span className="text-muted-foreground font-light">×</span>{" "}
              {awayScore}
            </span>
          ) : (
            <span className="font-mono text-muted-foreground text-lg">
              × 
            </span>
          )}
          <span
            className={cn(
              "text-[9px] font-medium uppercase tracking-widest mt-0.5 px-1.5 py-0.5 rounded",
              isLive &&
                "bg-danger/10 text-danger border border-danger/20 animate-pulse",
              isFinished && "text-muted-foreground",
              !isLive && !isFinished && "text-subtle",
            )}
          >
            {statusShort}
          </span>
        </div>

        {/* Away team */}
        <h2 className="font-display font-semibold text-base sm:text-xl flex-1 truncate">
          {awayTeamName}
        </h2>
      </div>

      {round && (
        <p className="text-center text-muted-foreground text-xs mt-2">
          {round}
        </p>
      )}
    </header>
  )
}

// ─── Lineup tab content ───────────────────────────────────────

interface LineupTabContentProps {
  readonly fixtureId: number
  readonly homeTeamId: number
  readonly awayTeamId: number
  readonly homeTeamName: string
  readonly awayTeamName: string
}

function LineupTabContent({
  fixtureId,
  homeTeamId,
  awayTeamId,
  homeTeamName,
  awayTeamName,
}: LineupTabContentProps) {
  const { data: lineups, isLoading: lineupsLoading } = useLineup(fixtureId)

  // Fetch injuries for both teams to overlay on the lineup grid
  const { data: homeInjuries } = useInjuries({ teamId: homeTeamId })
  const { data: awayInjuries } = useInjuries({ teamId: awayTeamId })

  // Flatten grouped injuries into a single InjuryWithPlayer[] array
  const allActiveInjuries: InjuryWithPlayer[] = [
    ...(homeInjuries?.flatMap((g) => g.injuries) ?? []),
    ...(awayInjuries?.flatMap((g) => g.injuries) ?? []),
  ]

  if (lineupsLoading) {
    return (
      <div className="flex flex-col gap-3">
        <SkeletonCard />
        <SkeletonCard />
      </div>
    )
  }

  if (!lineups?.home && !lineups?.away) {
    return (
      <div className="card-glass p-6 text-center">
        <p className="text-muted-foreground text-sm">
          Escalação ainda não disponível.
        </p>
        <p className="text-subtle text-xs mt-1">
          A publicação ocorre tipicamente 1h antes do jogo.
        </p>
      </div>
    )
  }

  const home = lineups?.home
  const away = lineups?.away

  // Determine confirmation status — true only if both lineups are confirmed
  const bothConfirmed =
    (home?.is_confirmed ?? false) && (away?.is_confirmed ?? false)
  const eitherConfirmed =
    (home?.is_confirmed ?? false) || (away?.is_confirmed ?? false)

  return (
    <motion.div
      variants={fadeIn}
      initial="initial"
      animate="animate"
      transition={fadeInTransition}
      className="flex flex-col gap-3"
    >
      {/* Status indicator */}
      <div className="flex items-center justify-between">
        <LineupStatusIndicator isConfirmed={bothConfirmed || eitherConfirmed} />
        {eitherConfirmed && !bothConfirmed && (
          <span className="text-[10px] text-muted-foreground">
            Parcialmente confirmada
          </span>
        )}
      </div>

      {/* Tactical pitch */}
      {home && away && (
        <LineupGrid
          home={home}
          away={away}
          homeTeamName={homeTeamName}
          awayTeamName={awayTeamName}
          activeInjuries={allActiveInjuries}
        />
      )}

      {/* Substitutes */}
      {(home ?? away) && (
        <div className="card-glass p-4">
          <h3 className="font-display font-semibold text-sm mb-3 text-muted-foreground uppercase tracking-wide">
            Suplentes
          </h3>
          <div className="grid grid-cols-2 gap-4">
            {home && home.substitutes.length > 0 && (
              <div>
                <p className="text-[10px] text-muted-foreground mb-2 uppercase tracking-wide">
                  {homeTeamName}
                </p>
                <div className="flex flex-col gap-1">
                  {home.substitutes.map((p) => (
                    <span
                      key={p.player_id}
                      className="text-xs text-foreground"
                    >
                      <span className="font-mono text-muted-foreground text-[10px] mr-1.5">
                        {p.number}
                      </span>
                      {p.name}
                    </span>
                  ))}
                </div>
              </div>
            )}
            {away && away.substitutes.length > 0 && (
              <div>
                <p className="text-[10px] text-muted-foreground mb-2 uppercase tracking-wide">
                  {awayTeamName}
                </p>
                <div className="flex flex-col gap-1">
                  {away.substitutes.map((p) => (
                    <span
                      key={p.player_id}
                      className="text-xs text-foreground"
                    >
                      <span className="font-mono text-muted-foreground text-[10px] mr-1.5">
                        {p.number}
                      </span>
                      {p.name}
                    </span>
                  ))}
                </div>
              </div>
            )}
          </div>
        </div>
      )}
    </motion.div>
  )
}

// ─── FixturePage ──────────────────────────────────────────────

const FixturePage = () => {
  const { id } = useParams<{ id: string }>()
  const fixtureId = id ? parseInt(id, 10) : undefined

  const { data: fixture, isLoading, isError } = useFixture(fixtureId)

  if (isLoading) {
    return (
      <div className="flex flex-col gap-4 animate-fade-in">
        <SkeletonCard />
        <SkeletonCard />
      </div>
    )
  }

  if (isError || !fixture) {
    return (
      <div className="card-glass p-6 text-center">
        <p className="text-danger text-sm">Jogo não encontrado.</p>
      </div>
    )
  }

  return (
    <AnimatePresence mode="wait">
      <motion.div
        key={fixture.id}
        variants={fadeIn}
        initial="initial"
        animate="animate"
        transition={{ duration: 0.3, ease: "easeOut" }}
        className="flex flex-col gap-4 animate-fade-in"
      >
        {/* ── Match header ── */}
        <FixtureHeader
          homeTeamName={fixture.home_team.name}
          awayTeamName={fixture.away_team.name}
          round={fixture.round}
          homeScore={fixture.home_score}
          awayScore={fixture.away_score}
          statusShort={fixture.status_short}
        />

        {/* ── Tabbed navigation ── */}
        <Tabs defaultValue="lineups" className="w-full">
          <TabsList className="w-full justify-start bg-surface border border-border rounded-lg p-1">
            <TabsTrigger
              value="lineups"
              id="tab-escalacoes"
              className="flex-1 sm:flex-none data-active:text-foreground"
            >
              Escalações
            </TabsTrigger>
            <TabsTrigger
              value="analysis"
              id="tab-analise"
              className="flex-1 sm:flex-none data-active:text-foreground"
            >
              ⚡ Flash Tático
            </TabsTrigger>
            <TabsTrigger
              value="info"
              id="tab-info"
              className="flex-1 sm:flex-none data-active:text-foreground"
            >
              Informações
            </TabsTrigger>
          </TabsList>

          {/* ── Escalações tab ── */}
          <TabsContent value="lineups" className="mt-4">
            <LineupTabContent
              fixtureId={fixture.id}
              homeTeamId={fixture.home_team.id}
              awayTeamId={fixture.away_team.id}
              homeTeamName={fixture.home_team.name}
              awayTeamName={fixture.away_team.name}
            />
          </TabsContent>

          {/* ── Flash Tático (Gemini AI) tab ── */}
          <TabsContent value="analysis" className="mt-4">
            <TacticalFlash fixtureId={fixture.id} />
          </TabsContent>

          {/* ── Informações tab ── */}
          <TabsContent value="info" className="mt-4">
            <div className="card-glass p-4 flex flex-col gap-3">
              <h3 className="font-display font-semibold text-sm text-muted-foreground uppercase tracking-wide">
                Detalhe do Jogo
              </h3>
              <dl className="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                {fixture.venue_name && (
                  <>
                    <dt className="text-muted-foreground">Estádio</dt>
                    <dd className="text-foreground">{fixture.venue_name}</dd>
                  </>
                )}
                {fixture.venue_city && (
                  <>
                    <dt className="text-muted-foreground">Cidade</dt>
                    <dd className="text-foreground">{fixture.venue_city}</dd>
                  </>
                )}
                {fixture.stage && (
                  <>
                    <dt className="text-muted-foreground">Fase</dt>
                    <dd className="text-foreground font-mono uppercase text-xs">
                      {fixture.stage}
                    </dd>
                  </>
                )}
                {fixture.group_name && (
                  <>
                    <dt className="text-muted-foreground">Grupo</dt>
                    <dd className="text-foreground">
                      Grupo {fixture.group_name}
                    </dd>
                  </>
                )}
                <dt className="text-muted-foreground">Escalação</dt>
                <dd>
                  <span
                    className={cn(
                      "text-xs",
                      fixture.lineup_confirmed
                        ? "text-[var(--gold)]"
                        : "text-muted-foreground",
                    )}
                  >
                    {fixture.lineup_confirmed ? "✓ Confirmada" : "Pendente"}
                  </span>
                </dd>
              </dl>
            </div>
          </TabsContent>
        </Tabs>
      </motion.div>
    </AnimatePresence>
  )
}

export default FixturePage
