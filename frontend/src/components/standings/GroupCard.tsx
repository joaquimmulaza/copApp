// GroupCard — card wrapper for a single group's standings table
import type { Standing } from "@/types/standing";
import { StandingsTable } from "./StandingsTable";

interface GroupCardProps {
  readonly groupName: string;
  readonly standings: Standing[];
}

export const GroupCard = ({ groupName, standings }: GroupCardProps) => (
  <div className="card-glass p-5 hover:border-gold/30 hover:shadow-gold-glow/5 transition-all duration-500">
    <StandingsTable groupName={groupName} standings={standings} />
  </div>
);
