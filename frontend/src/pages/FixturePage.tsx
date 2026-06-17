import { useParams } from 'react-router-dom'

// FixturePage — Match detail with lineups, AI analysis, and live score
// TODO (next session): compose LineupGrid + TacticalFlash + LiveScore
const FixturePage = () => {
  const { id } = useParams<{ id: string }>()

  return (
    <div className="animate-fade-in">
      <h1 className="font-display font-semibold text-2xl mb-6">
        Detalhe do Jogo #{id}
      </h1>
      <p className="text-muted-foreground">
        Escalação e análise tática — em desenvolvimento.
      </p>
    </div>
  )
}

export default FixturePage
