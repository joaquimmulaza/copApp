// FixtureList — grouped list of fixtures by date
// TODO (next session): implement groupBy date, FixtureCard mapping
import type { FixtureSummary } from '@/types/fixture'
import { FixtureCard } from './FixtureCard'

interface FixtureListProps {
  readonly fixtures: FixtureSummary[]
}

export const FixtureList = ({ fixtures }: FixtureListProps) => (
  <div className="flex flex-col gap-3">
    {fixtures.map((f) => (
      <FixtureCard key={f.id} fixture={f} />
    ))}
  </div>
)
