// PlayerCard — animated player tile used outside the main pitch grid
// (e.g. substitutes list, bench roster)
// Uses Framer Motion layoutId so that if a player transitions from
// probable → official confirmation, the avatar slides smoothly.

import { motion } from "framer-motion"
import { cn } from "@/lib/utils"
import { InjuryBadge } from "@/components/injuries/InjuryBadge"
import { PlayerAvatar } from "@/components/common/PlayerAvatar"
import {
  playerCardVariant,
  playerCardTransition,
} from "@/lib/animations"
import type { LineupPlayer } from "@/types/lineup"
import type { PlayerStatus } from "@/types/injury"

interface PlayerCardProps {
  readonly player: LineupPlayer
  readonly status?: PlayerStatus | null
}

export function PlayerCard({ player, status }: PlayerCardProps) {
  const isUnavailable = status?.is_active === true

  return (
    <motion.div
      layoutId={`player-${player.player_id}`}
      variants={playerCardVariant}
      initial="initial"
      animate="animate"
      transition={playerCardTransition}
      className={cn(
        "flex flex-col items-center gap-1",
        "transition-opacity duration-300",
        isUnavailable && "opacity-40",
      )}
    >
      <div className="relative">
        <PlayerAvatar name={player.name} photoUrl={player.photo_url} size="sm" />
        {isUnavailable && (
          <div className="absolute -top-1 -right-1">
            <InjuryBadge type={status.type} />
          </div>
        )}
      </div>

      <span className="text-xs text-muted-foreground truncate max-w-[80px] text-center">
        {player.name.split(" ").pop()}
      </span>

      <span className="text-[10px] font-mono text-subtle">{player.number}</span>
    </motion.div>
  )
}
