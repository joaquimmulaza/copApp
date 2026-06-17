// InjuryBadge — compact badge for player card indicating injury/suspension
import { cn } from '@/lib/utils'
import type { StatusType } from '@/types/injury'

interface InjuryBadgeProps {
  readonly type: StatusType
  readonly reason?: string | null
  readonly className?: string
}

export const InjuryBadge = ({ type, reason, className }: InjuryBadgeProps) => (
  <span
    role="status"
    aria-label={`${type === 'injury' ? 'Lesionado' : 'Suspenso'}${reason ? `: ${reason}` : ''}`}
    className={cn(
      'inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium',
      type === 'injury' ? 'status-warning' : 'status-danger',
      className
    )}
  >
    {type === 'injury' ? '🏥' : '🟥'}{' '}
    {type === 'injury' ? 'Lesionado' : 'Suspenso'}
  </span>
)
