// ─── FixtureCard — Compound Component ────────────────────────
// Implements CONTEXT.md §13 Compound Component pattern.
//
//   <FixtureCard fixture={f}>
//     <FixtureCard.Teams />
//     <FixtureCard.Score />
//     <FixtureCard.Status />
//   </FixtureCard>
//
// Styling: .card-glass bg · Bricolage Grotesque team names
//          JetBrains Mono scores · animate-score-update for live

import {
  createContext,
  useContext,
  useRef,
  useEffect,
  type ReactNode,
} from "react";
import { motion } from "framer-motion";
import { cn, isLive, isFinished, formatKickoff } from "@/lib/utils";
import { LiveBadge } from "@/components/common/LiveBadge";
import { TeamLogo } from "@/components/common/TeamLogo";
import { fadeIn, fadeInTransition } from "@/lib/animations";
import type { FixtureSummary } from "@/types/fixture";

// ─── Context ─────────────────────────────────────────────────
interface FixtureCardContextValue {
  readonly fixture: FixtureSummary;
  readonly live: boolean;
  readonly finished: boolean;
}

const FixtureCardContext = createContext<FixtureCardContextValue | null>(null);

function useFixtureCard(): FixtureCardContextValue {
  const ctx = useContext(FixtureCardContext);
  if (!ctx) {
    throw new Error(
      "FixtureCard sub-components must be used inside <FixtureCard>",
    );
  }
  return ctx;
}

// ─── Sub-component: Teams ─────────────────────────────────────
// Renders home and away flags + names in Bricolage Grotesque
function Teams() {
  const { fixture } = useFixtureCard();

  return (
    <div className="flex items-center justify-between gap-3">
      {/* Home team */}
      <div className="flex flex-1 items-center gap-2 min-w-0">
        <TeamLogo
          name={fixture.home_team.name}
          logoUrl={fixture.home_team.logo_url}
          size="md"
          className="shrink-0"
        />
        <span
          className={cn(
            "font-display font-semibold text-sm leading-tight truncate",
            "text-foreground",
          )}
          title={fixture.home_team.name}
        >
          {fixture.home_team.code ?? fixture.home_team.name}
        </span>
      </div>

      {/* Spacer replaced by Score sub-component — Teams only renders names */}
      <div className="shrink-0 w-24" aria-hidden="true" />

      {/* Away team — reversed layout */}
      <div className="flex flex-1 items-center gap-2 justify-end min-w-0">
        <span
          className={cn(
            "font-display font-semibold text-sm leading-tight truncate text-right",
            "text-foreground",
          )}
          title={fixture.away_team.name}
        >
          {fixture.away_team.code ?? fixture.away_team.name}
        </span>
        <TeamLogo
          name={fixture.away_team.name}
          logoUrl={fixture.away_team.logo_url}
          size="md"
          className="shrink-0"
        />
      </div>
    </div>
  );
}

// ─── Sub-component: Score ─────────────────────────────────────
// Renders the score / kickoff time centred between the team rows.
// Applies CSS animate-score-update class when a live score changes.
function Score() {
  const { fixture, live, finished } = useFixtureCard();

  const hasScore = fixture.home_score !== null && fixture.away_score !== null;

  // Detect score changes and apply CSS animation imperatively
  const scoreRef = useRef<HTMLDivElement>(null);
  const prevHomeRef = useRef<number | null>(fixture.home_score);
  const prevAwayRef = useRef<number | null>(fixture.away_score);

  useEffect(() => {
    if (!live || !scoreRef.current) return;

    const changed =
      prevHomeRef.current !== fixture.home_score ||
      prevAwayRef.current !== fixture.away_score;

    if (changed) {
      const el = scoreRef.current;
      el.classList.remove("animate-score-update");
      // Trigger reflow so CSS re-applies the animation from frame 0
      void el.offsetWidth;
      el.classList.add("animate-score-update");
    }

    prevHomeRef.current = fixture.home_score;
    prevAwayRef.current = fixture.away_score;
  });

  return (
    <div
      ref={scoreRef}
      className="absolute inset-0 flex items-center justify-center pointer-events-none"
      aria-label={
        hasScore
          ? `Resultado: ${fixture.home_score} a ${fixture.away_score}`
          : `Início às ${formatKickoff(fixture.kickoff_utc)}`
      }
    >
      {hasScore ? (
        <span
          className={cn(
            "font-mono tabular-nums font-bold leading-none select-none",
            live ? "text-2xl text-foreground" : "text-xl text-muted-foreground",
            finished && "text-foreground",
          )}
        >
          {fixture.home_score}
          <span className="mx-1 opacity-50">–</span>
          {fixture.away_score}
        </span>
      ) : (
        <span className="font-mono tabular-nums text-base font-medium text-muted-foreground select-none">
          {formatKickoff(fixture.kickoff_utc)}
        </span>
      )}
    </div>
  );
}

// ─── Sub-component: Status ────────────────────────────────────
// Renders LiveBadge, "FT", or the round/group label
function Status() {
  const { fixture, live, finished } = useFixtureCard();

  return (
    <div
      className="flex items-center justify-center gap-2 mt-3"
      role="status"
      aria-label={`Estado: ${fixture.status_short}`}
    >
      {live ? (
        <LiveBadge elapsed={fixture.elapsed_minutes} />
      ) : finished ? (
        <span className="text-xs font-mono font-semibold text-muted-foreground uppercase tracking-widest">
          {fixture.status_short === "AET"
            ? "AET"
            : fixture.status_short === "PEN"
              ? "PEN"
              : "FT"}
        </span>
      ) : (
        <span className="text-xs font-sans text-muted-foreground truncate max-w-48 text-center">
          {fixture.round ?? fixture.group_name ?? "Fase de Grupos"}
        </span>
      )}

      {fixture.lineup_confirmed && !live && (
        <span
          className="text-xs font-mono text-success ml-1"
          aria-label="Escalação confirmada"
          title="Escalação confirmada"
        >
          ✓
        </span>
      )}
    </div>
  );
}

// ─── Root Component ───────────────────────────────────────────
interface FixtureCardProps {
  readonly fixture: FixtureSummary;
  readonly className?: string;
  readonly children: ReactNode;
}

function FixtureCardRoot({ fixture, className, children }: FixtureCardProps) {
  const live = isLive(fixture.status_short);
  const finished = isFinished(fixture.status_short);

  return (
    <FixtureCardContext.Provider value={{ fixture, live, finished }}>
      <motion.article
        variants={fadeIn}
        initial="initial"
        animate="animate"
        exit="exit"
        transition={fadeInTransition}
        className={cn(
          "card-glass relative p-4 gold-hover cursor-default",
          "transition-colors duration-150",
          live &&
            "border border-danger/20 shadow-[0_0_16px_rgba(239,68,68,0.06)]",
          className,
        )}
        aria-label={`${fixture.home_team.name} vs ${fixture.away_team.name}`}
      >
        {children}
      </motion.article>
    </FixtureCardContext.Provider>
  );
}

// ─── Attach sub-components ────────────────────────────────────
export const FixtureCard = Object.assign(FixtureCardRoot, {
  Teams,
  Score,
  Status,
});
