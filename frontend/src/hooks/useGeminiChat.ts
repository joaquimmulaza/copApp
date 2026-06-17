import { useState, useCallback } from "react";
import { useQuery } from "@tanstack/react-query";
import { api } from "@/lib/axios";
import { QUERY_KEYS } from "@/lib/constants";
import type { GeminiChipId } from "@/lib/constants";
import type { ChatMessage } from "@/types/gemini";

export interface GeminiAnalysisResponse {
  readonly fixture_id: number;
  readonly chip_type: GeminiChipId;
  readonly analysis: string;
}

export const useGeminiChat = (
  fixtureId: number,
  initialChip: GeminiChipId | null = null
) => {
  const [activeChip, setActiveChip] = useState<GeminiChipId | null>(initialChip);
  const [messages, setMessages] = useState<ChatMessage[]>([]);

  // Fetch chip analysis using useQuery, which is automatically cached
  const {
    data: analysisData,
    isLoading: isAnalysisLoading,
    error: analysisError,
  } = useQuery({
    queryKey: QUERY_KEYS.geminiCache(fixtureId, activeChip as GeminiChipId),
    queryFn: async (): Promise<GeminiAnalysisResponse> => {
      const { data } = await api.get<GeminiAnalysisResponse>(
        `/fixtures/${fixtureId}/analysis`,
        {
          params: { chip: activeChip },
        }
      );
      return data;
    },
    enabled: !!fixtureId && !!activeChip,
    staleTime: 10 * 60 * 1000, // 10 minutes cache TTL matches backend
  });

  // A helper function to trigger chip analysis
  const selectChip = useCallback((chipId: GeminiChipId) => {
    setActiveChip(chipId);
  }, []);

  // Send message for free chat or trigger chip
  const sendMessage = useCallback(
    async (content: string, chipId?: GeminiChipId) => {
      const actualChip = chipId ?? null;

      if (actualChip) {
        setActiveChip(actualChip);
        return;
      }

      if (!content.trim()) return;

      const userMsg: ChatMessage = {
        id: crypto.randomUUID(),
        role: "user",
        content: content.trim(),
        timestamp: Date.now(),
      };

      setMessages((prev) => [...prev, userMsg]);

      // Detect relevant chip based on message keywords
      const text = content.toLowerCase();
      let matchedChip: GeminiChipId = "tactical_flash";

      if (
        text.includes("desfalque") ||
        text.includes("lesao") ||
        text.includes("lesão") ||
        text.includes("ausen") ||
        text.includes("machuca") ||
        text.includes("impacto") ||
        text.includes("dm")
      ) {
        matchedChip = "injury_impact";
      } else if (
        text.includes("palpite") ||
        text.includes("ganha") ||
        text.includes("vence") ||
        text.includes("aposta") ||
        text.includes("odds") ||
        text.includes("placar")
      ) {
        matchedChip = "guided_bet";
      } else if (
        text.includes("forma") ||
        text.includes("recente") ||
        text.includes("ultimos") ||
        text.includes("últimos") ||
        text.includes("historico") ||
        text.includes("histórico")
      ) {
        matchedChip = "recent_form";
      } else if (
        text.includes("confronto") ||
        text.includes("raio-x") ||
        text.includes("raio x") ||
        text.includes("versus") ||
        text.includes("vs") ||
        text.includes("h2h")
      ) {
        matchedChip = "head2head";
      }

      // Append typing indicator
      setMessages((prev) => [
        ...prev,
        {
          id: "typing-indicator",
          role: "assistant",
          content: "",
          timestamp: Date.now(),
          isStreaming: true,
        },
      ]);

      try {
        const { data } = await api.get<GeminiAnalysisResponse>(
          `/fixtures/${fixtureId}/analysis`,
          {
            params: { chip: matchedChip },
          }
        );

        const assistantMsg: ChatMessage = {
          id: crypto.randomUUID(),
          role: "assistant",
          content: data.analysis,
          timestamp: Date.now(),
        };

        setMessages((prev) =>
          prev.filter((m) => m.id !== "typing-indicator").concat(assistantMsg)
        );
      } catch {
        const errorMsg: ChatMessage = {
          id: crypto.randomUUID(),
          role: "assistant",
          content:
            "Não foi possível obter resposta do assistente. Tenta novamente.",
          timestamp: Date.now(),
        };
        setMessages((prev) =>
          prev.filter((m) => m.id !== "typing-indicator").concat(errorMsg)
        );
      }
    },
    [fixtureId]
  );

  const clearChat = useCallback(() => {
    setMessages([]);
    setActiveChip(null);
  }, []);

  return {
    activeChip,
    selectChip,
    analysis: analysisData?.analysis ?? null,
    isLoading: isAnalysisLoading,
    error: analysisError ? "Não foi possível obter a análise da IA." : null,
    messages,
    sendMessage,
    clearChat,
  };
};
