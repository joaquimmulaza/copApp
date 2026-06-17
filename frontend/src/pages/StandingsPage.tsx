import { motion } from "framer-motion";
import { RefreshCw, Trophy } from "lucide-react";
import { useStandings } from "@/hooks/useStandings";
import { GroupCard } from "@/components/standings/GroupCard";
import { Skeleton } from "@/components/ui/skeleton";
import { Separator } from "@/components/ui/separator";
import { Button } from "@/components/ui/button";
import { fadeIn, stagger, fadeInTransition } from "@/lib/animations";

// ─── Loading State Skeletons ──────────────────────────────────
function StandingsSkeleton() {
  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 p-1">
      {Array.from({ length: 12 }).map((_, i) => (
        <div
          key={i}
          className="card-glass p-5 border border-border/40 animate-pulse flex flex-col h-[280px]"
        >
          {/* Header */}
          <div className="flex items-center justify-between mb-4">
            <Skeleton className="h-5 w-20 rounded-sm" />
            <Skeleton className="h-3 w-16 rounded-sm" />
          </div>

          <Separator className="bg-border/20 mb-3" />

          {/* Table Header skeleton */}
          <div className="flex justify-between items-center pb-2 border-b border-border/10">
            <Skeleton className="h-3 w-4 rounded-sm" />
            <Skeleton className="h-3 w-20 rounded-sm" />
            <Skeleton className="h-3 w-4 rounded-sm" />
            <Skeleton className="h-3 w-6 rounded-sm" />
            <Skeleton className="h-3 w-6 rounded-sm" />
          </div>

          {/* List Skeletons */}
          <div className="flex flex-col gap-3 mt-2">
            {Array.from({ length: 4 }).map((_, j) => (
              <div
                key={j}
                className="flex items-center justify-between gap-3 py-1"
              >
                <Skeleton className="h-4 w-4 rounded-sm" />
                <div className="flex items-center gap-2 flex-1">
                  <Skeleton className="h-5 w-5 rounded-full" />
                  <Skeleton className="h-3.5 w-16 rounded-sm" />
                </div>
                <Skeleton className="h-3.5 w-4 rounded-sm" />
                <Skeleton className="h-3.5 w-6 rounded-sm" />
                <Skeleton className="h-3.5 w-6 rounded-sm font-semibold" />
              </div>
            ))}
          </div>
        </div>
      ))}
    </div>
  );
}

// ─── Main Page Component ──────────────────────────────────────
export default function StandingsPage() {
  const { data, isLoading, error, refetch, isRefetching } = useStandings();

  // Convert the Map (object) keys to array and sort them
  const groupKeys = data ? Object.keys(data).sort() : [];

  return (
    <motion.div
      initial={{ opacity: 0 }}
      animate={{ opacity: 1 }}
      transition={{ duration: 0.4 }}
      className="flex flex-col gap-6"
    >
      {/* Header section with page title and description */}
      <div className="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div className="flex flex-col gap-1.5">
          <span className="text-[10px] font-mono text-gold tracking-widest uppercase">
            Tabelas de Classificação
          </span>
          <h1 className="font-display font-semibold text-3xl text-foreground tracking-tight">
            Fase de Grupos
          </h1>
          <p className="text-sm text-muted-foreground max-w-xl">
            Consulte o estado de classificação em tempo real dos 12 grupos do Mundial de 2026.
            Os dois primeiros colocados avançam diretamente para a fase final.
          </p>
        </div>

        {/* Refresh button */}
        {!isLoading && !error && (
          <Button
            variant="outline"
            size="sm"
            onClick={() => refetch()}
            disabled={isRefetching}
            className="w-full md:w-auto h-9 gold-hover gap-2 text-xs"
          >
            <RefreshCw
              className={`size-3.5 ${isRefetching ? "animate-spin" : ""}`}
            />
            {isRefetching ? "Atualizando..." : "Atualizar"}
          </Button>
        )}
      </div>

      <Separator className="bg-border/60" />

      {/* Main Content Area */}
      {isLoading ? (
        <StandingsSkeleton />
      ) : error ? (
        <div className="card-glass p-8 py-12 flex flex-col items-center justify-center text-center gap-4 max-w-md mx-auto mt-8 border border-danger/20">
          <div className="size-12 rounded-full bg-danger/10 border border-danger/20 flex items-center justify-center text-danger">
            <span className="text-xl font-semibold font-mono">!</span>
          </div>
          <div className="flex flex-col gap-1">
            <h3 className="font-display font-semibold text-lg text-foreground">
              Erro ao carregar classificações
            </h3>
            <p className="text-xs text-muted-foreground">
              Não foi possível estabelecer ligação com o servidor. Por favor,
              tente novamente.
            </p>
          </div>
          <Button
            onClick={() => refetch()}
            variant="outline"
            className="mt-2 h-9 text-xs gold-hover"
          >
            <RefreshCw className="size-3.5 mr-2" />
            Tentar Novamente
          </Button>
        </div>
      ) : !data || groupKeys.length === 0 ? (
        /* Empty State */
        <motion.div
          initial={{ opacity: 0, scale: 0.98 }}
          animate={{ opacity: 1, scale: 1 }}
          transition={{ duration: 0.5 }}
          className="card-glass p-12 py-16 flex flex-col items-center justify-center text-center gap-5 max-w-xl mx-auto mt-12 border border-border/40 shadow-xl relative overflow-hidden"
        >
          <div className="absolute -top-24 left-1/2 -translate-x-1/2 size-48 rounded-full bg-gold/5 blur-[80px]" />

          <div className="size-14 rounded-full bg-gold/5 border border-gold/20 flex items-center justify-center text-gold shadow-[0_0_15px_var(--gold-glow)]">
            <Trophy className="size-7" />
          </div>

          <div className="flex flex-col gap-2 relative z-10">
            <h3 className="font-display font-semibold text-xl text-foreground">
              Tabelas Indisponíveis
            </h3>
            <p className="text-sm text-muted-foreground max-w-sm mx-auto leading-relaxed">
              Os dados de classificação dos grupos ainda não foram inicializados ou sincronizados.
              Por favor, execute a sincronização ou aguarde o início do campeonato.
            </p>
          </div>
        </motion.div>
      ) : (
        /* Animated Grid of Groups */
        <motion.div
          variants={stagger}
          initial="initial"
          animate="animate"
          className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 p-0.5"
        >
          {groupKeys.map((groupName) => (
            <motion.div
              key={groupName}
              variants={fadeIn}
              transition={fadeInTransition}
              className="flex flex-col"
            >
              <GroupCard
                groupName={groupName}
                standings={data[groupName]}
              />
            </motion.div>
          ))}
        </motion.div>
      )}
    </motion.div>
  );
}
