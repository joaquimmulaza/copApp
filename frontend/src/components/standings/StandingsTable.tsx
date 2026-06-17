// StandingsTable — group standings sorted by rank
import type { Standing } from "@/types/standing";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";

interface StandingsTableProps {
  readonly groupName: string;
  readonly standings: Standing[];
}

export const StandingsTable = ({ groupName, standings }: StandingsTableProps) => {
  return (
    <section aria-label={`Grupo ${groupName}`}>
      <div className="flex items-center justify-between mb-3 px-1">
        <h2 className="font-display font-semibold text-sm tracking-wide text-foreground">
          Grupo {groupName}
        </h2>
        <span className="text-[10px] text-muted-foreground/60 uppercase font-mono tracking-wider">
          Mundial 2026
        </span>
      </div>
      
      <div className="overflow-hidden">
        <table className="w-full text-left border-collapse" role="table">
          <thead>
            <tr className="border-b border-border/40 text-muted-foreground/50 text-[9px] uppercase font-mono tracking-wider">
              <th scope="col" className="text-center pb-2 w-8 font-medium">
                #
              </th>
              <th scope="col" className="pb-2 font-medium">
                Seleção
              </th>
              <th scope="col" className="text-center pb-2 w-8 font-medium">
                J
              </th>
              <th scope="col" className="text-center pb-2 w-10 font-medium">
                SG
              </th>
              <th scope="col" className="text-right pb-2 pr-1 w-10 font-medium text-gold/80">
                P
              </th>
            </tr>
          </thead>
          <tbody className="divide-y divide-border/20">
            {standings.map((s) => {
              const isQualified = s.rank <= 2;
              
              // Calculate goals difference display
              const sgValue = s.goals_diff;
              const sgDisplay = sgValue > 0 ? `+${sgValue}` : `${sgValue}`;
              
              return (
                <tr
                  key={s.id}
                  className={`group relative transition-all duration-300 hover:bg-white/[0.02] ${
                    isQualified ? "bg-gold/[0.01]" : ""
                  }`}
                >
                  {/* Position / Rank */}
                  <td className="py-2.5 text-center font-mono font-medium text-xs relative">
                    {/* Qualification highlight vertical indicator */}
                    {isQualified && (
                      <div 
                        className="absolute left-0 top-1.5 bottom-1.5 w-[2px] bg-gold rounded-r"
                        style={{ height: "calc(100% - 12px)" }}
                      />
                    )}
                    <span className={isQualified ? "text-gold font-semibold" : "text-muted-foreground/50"}>
                      {s.rank}
                    </span>
                  </td>
                  
                  {/* Team details with Avatar */}
                  <td className="py-2.5">
                    <div className="flex items-center gap-2">
                      <Avatar className="h-5 w-5 rounded-full border border-border/40 bg-surface-elevated flex-shrink-0">
                        {s.team?.logo_url && (
                          <AvatarImage 
                            src={s.team.logo_url} 
                            alt={s.team.name} 
                            className="object-contain p-0.5"
                          />
                        )}
                        <AvatarFallback className="text-[8px] font-mono font-bold bg-surface-elevated text-muted-foreground/70">
                          {s.team?.code || s.team?.name.substring(0, 3).toUpperCase()}
                        </AvatarFallback>
                      </Avatar>
                      <span 
                        className={`font-display font-medium text-xs truncate max-w-[120px] transition-colors group-hover:text-foreground ${
                          isQualified ? "text-foreground font-semibold" : "text-muted-foreground/80"
                        }`}
                        title={s.team?.name}
                      >
                        {s.team?.name}
                      </span>
                    </div>
                  </td>
                  
                  {/* Played Games (J) */}
                  <td className="py-2.5 text-center font-mono tabular-nums text-[11px] text-muted-foreground/80">
                    {s.played}
                  </td>
                  
                  {/* Goal Difference (SG) */}
                  <td 
                    className={`py-2.5 text-center font-mono tabular-nums text-[11px] ${
                      sgValue > 0 
                        ? "text-success/90 font-medium" 
                        : sgValue < 0 
                        ? "text-danger/90" 
                        : "text-muted-foreground/50"
                    }`}
                  >
                    {sgDisplay}
                  </td>
                  
                  {/* Points (P) */}
                  <td className={`py-2.5 text-right pr-1 font-mono tabular-nums text-xs font-semibold ${
                    isQualified ? "text-gold" : "text-foreground"
                  }`}>
                    {s.points}
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>
      
      {/* Qualification Line Legend/Indicator */}
      <div className="mt-2.5 flex items-center justify-between border-t border-border/10 pt-2 text-[9px] text-muted-foreground/40 font-mono uppercase tracking-wider">
        <span>Zonas de Qualificação</span>
        <div className="flex gap-2 items-center">
          <span className="flex items-center gap-1">
            <span className="h-1.5 w-1.5 rounded-full bg-gold" />
            <span>Top 2 (Oitavos)</span>
          </span>
        </div>
      </div>
    </section>
  );
};
