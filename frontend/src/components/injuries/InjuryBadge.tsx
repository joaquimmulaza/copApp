import * as React from "react";
import { cn } from "@/lib/utils";
import { Activity, Ban } from "lucide-react";

interface InjuryBadgeProps extends React.ComponentProps<"span"> {
  readonly type: "injury" | "suspension";
}

export function InjuryBadge({ type, className, ...props }: InjuryBadgeProps) {
  const isInjury = type === "injury";

  return (
    <span
      className={cn(
        "inline-flex items-center gap-1.5 shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium tracking-wide uppercase border",
        isInjury ? "status-warning" : "status-danger",
        className,
      )}
      {...props}
    >
      {isInjury ? (
        <Activity className="size-3 shrink-0" />
      ) : (
        <Ban className="size-3 shrink-0" />
      )}
      <span>{isInjury ? "Lesão" : "Suspenso"}</span>
    </span>
  );
}
