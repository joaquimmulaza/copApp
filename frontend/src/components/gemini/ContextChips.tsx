// ContextChips — horizontal chip row for AI analysis shortcuts
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
    className="flex flex-wrap gap-2"
  >
    {GEMINI_CHIPS.map((chip) => (
      <button
        key={chip.id}
        type="button"
        onClick={() => onSelect(chip.id)}
        disabled={disabled}
        aria-pressed={activeChip === chip.id}
        aria-label={`Análise: ${chip.label}`}
        className={cn(
          "px-3 py-1.5 rounded-full text-sm font-medium transition-all duration-150",
          "border focus-visible:outline-gold",
          activeChip === chip.id
            ? "bg-gold/10 border-gold text-gold"
            : "border-border text-muted-foreground hover:text-foreground hover:bg-surface-overlay hover:border-border-strong",
          disabled && "opacity-50 cursor-not-allowed",
        )}
      >
        {chip.label}
      </button>
    ))}
  </div>
);
