import { useCallback, useReducer } from "react";
import { api } from "@/lib/axios";
import type {
  ChatMessage,
  GeminiChatRequest,
  GeminiChatResponse,
} from "@/types/gemini";
import type { GeminiChipId } from "@/lib/constants";

interface GeminiState {
  readonly messages: ChatMessage[];
  readonly isLoading: boolean;
  readonly error: string | null;
}

type GeminiAction =
  | { type: "ADD_USER_MESSAGE"; payload: ChatMessage }
  | { type: "ADD_ASSISTANT_MESSAGE"; payload: ChatMessage }
  | { type: "SET_LOADING"; payload: boolean }
  | { type: "SET_ERROR"; payload: string | null }
  | { type: "CLEAR" };

const reducer = (state: GeminiState, action: GeminiAction): GeminiState => {
  switch (action.type) {
    case "ADD_USER_MESSAGE":
    case "ADD_ASSISTANT_MESSAGE":
      return { ...state, messages: [...state.messages, action.payload] };
    case "SET_LOADING":
      return { ...state, isLoading: action.payload };
    case "SET_ERROR":
      return { ...state, error: action.payload };
    case "CLEAR":
      return { messages: [], isLoading: false, error: null };
    default:
      return state;
  }
};

export const useGeminiChat = (fixtureId: number) => {
  const [state, dispatch] = useReducer(reducer, {
    messages: [],
    isLoading: false,
    error: null,
  });

  const sendMessage = useCallback(
    async (content: string, chipId?: GeminiChipId) => {
      if (!content.trim() || state.isLoading) return;

      const userMessage: ChatMessage = {
        id: crypto.randomUUID(),
        role: "user",
        content: content.trim(),
        timestamp: Date.now(),
      };

      dispatch({ type: "ADD_USER_MESSAGE", payload: userMessage });
      dispatch({ type: "SET_LOADING", payload: true });
      dispatch({ type: "SET_ERROR", payload: null });

      try {
        const body: GeminiChatRequest = {
          fixture_id: fixtureId,
          message: content.trim(),
          chip_type: chipId ?? null,
        };

        const { data } = await api.post<GeminiChatResponse>(
          "/gemini/chat",
          body,
        );

        const assistantMessage: ChatMessage = {
          id: crypto.randomUUID(),
          role: "assistant",
          content: data.response,
          timestamp: Date.now(),
        };

        dispatch({ type: "ADD_ASSISTANT_MESSAGE", payload: assistantMessage });
      } catch {
        dispatch({
          type: "SET_ERROR",
          payload:
            "Não foi possível obter resposta do assistente. Tenta novamente.",
        });
      } finally {
        dispatch({ type: "SET_LOADING", payload: false });
      }
    },
    [fixtureId, state.isLoading],
  );

  const clearChat = useCallback(() => dispatch({ type: "CLEAR" }), []);

  return {
    messages: state.messages,
    isLoading: state.isLoading,
    error: state.error,
    sendMessage,
    clearChat,
  };
};
