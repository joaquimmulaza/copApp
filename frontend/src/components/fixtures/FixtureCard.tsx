// FixtureCard — glass card displaying a fixture summary
// Implements the compound component pattern from CONTEXT.md §13
// TODO (next session): implement sub-components (Teams, Score, Status, Actions)
import type { FixtureSummary } from '@/types/fixture'

interface FixtureCardProps {
  readonly fixture: FixtureSummary
}

export const FixtureCard = ({ fixture }: FixtureCardProps) => (
  <article
    className="card-glass p-4 hover:bg-surface-overlay/50 transition-colors duration-150"
    aria-label={`${fixture.home_team.name} vs ${fixture.away_team.name}`}
  >
    <p className="text-muted-foreground text-sm">{fixture.home_team.name} — {fixture.away_team.name}</p>
  </article>
)
