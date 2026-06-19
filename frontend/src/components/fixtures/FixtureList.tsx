// ─── FixtureList — Date-grouped fixture list ─────────────────
// Groups fixtures by calendar day, renders FixtureCard for each,
// and shows shimmer SkeletonCard while data loads (no blink).
//
// Loading: SkeletonCard (shimmer only)
// Error:   Inline error state with retry
// Empty:   Contextual empty state

import { AnimatePresence, motion } from "framer-motion";
import { CalendarX, AlertTriangle } from "lucide-react";
import { cn, getDateKey, formatDateLabel } from "@/lib/utils";
import { groupBy } from "@/lib/utils";
import { FixtureCard } from "./FixtureCard";
import { SkeletonCard } from "@/components/common/SkeletonCard";
import { stagger, fadeIn, fadeInTransition } from "@/lib/animations";
import type { FixtureSummary } from "@/types/fixture";

// ─── Date group header ────────────────────────────────────────
interface DateGroupHeaderProps {
  readonly firstKickoff: string;
}

function DateGroupHeader({ firstKickoff }: DateGroupHeaderProps) {
  const label = formatDateLabel(firstKickoff);
  const isToday = label === "Hoje";

  return (
    <div className="flex items-center gap-3 mb-3">
      <h2
        className={cn(
          "font-display font-semibold text-sm uppercase tracking-widest leading-none",
          isToday ? "text-gold" : "text-muted-foreground",
        )}
      >
        {label}
      </h2>
      <div className="flex-1 h-px bg-border" aria-hidden="true" />
    </div>
  );
}

// ─── Empty state ──────────────────────────────────────────────
function EmptyState() {
  return (
    <motion.div
      {...fadeIn}
      transition={fadeInTransition}
      className="flex flex-col items-center justify-center py-16 text-center"
      role="status"
      aria-label="Sem jogos para exibir"
    >
      <CalendarX className="size-10 mb-4 text-muted-foreground/50" aria-hidden="true" strokeWidth={1.5} />
      <p className="font-display font-semibold text-foreground text-lg mb-1">
        Sem jogos neste período
      </p>
      <p className="font-sans text-sm text-muted-foreground max-w-xs">
        Selecione outro dia ou aguarde a atualização do calendário.
      </p>
    </motion.div>
  );
}

// ─── Error state ──────────────────────────────────────────────
interface ErrorStateProps {
  readonly message: string;
  readonly onRetry?: () => void;
}

function ErrorState({ message, onRetry }: ErrorStateProps) {
  return (
    <motion.div
      {...fadeIn}
      transition={fadeInTransition}
      className="flex flex-col items-center justify-center py-12 text-center gap-3"
      role="alert"
    >
      <AlertTriangle className="size-10 text-muted-foreground/50" aria-hidden="true" strokeWidth={1.5} />
      <p className="font-sans text-sm text-muted-foreground max-w-sm">
        {message}
      </p>
      {onRetry && (
        <button
          onClick={onRetry}
          className={cn(
            "text-xs font-sans font-medium px-3 py-1.5 rounded-md",
            "border border-border text-muted-foreground",
            "hover:border-gold hover:text-foreground transition-colors duration-150",
          )}
          aria-label="Tentar novamente"
        >
          Tentar novamente
        </button>
      )}
    </motion.div>
  );
}

// ─── Main component ───────────────────────────────────────────
interface FixtureListProps {
  readonly fixtures?: FixtureSummary[] | undefined;
  readonly isLoading?: boolean;
  readonly isError?: boolean;
  readonly errorMessage?: string;
  readonly onRetry?: (() => void) | undefined;
  readonly className?: string;
}

export const FixtureList = ({
  fixtures,
  isLoading = false,
  isError = false,
  errorMessage = "Erro ao carregar os jogos. Verifique a sua ligação.",
  onRetry,
  className,
}: FixtureListProps) => {
  // ── Loading ──────────────────────────────────────────────────
  if (isLoading) {
    return (
      <div className={cn("flex flex-col gap-8", className)}>
        {/* Render 3 date groups of 2 skeletons each */}
        {[0, 1, 2].map((groupIdx) => (
          <section key={groupIdx} aria-hidden="true">
            {/* Fake date header */}
            <div className="flex items-center gap-3 mb-3">
              <div className="skeleton h-3 w-24 rounded" />
              <div className="flex-1 h-px bg-surface-elevated" />
            </div>
            {/* Two card skeletons per group */}
            <SkeletonCard count={2} />
          </section>
        ))}
      </div>
    );
  }

  // ── Error ────────────────────────────────────────────────────
  if (isError) {
    return (
      <ErrorState
        message={errorMessage ?? "Erro ao carregar os jogos."}
        {...(onRetry !== undefined ? { onRetry } : {})}
      />
    );
  }

  // ── Empty ────────────────────────────────────────────────────
  if (!fixtures || fixtures.length === 0) {
    return <EmptyState />;
  }

  // ── Group by date ────────────────────────────────────────────
  const grouped = groupBy(fixtures, (f) => getDateKey(f.kickoff_utc));
  // Map preserves insertion order; sort keys ascending
  const sortedKeys = [...grouped.keys()].sort();

  return (
    <motion.div
      variants={stagger}
      initial="initial"
      animate="animate"
      className={cn("flex flex-col gap-8", className)}
    >
      <AnimatePresence mode="popLayout">
        {sortedKeys.map((dateKey) => {
          const group = grouped.get(dateKey)!;
          const firstKickoff = group[0].kickoff_utc;

          return (
            <motion.section
              key={dateKey}
              variants={fadeIn}
              transition={fadeInTransition}
              aria-label={formatDateLabel(firstKickoff)}
            >
              <DateGroupHeader firstKickoff={firstKickoff} />

              <div className="flex flex-col gap-3">
                {group.map((fixture) => (
                  <FixtureCard key={fixture.id} fixture={fixture}>
                    {/* Compound component composition per CONTEXT.md §13 */}
                    <div className="relative">
                      <FixtureCard.Teams />
                      <FixtureCard.Score />
                    </div>
                    <FixtureCard.Status />
                  </FixtureCard>
                ))}
              </div>
            </motion.section>
          );
        })}
      </AnimatePresence>
    </motion.div>
  );
};
