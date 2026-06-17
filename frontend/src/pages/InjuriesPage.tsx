import { motion } from "framer-motion";
import { ShieldCheck, RefreshCw } from "lucide-react";
import { useInjuries } from "@/hooks/useInjuries";
import { InjuryPanel } from "@/components/injuries/InjuryPanel";
import { Skeleton } from "@/components/ui/skeleton";
import { Separator } from "@/components/ui/separator";
import { Button } from "@/components/ui/button";

// ─── Loading State Skeletons ──────────────────────────────────
function InjuryPanelSkeleton() {
  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-1">
      {Array.from({ length: 6 }).map((_, i) => (
        <div
          key={i}
          className="card-glass flex flex-col h-[400px] p-5 border border-border/40"
        >
          {/* Header */}
          <div className="flex items-center gap-3.5 mb-4">
            <Skeleton className="size-10 rounded-full shrink-0" />
            <div className="flex flex-col gap-2 w-full">
              <Skeleton className="h-5 w-2/3 rounded-sm" />
              <Skeleton className="h-3 w-1/4 rounded-sm" />
            </div>
          </div>

          <Separator className="bg-border/40 mb-3" />

          {/* List Skeletons */}
          <div className="flex flex-col gap-4 mt-2">
            {Array.from({ length: 4 }).map((_, j) => (
              <div
                key={j}
                className="flex items-center justify-between gap-3 p-1"
              >
                <div className="flex items-center gap-3 w-full">
                  <Skeleton className="size-8 rounded-full shrink-0" />
                  <div className="flex flex-col gap-1.5 w-full">
                    <Skeleton className="h-3.5 w-1/2 rounded-sm" />
                    <Skeleton className="h-2.5 w-1/3 rounded-sm" />
                  </div>
                </div>
                <Skeleton className="h-5 w-16 rounded-full shrink-0" />
              </div>
            ))}
          </div>
        </div>
      ))}
    </div>
  );
}

// ─── Main Page Component ──────────────────────────────────────
export default function InjuriesPage() {
  const { data, isLoading, error, refetch, isRefetching } = useInjuries();

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
            Boletim Médico & Disciplinar
          </span>
          <h1 className="font-display font-semibold text-3xl text-foreground tracking-tight">
            Desfalques Ativos
          </h1>
          <p className="text-sm text-muted-foreground max-w-xl">
            Acompanhe lesões físicas, reabilitações e suspensões acumuladas por
            cartão em tempo real para cada uma das seleções do Mundial de 2026.
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
        <InjuryPanelSkeleton />
      ) : error ? (
        <div className="card-glass p-8 py-12 flex flex-col items-center justify-center text-center gap-4 max-w-md mx-auto mt-8 border border-danger/20">
          <div className="size-12 rounded-full bg-danger/10 border border-danger/20 flex items-center justify-center text-danger">
            <span className="text-xl font-semibold font-mono">!</span>
          </div>
          <div className="flex flex-col gap-1">
            <h3 className="font-display font-semibold text-lg text-foreground">
              Erro ao carregar desfalques
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
      ) : !data || data.length === 0 ? (
        /* Premium Empty State */
        <motion.div
          initial={{ opacity: 0, scale: 0.98 }}
          animate={{ opacity: 1, scale: 1 }}
          transition={{ duration: 0.5 }}
          className="card-glass p-12 py-16 flex flex-col items-center justify-center text-center gap-5 max-w-xl mx-auto mt-12 border border-border/40 shadow-xl relative overflow-hidden"
        >
          {/* Subtle gold accent glow behind the card */}
          <div className="absolute -top-24 left-1/2 -translate-x-1/2 size-48 rounded-full bg-gold/5 blur-[80px]" />

          <div className="size-14 rounded-full bg-gold/5 border border-gold/20 flex items-center justify-center text-gold shadow-[0_0_15px_var(--gold-glow)]">
            <ShieldCheck className="size-7" />
          </div>

          <div className="flex flex-col gap-2 relative z-10">
            <h3 className="font-display font-semibold text-xl text-foreground">
              Elencos em Força Máxima
            </h3>
            <p className="text-sm text-muted-foreground max-w-sm mx-auto leading-relaxed">
              Excelente notícia para a competição. No momento, nenhuma seleção
              possui jogadores sob desfalque ativo de lesão ou suspensão
              disciplinar.
            </p>
          </div>
        </motion.div>
      ) : (
        <InjuryPanel data={data} />
      )}
    </motion.div>
  );
}
