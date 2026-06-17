import { Button } from "@/components/ui/button";
import { GEMINI_CHIPS } from "@/lib/constants";
import type { GeminiChipId } from "@/lib/constants";
import { cn } from "@/lib/utils";

interface ContextChipsProps {
  readonly activeChip: GeminiChipId | null;
  readonly onSelect: (chipId: GeminiChipId) => void;
  readonly disabled?: boolean;
}

export const ContextChips = ({
  activeChip,
  onSelect,
  disabled = false,
}: ContextChipsProps) => (
  <div
    role="group"
    aria-label="Chips de análise por IA"
    className="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none -mx-4 px-4 sm:mx-0 sm:px-0 w-[calc(100%+2rem)] sm:w-full select-none"
  >
    {GEMINI_CHIPS.map((chip) => (
      <Button
        key={chip.id}
        variant="ghost"
        onClick={() => onSelect(chip.id)}
        disabled={disabled}
        aria-pressed={activeChip === chip.id}
        aria-label={`Análise: ${chip.label}`}
        className={cn(
          "h-8 px-4 rounded-full text-xs font-medium tracking-wide transition-all border shrink-0",
          activeChip === chip.id
            ? "border-[var(--gold)]/40 bg-[var(--gold-glow)] text-[var(--gold)] hover:bg-[var(--gold-glow)] hover:text-[var(--gold)] shadow-[0_0_12px_var(--gold-glow)]"
            : "border-border text-muted-foreground hover:text-foreground hover:bg-surface-overlay hover:border-border-strong",
          disabled && "opacity-50 cursor-not-allowed",
        )}
      >
        {chip.label}
      </Button>
    ))}
  </div>
);
