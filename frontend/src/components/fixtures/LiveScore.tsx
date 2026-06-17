// LiveScore — animated score display with gold flash on update
// Uses the .score-live CSS class and animate-score-update keyframe
// TODO (next session): implement useAnimation trigger on score change
interface LiveScoreProps {
  readonly homeScore: number | null;
  readonly awayScore: number | null;
  readonly elapsed?: number | null;
}

export const LiveScore = ({
  homeScore,
  awayScore,
  elapsed,
}: LiveScoreProps) => (
  <div
    className="flex items-center gap-2 font-mono tabular-nums"
    aria-live="polite"
  >
    <span className="score-live">{homeScore ?? "–"}</span>
    <span className="text-muted-foreground">:</span>
    <span className="score-live">{awayScore ?? "–"}</span>
    {elapsed !== null && elapsed !== undefined && (
      <span className="text-xs text-danger font-medium">{elapsed}'</span>
    )}
  </div>
);
