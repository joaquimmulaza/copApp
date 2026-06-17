// PlayerCard — individual player tile in the lineup grid
// Shows status (injury/suspension) as a badge overlay
import { PlayerAvatar } from '@/components/common/PlayerAvatar'
import { InjuryBadge } from '@/components/injuries/InjuryBadge'
import type { LineupPlayer } from '@/types/lineup'
import type { PlayerStatus } from '@/types/injury'

interface PlayerCardProps {
  readonly player: LineupPlayer
  readonly status?: PlayerStatus | null
}

export const PlayerCard = ({ player, status }: PlayerCardProps) => (
  <div className="flex flex-col items-center gap-1">
    <div className="relative">
      <PlayerAvatar
        name={player.name}
        photoUrl={player.photo_url}
        size="sm"
      />
      {status?.is_active && (
        <div className="absolute -top-1 -right-1">
          <InjuryBadge type={status.type} />
        </div>
      )}
    </div>
    <span className="text-xs text-muted-foreground truncate max-w-[80px] text-center">
      {player.name.split(' ').pop()}
    </span>
    <span className="text-[10px] font-mono text-subtle">{player.number}</span>
  </div>
)
