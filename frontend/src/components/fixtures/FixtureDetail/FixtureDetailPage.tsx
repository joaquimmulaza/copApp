// FixtureDetailPage — orchestrates all sub-components for match detail
import type { Fixture } from '@/types/fixture'

interface FixtureDetailPageProps {
  readonly fixture: Fixture
}

export const FixtureDetailPage = ({ fixture }: FixtureDetailPageProps) => (
  <div className="flex flex-col gap-6 animate-fade-in">
    <header className="card-glass p-4">
      <p className="font-display font-semibold text-xl text-center">
        {fixture.home_team.name} × {fixture.away_team.name}
      </p>
      <p className="text-center text-muted-foreground text-sm mt-1">
        {fixture.round}
      </p>
    </header>

    {/* LineupGrid, TacticalFlash, LiveScore — next session */}
    <p className="text-muted-foreground">Detalhes do jogo — em desenvolvimento.</p>
  </div>
)
