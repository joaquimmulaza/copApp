// ─── HomePage — Jogos do dia + próximos fixtures ──────────────
// Integrates useFixtures with FixtureList, providing tab-based
// filtering (Hoje / Todos / Por fase) in the Quiet Luxury style.

import { useState } from "react";
import { motion } from "framer-motion";
import { CalendarDays, Layers } from "lucide-react";
import { format } from "date-fns";
import { ptBR } from "date-fns/locale";
import { useFixtures } from "@/hooks/useFixtures";
import { FixtureList } from "@/components/fixtures/FixtureList";
import { cn } from "@/lib/utils";
import { fadeIn, fadeInTransition } from "@/lib/animations";
import type { FixtureStage } from "@/types/fixture";

// ─── Filter tabs ──────────────────────────────────────────────
type FilterTab = "today" | "all" | "r32" | "r16" | "qf" | "sf" | "f";

const TABS: { id: FilterTab; label: string }[] = [
  { id: "today", label: "Hoje" },
  { id: "all", label: "Todos" },
  { id: "r32", label: "Oitavos" },
  { id: "r16", label: "Quartas" },
  { id: "qf", label: "Semis" },
  { id: "sf", label: "Final" },
];

// ─── Build query params from selected tab ────────────────────
function buildParams(tab: FilterTab) {
  if (tab === "today") {
    return { date: format(new Date(), "yyyy-MM-dd") };
  }
  if (tab === "all") {
    return undefined;
  }
  return { stage: tab as FixtureStage };
}

// ─── Page component ───────────────────────────────────────────
const HomePage = () => {
  const [activeTab, setActiveTab] = useState<FilterTab>("today");

  const params = buildParams(activeTab);
  const { data, isLoading, isError, error, refetch } = useFixtures(params);

  const today = format(new Date(), "d 'de' MMMM yyyy", { locale: ptBR });

  return (
    <div className="animate-fade-in py-6">
      {/* ── Page header ─────────────────────────────────────── */}
      <motion.header
        variants={fadeIn}
        initial="initial"
        animate="animate"
        transition={fadeInTransition}
        className="mb-8"
      >
        <div className="flex items-start justify-between gap-4">
          <div>
            <h1 className="font-display font-semibold text-2xl text-foreground leading-tight">
              Calendário
            </h1>
            <p className="font-sans text-sm text-muted-foreground mt-1 flex items-center gap-1.5">
              <CalendarDays className="size-3.5 shrink-0" aria-hidden="true" />
              {today}
            </p>
          </div>

          <div
            className="flex items-center gap-1 text-muted-foreground"
            aria-label="FIFA World Cup 2026"
          >
            <Layers className="size-4" aria-hidden="true" />
            <span className="font-mono text-xs tracking-widest uppercase">
              WC 2026
            </span>
          </div>
        </div>
      </motion.header>

      {/* ── Filter tabs ─────────────────────────────────────── */}
      <motion.nav
        variants={fadeIn}
        initial="initial"
        animate="animate"
        transition={{ ...fadeInTransition, delay: 0.05 }}
        className="mb-6"
        aria-label="Filtrar jogos"
      >
        <div
          className="flex gap-1 overflow-x-auto pb-1 scrollbar-none"
          role="tablist"
          aria-label="Fase do torneio"
        >
          {TABS.map((tab) => {
            const active = activeTab === tab.id;
            return (
              <button
                key={tab.id}
                id={`tab-${tab.id}`}
                role="tab"
                aria-selected={active}
                aria-controls="fixture-panel"
                onClick={() => setActiveTab(tab.id)}
                className={cn(
                  "shrink-0 px-3 py-1.5 rounded-md text-xs font-sans font-medium",
                  "transition-all duration-150 outline-none",
                  "focus-visible:ring-2 focus-visible:ring-gold focus-visible:ring-offset-1",
                  active
                    ? "bg-gold text-background"
                    : "text-muted-foreground hover:text-foreground hover:bg-surface-overlay",
                )}
              >
                {tab.label}
              </button>
            );
          })}
        </div>
      </motion.nav>

      {/* ── Fixture list ─────────────────────────────────────── */}
      <div
        id="fixture-panel"
        role="tabpanel"
        aria-labelledby={`tab-${activeTab}`}
      >
        <FixtureList
          fixtures={data ?? undefined}
          isLoading={isLoading}
          isError={isError}
          errorMessage={
            error instanceof Error
              ? error.message
              : "Erro ao carregar os jogos."
          }
          onRetry={() => {
            void refetch();
          }}
        />
      </div>
    </div>
  );
};

export default HomePage;
