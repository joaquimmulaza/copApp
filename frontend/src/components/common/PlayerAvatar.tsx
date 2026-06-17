// PlayerAvatar — player photo with position badge and fallback
import { useState } from "react";
import { cn, getInitials } from "@/lib/utils";
import type { Position } from "@/types/player";

interface PlayerAvatarProps {
  readonly name: string;
  readonly photoUrl: string | null;
  readonly position?: Position | null;
  readonly size?: "sm" | "md" | "lg";
  readonly className?: string;
}

const SIZE_CLASSES = {
  sm: { wrapper: "size-8", text: "text-xs", badge: "text-[10px]" },
  md: { wrapper: "size-10", text: "text-sm", badge: "text-[11px]" },
  lg: { wrapper: "size-14", text: "text-base", badge: "text-xs" },
} as const;

const POSITION_ABBR: Record<string, string> = {
  Goalkeeper: "GK",
  Defender: "DEF",
  Midfielder: "MID",
  Attacker: "FW",
};

export const PlayerAvatar = ({
  name,
  photoUrl,
  position,
  size = "md",
  className,
}: PlayerAvatarProps) => {
  const [hasError, setHasError] = useState(false);
  const sizes = SIZE_CLASSES[size];

  return (
    <div
      className={cn("relative inline-flex flex-col items-center", className)}
    >
      {!photoUrl || hasError ? (
        <div
          aria-label={name}
          className={cn(
            "rounded-full flex items-center justify-center font-mono font-semibold",
            "bg-surface-elevated text-muted-foreground border border-border",
            sizes.wrapper,
            sizes.text,
          )}
        >
          {getInitials(name)}
        </div>
      ) : (
        <img
          src={photoUrl}
          alt={`Foto de ${name}`}
          width={size === "lg" ? 56 : size === "md" ? 40 : 32}
          height={size === "lg" ? 56 : size === "md" ? 40 : 32}
          loading="lazy"
          decoding="async"
          onError={() => setHasError(true)}
          className={cn(
            "rounded-full object-cover border border-border",
            sizes.wrapper,
          )}
        />
      )}

      {position && (
        <span
          className={cn(
            "mt-0.5 px-1 py-px rounded font-mono font-medium text-muted-foreground",
            "bg-surface-elevated border border-border",
            sizes.badge,
          )}
          aria-label={`Posição: ${position}`}
        >
          {POSITION_ABBR[position] ?? position.slice(0, 3).toUpperCase()}
        </span>
      )}
    </div>
  );
};
