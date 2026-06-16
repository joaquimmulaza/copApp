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
 * Camada 1 — Sync de jogadores (players).
 *
 * O endpoint /players é paginado.  Este job processa uma página de cada vez
 * e auto-dispara uma nova instância de si mesmo para a página seguinte,
 * desde que o QuotaService ainda dê luz verde.
 *
 * Isto garante:
 *  - Zero duplicação de requests (cada página é pedida apenas uma vez).
 *  - Cancelamento seguro a meio do processo caso o orçamento se esgote.
 *  - Rastreabilidade granular — uma linha em sync_logs por página.
 *
 * Frequência:  1× por dia às 03:30 UTC (página 1 disparada pelo scheduler).
 *              As páginas seguintes são auto-despachadas pelo próprio job.
 * Custo:       1 request por página (~2–3 req/dia no total para o Mundial 2026).
 * Pré-requisito: ApiFootballQuotaService deve dar luz verde para 'layer1'.
 *
 * Ciclo de vida por invocação:
 *  1. Verifica semáforo canProceed('layer1').                → aborto silencioso se falso.
 *  2. Chama GET /players?league=1&season=2026&page=N.        → aborto se a API falhar.
 *  3. Faz upsert dos jogadores, vinculando ao team_id correto.
 *  4. Chama recordUsage().
 *  5. Persiste o resultado em sync_logs.
 *  6. Se existirem mais páginas E quota disponível → dispatch(page + 1).
 *
 * @see CONTEXT.md §9.2 (migration players) e §10.1 (Camada 1)
 */
class SyncPlayersJob implements ShouldQueue
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
     * Um upsert de ~25 jogadores por página não deve exceder 60 s.
     */
    public int $timeout = 60;

    /**
     * Nome do layer usado em todos os registos e no semáforo.
     */
    private const string LAYER = 'layer1';

    /**
     * Endpoint relativo na API-Football.
     */
    private const string ENDPOINT = '/players';

    // ─────────────────────────────────────────────────────────────────────────
    // Constructor
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @param  int  $page  Página a processar (1-indexed).  A página 1 é despachada
     *                     pelo scheduler; as páginas seguintes são auto-despachadas.
     */
    public function __construct(
        private readonly ApiFootballQuotaServiceInterface $quotaService,
        private readonly ApiFootballService $apiService,
        private readonly int $page = 1,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Lógica principal
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Executa a sincronização de jogadores para a página $this->page.
     */
    public function handle(): void
    {
        $startedAt = Carbon::now();

        // ── 1. Verificar semáforo ────────────────────────────────────────────
        if (! $this->quotaService->canProceed(self::LAYER)) {
            Log::info('[SyncPlayersJob] Abortado — orçamento insuficiente para layer1.', [
                'page'      => $this->page,
                'remaining' => $this->quotaService->getRemainingBudget(),
                'reserved'  => $this->quotaService->getReservedForLayer3(),
            ]);

            $this->writeSyncLog(
                status: 'skipped',
                page: $this->page,
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
            'page'   => $this->page,
        ];

        $payload = $this->apiService->get(self::ENDPOINT, $params);

        if ($payload === null) {
            Log::error('[SyncPlayersJob] Falhou — a API não devolveu resposta válida.', [
                'endpoint' => self::ENDPOINT,
                'page'     => $this->page,
                'params'   => $params,
            ]);

            $this->writeSyncLog(
                status: 'failed',
                page: $this->page,
                recordsSynced: 0,
                apiRequestsUsed: 0,
                startedAt: $startedAt,
                errorMessage: 'API-Football não devolveu resposta (timeout ou erro de servidor).',
            );

            return;
        }

        // ── 3. Persistir os dados em `players` ───────────────────────────────
        /** @var list<array<string, mixed>> $playersData */
        $playersData = $payload['response'] ?? [];

        /** @var array<string, mixed> $paging */
        $paging = $payload['paging'] ?? ['current' => $this->page, 'total' => 1];

        $currentPage = (int) ($paging['current'] ?? $this->page);
        $totalPages  = (int) ($paging['total']   ?? 1);

        if (empty($playersData)) {
            Log::warning('[SyncPlayersJob] A API devolveu uma lista de jogadores vazia.', [
                'page'       => $this->page,
                'raw_errors' => $payload['errors'] ?? [],
            ]);

            $this->writeSyncLog(
                status: 'completed',
                page: $this->page,
                recordsSynced: 0,
                apiRequestsUsed: 1,
                startedAt: $startedAt,
                errorMessage: 'Payload vazio — possível resposta de API sem dados nesta página.',
            );

            // O request foi consumido mesmo sem dados úteis.
            $this->registerApiUsage();

            return;
        }

        // Pré-carregar mapa de api_football_id → teams.id para resolver FKs.
        $teamIdMap = $this->buildTeamIdMap();

        $syncedCount = $this->upsertPlayers($playersData, $teamIdMap);

        // ── 4. Registar o consumo de quota ───────────────────────────────────
        $this->registerApiUsage();

        // ── 5. Registar o ciclo de vida em sync_logs ─────────────────────────
        $this->writeSyncLog(
            status: 'completed',
            page: $this->page,
            recordsSynced: $syncedCount,
            apiRequestsUsed: 1,
            startedAt: $startedAt,
        );

        Log::info('[SyncPlayersJob] Página processada com sucesso.', [
            'page'           => $currentPage,
            'total_pages'    => $totalPages,
            'players_synced' => $syncedCount,
        ]);

        // ── 6. Auto-dispatch da próxima página, se aplicável ──────────────────
        // Condições para prosseguir:
        //   a) Existem mais páginas (current < total).
        //   b) O QuotaService ainda dá luz verde para a próxima invocação.
        if ($currentPage < $totalPages) {
            if ($this->quotaService->canProceed(self::LAYER)) {
                Log::info('[SyncPlayersJob] A despachar página seguinte.', [
                    'next_page'   => $currentPage + 1,
                    'total_pages' => $totalPages,
                    'remaining'   => $this->quotaService->getRemainingBudget(),
                ]);

                // O construtor injeta as dependências via IoC — apenas $page é passado.
                self::dispatch($currentPage + 1);
            } else {
                Log::warning('[SyncPlayersJob] Próxima página cancelada — quota insuficiente.', [
                    'next_page'   => $currentPage + 1,
                    'total_pages' => $totalPages,
                    'remaining'   => $this->quotaService->getRemainingBudget(),
                ]);

                // Registar a omissão para auditoria.
                $this->writeSyncLog(
                    status: 'skipped',
                    page: $currentPage + 1,
                    recordsSynced: 0,
                    apiRequestsUsed: 0,
                    startedAt: Carbon::now(),
                    errorMessage: sprintf(
                        'Página %d não despachada — quota insuficiente após página %d.',
                        $currentPage + 1,
                        $currentPage,
                    ),
                );
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Tratamento de falhas do job
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Invocado pelo framework quando o job falha definitivamente.
     * Garante que o sync_log é sempre actualizado mesmo em excepções inesperadas.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[SyncPlayersJob] Falha inesperada.', [
            'page'  => $this->page,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $this->writeSyncLog(
            status: 'failed',
            page: $this->page,
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
     * Evita N+1 queries no loop de upsert e garante que a resolução das FKs
     * é feita com uma única query.
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
     * Faz o upsert dos jogadores na tabela `players`.
     *
     * O campo `api_football_id` é a chave natural e garante idempotência.
     *
     * Cada entrada da API-Football v3 tem a estrutura:
     *   { player: {...}, statistics: [{team: {id}, games: {...}, ...}] }
     *
     * Usamos `statistics[0].team.id` para obter o api_football_id da equipa
     * e resolver o team_id via o mapa pré-carregado.
     *
     * Jogadores sem equipa reconhecida são ignorados com aviso.
     *
     * @param  list<array<string, mixed>> $playersData  Array `response[]` da API
     * @param  array<int, int>            $teamIdMap    Mapa api_football_id → teams.id
     * @return int Número de linhas efectivamente preparadas para upsert
     */
    private function upsertPlayers(array $playersData, array $teamIdMap): int
    {
        $rows = [];
        $now  = Carbon::now();

        foreach ($playersData as $entry) {
            /** @var array<string, mixed> $player */
            $player = $entry['player'] ?? [];

            /** @var list<array<string, mixed>> $statistics */
            $statistics = $entry['statistics'] ?? [];

            $apiId = isset($player['id']) ? (int) $player['id'] : null;

            if ($apiId === null) {
                Log::warning('[SyncPlayersJob] Entrada sem api_football_id ignorada.', [
                    'player' => $player,
                ]);
                continue;
            }

            // O team_id é resolvido a partir do primeiro elemento de statistics.
            // A API retorna sempre statistics[0] com dados da equipa actual.
            /** @var array<string, mixed> $firstStat */
            $firstStat = $statistics[0] ?? [];

            /** @var array<string, mixed> $teamData */
            $teamData = $firstStat['team'] ?? [];

            $teamApiId = isset($teamData['id']) ? (int) $teamData['id'] : null;

            if ($teamApiId === null) {
                Log::warning('[SyncPlayersJob] Jogador sem equipa na API ignorado.', [
                    'api_player_id' => $apiId,
                    'name'          => $player['name'] ?? 'desconhecido',
                ]);
                continue;
            }

            $teamId = $teamIdMap[$teamApiId] ?? null;

            if ($teamId === null) {
                Log::warning('[SyncPlayersJob] Equipa do jogador não encontrada na BD — jogador ignorado.', [
                    'api_player_id' => $apiId,
                    'team_api_id'   => $teamApiId,
                ]);
                continue;
            }

            // Dados biográficos do jogador.
            /** @var array<string, mixed> $birth */
            $birth = $player['birth'] ?? [];

            // Posição e número vêm de statistics[0].games.
            /** @var array<string, mixed> $games */
            $games = $firstStat['games'] ?? [];

            // Altura e peso são strings como "183 cm" e "78 kg" — extrair apenas o número.
            $heightCm = $this->parsePhysicalMeasure($player['height'] ?? null);
            $weightKg = $this->parsePhysicalMeasure($player['weight'] ?? null);

            $rows[] = [
                'api_football_id' => $apiId,
                'team_id'         => $teamId,
                'name'            => (string) ($player['name'] ?? ''),
                'firstname'       => isset($player['firstname']) ? (string) $player['firstname'] : null,
                'lastname'        => isset($player['lastname'])  ? (string) $player['lastname']  : null,
                'birth_date'      => isset($birth['date']) && $birth['date'] !== null
                    ? Carbon::parse((string) $birth['date'])->toDateString()
                    : null,
                'nationality'     => isset($player['nationality']) ? (string) $player['nationality'] : null,
                'age'             => isset($player['age']) ? (int) $player['age'] : null,
                'height'          => $heightCm,
                'weight'          => $weightKg,
                'photo_url'       => isset($player['photo']) ? (string) $player['photo'] : null,
                'position'        => isset($games['position']) ? (string) $games['position'] : null,
                'number'          => isset($games['number']) && $games['number'] !== null
                    ? (string) $games['number']
                    : null,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }

        if (empty($rows)) {
            return 0;
        }

        // upsert() usa ON CONFLICT DO UPDATE em PostgreSQL.
        // team_id é incluído no update para reflectir possíveis transferências.
        DB::table('players')->upsert(
            $rows,
            uniqueBy: ['api_football_id'],
            update: [
                'team_id',
                'name',
                'firstname',
                'lastname',
                'birth_date',
                'nationality',
                'age',
                'height',
                'weight',
                'photo_url',
                'position',
                'number',
                'updated_at',
            ],
        );

        return count($rows);
    }

    /**
     * Extrai o valor numérico de uma string de medida física da API-Football.
     *
     * A API devolve valores como "183 cm" ou "78 kg".
     * Retorna null se a string for null, vazia, ou não puder ser parseada.
     *
     * @param  string|null $raw   Valor bruto, e.g. "183 cm"
     * @return numeric-string|null  Valor decimal, e.g. "183.00", ou null
     */
    private function parsePhysicalMeasure(?string $raw): ?string
    {
        if ($raw === null || $raw === '' || $raw === 'null') {
            return null;
        }

        // Extrair apenas a parte numérica (suporta "183 cm" e "78 kg").
        if (preg_match('/^(\d+(?:\.\d+)?)/', trim($raw), $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    /**
     * Regista o consumo de quota no QuotaService.
     *
     * Usa o valor local decrementado em 1 como estimativa conservadora,
     * dado que o ApiFootballService não expõe o header de rate limit nesta versão.
     */
    private function registerApiUsage(): void
    {
        $remaining = max(0, $this->quotaService->getRemainingBudget() - 1);
        $this->quotaService->recordUsage(self::ENDPOINT, self::LAYER, $remaining);
    }

    /**
     * Persiste uma linha em `sync_logs` para rastrear o ciclo de vida do job.
     *
     * A coluna `error_message` contém a página processada para facilitar
     * a leitura dos logs quando múltiplas páginas são registadas.
     *
     * Usa DB::table() directamente para evitar dependência de um Model
     * que pode ainda não existir.
     */
    private function writeSyncLog(
        string $status,
        int $page,
        int $recordsSynced,
        int $apiRequestsUsed,
        Carbon $startedAt,
        ?string $errorMessage = null,
    ): void {
        $completedAt = Carbon::now();
        $durationMs  = (int) $startedAt->diffInMilliseconds($completedAt);

        // O nome da classe inclui a página para diferenciar entradas no log.
        $jobClass = self::class . "@page={$page}";

        DB::table('sync_logs')->insert([
            'job_class'         => $jobClass,
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
