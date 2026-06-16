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
 * Camada 2 — Sync de classificações (standings).
 *
 * Executa 2× por dia (22:00 e 01:00 UTC) para manter a tabela `standings`
 * actualizada após os jogos do dia.
 *
 * O endpoint /standings devolve um array aninhado complexo com grupos
 * (Group A … Group L). Este job itera por cada grupo e, dentro de cada
 * grupo, por cada seleção, fazendo upsert com todos os campos relevantes.
 *
 * Resolução de team_id:
 *  - Pré-carrega o mapa [api_football_id → teams.id] com uma única query.
 *  - Se a equipa não existir na BD, a linha é ignorada com aviso.
 *
 * A escrita é atómica — todo o conjunto de linhas é persistido numa única
 * transação de base de dados para evitar estados intermédios inconsistentes.
 *
 * Frequência:  2× por dia (22:00 UTC e 01:00 UTC do dia seguinte).
 * Custo:       1 request por execução.
 * Pré-requisito: ApiFootballQuotaService deve dar luz verde para 'layer2'.
 *
 * Ciclo de vida do job:
 *  1. Verifica semáforo canProceed('layer2').                → aborto silencioso + log 'skipped'.
 *  2. Chama GET /standings?league={id}&season={ano}.         → aborto se a API falhar.
 *  3. Itera sobre grupos e seleções → upsert atómico em `standings`.
 *  4. Chama recordUsage().
 *  5. Persiste o resultado em sync_logs.
 *
 * @see CONTEXT.md §9.2 (migration standings), §10.1 (Camada 2), §12 (RF04)
 */
class SyncStandingsJob implements ShouldQueue
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
     * O upsert de 48 seleções distribuídas em 12 grupos não deve exceder 60 s.
     */
    public int $timeout = 60;

    /**
     * Nome do layer usado em todos os registos e no semáforo.
     */
    private const string LAYER = 'layer2';

    /**
     * Endpoint relativo na API-Football.
     */
    private const string ENDPOINT = '/standings';

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
     * Executa a sincronização das classificações por grupo.
     */
    public function handle(): void
    {
        $startedAt = Carbon::now();

        // ── 1. Verificar semáforo ────────────────────────────────────────────
        if (! $this->quotaService->canProceed(self::LAYER)) {
            Log::info('[SyncStandingsJob] Abortado — orçamento insuficiente para layer2.', [
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
            Log::error('[SyncStandingsJob] Falhou — a API não devolveu resposta válida.', [
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

        // ── 3. Navegar na estrutura aninhada e persistir ─────────────────────
        //
        // Estrutura do payload da API-Football v3 /standings:
        //   response[0].league.standings[]  — array de grupos
        //   Cada grupo é um array de entradas de seleções:
        //     [ { rank, team, points, goalsDiff, group, form, status, description, ... }, ... ]
        //
        // response pode conter múltiplos elementos de liga (e.g., fase de grupos
        // e fase de eliminatórias), mas para o Mundial usamos response[0].

        /** @var list<array<string, mixed>> $response */
        $response = $payload['response'] ?? [];

        if (empty($response)) {
            Log::warning('[SyncStandingsJob] A API devolveu um payload de standings vazio.', [
                'raw_errors' => $payload['errors'] ?? [],
            ]);

            $this->writeSyncLog(
                status: 'completed',
                recordsSynced: 0,
                apiRequestsUsed: 1,
                startedAt: $startedAt,
                errorMessage: 'Payload vazio — possível início do torneio sem classificações.',
            );

            $this->registerApiUsage();

            return;
        }

        // Pré-carregar mapa de api_football_id → teams.id num único query.
        $teamIdMap = $this->buildTeamIdMap();

        // Extrair o array de grupos: response[0].league.standings
        /** @var array<string, mixed> $leagueData */
        $leagueData = $response[0]['league'] ?? [];

        /** @var list<list<array<string, mixed>>> $standingsGroups */
        $standingsGroups = $leagueData['standings'] ?? [];

        if (empty($standingsGroups)) {
            Log::warning('[SyncStandingsJob] Nenhum grupo encontrado no payload de standings.', [
                'league_data_keys' => array_keys($leagueData),
            ]);

            $this->writeSyncLog(
                status: 'completed',
                recordsSynced: 0,
                apiRequestsUsed: 1,
                startedAt: $startedAt,
                errorMessage: 'Estrutura standings.groups vazia no payload.',
            );

            $this->registerApiUsage();

            return;
        }

        // Upsert atómico de todas as linhas num único bloco de transação.
        $syncedCount = $this->upsertStandingsAtomic($standingsGroups, $teamIdMap);

        // ── 4. Registar o consumo de quota ───────────────────────────────────
        $this->registerApiUsage();

        // ── 5. Registar o ciclo de vida em sync_logs ─────────────────────────
        $this->writeSyncLog(
            status: 'completed',
            recordsSynced: $syncedCount,
            apiRequestsUsed: 1,
            startedAt: $startedAt,
        );

        Log::info('[SyncStandingsJob] Concluído com sucesso.', [
            'teams_synced' => $syncedCount,
            'groups_total' => count($standingsGroups),
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
        Log::error('[SyncStandingsJob] Falha inesperada.', [
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
     * Carrega todos os teams da BD num mapa [api_football_id => id].
     *
     * Evita N+1 queries no loop de upsert.
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
     * Itera sobre todos os grupos do payload e faz upsert atómico em `standings`.
     *
     * Estrutura de cada grupo (array de standings por equipa):
     *   [
     *     {
     *       "rank":        1,
     *       "team":        { "id": 6, "name": "Brazil", "logo": "..." },
     *       "points":      9,
     *       "goalsDiff":   5,
     *       "group":       "Group A",
     *       "form":        "WWW",
     *       "status":      "same" | "up" | "down",
     *       "description": "Promotion - Round of 32",
     *       "all":         { "played": 3, "win": 3, "draw": 0, "lose": 0,
     *                        "goals": { "for": 8, "against": 3 } },
     *       ...
     *     },
     *     ...
     *   ]
     *
     * A escrita é envolvida numa transação para garantir que ou todos os
     * grupos são actualizados, ou nenhum é (atomicidade).
     *
     * @param  list<list<array<string, mixed>>>  $standingsGroups  Array de grupos da API
     * @param  array<int, int>                   $teamIdMap        Mapa api_football_id → teams.id
     * @return int  Número total de seleções persistidas
     */
    private function upsertStandingsAtomic(
        array $standingsGroups,
        array $teamIdMap,
    ): int {
        $totalRows = 0;

        DB::transaction(function () use ($standingsGroups, $teamIdMap, &$totalRows): void {
            $rows = [];
            $now  = Carbon::now();

            foreach ($standingsGroups as $group) {
                // Cada $group é um array de entradas de seleções dentro de um grupo.
                foreach ($group as $entry) {
                    /** @var array<string, mixed> $entry */
                    $row = $this->mapEntryToRow($entry, $teamIdMap, $now);

                    if ($row !== null) {
                        $rows[] = $row;
                    }
                }
            }

            if (empty($rows)) {
                return;
            }

            // Upsert único com ON CONFLICT (team_id, group_name) DO UPDATE.
            // A unique constraint na migration garante idempotência:
            //   $table->unique(['team_id', 'group_name']);
            DB::table('standings')->upsert(
                $rows,
                uniqueBy: ['team_id', 'group_name'],
                update: [
                    'rank',
                    'played',
                    'won',
                    'drawn',
                    'lost',
                    'goals_for',
                    'goals_against',
                    'goals_diff',
                    'points',
                    'form',
                    'status',
                    'description',
                    'synced_at',
                    'updated_at',
                ],
            );

            $totalRows = count($rows);
        });

        return $totalRows;
    }

    /**
     * Mapeia uma entrada do payload da API para uma linha da tabela `standings`.
     *
     * Devolve null se o team_id não puder ser resolvido (equipa não encontrada
     * na BD), registando um aviso para facilitar o diagnóstico.
     *
     * @param  array<string, mixed>  $entry       Entrada da API para uma seleção
     * @param  array<int, int>       $teamIdMap   Mapa api_football_id → teams.id
     * @param  Carbon                $now         Timestamp para created_at / updated_at
     * @return array<string, mixed>|null          Linha pronta para upsert, ou null
     */
    private function mapEntryToRow(
        array $entry,
        array $teamIdMap,
        Carbon $now,
    ): ?array {
        /** @var array<string, mixed> $teamData */
        $teamData = $entry['team'] ?? [];

        $teamApiId = isset($teamData['id']) ? (int) $teamData['id'] : null;

        if ($teamApiId === null) {
            Log::warning('[SyncStandingsJob] Entrada sem team.id ignorada.', [
                'entry' => $entry,
            ]);
            return null;
        }

        $teamId = $teamIdMap[$teamApiId] ?? null;

        if ($teamId === null) {
            Log::warning('[SyncStandingsJob] Equipa não encontrada na BD — classificação ignorada.', [
                'api_team_id'  => $teamApiId,
                'team_name'    => $teamData['name'] ?? 'desconhecido',
            ]);
            return null;
        }

        // Dados do grupo — "Group A", "Group B", ..., "Group L".
        // Extraímos apenas a letra para consistência com o resto do schema.
        $groupRaw  = isset($entry['group']) ? (string) $entry['group'] : '';
        $groupName = $this->extractGroupLetter($groupRaw);

        if ($groupName === null) {
            Log::warning('[SyncStandingsJob] group_name inválido — classificação ignorada.', [
                'api_team_id' => $teamApiId,
                'group_raw'   => $groupRaw,
            ]);
            return null;
        }

        // Estatísticas agrupadas em "all" (totais do torneio).
        /** @var array<string, mixed> $all */
        $all = $entry['all'] ?? [];

        /** @var array<string, mixed> $goals */
        $goals = $all['goals'] ?? [];

        $goalsFor     = isset($goals['for'])     ? (int) $goals['for']     : 0;
        $goalsAgainst = isset($goals['against'])  ? (int) $goals['against'] : 0;
        $goalsDiff    = isset($entry['goalsDiff']) ? (int) $entry['goalsDiff'] : ($goalsFor - $goalsAgainst);

        // Forma recente — string de até 5 caracteres: W, D, L.
        $form = isset($entry['form']) && $entry['form'] !== null
            ? (string) $entry['form']
            : null;

        // Truncar para o máximo definido na migration (varchar 15).
        if ($form !== null && strlen($form) > 15) {
            $form = substr($form, -15);
        }

        // Status de movimento na tabela: "same" | "up" | "down".
        $status = isset($entry['status']) ? (string) $entry['status'] : null;

        // Descrição: "Promotion - Round of 32", "Relegation", null.
        $description = isset($entry['description']) ? (string) $entry['description'] : null;

        // Truncar para o máximo definido na migration (varchar 100).
        if ($description !== null && strlen($description) > 100) {
            $description = substr($description, 0, 100);
        }

        return [
            'team_id'       => $teamId,
            'group_name'    => $groupName,
            'rank'          => isset($entry['rank'])   ? (int) $entry['rank']   : 1,
            'played'        => isset($all['played'])   ? (int) $all['played']   : 0,
            'won'           => isset($all['win'])      ? (int) $all['win']      : 0,
            'drawn'         => isset($all['draw'])     ? (int) $all['draw']     : 0,
            'lost'          => isset($all['lose'])     ? (int) $all['lose']     : 0,
            'goals_for'     => $goalsFor,
            'goals_against' => $goalsAgainst,
            'goals_diff'    => $goalsDiff,
            'points'        => isset($entry['points']) ? (int) $entry['points'] : 0,
            'form'          => $form,
            'status'        => $status,
            'description'   => $description,
            'synced_at'     => $now,
            'created_at'    => $now,
            'updated_at'    => $now,
        ];
    }

    /**
     * Extrai a letra do grupo a partir da string completa da API.
     *
     * Exemplos de entrada:
     *   "Group A"   → "A"
     *   "Group L"   → "L"
     *   "Group Stage - 1" → null  (eliminatórias, sem letra de grupo)
     *
     * @param  string       $raw  Valor bruto do campo `group` da API
     * @return string|null        Letra maiúscula do grupo, ou null se inválido
     */
    private function extractGroupLetter(string $raw): ?string
    {
        // Padrão: "Group A", "Group B", ..., "Group L"
        if (preg_match('/\bGroup\s+([A-L])\b/i', $raw, $matches) === 1) {
            return strtoupper($matches[1]);
        }

        return null;
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
