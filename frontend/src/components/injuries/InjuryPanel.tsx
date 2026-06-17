import { motion } from "framer-motion";
import { ScrollArea } from "@/components/ui/scroll-area";
import { Separator } from "@/components/ui/separator";
import { Avatar, AvatarImage, AvatarFallback } from "@/components/ui/avatar";
import { InjuryBadge } from "@/components/injuries/InjuryBadge";
import { InjuryTooltip } from "@/components/injuries/InjuryTooltip";
import { getInitials } from "@/lib/utils";
import type { GroupedInjuries, InjuryWithPlayer } from "@/types/injury";

interface InjuryPanelProps {
  readonly data: GroupedInjuries[];
}

// ─── Severity Sorter ──────────────────────────────────────────
// Orders absences by severity:
// 1. Injuries before suspensions
// 2. For injuries, longer/unspecified expected return times first
// 3. Fallback to alphabetical sorting of names
const sortAbsencesBySeverity = (
  absences: InjuryWithPlayer[],
): InjuryWithPlayer[] => {
  return [...absences].sort((a, b) => {
    if (a.type !== b.type) {
      return a.type === "injury" ? -1 : 1;
    }

    if (a.type === "injury") {
      if (a.expected_return && b.expected_return) {
        return (
          new Date(b.expected_return).getTime() -
          new Date(a.expected_return).getTime()
        );
      }
      if (a.expected_return) return 1;
      if (b.expected_return) return -1;
    }

    return a.player.name.localeCompare(b.player.name);
  });
};

// ─── Framer Motion Variants ────────────────────────────────────
const containerVariants = {
  hidden: { opacity: 0 },
  show: {
    opacity: 1,
    transition: {
      staggerChildren: 0.04,
    },
  },
};

const cardVariants = {
  hidden: { opacity: 0, y: 16 },
  show: {
    opacity: 1,
    y: 0,
    transition: {
      type: "spring",
      stiffness: 90,
      damping: 15,
    },
  },
};

export function InjuryPanel({ data }: InjuryPanelProps) {
  return (
    <motion.div
      variants={containerVariants}
      initial="hidden"
      animate="show"
      className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-1"
    >
      {data.map((group) => {
        const sortedInjuries = sortAbsencesBySeverity(group.injuries);

        return (
          <motion.div
            key={group.team.id}
            variants={cardVariants}
            className="card-glass flex flex-col h-[400px] overflow-hidden p-5 border border-border/40 hover:border-gold/30 transition-all duration-300"
          >
            {/* Team Header */}
            <div className="flex items-center gap-3.5 mb-4">
              <Avatar className="size-10 border border-border bg-surface-elevated shrink-0">
                {group.team.logo_url && (
                  <AvatarImage
                    src={group.team.logo_url}
                    alt={`${group.team.name} Logo`}
                    loading="lazy"
                  />
                )}
                <AvatarFallback className="text-xs font-semibold font-mono">
                  {getInitials(group.team.name)}
                </AvatarFallback>
              </Avatar>

              <div className="flex flex-col min-w-0">
                <h2 className="font-display font-semibold text-lg text-foreground truncate leading-snug">
                  {group.team.name}
                </h2>
                {group.team.code && (
                  <span className="text-[10px] font-mono text-muted-foreground tracking-wider uppercase">
                    {group.team.code}
                  </span>
                )}
              </div>
            </div>

            <Separator className="bg-border/40 mb-3" />

            {/* Absent Players List */}
            <ScrollArea className="flex-1 pr-1.5 -mr-1.5">
              <div className="flex flex-col gap-3 py-1">
                {sortedInjuries.map((injury) => (
                  <div
                    key={injury.id}
                    className="flex items-center justify-between gap-3 p-2 rounded-lg hover:bg-surface-elevated/40 border border-transparent hover:border-border/10 transition-all duration-200"
                  >
                    <div className="flex items-center gap-3 min-w-0">
                      <Avatar className="size-8 border border-border/60 bg-surface">
                        {injury.player.photo_url && (
                          <AvatarImage
                            src={injury.player.photo_url}
                            alt={injury.player.name}
                            loading="lazy"
                          />
                        )}
                        <AvatarFallback className="text-[10px] font-mono">
                          {getInitials(injury.player.name)}
                        </AvatarFallback>
                      </Avatar>

                      <div className="flex flex-col min-w-0">
                        <span className="text-sm font-medium text-foreground truncate">
                          {injury.player.name}
                        </span>
                        {injury.player.position && (
                          <span className="text-[11px] text-muted-foreground">
                            {injury.player.position}
                          </span>
                        )}
                      </div>
                    </div>

                    <InjuryTooltip
                      type={injury.type}
                      reason={injury.reason}
                      expectedReturn={injury.expected_return}
                      startDate={injury.start_date}
                    >
                      <button
                        type="button"
                        className="focus:outline-none"
                        aria-label={`Mais informações sobre desfalque de ${injury.player.name}`}
                      >
                        <InjuryBadge type={injury.type} />
                      </button>
                    </InjuryTooltip>
                  </div>
                ))}
              </div>
            </ScrollArea>
          </motion.div>
        );
      })}
    </motion.div>
  );
}
