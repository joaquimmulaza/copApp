// LiveBadge — pulsing LIVE indicator for active fixtures
import { cn } from '@/lib/utils'

interface LiveBadgeProps {
  readonly className?: string
  readonly elapsed?: number | null
}

export const LiveBadge = ({ className, elapsed }: LiveBadgeProps) => (
  <span
    role="status"
    aria-live="polite"
    aria-label={`Jogo em direto${elapsed ? ` — ${elapsed} minutos` : ''}`}
    className={cn(
      'inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-mono font-semibold uppercase tracking-wider',
      'badge-live',
      className
    )}
  >
    <span className="size-1.5 rounded-full bg-danger animate-pulse-live" aria-hidden="true" />
    {elapsed !== undefined && elapsed !== null ? `${elapsed}'` : 'LIVE'}
  </span>
)
