import { Sparkles } from "lucide-react";
import { cn } from "@/lib/utils";

interface TacticalFlashProps {
  readonly analysis: string | null;
  readonly isLoading?: boolean;
  readonly className?: string;
}

export const TacticalFlash = ({
  analysis,
  isLoading = false,
  className,
}: TacticalFlashProps) => {
  if (!analysis && !isLoading) return null;

  // Split response by double linebreaks to create elegant paragraphs
  const paragraphs = analysis
    ? analysis.split(/\n\n+/).filter((p) => p.trim() !== "")
    : [];

  return (
    <div
      className={cn(
        "card-glass p-6 sm:p-8 flex flex-col gap-5 relative overflow-hidden border border-border/60",
        "bg-gradient-to-br from-surface to-background/50",
        className
      )}
    >
      {/* Decorative background glow */}
      <div
        className="absolute -right-16 -top-16 size-48 rounded-full bg-gold/5 blur-[40px] pointer-events-none"
        aria-hidden="true"
      />

      <div className="flex items-center gap-2 text-gold">
        <Sparkles className="size-4 shrink-0" />
        <span className="font-display font-medium text-xs tracking-wider uppercase">
          Análise Gemini Flash
        </span>
      </div>

      <div className="flex flex-col gap-4">
        {paragraphs.map((p, idx) => (
          <p
            key={idx}
            className="text-sm sm:text-[15px] text-foreground/90 leading-relaxed font-sans text-justify"
          >
            {p}
          </p>
        ))}
      </div>

      <div className="border-t border-border/30 pt-4 flex items-center justify-between text-[10px] text-muted-foreground">
        <span>Modelo: gemini-1.5-flash</span>
        <span>Atualizado em tempo real</span>
      </div>
    </div>
  );
};
