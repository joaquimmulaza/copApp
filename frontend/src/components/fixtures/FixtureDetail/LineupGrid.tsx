// LineupGrid — tactical 11x11 formation display
// Uses player grid positions from the lineup API
// TODO (next session): render actual formation grid with Framer Motion
import type { FixtureLineup } from "@/types/lineup";

interface LineupGridProps {
  readonly lineup: FixtureLineup;
  readonly side: "home" | "away";
}

export const LineupGrid = ({ lineup, side }: LineupGridProps) => (
  <section
    aria-label={`Escalação — ${side === "home" ? "Casa" : "Fora"}`}
    className="card-glass p-4"
  >
    <p className="font-mono text-sm text-muted-foreground mb-2">
      {lineup.formation ?? "—"}
    </p>
    <div className="grid grid-cols-1 gap-1">
      {lineup.starting_xi.map((p) => (
        <span key={p.player_id} className="text-sm text-foreground">
          {p.number}. {p.name}
        </span>
      ))}
    </div>
  </section>
);
