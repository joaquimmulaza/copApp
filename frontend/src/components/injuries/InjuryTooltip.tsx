import * as React from "react";
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "@/components/ui/tooltip";
import { format, parseISO } from "date-fns";
import { ptBR } from "date-fns/locale";
import { Calendar, AlertCircle } from "lucide-react";

interface InjuryTooltipProps {
  readonly children: React.ReactNode;
  readonly type: "injury" | "suspension";
  readonly reason: string | null;
  readonly expectedReturn: string | null;
  readonly startDate?: string | null;
}

export function InjuryTooltip({
  children,
  type,
  reason,
  expectedReturn,
  startDate,
}: InjuryTooltipProps) {
  const isInjury = type === "injury";

  const formatDate = (dateStr: string | null) => {
    if (!dateStr) return null;
    try {
      const date = parseISO(dateStr);
      return format(date, "d 'de' MMMM, yyyy", { locale: ptBR });
    } catch {
      return dateStr;
    }
  };

  const formattedReturn = formatDate(expectedReturn);
  const formattedStart = formatDate(startDate ?? null);

  return (
    <TooltipProvider delayDuration={100}>
      <Tooltip>
        <TooltipTrigger asChild>{children}</TooltipTrigger>
        <TooltipContent
          side="top"
          className="flex flex-col gap-2 p-3 text-xs min-w-[220px] bg-surface-elevated text-foreground card-glass border border-border/40 shadow-xl"
        >
          <div className="flex items-center gap-1.5 border-b border-border pb-1.5 font-semibold font-display">
            <AlertCircle
              className={`size-3.5 ${isInjury ? "text-warning" : "text-danger"}`}
            />
            <span>
              {isInjury ? "Detalhes da Lesão" : "Detalhes da Suspensão"}
            </span>
          </div>

          <div className="flex flex-col gap-0.5">
            <span className="text-muted-foreground text-[10px] uppercase tracking-wider">
              Motivo
            </span>
            <span className="font-medium text-foreground">
              {reason ||
                (isInjury
                  ? "Problema físico não especificado"
                  : "Suspensão ativa")}
            </span>
          </div>

          {formattedStart && (
            <div className="flex items-center gap-1.5 text-muted-foreground">
              <Calendar className="size-3 shrink-0" />
              <span>Início: {formattedStart}</span>
            </div>
          )}

          <div className="flex items-center gap-1.5 text-muted-foreground">
            <Calendar className="size-3 shrink-0" />
            <span>
              Retorno:{" "}
              <span className="text-foreground font-medium">
                {formattedReturn || "Sem previsão"}
              </span>
            </span>
          </div>
        </TooltipContent>
      </Tooltip>
    </TooltipProvider>
  );
}
