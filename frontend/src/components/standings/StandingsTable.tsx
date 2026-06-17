// StandingsTable — group standings sorted by rank
// TODO (next session): implement with framer-motion layout transitions
import type { GroupStandings } from "@/types/standing";

interface StandingsTableProps {
  readonly group: GroupStandings;
}

export const StandingsTable = ({ group }: StandingsTableProps) => (
  <section aria-label={`Grupo ${group.group}`}>
    <h2 className="font-display font-semibold text-lg mb-3">
      Grupo {group.group}
    </h2>
    <div className="card-glass overflow-hidden">
      <table className="w-full text-sm" role="table">
        <thead>
          <tr className="border-b border-border text-muted-foreground text-xs uppercase">
            <th scope="col" className="text-left p-3 w-8">
              #
            </th>
            <th scope="col" className="text-left p-3">
              Equipa
            </th>
            <th scope="col" className="text-right p-3 w-10">
              J
            </th>
            <th scope="col" className="text-right p-3 w-10">
              V
            </th>
            <th scope="col" className="text-right p-3 w-10">
              E
            </th>
            <th scope="col" className="text-right p-3 w-10">
              D
            </th>
            <th scope="col" className="text-right p-3 w-10">
              Pts
            </th>
          </tr>
        </thead>
        <tbody>
          {group.standings.map((s) => (
            <tr
              key={s.id}
              className="border-b border-border/50 last:border-0 hover:bg-surface-overlay/30 transition-colors"
            >
              <td className="p-3 font-mono text-muted-foreground">{s.rank}</td>
              <td className="p-3 font-medium">{s.team.name}</td>
              <td className="p-3 text-right font-mono">{s.played}</td>
              <td className="p-3 text-right font-mono">{s.won}</td>
              <td className="p-3 text-right font-mono">{s.drawn}</td>
              <td className="p-3 text-right font-mono">{s.lost}</td>
              <td className="p-3 text-right font-mono font-semibold text-gold">
                {s.points}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  </section>
);
