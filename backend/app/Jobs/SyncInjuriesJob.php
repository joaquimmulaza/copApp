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
 * Camada 2 — Sync de lesões e suspensões (player_statuses).
 *
 * Executa 3× por dia (07h, 13h, 18h UTC) para manter a tabela
 * `player_statuses` actualizada com os desfalques actuais do torneio.
 *
 * Lógica de reconciliação activa/inactiva:
 *  - Jogadores presentes no payload → upsert com is_active = true.
 *  - Jogadores que tinham is_active = true mas NÃO vieram no payload
 *    de hoje → marcados com is_active = false (recuperados/disponíveis).
 *
 * Isto garante que a UI nunca mostra lesões antigas como activas,
 * e que jogadores recuperados voltam imediatamente ao squad disponível.
 *
 * Frequência:  3× por dia (07:00, 13:00, 18:00 UTC).
 * Custo:       1 request por execução.
 * Pré-requisito: ApiFootballQuotaService deve dar luz verde para 'layer2'.
 *
 * Ciclo de vida do job:
 *  1. Verifica semáforo canProceed('layer2').                → aborto silencioso + log 'skipped'.
 *  2. Chama GET /injuries?league={id}&season={ano}.          → aborto se a API falhar.
 *  3. Upsert na tabela `player_statuses` (is_active = true).
 *  4. Desactiva registos não presentes no payload do dia.
 *  5. Chama recordUsage().
 *  6. Persiste o resultado em sync_logs.
 *
 * @see CONTEXT.md §9.2 (migration player_statuses), §10.1 (Camada 2), §12 (RF01)
 */
class SyncInjuriesJob implements ShouldQueue
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
     * O upsert + deactivation de ~50 registos não deve exceder 60 s.
     */
    public int $timeout = 60;

    /**
     * Nome do layer usado em todos os registos e no semáforo.
     */
    private const string LAYER = 'layer2';

    /**
     * Endpoint relativo na API-Football.
     */
    private const string ENDPOINT = '/injuries';

    // ─────────────────────────────────────────────────────────────────────────
    // Constructor
    // ─────────────────────────────────────────────────────────────────────────

    public function __construct(
        private readonly ApiFootballQuotaServiceInterface $quotaService,
        private readonly ApiFootballService $apiService,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Lógica principal
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Executa a sincronização de lesões e suspensões activas.
     */
    public function handle(): void
    {
        $startedAt = Carbon::now();

        // ── 1. Verificar semáforo ────────────────────────────────────────────
        if (! $this->quotaService->canProceed(self::LAYER)) {
            Log::info('[SyncInjuriesJob] Abortado — orçamento insuficiente para layer2.', [
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

        // ── 2. Chamar a API ──────────────────────────────────────────────────
        $params = [
            'league' => $this->apiService->getLeagueId(),
            'season' => $this->apiService->getSeason(),
        ];

        $payload = $this->apiService->get(self::ENDPOINT, $params);

        if ($payload === null) {
            Log::error('[SyncInjuriesJob] Falhou — a API não devolveu resposta válida.', [
                'endpoint' => self::ENDPOINT,
                'params'   => $params,
            ]);

            $this->writeSyncLog(
                status: 'failed',
                recordsSynced: 0,
                apiRequestsUsed: 0,
                startedAt: $startedAt,
                errorMessage: 'API-Football não devolveu resposta (timeout ou erro de servidor).',
            );

            return;
        }

        // ── 3. Persistir e reconciliar na tabela `player_statuses` ──────────
        /** @var list<array<string, mixed>> $injuriesData */
        $injuriesData = $payload['response'] ?? [];

        if (empty($injuriesData)) {
            Log::warning('[SyncInjuriesJob] A API devolveu uma lista de lesões vazia.', [
                'raw_errors' => $payload['errors'] ?? [],
            ]);

            $this->writeSyncLog(
                status: 'completed',
                recordsSynced: 0,
                apiRequestsUsed: 1,
                startedAt: $startedAt,
                errorMessage: 'Payload vazio — sem lesões activas reportadas pela API.',
            );

            // O request foi consumido mesmo sem dados úteis.
            $this->registerApiUsage();

            return;
        }

        // Pré-carregar mapa de api_football_id → players.id e teams.id num N+1-safe lookup.
        $playerIdMap = $this->buildPlayerIdMap();
        $teamIdMap   = $this->buildTeamIdMap();

        // Executa o upsert e devolve os api_football_ids das lesões activas.
        [$syncedCount, $activeApiIds] = $this->upsertInjuries($injuriesData, $playerIdMap, $teamIdMap);

        // Desactiva registos que não vieram no payload de hoje (jogadores recuperados).
        $deactivatedCount = $this->deactivateRecoveredPlayers($activeApiIds);

        // ── 4. Registar o consumo de quota ───────────────────────────────────
        $this->registerApiUsage();

        // ── 5. Registar o ciclo de vida em sync_logs ─────────────────────────
        $totalProcessed = $syncedCount + $deactivatedCount;

        $this->writeSyncLog(
            status: 'completed',
            recordsSynced: $totalProcessed,
            apiRequestsUsed: 1,
            startedAt: $startedAt,
        );

        Log::info('[SyncInjuriesJob] Concluído com sucesso.', [
            'injuries_upserted'    => $syncedCount,
            'players_deactivated'  => $deactivatedCount,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Tratamento de falhas do job
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Método invocado pelo framework quando o job falha definitivamente.
     * Garante que o sync_log é sempre actualizado mesmo em excepções inesperadas.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[SyncInjuriesJob] Falha inesperada.', [
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
     * Carrega todos os players da BD num mapa [api_football_id => id].
     *
     * Evita N+1 queries no loop de upsert e garante resolução de FKs
     * com uma única query.
     *
     * @return array<int, int>  Mapa api_football_id → players.id
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
     * Carrega todos os teams da BD num mapa [api_football_id => id].
     *
     * @return array<int, int>  Mapa api_football_id → teams.id
     */
    private function buildTeamIdMap(): array
    {
        /** @var array<object{api_football_id: int, id: int}> $teams */
        $teams = DB::table('teams')
            ->select(['id', 'api_football_id'])
            ->get();

        $map = [];
        foreach ($teams as $team) {
            $map[(int) $team->api_football_id] = (int) $team->id;
        }

        return $map;
    }

    /**
     * Faz o upsert das lesões activas na tabela `player_statuses`.
     *
     * A chave natural é (api_football_id, type) — um jogador pode ter
     * simultaneamente uma lesão física e um cartão acumulado.
     *
     * Estrutura do payload da API-Football v3 /injuries:
     *   {
     *     player: { id, name, photo },
     *     team:   { id, name, logo },
     *     fixture: { id, ... },
     *     league: { ... },
     *     type:   "injury" | "suspension",
     *     reason: "Muscular" | "Yellow Card Accumulation" | ...
     *   }
     *
     * @param  list<array<string, mixed>>  $injuriesData   Array `response[]` da API
     * @param  array<int, int>             $playerIdMap    Mapa api_football_id → players.id
     * @param  array<int, int>             $teamIdMap      Mapa api_football_id → teams.id
     * @return array{int, list<int>}       [count de linhas upserted, lista de api_football_ids activos]
     */
    private function upsertInjuries(
        array $injuriesData,
        array $playerIdMap,
        array $teamIdMap,
    ): array {
        $rows          = [];
        $activeApiIds  = [];
        $now           = Carbon::now();

        foreach ($injuriesData as $entry) {
            /** @var array<string, mixed> $playerData */
            $playerData = $entry['player'] ?? [];

            /** @var array<string, mixed> $teamData */
            $teamData = $entry['team'] ?? [];

            $playerApiId = isset($playerData['id']) ? (int) $playerData['id'] : null;

            if ($playerApiId === null) {
                Log::warning('[SyncInjuriesJob] Entrada sem player.id ignorada.', [
                    'entry' => $entry,
                ]);
                continue;
            }

            $teamApiId = isset($teamData['id']) ? (int) $teamData['id'] : null;

            $playerId = $playerIdMap[$playerApiId] ?? null;
            $teamId   = $teamApiId !== null ? ($teamIdMap[$teamApiId] ?? null) : null;

            if ($playerId === null) {
                // Jogador ainda não existe em `players` (SyncPlayersJob ainda não correu).
                Log::warning('[SyncInjuriesJob] Jogador não encontrado na BD — lesão ignorada.', [
                    'api_player_id' => $playerApiId,
                    'name'          => $playerData['name'] ?? 'desconhecido',
                ]);
                continue;
            }

            if ($teamId === null) {
                Log::warning('[SyncInjuriesJob] Equipa não encontrada na BD — lesão ignorada.', [
                    'api_player_id' => $playerApiId,
                    'api_team_id'   => $teamApiId,
                ]);
                continue;
            }

            // type da API: "Missing Fixture", "Questionable", ou o tipo de carta.
            // Normalizar para 'injury' | 'suspension'.
            $rawType       = isset($entry['type']) ? (string) $entry['type'] : 'injury';
            $normalizedType = $this->normalizeStatusType($rawType);

            // reason: "Muscular", "Yellow Card Accumulation", etc.
            $reason = isset($entry['reason']) ? (string) $entry['reason'] : null;

            $activeApiIds[] = $playerApiId;

            $rows[] = [
                'api_football_id' => $playerApiId,
                'player_id'       => $playerId,
                'team_id'         => $teamId,
                'type'            => $normalizedType,
                'reason'          => $reason,
                'start_date'      => null,   // /injuries não devolve datas de início
                'expected_return' => null,   // idem — disponível em /injuries/{id}
                'is_active'       => true,
                'raw_api_data'    => json_encode($entry, JSON_THROW_ON_ERROR),
                'synced_at'       => $now,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }

        if (! empty($rows)) {
            // ON CONFLICT (api_football_id, type) → atualiza os campos dinâmicos.
            // A combinação (api_football_id, type) não tem um unique index explícito
            // na migration — usamos (api_football_id) como pivot e o type como parte
            // do update. Para idempotência correcta com vários tipos por jogador,
            // fazemos upsert linha a linha dentro de uma transação.
            DB::transaction(function () use ($rows, $now): void {
                foreach ($rows as $row) {
                    DB::table('player_statuses')->upsert(
                        [$row],
                        // A combinação api_football_id + type identifica univocamente
                        // uma entrada activa. Dado que a tabela não tem unique em
                        // (api_football_id, type), usamos api_football_id como pivot
                        // (assume um tipo por jogador activo, o mais comum).
                        uniqueBy: ['api_football_id'],
                        update: [
                            'player_id',
                            'team_id',
                            'type',
                            'reason',
                            'is_active',
                            'raw_api_data',
                            'synced_at',
                            'updated_at',
                        ],
                    );
                }
            });
        }

        return [count($rows), array_unique($activeApiIds)];
    }

    /**
     * Desactiva registos de lesões/suspensões que já não estão no payload do dia.
     *
     * Um jogador é considerado "recuperado" ou "cumpriu suspensão" se:
     *  - Tinha um registo com is_active = true
     *  - O seu api_football_id NÃO aparece na lista activa deste sync
     *
     * Isto garante que jogadores que recuperaram voltam automaticamente
     * ao squad disponível para escalação sem intervenção manual.
     *
     * @param  list<int>  $activeApiIds  Lista de api_football_ids presentes no payload
     * @return int Número de registos desactivados
     */
    private function deactivateRecoveredPlayers(array $activeApiIds): int
    {
        $query = DB::table('player_statuses')
            ->where('is_active', true);

        // Se há jogadores activos no payload, excluímo-los da desactivação.
        // Se o payload estava vazio (improvável, mas seguro), desactivamos todos.
        if (! empty($activeApiIds)) {
            $query->whereNotIn('api_football_id', $activeApiIds);
        }

        $deactivatedCount = $query->count();

        if ($deactivatedCount > 0) {
            $query->update([
                'is_active'  => false,
                'synced_at'  => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            Log::info('[SyncInjuriesJob] Jogadores marcados como recuperados.', [
                'count' => $deactivatedCount,
            ]);
        }

        return $deactivatedCount;
    }

    /**
     * Normaliza o campo `type` da API-Football para os valores aceites pela BD.
     *
     * A API pode devolver valores variados como "Missing Fixture", "Questionable",
     * "Yellow Cards" ou strings de suspensão. Mapeamos para 'injury' | 'suspension'.
     *
     * @param  string $rawType  Valor bruto do campo `type` da API
     * @return string           'injury' | 'suspension'
     */
    private function normalizeStatusType(string $rawType): string
    {
        $lower = strtolower($rawType);

        // Termos associados a suspensões por acumulação de cartões ou indisciplina.
        if (
            str_contains($lower, 'suspension')
            || str_contains($lower, 'card')
            || str_contains($lower, 'ban')
        ) {
            return 'suspension';
        }

        // Todo o resto (muscular, illness, missing fixture, questionable, …) é lesão.
        return 'injury';
    }

    /**
     * Regista o consumo de quota no QuotaService.
     */
    private function registerApiUsage(): void
    {
        $remaining = max(0, $this->quotaService->getRemainingBudget() - 1);
        $this->quotaService->recordUsage(self::ENDPOINT, self::LAYER, $remaining);
    }

    /**
     * Persiste uma linha em `sync_logs` para rastrear o ciclo de vida do job.
     *
     * Usa DB::table() directamente para evitar dependência de um Model
     * que pode ainda não existir.
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
