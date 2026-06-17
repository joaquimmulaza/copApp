// GroupCard — card wrapper for a single group's standings table
import type { GroupStandings } from "@/types/standing";
import { StandingsTable } from "./StandingsTable";

interface GroupCardProps {
  readonly group: GroupStandings;
}

export const GroupCard = ({ group }: GroupCardProps) => (
  <div className="card-glass p-4">
    <StandingsTable group={group} />
  </div>
);
