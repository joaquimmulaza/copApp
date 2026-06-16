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
 * Camada 1 — Sync de fixtures (jogos).
 *
 * Sincroniza os 104 jogos do Mundial 2026 a partir do endpoint
 * /fixtures da API-Football para a tabela `fixtures`.
 *
 * A FK para home_team_id / away_team_id é resolvida internamente
 * via lookup em `teams.api_football_id`.  Se uma equipa ainda não
 * existir na BD, a fixture é ignorada com aviso — SyncTeamsJob deve
 * correr primeiro (03:00 UTC → SyncFixturesJob às 04:00 UTC).
 *
 * Frequência:  1× por dia às 04:00 UTC.
 * Custo:       1 request por execução.
 * Pré-requisito: ApiFootballQuotaService deve dar luz verde para 'layer1'.
 *
 * Ciclo de vida do job:
 *  1. Verifica semáforo canProceed('layer1').          → aborto silencioso se falso.
 *  2. Chama GET /fixtures?league={id}&season={ano}.    → aborto se a API falhar.
 *  3. Faz upsert de cada jogo na tabela `fixtures`.
 *  4. Chama recordUsage() com os requests restantes.
 *  5. Persiste o resultado em `sync_logs`.
 *
 * @see CONTEXT.md §9.2 (migration fixtures) e §10.1 (Camada 1)
 */
class SyncFixturesJob implements ShouldQueue
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
     * Um upsert de 104 jogos não deve exceder 90 s normalmente.
     */
    public int $timeout = 90;

    /**
     * Nome do layer usado em todos os registos e no semáforo.
     */
    private const string LAYER = 'layer1';

    /**
     * Endpoint relativo na API-Football.
     */
    private const string ENDPOINT = '/fixtures';

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
     * Executa a sincronização de fixtures.
     */
    public function handle(): void
    {
        $startedAt = Carbon::now();

        // ── 1. Verificar semáforo ────────────────────────────────────────────
        if (! $this->quotaService->canProceed(self::LAYER)) {
            Log::info('[SyncFixturesJob] Abortado — orçamento insuficiente para layer1.', [
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
            Log::error('[SyncFixturesJob] Falhou — a API não devolveu resposta válida.', [
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

        // ── 3. Persistir os dados em `fixtures` ──────────────────────────────
        /** @var list<array<string, mixed>> $fixturesData */
        $fixturesData = $payload['response'] ?? [];

        if (empty($fixturesData)) {
            Log::warning('[SyncFixturesJob] A API devolveu uma lista de fixtures vazia.', [
                'raw_errors' => $payload['errors'] ?? [],
            ]);

            $this->writeSyncLog(
                status: 'completed',
                recordsSynced: 0,
                apiRequestsUsed: 1,
                startedAt: $startedAt,
                errorMessage: 'Payload vazio — possível resposta de API sem dados.',
            );

            // O request foi consumido mesmo sem dados úteis.
            $this->registerApiUsage();

            return;
        }

        // Pré-carregar mapa de api_football_id → id para evitar N+1 queries.
        $teamIdMap = $this->buildTeamIdMap();

        $syncedCount = $this->upsertFixtures($fixturesData, $teamIdMap);

        // ── 4. Registar o consumo de quota ───────────────────────────────────
        $this->registerApiUsage();

        // ── 5. Registar o ciclo de vida na tabela sync_logs ──────────────────
        $this->writeSyncLog(
            status: 'completed',
            recordsSynced: $syncedCount,
            apiRequestsUsed: 1,
            startedAt: $startedAt,
        );

        Log::info('[SyncFixturesJob] Concluído com sucesso.', [
            'fixtures_synced' => $syncedCount,
            'fixtures_total'  => count($fixturesData),
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
        Log::error('[SyncFixturesJob] Falha inesperada.', [
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
     * Esta query única substitui uma sub-query por linha no loop de upsert,
     * eliminando o problema N+1 e garantindo consistência transaccional.
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
     * Faz o upsert das fixtures na tabela `fixtures`.
     *
     * O campo `api_football_id` é a chave natural e garante idempotência:
     * executar o job duas vezes não cria duplicados.
     *
     * Os campos de pontuação são extraídos a partir de `goals`, `score.halftime`,
     * `score.extratime` e `score.penalty` conforme a estrutura da API-Football v3.
     *
     * Fixtures cujas equipas ainda não existam na BD são ignoradas com aviso
     * (devem ser resolvidas re-correndo SyncTeamsJob antes deste job).
     *
     * @param  list<array<string, mixed>> $fixturesData  Array `response[]` da API
     * @param  array<int, int>            $teamIdMap     Mapa api_football_id → teams.id
     * @return int Número de linhas preparadas para upsert
     */
    private function upsertFixtures(array $fixturesData, array $teamIdMap): int
    {
        $rows = [];
        $now  = Carbon::now();

        foreach ($fixturesData as $entry) {
            /** @var array<string, mixed> $fixture */
            $fixture = $entry['fixture'] ?? [];

            /** @var array<string, mixed> $league */
            $league = $entry['league'] ?? [];

            /** @var array<string, mixed> $teams */
            $teams = $entry['teams'] ?? [];

            /** @var array<string, mixed> $goals */
            $goals = $entry['goals'] ?? [];

            /** @var array<string, mixed> $score */
            $score = $entry['score'] ?? [];

            $apiId = isset($fixture['id']) ? (int) $fixture['id'] : null;

            if ($apiId === null) {
                Log::warning('[SyncFixturesJob] Entrada sem api_football_id ignorada.', [
                    'fixture' => $fixture,
                ]);
                continue;
            }

            // Resolver FKs a partir do mapa pré-carregado.
            $homeApiId = isset($teams['home']['id']) ? (int) $teams['home']['id'] : null;
            $awayApiId = isset($teams['away']['id']) ? (int) $teams['away']['id'] : null;

            if ($homeApiId === null || $awayApiId === null) {
                Log::warning('[SyncFixturesJob] Fixture sem equipas definidas ignorada.', [
                    'api_fixture_id' => $apiId,
                ]);
                continue;
            }

            $homeTeamId = $teamIdMap[$homeApiId] ?? null;
            $awayTeamId = $teamIdMap[$awayApiId] ?? null;

            if ($homeTeamId === null || $awayTeamId === null) {
                Log::warning('[SyncFixturesJob] Equipa não encontrada na BD — fixture ignorada.', [
                    'api_fixture_id' => $apiId,
                    'home_api_id'    => $homeApiId,
                    'away_api_id'    => $awayApiId,
                ]);
                continue;
            }

            // Status do jogo (NS, 1H, HT, 2H, FT, AET, PEN, PST, CANC, …)
            /** @var array<string, mixed> $status */
            $status = $fixture['status'] ?? [];

            // Timestamp de kickoff — pode vir como string ISO 8601.
            $kickoffRaw = $fixture['date'] ?? null;
            $kickoffUtc = $kickoffRaw !== null
                ? Carbon::parse((string) $kickoffRaw)->utc()
                : null;

            // Pontuações parciais — a API devolve null antes do jogo começar.
            /** @var array<string, mixed>|null $halftime */
            $halftime = $score['halftime'] ?? null;

            /** @var array<string, mixed>|null $extratime */
            $extratime = $score['extratime'] ?? null;

            /** @var array<string, mixed>|null $penalty */
            $penalty = $score['penalty'] ?? null;

            $rows[] = [
                'api_football_id'   => $apiId,
                'home_team_id'      => $homeTeamId,
                'away_team_id'      => $awayTeamId,
                'round'             => isset($league['round'])   ? (string) $league['round']   : null,
                'group_name'        => isset($league['round'])   ? $this->extractGroupName((string) $league['round']) : null,
                // `stage` é derivado do round para uso em filtros de fase.
                'stage'             => isset($league['round'])   ? $this->classifyStage((string) $league['round']) : null,
                'venue_name'        => isset($fixture['venue']['name']) ? (string) $fixture['venue']['name'] : null,
                'venue_city'        => isset($fixture['venue']['city']) ? (string) $fixture['venue']['city'] : null,
                'kickoff_utc'       => $kickoffUtc,
                'status_short'      => isset($status['short']) ? (string) $status['short'] : 'NS',
                'status_long'       => isset($status['long'])  ? (string) $status['long']  : null,
                'elapsed_minutes'   => isset($status['elapsed']) ? (int) $status['elapsed'] : null,
                // Golos do jogo completo.
                'home_score'        => isset($goals['home'])  ? (int) $goals['home']  : null,
                'away_score'        => isset($goals['away'])  ? (int) $goals['away']  : null,
                // Golos ao intervalo.
                'home_score_ht'     => isset($halftime['home'])  ? (int) $halftime['home']  : null,
                'away_score_ht'     => isset($halftime['away'])  ? (int) $halftime['away']  : null,
                // Golos no prolongamento.
                'home_score_et'     => isset($extratime['home']) ? (int) $extratime['home'] : null,
                'away_score_et'     => isset($extratime['away']) ? (int) $extratime['away'] : null,
                // Golos nas grandes penalidades.
                'home_score_pen'    => isset($penalty['home'])   ? (int) $penalty['home']   : null,
                'away_score_pen'    => isset($penalty['away'])   ? (int) $penalty['away']   : null,
                // lineup_confirmed não é modificado aqui — gerido pelo PollLineupJob.
                'lineup_confirmed'  => false,
                'lineup_confirmed_at' => null,
                // Dados brutos completos para auditoria e futura extensão.
                'raw_api_data'      => json_encode($entry, JSON_THROW_ON_ERROR),
                'created_at'        => $now,
                'updated_at'        => $now,
            ];
        }

        if (empty($rows)) {
            return 0;
        }

        // upsert() usa ON CONFLICT DO UPDATE em PostgreSQL.
        // lineup_confirmed e lineup_confirmed_at são propositadamente excluídos
        // do bloco `update` para nunca sobrescrever confirmações do PollLineupJob.
        DB::table('fixtures')->upsert(
            $rows,
            uniqueBy: ['api_football_id'],
            update: [
                'home_team_id',
                'away_team_id',
                'round',
                'group_name',
                'stage',
                'venue_name',
                'venue_city',
                'kickoff_utc',
                'status_short',
                'status_long',
                'elapsed_minutes',
                'home_score',
                'away_score',
                'home_score_ht',
                'away_score_ht',
                'home_score_et',
                'away_score_et',
                'home_score_pen',
                'away_score_pen',
                'raw_api_data',
                'updated_at',
            ],
        );

        return count($rows);
    }

    /**
     * Extrai a letra do grupo a partir do campo `round` da liga.
     *
     * Exemplos de entrada da API-Football:
     *   "Group Stage - 1"  → null (a letra do grupo não está no round, mas no teams.group)
     *   "Group A"          → "A"
     *   "Round of 32"      → null
     *
     * A API-Football v3 devolve o grupo no campo `league.group` de cada fixture.
     * Se esse campo não estiver disponível, devolvemos null e deixamos o
     * SyncStandingsJob preencher via standings.
     */
    private function extractGroupName(string $round): ?string
    {
        // Padrão: "Group A", "Group B", …, "Group L"
        if (preg_match('/\bGroup\s+([A-L])\b/i', $round, $matches) === 1) {
            return strtoupper($matches[1]);
        }

        return null;
    }

    /**
     * Classifica o round num stage normalizado para filtros de fase no frontend.
     *
     * Mapeamento baseado nos valores reais devolvidos pela API-Football v3
     * para o Mundial 2026 com 48 seleções.
     *
     * @param  string $round  Valor bruto de `league.round`
     * @return string         Um dos: 'group' | 'r32' | 'r16' | 'qf' | 'sf' | '3rd' | 'f'
     */
    private function classifyStage(string $round): string
    {
        $lower = strtolower($round);

        return match (true) {
            str_contains($lower, 'group')         => 'group',
            str_contains($lower, 'round of 32')   => 'r32',
            str_contains($lower, 'round of 16')   => 'r16',
            str_contains($lower, 'quarter')        => 'qf',
            str_contains($lower, 'semi')           => 'sf',
            str_contains($lower, '3rd place')      => '3rd',
            str_contains($lower, 'final')          => 'f',
            default                                => 'group',
        };
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
