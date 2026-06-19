import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/axios';
import type { Player } from '@/types/player';

export interface UsePlayersParams {
  team_id?: number;
  name?: string;
}

export function usePlayers(params?: UsePlayersParams) {
  return useQuery<Player[], Error>({
    queryKey: ['players', params],
    queryFn: async () => {
      const { data } = await api.get<{ data: Player[] }>('/players', { params });
      return data.data;
    },
  });
}

export function usePlayer(id: number | null) {
  return useQuery<Player, Error>({
    queryKey: ['player', id],
    queryFn: async () => {
      if (id === null) throw new Error('Player ID cannot be null');
      const { data } = await api.get<{ data: Player }>(`/players/${id}`);
      return data.data;
    },
    enabled: id !== null,
  });
}
