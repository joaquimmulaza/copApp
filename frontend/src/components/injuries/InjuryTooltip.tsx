// InjuryTooltip — hover tooltip with full injury details
// Wraps shadcn Tooltip (install: npx shadcn@latest add tooltip)
// Placeholder until shadcn is initialised
import type { ReactNode } from 'react'
import type { InjuryWithPlayer } from '@/types/injury'

interface InjuryTooltipProps {
  readonly injury: InjuryWithPlayer
  readonly children: ReactNode
}

export const InjuryTooltip = ({ injury, children }: InjuryTooltipProps) => (
  <div
    title={`${injury.reason ?? injury.type} — Regresso previsto: ${injury.expected_return ?? 'Indefinido'}`}
    className="cursor-help"
  >
    {children}
  </div>
)
