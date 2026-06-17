<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\ApiFootballQuotaServiceInterface;
use App\Services\ApiFootballService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Camada 1 — Sync de estatísticas de topo (top scorers / top assists).
 *
 * Sincroniza os artilheiros e assistentes de topo do Mundial 2026 a partir do
 * endpoint /players/topscorers e /players/topassists da API-Football, persistindo
 * os dados na tabela `player_stats`.
 *
 * Frequência:  1× por dia às 04:30 UTC (definida no scheduler — routes/console.php).
 * Custo:       2 requests por execução (1 scorers + 1 assists).
 * Pré-requisito: ApiFootballQuotaService deve dar luz verde para 'layer1'.
 *                SyncTeamsJob (03:00) e SyncPlayersJob (03:30) devem ter corrido antes.
 *
 * Ciclo de vida do job:
 *  1. Verifica semáforo canProceed('layer1').                    → aborto silencioso se falso.
 *  2. Chama GET /players/topscorers?league={id}&season={ano}.    → aborto se a API falhar.
 *  3. Chama GET /players/topassists?league={id}&season={ano}.    → falha suave (usa scorers).
 *  4. Faz upsert dos dados na tabela `player_stats`.
 *  5. Chama recordUsage() pelos requests consumidos.
 *  6. Persiste o resultado em `sync_logs`.
 *
 * @see CONTEXT.md §10.1 (Camada 1 — Layer 1 Static Data)
 */
class SyncTopStatsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Número máximo de tentativas.
     * Mantemos 1 para não gastar requests extras em caso de falha API.
     */
    public int $tries = 1;

    /**
     * Timeout do job em segundos.
     * Dois requests + upsert não devem exceder 90 s normalmente.
     */
    public int $timeout = 90;

    /**
     * Nome do layer usado em todos os registos e no semáforo.
     */
    private const string LAYER = 'layer1';

    /**
     * Endpoints relativos na API-Football.
     */
    private const string ENDPOINT_SCORERS = '/players/topscorers';
    private const string ENDPOINT_ASSISTS = '/players/topassists';

    // ─────────────────────────────────────────────────────────────────────────
    // Constructor (injecção de dependências)
    // ─────────────────────────────────────────────────────────────────────────

    public function __construct(
        private readonly ApiFootballQuotaServiceInterface $quotaService,
        private readonly ApiFootballService $apiService,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Lógica principal
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Executa a sincronização de estatísticas de topo.
     */
    public function handle(): void
    {
        $startedAt       = Carbon::now();
        $requestsUsed    = 0;
        $totalSynced     = 0;

        // ── 1. Verificar semáforo ────────────────────────────────────────────
        if (! $this->quotaService->canProceed(self::LAYER)) {
            Log::info('[SyncTopStatsJob] Abortado — orçamento insuficiente para layer1.', [
                'remaining' => $this->quotaService->getRemainingBudget(),
                'reserved'  => $this->quotaService->getReservedForLayer3(),
            ]);

            $this->writeSyncLog(
                status: 'skipped',
                recordsSynced: 0,
                apiRequestsUsed: 0,
                startedAt: $startedAt,
                errorMessage: 'Quota insuficiente — job abortado pelo semáforo.',
            );

            return;
        }

        $params = [
            'league' => $this->apiService->getLeagueId(),
            'season' => $this->apiService->getSeason(),
        ];

        // ── 2. Fetch top scorers ─────────────────────────────────────────────
        $scorersPayload = $this->apiService->get(self::ENDPOINT_SCORERS, $params);

        if ($scorersPayload === null) {
            Log::error('[SyncTopStatsJob] Falhou — /players/topscorers não respondeu.', [
                'params' => $params,
            ]);

            $this->writeSyncLog(
                status: 'failed',
                recordsSynced: 0,
                apiRequestsUsed: 0,
                startedAt: $startedAt,
                errorMessage: 'API-Football não devolveu resposta para topscorers.',
            );

            return;
        }

        $requestsUsed++;

        /** @var list<array<string, mixed>> $scorersData */
        $scorersData = $scorersPayload['response'] ?? [];
        $playerIdMap = $this->buildPlayerIdMap();
        $totalSynced += $this->upsertPlayerStats($scorersData, $playerIdMap);

        // ── 3. Fetch top assists (falha suave) ───────────────────────────────
        if ($this->quotaService->canProceed(self::LAYER)) {
            $assistsPayload = $this->apiService->get(self::ENDPOINT_ASSISTS, $params);

            if ($assistsPayload !== null) {
                $requestsUsed++;

                /** @var list<array<string, mixed>> $assistsData */
                $assistsData  = $assistsPayload['response'] ?? [];
                $totalSynced += $this->upsertPlayerStats($assistsData, $playerIdMap);
            } else {
                Log::warning('[SyncTopStatsJob] /players/topassists não respondeu — ignorado.', [
                    'params' => $params,
                ]);
            }
        }

        // ── 4-5. Registar quota e log ────────────────────────────────────────
        $this->registerApiUsage($requestsUsed);

        $this->writeSyncLog(
            status: 'completed',
            recordsSynced: $totalSynced,
            apiRequestsUsed: $requestsUsed,
            startedAt: $startedAt,
        );

        Log::info('[SyncTopStatsJob] Concluído com sucesso.', [
            'stats_synced'  => $totalSynced,
            'requests_used' => $requestsUsed,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Tratamento de falhas do job
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Invocado pelo framework quando o job falha definitivamente.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[SyncTopStatsJob] Falha inesperada.', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $this->writeSyncLog(
            status: 'failed',
            recordsSynced: 0,
            apiRequestsUsed: 0,
            startedAt: Carbon::now(),
            errorMessage: $exception->getMessage(),
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Métodos privados de suporte
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Carrega um mapa [api_football_id => players.id] para resolver FKs.
     *
     * @return array<int, int>
     */
    private function buildPlayerIdMap(): array
    {
        /** @var array<object{api_football_id: int, id: int}> $players */
        $players = DB::table('players')
            ->select(['id', 'api_football_id'])
            ->get();

        $map = [];
        foreach ($players as $player) {
            $map[(int) $player->api_football_id] = (int) $player->id;
        }

        return $map;
    }

    /**
     * Faz o upsert das estatísticas na tabela `player_stats`.
     *
     * A chave de conflito é (player_id, fixture_id); como top scorers/assists
     * não são ligados a uma fixture específica, usamos um fixture_id nulo
     * para representar dados agregados da época.
     *
     * @param  list<array<string, mixed>> $data       Array `response[]` da API
     * @param  array<int, int>            $playerIdMap Mapa api_football_id → players.id
     * @return int Número de linhas preparadas para upsert
     */
    private function upsertPlayerStats(array $data, array $playerIdMap): int
    {
        $rows = [];
        $now  = Carbon::now();

        foreach ($data as $entry) {
            /** @var array<string, mixed> $player */
            $player = $entry['player'] ?? [];

            /** @var list<array<string, mixed>> $statistics */
            $statistics = $entry['statistics'] ?? [];

            $apiPlayerId = isset($player['id']) ? (int) $player['id'] : null;

            if ($apiPlayerId === null) {
                Log::warning('[SyncTopStatsJob] Entrada sem api_football_id de jogador ignorada.');
                continue;
            }

            $playerId = $playerIdMap[$apiPlayerId] ?? null;

            if ($playerId === null) {
                Log::warning('[SyncTopStatsJob] Jogador não encontrado na BD — entrada ignorada.', [
                    'api_player_id' => $apiPlayerId,
                    'name'          => $player['name'] ?? 'desconhecido',
                ]);
                continue;
            }

            /** @var array<string, mixed> $stats */
            $stats  = $statistics[0] ?? [];
            $goals  = $stats['goals']  ?? [];
            $games  = $stats['games']  ?? [];
            $passes = $stats['passes'] ?? [];

            $rows[] = [
                'player_id'    => $playerId,
                'fixture_id'   => null, // Estatísticas agregadas da época (sem fixture específica)
                'goals'        => isset($goals['total'])   ? (int) $goals['total']   : 0,
                'assists'      => isset($goals['assists']) ? (int) $goals['assists'] : 0,
                'appearances'  => isset($games['appearences']) ? (int) $games['appearences'] : 0,
                'minutes'      => isset($games['minutes'])     ? (int) $games['minutes']     : 0,
                'rating'       => isset($games['rating']) && $games['rating'] !== null
                    ? (string) $games['rating']
                    : null,
                'key_passes'   => isset($passes['key']) ? (int) $passes['key'] : 0,
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
        }

        if (empty($rows)) {
            return 0;
        }

        DB::table('player_stats')->upsert(
            $rows,
            uniqueBy: ['player_id', 'fixture_id'],
            update: [
                'goals',
                'assists',
                'appearances',
                'minutes',
                'rating',
                'key_passes',
                'updated_at',
            ],
        );

        return count($rows);
    }

    /**
     * Regista o consumo de quota no QuotaService.
     */
    private function registerApiUsage(int $requestsUsed): void
    {
        $remaining = max(0, $this->quotaService->getRemainingBudget() - $requestsUsed);
        $this->quotaService->recordUsage(self::ENDPOINT_SCORERS, self::LAYER, $remaining);
    }

    /**
     * Persiste uma linha em `sync_logs` para rastrear o ciclo de vida do job.
     */
    private function writeSyncLog(
        string $status,
        int $recordsSynced,
        int $apiRequestsUsed,
        Carbon $startedAt,
        ?string $errorMessage = null,
    ): void {
        $completedAt = Carbon::now();
        $durationMs  = (int) $startedAt->diffInMilliseconds($completedAt);

        DB::table('sync_logs')->insert([
            'job_class'         => self::class,
            'layer'             => self::LAYER,
            'status'            => $status,
            'records_synced'    => $recordsSynced,
            'api_requests_used' => $apiRequestsUsed,
            'error_message'     => $errorMessage,
            'duration_ms'       => $durationMs,
            'started_at'        => $startedAt,
            'completed_at'      => $completedAt,
        ]);
    }
}
