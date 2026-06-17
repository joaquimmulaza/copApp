// TeamLogo — team flag/crest with fallback initials
import { useState } from 'react'
import { cn, getInitials } from '@/lib/utils'

interface TeamLogoProps {
  readonly name: string
  readonly logoUrl: string | null
  readonly size?: 'sm' | 'md' | 'lg'
  readonly className?: string
}

const SIZE_CLASSES = {
  sm: 'size-6 text-xs',
  md: 'size-8 text-sm',
  lg: 'size-12 text-base',
} as const

export const TeamLogo = ({
  name,
  logoUrl,
  size = 'md',
  className,
}: TeamLogoProps) => {
  const [hasError, setHasError] = useState(false)

  if (!logoUrl || hasError) {
    return (
      <div
        aria-label={name}
        className={cn(
          'rounded-full flex items-center justify-center font-mono font-semibold',
          'bg-surface-elevated text-muted-foreground border border-border',
          SIZE_CLASSES[size],
          className
        )}
      >
        {getInitials(name)}
      </div>
    )
  }

  return (
    <img
      src={logoUrl}
      alt={`Escudo de ${name}`}
      width={size === 'lg' ? 48 : size === 'md' ? 32 : 24}
      height={size === 'lg' ? 48 : size === 'md' ? 32 : 24}
      loading="lazy"
      decoding="async"
      onError={() => setHasError(true)}
      className={cn('object-contain rounded-full', SIZE_CLASSES[size], className)}
    />
  )
}
