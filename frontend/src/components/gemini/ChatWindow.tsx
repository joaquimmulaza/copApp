import { useEffect, useRef, useState } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { SendHorizontal, Trash2, Bot, User } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { TypingIndicator } from "./TypingIndicator";
import { useGeminiChat } from "@/hooks/useGeminiChat";
import { cn } from "@/lib/utils";

interface ChatWindowProps {
  readonly fixtureId: number;
  readonly className?: string;
}

export const ChatWindow = ({ fixtureId, className }: ChatWindowProps) => {
  const { messages, sendMessage, isLoading, clearChat } = useGeminiChat(fixtureId);
  const [inputValue, setInputValue] = useState("");
  const scrollRef = useRef<HTMLDivElement>(null);

  // Auto-scroll to bottom on new messages
  useEffect(() => {
    if (scrollRef.current) {
      scrollRef.current.scrollTop = scrollRef.current.scrollHeight;
    }
  }, [messages, isLoading]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!inputValue.trim() || isLoading) return;
    void sendMessage(inputValue.trim());
    setInputValue("");
  };

  return (
    <div
      className={cn(
        "card-glass flex flex-col h-[500px] sm:h-[600px] border border-border/60 overflow-hidden bg-gradient-to-b from-surface to-background/30",
        className
      )}
    >
      {/* Header */}
      <div className="flex items-center justify-between px-4 py-3 border-b border-border/40 bg-surface/80 backdrop-blur-md">
        <div className="flex items-center gap-2">
          <Bot className="size-4.5 text-gold" />
          <span className="font-display font-medium text-sm text-foreground tracking-wide">
            Assistente Tático
          </span>
        </div>
        {messages.length > 0 && (
          <Button
            variant="ghost"
            size="xs"
            onClick={clearChat}
            className="text-muted-foreground hover:text-danger hover:bg-danger/10 text-xs px-2 py-1 gap-1 h-7"
            aria-label="Limpar chat"
          >
            <Trash2 className="size-3.5" />
            Limpar
          </Button>
        )}
      </div>

      {/* Message List */}
      <div
        ref={scrollRef}
        className="flex-1 overflow-y-auto p-4 flex flex-col gap-4 scroll-smooth scrollbar-none"
      >
        {messages.length === 0 ? (
          <div className="flex flex-col items-center justify-center h-full text-center px-4 max-w-sm mx-auto gap-3">
            <Bot className="size-8 text-gold/60 animate-pulse" />
            <p className="font-display font-medium text-sm text-foreground">
              Como posso ajudar na análise deste confronto?
            </p>
            <p className="text-xs text-muted-foreground leading-relaxed">
              Faz perguntas livres sobre ausências, palpites, histórico dos
              times ou clica nos chips acima para análises rápidas.
            </p>
          </div>
        ) : (
          <AnimatePresence initial={false}>
            {messages.map((msg) => (
              <motion.div
                key={msg.id}
                initial={{ opacity: 0, y: 10 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, y: -10 }}
                transition={{ duration: 0.2 }}
                className={cn(
                  "flex items-start gap-2.5 max-w-[85%]",
                  msg.role === "user" ? "self-end flex-row-reverse" : "self-start"
                )}
              >
                {/* Avatar */}
                <div
                  className={cn(
                    "size-7 rounded-full flex items-center justify-center border shrink-0 text-xs",
                    msg.role === "user"
                      ? "border-border bg-surface-elevated text-muted-foreground"
                      : "border-gold/20 bg-gold-glow text-gold"
                  )}
                >
                  {msg.role === "user" ? (
                    <User className="size-3.5" />
                  ) : (
                    <Bot className="size-3.5" />
                  )}
                </div>

                {/* Bubble */}
                <div
                  className={cn(
                    "px-4 py-2.5 rounded-2xl text-[13px] leading-relaxed shadow-sm",
                    msg.role === "user"
                      ? "bg-surface border border-border/80 text-foreground rounded-tr-none"
                      : "bg-surface-elevated/40 border border-gold/10 text-foreground rounded-tl-none"
                  )}
                >
                  <p className="whitespace-pre-wrap">{msg.content}</p>
                </div>
              </motion.div>
            ))}
          </AnimatePresence>
        )}

        {/* Loading Indicator */}
        {isLoading && (
          <div className="self-start flex items-start gap-2.5 max-w-[85%]">
            <div className="size-7 rounded-full flex items-center justify-center border border-gold/20 bg-gold-glow text-gold shrink-0">
              <Bot className="size-3.5" />
            </div>
            <TypingIndicator />
          </div>
        )}
      </div>

      {/* Input Form */}
      <form
        onSubmit={handleSubmit}
        className="p-3 border-t border-border/40 bg-surface/50 backdrop-blur-md flex items-center gap-2"
      >
        <Input
          value={inputValue}
          onChange={(e: React.ChangeEvent<HTMLInputElement>) => setInputValue(e.target.value)}
          placeholder="Escreve uma pergunta tática..."
          disabled={isLoading}
          className={cn(
            "flex-1 h-9 bg-surface border-border text-xs rounded-xl transition-all duration-200",
            "focus:border-gold focus:ring-2 focus:ring-gold/15 focus:outline-none placeholder:text-muted-foreground/60"
          )}
        />
        <Button
          type="submit"
          disabled={!inputValue.trim() || isLoading}
          size="icon-sm"
          variant="ghost"
          className="text-muted-foreground hover:text-gold hover:bg-gold-glow border border-border/60 rounded-xl size-9 shrink-0 transition-colors"
          aria-label="Enviar mensagem"
        >
          <SendHorizontal className="size-4" />
        </Button>
      </form>
    </div>
  );
};
