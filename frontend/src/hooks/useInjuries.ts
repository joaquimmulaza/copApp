import { useQuery } from "@tanstack/react-query";
import { api } from "@/lib/axios";
import { QUERY_KEYS } from "@/lib/constants";
import type {
  InjuryListResponse,
  InjuryWithPlayer,
  GroupedInjuries,
} from "@/types/injury";

interface UseInjuriesParams {
  readonly teamId?: number;
}

const groupInjuriesByTeam = (
  injuries: InjuryWithPlayer[],
): GroupedInjuries[] => {
  const groupsMap = new Map<number, GroupedInjuries>();

  for (const injury of injuries) {
    const teamId = injury.team_id;
    if (!groupsMap.has(teamId)) {
      groupsMap.set(teamId, {
        team: {
          id: injury.team.id,
          name: injury.team.name,
          code: injury.team.code,
          logo_url: injury.team.logo_url,
        },
        injuries: [],
      });
    }
    groupsMap.get(teamId)!.injuries.push(injury);
  }

  return Array.from(groupsMap.values());
};

export const useInjuries = (params?: UseInjuriesParams) =>
  useQuery({
    queryKey: QUERY_KEYS.injuries(params?.teamId),
    queryFn: async (): Promise<GroupedInjuries[]> => {
      const { data } = await api.get<InjuryListResponse>("/injuries", {
        params: params?.teamId ? { team_id: params.teamId } : undefined,
      });
      return groupInjuriesByTeam(data.data);
    },
    staleTime: 5 * 60_000, // Injuries update infrequently — 5 min
  });
