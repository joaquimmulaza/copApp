// SkeletonCard — shimmer loading placeholder for fixture cards
// Uses the .skeleton CSS class from index.css (gradient wave, never blink).
// Strictly no animate-pulse — that causes a flicker. Only shimmer allowed.
import { cn } from '@/lib/utils'

interface SkeletonCardProps {
  readonly className?: string
  /** Number of skeleton rows to render (default 1). Used in FixtureList. */
  readonly count?: number
}

function SingleSkeleton({ className }: { className?: string }) {
  return (
    <div
      role="status"
      aria-label="A carregar jogo..."
      aria-busy="true"
      className={cn('card-glass p-4', className)}
    >
      {/* Teams row */}
      <div className="flex items-center justify-between">
        {/* Home team */}
        <div className="flex flex-1 items-center gap-2">
          <div className="skeleton size-8 rounded-full shrink-0" />
          <div className="skeleton h-4 w-16 rounded-md" />
        </div>

        {/* Score centre */}
        <div className="skeleton h-6 w-20 rounded-md shrink-0 mx-3" />

        {/* Away team — reversed */}
        <div className="flex flex-1 items-center gap-2 justify-end">
          <div className="skeleton h-4 w-16 rounded-md" />
          <div className="skeleton size-8 rounded-full shrink-0" />
        </div>
      </div>

      {/* Status row */}
      <div className="flex justify-center mt-3">
        <div className="skeleton h-3 w-28 rounded-md" />
      </div>

      <span className="sr-only">A carregar conteúdo do jogo</span>
    </div>
  )
}

export const SkeletonCard = ({ className, count = 1 }: SkeletonCardProps) => {
  const props = className !== undefined ? { className } : {}

  if (count === 1) return <SingleSkeleton {...props} />

  return (
    <div className="flex flex-col gap-3" aria-label="A carregar jogos...">
      {Array.from({ length: count }, (_, i) => (
        <SingleSkeleton key={i} {...props} />
      ))}
    </div>
  )
}
