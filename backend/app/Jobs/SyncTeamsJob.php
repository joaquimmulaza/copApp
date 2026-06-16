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
 * Camada 1 — Sync de seleções (teams).
 *
 * Sincroniza as seleções participantes do Mundial 2026 a partir do
 * endpoint /teams da API-Football para a tabela `teams`.
 *
 * Frequência:  1× por dia às 03:00 UTC (definida no kernel de agendamento).
 * Custo:       1 request por execução.
 * Pré-requisito: ApiFootballQuotaService deve dar luz verde para 'layer1'.
 *
 * Ciclo de vida do job:
 *  1. Verifica semáforo canProceed('layer1').         → aborto silencioso se falso.
 *  2. Chama GET /teams?league=1&season=2026.          → aborto se a API falhar.
 *  3. Faz upsert de cada seleção na tabela `teams`.
 *  4. Chama recordUsage() com os requests restantes.
 *  5. Persiste o resultado em `sync_logs`.
 *
 * @see CONTEXT.md §10.1 (Camada 1) e §9.2 (migration teams)
 */
class SyncTeamsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Número máximo de tentativas antes de o job ser marcado como falhado.
     * Mantemos 1 para não gastar requests extras em caso de falha API.
     */
    public int $tries = 1;

    /**
     * Timeout do job em segundos.
     * Um upsert de 48 seleções não deve exceder 60 s normalmente.
     */
    public int $timeout = 60;

    /**
     * Nome do layer usado em todos os registos e no semáforo.
     */
    private const string LAYER = 'layer1';

    /**
     * Endpoint relativo na API-Football.
     */
    private const string ENDPOINT = '/teams';

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
     * Executa a sincronização de seleções.
     */
    public function handle(): void
    {
        $startedAt = Carbon::now();

        // ── 1. Verificar semáforo ────────────────────────────────────────────
        if (! $this->quotaService->canProceed(self::LAYER)) {
            Log::info('[SyncTeamsJob] Abortado — orçamento insuficiente para layer1.', [
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
            Log::error('[SyncTeamsJob] Falhou — a API não devolveu resposta válida.', [
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

        // ── 3. Persistir os dados em `teams` ─────────────────────────────────
        /** @var list<array<string, mixed>> $teamsData */
        $teamsData = $payload['response'] ?? [];

        if (empty($teamsData)) {
            Log::warning('[SyncTeamsJob] A API devolveu uma lista de seleções vazia.', [
                'raw_errors' => $payload['errors'] ?? [],
            ]);

            $this->writeSyncLog(
                status: 'completed',
                recordsSynced: 0,
                apiRequestsUsed: 1,
                startedAt: $startedAt,
                errorMessage: 'Payload vazio — possível resposta de API sem dados.',
            );

            // Ainda assim registamos o uso pois o request foi consumido.
            $this->registerApiUsage($payload);

            return;
        }

        $syncedCount = $this->upsertTeams($teamsData);

        // ── 4. Registar o consumo de quota ───────────────────────────────────
        $this->registerApiUsage($payload);

        // ── 5. Registar o ciclo de vida na tabela sync_logs ──────────────────
        $this->writeSyncLog(
            status: 'completed',
            recordsSynced: $syncedCount,
            apiRequestsUsed: 1,
            startedAt: $startedAt,
        );

        Log::info('[SyncTeamsJob] Concluído com sucesso.', [
            'teams_synced' => $syncedCount,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Tratamento de falhas do job (chamado automaticamente pelo Laravel)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Método invocado pelo framework quando o job falha definitivamente.
     * Garante que o sync_log é sempre actualizado mesmo em excepções inesperadas.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[SyncTeamsJob] Falha inesperada.', [
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
     * Faz o upsert das seleções na tabela `teams`.
     *
     * O campo `api_football_id` é a chave natural e garante idempotência:
     * executar o job duas vezes não cria duplicados.
     *
     * Os campos JSONB `venue` e `coach` são serializados como JSON string
     * antes de serem persistidos, compatível com o driver PostgreSQL do Laravel.
     *
     * @param  list<array<string, mixed>> $teamsData  Array `response[]` da API
     * @return int Número de linhas efectivamente inseridas ou actualizadas
     */
    private function upsertTeams(array $teamsData): int
    {
        $rows = [];
        $now  = Carbon::now();

        foreach ($teamsData as $entry) {
            /** @var array<string, mixed> $team */
            $team = $entry['team'] ?? [];

            /** @var array<string, mixed> $venue */
            $venue = $entry['venue'] ?? null;

            /** @var array<string, mixed>|null $coach */
            $coach = $entry['coach'] ?? null;

            $apiId = isset($team['id']) ? (int) $team['id'] : null;

            if ($apiId === null) {
                Log::warning('[SyncTeamsJob] Entrada sem api_football_id ignorada.', [
                    'entry' => $team,
                ]);
                continue;
            }

            $rows[] = [
                'api_football_id' => $apiId,
                'name'            => (string) ($team['name'] ?? ''),
                'code'            => isset($team['code']) ? (string) $team['code'] : null,
                'country'         => isset($team['country']) ? (string) $team['country'] : null,
                'logo_url'        => isset($team['logo']) ? (string) $team['logo'] : null,
                // group_name não é fornecido pelo endpoint /teams; será preenchido
                // pelo SyncFixturesJob quando os grupos forem conhecidos.
                'group_name'      => null,
                // JSONB: serializar para string JSON — o driver PostgreSQL
                // aceita string e converte para jsonb automaticamente.
                'venue'           => $venue !== null ? json_encode($venue, JSON_THROW_ON_ERROR) : null,
                'coach'           => $coach !== null ? json_encode($coach, JSON_THROW_ON_ERROR) : null,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }

        if (empty($rows)) {
            return 0;
        }

        // upsert() do Laravel usa ON CONFLICT DO UPDATE em PostgreSQL.
        // Apenas os campos listados em `update` são modificados em caso de conflito.
        DB::table('teams')->upsert(
            $rows,
            uniqueBy: ['api_football_id'],
            update: [
                'name',
                'code',
                'country',
                'logo_url',
                'venue',
                'coach',
                'updated_at',
            ],
        );

        return count($rows);
    }

    /**
     * Extrai o header `x-ratelimit-requests-remaining` do payload (API-Football
     * não expõe este valor no corpo; o ApiFootballService não o captura
     * directamente nesta versão, por isso usamos o valor local do QuotaService
     * como fallback seguro).
     *
     * @param  array<string, mixed> $payload  Resposta completa da API
     */
    private function registerApiUsage(array $payload): void
    {
        // O valor authoritative de requests restantes vem do header HTTP.
        // Como o ApiFootballService retorna apenas o corpo JSON, usamos o valor
        // local da base de dados decrementado em 1 como estimativa conservadora.
        // Uma versão futura pode expor o header via ApiFootballService.
        $remaining = max(0, $this->quotaService->getRemainingBudget() - 1);

        $this->quotaService->recordUsage(self::ENDPOINT, self::LAYER, $remaining);
    }

    /**
     * Persiste uma linha em `sync_logs` para rastrear o ciclo de vida do job.
     *
     * Esta escrita usa DB::table() directamente para evitar dependência de um
     * Model que pode ainda não existir.  A tabela é definida na Migration 11.
     */
    private function writeSyncLog(
        string $status,
        int $recordsSynced,
        int $apiRequestsUsed,
        Carbon $startedAt,
        ?string $errorMessage = null,
    ): void {
        $completedAt  = Carbon::now();
        $durationMs   = (int) $startedAt->diffInMilliseconds($completedAt);

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
