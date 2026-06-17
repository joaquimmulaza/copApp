// SkeletonCard — shimmer loading placeholder for fixture cards
// Uses the .skeleton CSS class from index.css (wave animation)
import { cn } from '@/lib/utils'

interface SkeletonCardProps {
  readonly className?: string
}

export const SkeletonCard = ({ className }: SkeletonCardProps) => (
  <div
    role="status"
    aria-label="A carregar..."
    className={cn('card-glass p-4 animate-pulse', className)}
  >
    {/* Teams row */}
    <div className="flex items-center justify-between mb-3">
      <div className="flex items-center gap-2">
        <div className="skeleton size-8 rounded-full" />
        <div className="skeleton h-4 w-24 rounded" />
      </div>
      <div className="skeleton h-6 w-16 rounded" />
      <div className="flex items-center gap-2">
        <div className="skeleton h-4 w-24 rounded" />
        <div className="skeleton size-8 rounded-full" />
      </div>
    </div>

    {/* Meta row */}
    <div className="flex justify-center gap-3">
      <div className="skeleton h-3 w-20 rounded" />
      <div className="skeleton h-3 w-16 rounded" />
    </div>

    <span className="sr-only">A carregar conteúdo</span>
  </div>
)
