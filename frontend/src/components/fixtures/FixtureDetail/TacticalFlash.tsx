// TacticalFlash — Gemini AI analysis block for a specific fixture
// Shows chip selector and AI-generated tactical insight
import { ContextChips } from "@/components/gemini/ContextChips";
import { TypingIndicator } from "@/components/gemini/TypingIndicator";
import { TacticalFlash as VisualTacticalFlash } from "@/components/gemini/TacticalFlash";
import { useGeminiChat } from "@/hooks/useGeminiChat";
import { useEffect } from "react";
import { motion, AnimatePresence } from "framer-motion";

interface TacticalFlashProps {
  readonly fixtureId: number;
}

export const TacticalFlash = ({ fixtureId }: TacticalFlashProps) => {
  const { activeChip, selectChip, analysis, isLoading, error } = useGeminiChat(fixtureId);

  // Set default chip to tactical_flash if none is selected
  useEffect(() => {
    if (!activeChip) {
      selectChip("tactical_flash");
    }
  }, [activeChip, selectChip]);

  return (
    <section
      aria-label="Análise tática por IA"
      className="flex flex-col gap-4 animate-fade-in"
    >
      <div className="card-glass p-4 flex flex-col gap-4">
        <h2 className="font-display font-semibold text-sm uppercase tracking-wide text-muted-foreground">
          ⚡ Análise por IA
        </h2>

        <ContextChips
          activeChip={activeChip}
          onSelect={selectChip}
          disabled={isLoading}
        />
      </div>

      <div className="relative min-h-[140px]">
        <AnimatePresence mode="wait">
          {isLoading ? (
            <motion.div
              key="loading"
              initial={{ opacity: 0, y: 10 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -10 }}
              transition={{ duration: 0.25 }}
              className="flex justify-center items-center py-12"
            >
              <TypingIndicator />
            </motion.div>
          ) : error ? (
            <motion.div
              key="error"
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              className="card-glass p-6 text-center border border-danger/20"
            >
              <p className="text-danger text-sm">{error}</p>
            </motion.div>
          ) : analysis ? (
            <motion.div
              key="analysis"
              initial={{ opacity: 0, y: 15 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -10 }}
              transition={{ duration: 0.3, ease: "easeOut" }}
            >
              <VisualTacticalFlash analysis={analysis} />
            </motion.div>
          ) : (
            <motion.div
              key="empty"
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              className="card-glass p-8 text-center text-muted-foreground text-sm"
            >
              Clica em um dos chips acima para iniciar a análise por IA.
            </motion.div>
          )}
        </AnimatePresence>
      </div>
    </section>
  );
};
