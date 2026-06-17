import { useState, useEffect } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { Sparkles, Calendar, ChevronDown } from "lucide-react";
import { useFixtures } from "@/hooks/useFixtures";
import { useGeminiChat } from "@/hooks/useGeminiChat";
import { ContextChips } from "@/components/gemini/ContextChips";
import { TacticalFlash } from "@/components/gemini/TacticalFlash";
import { ChatWindow } from "@/components/gemini/ChatWindow";
import { TypingIndicator } from "@/components/gemini/TypingIndicator";
import { SkeletonCard } from "@/components/common/SkeletonCard";

const AiPage = () => {
  const { data: fixtures, isLoading: isLoadingFixtures, isError: isErrorFixtures } = useFixtures();
  const [selectedFixtureId, setSelectedFixtureId] = useState<number | null>(null);

  // Set default selected fixture to the first available match
  useEffect(() => {
    if (fixtures && fixtures.length > 0 && selectedFixtureId === null) {
      setSelectedFixtureId(fixtures[0].id);
    }
  }, [fixtures, selectedFixtureId]);

  const selectedFixture = fixtures?.find((f) => f.id === selectedFixtureId);

  // Gemini state for active fixture
  const {
    activeChip,
    selectChip,
    analysis,
    isLoading: isLoadingAnalysis,
    error: analysisError,
  } = useGeminiChat(selectedFixtureId ?? 0);

  // Automatically trigger first analysis when match changes
  useEffect(() => {
    if (selectedFixtureId) {
      selectChip("tactical_flash");
    }
  }, [selectedFixtureId, selectChip]);

  if (isLoadingFixtures) {
    return (
      <div className="flex flex-col gap-6 animate-fade-in">
        <div className="h-10 w-48 bg-surface-elevated animate-pulse rounded-lg" />
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
          <div className="lg:col-span-8 flex flex-col gap-4">
            <SkeletonCard />
            <SkeletonCard />
          </div>
          <div className="lg:col-span-4">
            <SkeletonCard />
          </div>
        </div>
      </div>
    );
  }

  if (isErrorFixtures || !fixtures || fixtures.length === 0) {
    return (
      <div className="text-center">
        <div className="card-glass p-8 max-w-md mx-auto border border-danger/20">
          <p className="text-danger font-medium">Não foi possível carregar os jogos do torneio.</p>
          <p className="text-muted-foreground text-xs mt-1">Verifica a ligação ao servidor.</p>
        </div>
      </div>
    );
  }

  return (
    <div className="animate-fade-in flex flex-col gap-6">
      {/* Header Area */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-border/40 pb-4">
        <div>
          <h1 className="font-display font-semibold text-2xl text-foreground flex items-center gap-2">
            <Sparkles className="size-5 text-gold" />
            Análise por IA
          </h1>
          <p className="text-xs text-muted-foreground mt-0.5">
            Insights e projeções táticas detalhadas com inteligência artificial.
          </p>
        </div>

        {/* Custom Styled Select Dropdown */}
        <div className="relative inline-flex items-center">
          <Calendar className="absolute left-3 size-4 text-muted-foreground pointer-events-none" />
          <select
            value={selectedFixtureId ?? ""}
            onChange={(e) => {
              const val = e.target.value;
              setSelectedFixtureId(val ? parseInt(val, 10) : null);
            }}
            className="pl-9 pr-10 py-2 bg-surface border border-border hover:border-border-strong text-foreground text-xs rounded-xl focus:border-gold focus:ring-2 focus:ring-gold/15 focus:outline-none transition-all cursor-pointer appearance-none min-w-[220px] font-sans"
            aria-label="Selecionar confronto"
          >
            {fixtures.map((f) => (
              <option key={f.id} value={f.id} className="bg-background">
                {f.home_team.name} vs {f.away_team.name} ({f.round})
              </option>
            ))}
          </select>
          <ChevronDown className="absolute right-3 size-4 text-muted-foreground pointer-events-none" />
        </div>
      </div>

      {selectedFixture ? (
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
          {/* Left Column: Chip Selection + Analysis Text */}
          <div className="lg:col-span-7 xl:col-span-8 flex flex-col gap-4">
            <div className="card-glass p-4 sm:p-5 flex flex-col gap-4 bg-gradient-to-br from-surface to-background/20">
              <div className="flex items-center justify-between">
                <span className="text-xs font-mono uppercase tracking-wider text-muted-foreground">
                  Filtros de Análise
                </span>
                <span className="text-[10px] px-2 py-0.5 rounded-full border border-gold/20 bg-gold-glow text-gold font-medium">
                  {selectedFixture.status_short}
                </span>
              </div>

              <ContextChips
                activeChip={activeChip}
                onSelect={selectChip}
                disabled={isLoadingAnalysis}
              />
            </div>

            <div className="relative min-h-[200px]">
              <AnimatePresence mode="wait">
                {isLoadingAnalysis ? (
                  <motion.div
                    key="loading"
                    initial={{ opacity: 0, y: 10 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={{ opacity: 0, y: -10 }}
                    transition={{ duration: 0.25 }}
                    className="flex justify-center items-center py-20"
                  >
                    <TypingIndicator />
                  </motion.div>
                ) : analysisError ? (
                  <motion.div
                    key="error"
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    className="card-glass p-8 text-center border border-danger/20"
                  >
                    <p className="text-danger text-sm font-medium">{analysisError}</p>
                  </motion.div>
                ) : analysis ? (
                  <motion.div
                    key="analysis"
                    initial={{ opacity: 0, y: 15 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={{ opacity: 0, y: -10 }}
                    transition={{ duration: 0.35, ease: "easeOut" }}
                  >
                    <TacticalFlash analysis={analysis} />
                  </motion.div>
                ) : (
                  <motion.div
                    key="empty"
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    className="card-glass p-10 text-center text-muted-foreground text-sm"
                  >
                    Clica em um dos chips acima para iniciar a análise por IA.
                  </motion.div>
                )}
              </AnimatePresence>
            </div>
          </div>

          {/* Right Column: Chat Window */}
          <div className="lg:col-span-5 xl:col-span-4">
            <ChatWindow fixtureId={selectedFixture.id} />
          </div>
        </div>
      ) : (
        <div className="card-glass p-8 text-center text-muted-foreground text-sm">
          Nenhum jogo selecionado.
        </div>
      )}
    </div>
  );
};

export default AiPage;
