// TacticalFlash — Gemini AI analysis block for a specific fixture
// Shows chip selector and AI-generated tactical insight
import { ContextChips } from "@/components/gemini/ContextChips";
import { TypingIndicator } from "@/components/gemini/TypingIndicator";
import { useGeminiChat } from "@/hooks/useGeminiChat";
import { useState } from "react";
import type { GeminiChipId } from "@/lib/constants";

interface TacticalFlashProps {
  readonly fixtureId: number;
}

export const TacticalFlash = ({ fixtureId }: TacticalFlashProps) => {
  const [activeChip, setActiveChip] = useState<GeminiChipId | null>(null);
  const { sendMessage, isLoading, messages } = useGeminiChat(fixtureId);

  const handleChipSelect = (chipId: GeminiChipId) => {
    setActiveChip(chipId);
    void sendMessage(`Analisa o confronto com foco em: ${chipId}`, chipId);
  };

  const lastAssistantMessage = messages
    .filter((m) => m.role === "assistant")
    .slice(-1)[0];

  return (
    <section
      aria-label="Análise tática por IA"
      className="card-glass p-4 flex flex-col gap-4"
    >
      <h2 className="font-display font-semibold text-base">⚡ Flash Tático</h2>

      <ContextChips
        activeChip={activeChip}
        onSelect={handleChipSelect}
        disabled={isLoading}
      />

      {isLoading && <TypingIndicator />}

      {lastAssistantMessage && !isLoading && (
        <p className="text-sm text-foreground leading-relaxed">
          {lastAssistantMessage.content}
        </p>
      )}
    </section>
  );
};
