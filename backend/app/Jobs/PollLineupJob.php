<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\LineupConfirmed;
use App\Services\ApiFootballQuotaServiceInterface;
use App\Services\ApiFootballService;
use App\Services\FcmNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Camada 3 — Polling Adaptativo de Escalações Oficiais (RF02).
 *
 * Este job implementa o ciclo de polling adaptativo para detectar o momento
 * exacto em que a API-Football disponibiliza as escalações oficiais de um
 * jogo, e propaga essa informação em tempo real para o frontend via
 * WebSockets (Laravel Reverb → evento `LineupConfirmed`).
 *
 * ── Ciclo de vida ───────────────────────────────────────────────────────────
 *
 *  1. Verifica quota (`canProceed('layer3')`).           → aborto silencioso.
 *  2. Verifica `lineup_confirmed` na BD.                 → encerra se já confirmado.
 *  3. Chama GET /fixtures?id={fixtureApiId}.
 *  4a. Se lineups disponíveis:
 *       → Upsert atómico em `fixture_lineups`.
 *       → Actualiza `fixtures` (lineup_confirmed + confirmed_at).
 *       → Dispara evento `LineupConfirmed`.
 *       → ENCERRA (não se reagenda).
 *  4b. Se lineups ainda não disponíveis:
 *       → Calcula tempo até kickoff.
 *       → Se > 30 min: reagenda em 10 min.
 *       → Se ≤ 30 min: reagenda em 5 min (maior frequência).
 *       → Se jogo começou há > 15 min sem escalação: ENCERRA (anti-loop).
 *
 * ── Estratégia de reagendamento ────────────────────────────────────────────
 *
 * Usa `$this->release(delay)` em vez de `self::dispatch()` para que o job
 * seja recolocado na fila como uma nova tentativa do mesmo job, mantendo o
 * estado e evitando a criação de instâncias duplicadas.
 *
 * ── Tratamento de erro ──────────────────────────────────────────────────────
 *
 * `$tries = 1` porque este job se auto-reagenda via `release()` em vez de
 * depender do sistema de retries do Laravel. Um erro inesperado numa
 * tentativa deve ser resolvido no próximo ciclo de polling, não forçado
 * imediatamente (o que poderia desperdiçar quota).
 *
 * ── Atomicidade ─────────────────────────────────────────────────────────────
 *
 * A escrita em `fixture_lineups` + actualização de `fixtures` é envolvida
 * numa única transação de base de dados. Se qualquer operação falhar, o
 * estado da BD permanece consistente e o evento NÃO é disparado.
 *
 * @see CONTEXT.md §10.1 (Camada 3), §11 (Pastas), §12 (RF02)
 * @see LineupConfirmed — evento disparado quando as escalações são confirmadas
 * @see ApiFootballQuotaService — semáforo de quota obrigatório
 */
class PollLineupJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Número de tentativas.
     *
     * Mantido em 1 porque este job usa `release()` para auto-reagendamento
     * controlado. O sistema de retries automático do Laravel não é adequado
     * aqui — queremos controlo explícito do intervalo entre tentativas.
     */
    public int $tries = 1;

    /**
     * Timeout do job em segundos.
     * A chamada à API e os writes na BD devem concluir bem dentro de 30 s.
     */
    public int $timeout = 30;

    /**
     * Nome do layer usado no semáforo de quota e nos logs.
     */
    private const string LAYER = 'layer3';

    /**
     * Endpoint relativo da API-Football para dados de um jogo específico.
     */
    private const string ENDPOINT = '/fixtures';

    /**
     * Minutos antes do kickoff para mudar para intervalo de alta frequência (5 min).
     */
    private const int HIGH_FREQ_THRESHOLD_MINUTES = 30;

    /**
     * Intervalo de polling (minutos) quando faltam mais de HIGH_FREQ_THRESHOLD_MINUTES.
     */
    private const int POLL_INTERVAL_NORMAL_MINUTES = 10;

    /**
     * Intervalo de polling (minutos) quando faltam menos de HIGH_FREQ_THRESHOLD_MINUTES.
     */
    private const int POLL_INTERVAL_HIGH_FREQ_MINUTES = 5;

    /**
     * Minutos após o kickoff sem escalação confirmada antes de encerrar o polling.
     * Evita loops infinitos de erro em jogos onde a API nunca publica lineups.
     */
    private const int ABORT_AFTER_KICKOFF_MINUTES = 15;

    // ─────────────────────────────────────────────────────────────────────────
    // Constructor
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @param  int                    $fixtureApiId  ID do jogo na API-Football (api_football_id na tabela fixtures).
     * @param  FcmNotificationService $fcmService    Serviço de push notifications FCM (RF02).
     */
    public function __construct(
        private readonly int $fixtureApiId,
        private readonly ApiFootballQuotaServiceInterface $quotaService,
        private readonly ApiFootballService $apiService,
        private readonly FcmNotificationService $fcmService,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Lógica principal
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Executa um ciclo de polling para verificar se as escalações estão disponíveis.
     */
    public function handle(): void
    {
        Log::info('[PollLineupJob] Ciclo iniciado.', [
            'fixture_api_id' => $this->fixtureApiId,
        ]);

        // ── 1. Verificar semáforo de quota ───────────────────────────────────
        if (! $this->quotaService->canProceed(self::LAYER)) {
            Log::warning('[PollLineupJob] Abortado — orçamento insuficiente para layer3.', [
                'fixture_api_id' => $this->fixtureApiId,
                'remaining'      => $this->quotaService->getRemainingBudget(),
            ]);

            return;
        }

        // ── 2. Verificar se a escalação já está confirmada na BD ─────────────
        // Evita requests desnecessários à API em corridas de fila (race conditions)
        // ou se o job foi despachado por engano para um jogo já processado.
        $fixtureRow = $this->loadFixtureRow();

        if ($fixtureRow === null) {
            Log::error('[PollLineupJob] Fixture não encontrado na BD — job encerrado.', [
                'fixture_api_id' => $this->fixtureApiId,
            ]);

            return;
        }

        if ((bool) $fixtureRow->lineup_confirmed === true) {
            Log::info('[PollLineupJob] Escalação já confirmada na BD — ciclo encerrado sem request à API.', [
                'fixture_api_id'      => $this->fixtureApiId,
                'fixture_id'          => $fixtureRow->id,
                'lineup_confirmed_at' => $fixtureRow->lineup_confirmed_at,
            ]);

            return;
        }

        // ── 3. Chamada à API-Football ────────────────────────────────────────
        $payload = $this->apiService->get(self::ENDPOINT, ['id' => $this->fixtureApiId]);

        // Registar o consumo de quota independentemente do resultado da call.
        $remaining = max(0, $this->quotaService->getRemainingBudget() - 1);
        $this->quotaService->recordUsage(self::ENDPOINT, self::LAYER, $remaining);

        if ($payload === null) {
            Log::error('[PollLineupJob] API-Football não devolveu resposta válida.', [
                'fixture_api_id' => $this->fixtureApiId,
                'endpoint'       => self::ENDPOINT,
            ]);

            // Falha de rede — reagendar para tentar novamente no próximo ciclo.
            $kickoffUtc = Carbon::parse((string) $fixtureRow->kickoff_utc);
            $this->reschedule($kickoffUtc);

            return;
        }

        // ── 4. Analisar o payload e decidir o próximo passo ─────────────────
        /** @var array<string, mixed> $response */
        $response = $payload['response'] ?? [];

        if (empty($response)) {
            Log::warning('[PollLineupJob] Payload vazio da API — nenhum fixture encontrado.', [
                'fixture_api_id' => $this->fixtureApiId,
                'api_errors'     => $payload['errors'] ?? [],
            ]);

            $kickoffUtc = Carbon::parse((string) $fixtureRow->kickoff_utc);
            $this->reschedule($kickoffUtc);

            return;
        }

        /** @var array<string, mixed> $fixtureData */
        $fixtureData = (array) ($response[0] ?? []);

        /** @var list<array<string, mixed>> $apiLineups */
        $apiLineups = (array) ($fixtureData['lineups'] ?? []);

        $kickoffUtc = Carbon::parse((string) $fixtureRow->kickoff_utc);

        // ── 4a. Escalações disponíveis ───────────────────────────────────────
        if (! empty($apiLineups)) {
            Log::info('[PollLineupJob] Escalações detectadas — a persistir e a disparar evento.', [
                'fixture_api_id' => $this->fixtureApiId,
                'fixture_id'     => $fixtureRow->id,
                'teams_count'    => count($apiLineups),
            ]);

            $this->persistLineupsAndFireEvent($fixtureRow, $fixtureData, $apiLineups, $kickoffUtc);

            return; // Ciclo terminado — NÃO reagendar.
        }

        // ── 4b. Escalações ainda não disponíveis ────────────────────────────
        Log::info('[PollLineupJob] Escalações ainda não disponíveis — a calcular próximo ciclo.', [
            'fixture_api_id' => $this->fixtureApiId,
            'kickoff_utc'    => $kickoffUtc->toIso8601String(),
            'now_utc'        => Carbon::now()->toIso8601String(),
        ]);

        $this->reschedule($kickoffUtc);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Tratamento de falhas do job
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Invocado pelo framework quando o job falha definitivamente.
     * Com `$tries = 1`, este método é chamado logo na primeira falha não capturada.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[PollLineupJob] Falha inesperada — job terminado.', [
            'fixture_api_id' => $this->fixtureApiId,
            'error'          => $exception->getMessage(),
            'trace'          => $exception->getTraceAsString(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Métodos privados de suporte
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Carrega a linha do fixture da BD pelo `api_football_id`.
     *
     * Selecciona apenas as colunas necessárias para minimizar o payload
     * da query e evitar carregar `raw_api_data` (potencialmente grande).
     *
     * @return \stdClass|null
     */
    private function loadFixtureRow(): ?\stdClass
    {
        /** @var \stdClass|null $row */
        $row = DB::table('fixtures')
            ->where('api_football_id', $this->fixtureApiId)
            ->select([
                'id',
                'api_football_id',
                'home_team_id',
                'away_team_id',
                'kickoff_utc',
                'lineup_confirmed',
                'lineup_confirmed_at',
            ])
            ->first();

        return $row;
    }

    /**
     * Calcula o intervalo de reagendamento e decide se deve encerrar o polling.
     *
     * Regras de polling adaptativo (CONTEXT.md §10.1 Camada 3):
     *   - Mais de 30 min até kickoff  → reagendar em 10 min.
     *   - 30 min ou menos até kickoff → reagendar em 5 min.
     *   - Mais de 15 min após kickoff → encerrar (anti-loop de erro).
     *
     * @param  Carbon  $kickoffUtc  Hora de início do jogo em UTC.
     */
    private function reschedule(Carbon $kickoffUtc): void
    {
        $now               = Carbon::now();
        $minutesUntilKickoff = $now->diffInMinutes($kickoffUtc, absolute: false);
        // diffInMinutes com absolute: false é positivo se kickoff está no futuro,
        // negativo se kickoff já passou.
        $minutesSinceKickoff = $now->diffInMinutes($kickoffUtc, absolute: true);
        $kickoffHasPassed    = $now->isAfter($kickoffUtc);

        // ── Anti-loop: jogo começou há mais de ABORT_AFTER_KICKOFF_MINUTES ──
        if ($kickoffHasPassed && $minutesSinceKickoff > self::ABORT_AFTER_KICKOFF_MINUTES) {
            Log::warning('[PollLineupJob] Jogo começou há ' . $minutesSinceKickoff . ' min sem escalação — polling encerrado (anti-loop).', [
                'fixture_api_id'       => $this->fixtureApiId,
                'kickoff_utc'          => $kickoffUtc->toIso8601String(),
                'minutes_since_kickoff' => $minutesSinceKickoff,
                'abort_threshold_min'  => self::ABORT_AFTER_KICKOFF_MINUTES,
            ]);

            return; // Encerrar sem reagendar.
        }

        // ── Polling adaptativo ───────────────────────────────────────────────
        if (! $kickoffHasPassed && $minutesUntilKickoff > self::HIGH_FREQ_THRESHOLD_MINUTES) {
            // Mais de 30 min para o kickoff → intervalo normal de 10 min.
            Log::info('[PollLineupJob] Reagendado em ' . self::POLL_INTERVAL_NORMAL_MINUTES . ' min (falta ' . $minutesUntilKickoff . ' min para kickoff).', [
                'fixture_api_id'       => $this->fixtureApiId,
                'minutes_until_kickoff' => $minutesUntilKickoff,
                'next_poll_in_minutes' => self::POLL_INTERVAL_NORMAL_MINUTES,
            ]);

            $this->release(now()->addMinutes(self::POLL_INTERVAL_NORMAL_MINUTES));
        } else {
            // 30 min ou menos até kickoff (ou já passou mas dentro do limite) → alta frequência.
            Log::info('[PollLineupJob] Reagendado em ' . self::POLL_INTERVAL_HIGH_FREQ_MINUTES . ' min (alta frequência — ' . ($kickoffHasPassed ? $minutesSinceKickoff . ' min após kickoff' : $minutesUntilKickoff . ' min para kickoff') . ').', [
                'fixture_api_id'       => $this->fixtureApiId,
                'kickoff_has_passed'   => $kickoffHasPassed,
                'minutes_until_kickoff' => $kickoffHasPassed ? -$minutesSinceKickoff : $minutesUntilKickoff,
                'next_poll_in_minutes' => self::POLL_INTERVAL_HIGH_FREQ_MINUTES,
            ]);

            $this->release(now()->addMinutes(self::POLL_INTERVAL_HIGH_FREQ_MINUTES));
        }
    }

    /**
     * Persiste as escalações na BD de forma atómica e dispara o evento de broadcast.
     *
     * ── Transação atómica ────────────────────────────────────────────────────
     * Todas as escritas (fixture_lineups × 2 + fixtures update) estão dentro
     * de um único bloco `DB::transaction()`. Se qualquer operação falhar, a BD
     * permanece no estado anterior e o evento NÃO é disparado.
     *
     * ── Estrutura do payload `lineups` (API-Football v3) ────────────────────
     * [
     *   {
     *     "team":      { "id": 6, "name": "Brazil", "logo": "..." },
     *     "coach":     { "id": 1, "name": "Dorival", "photo": "..." },
     *     "formation": "4-3-3",
     *     "startXI":   [ { "player": { "id": 1, "name": "Alisson", "number": 1, "pos": "G", "grid": "1:1" } }, ... ],
     *     "substitutes": [ ... ]
     *   },
     *   { ... }  (segundo team)
     * ]
     *
     * @param  \stdClass                    $fixtureRow   Linha da tabela `fixtures` (ID interno).
     * @param  array<string, mixed>         $fixtureData  Payload completo do jogo da API.
     * @param  list<array<string, mixed>>   $apiLineups   Array de escalações da API (2 equipas).
     * @param  Carbon                       $kickoffUtc   Hora de início do jogo.
     */
    private function persistLineupsAndFireEvent(
        \stdClass $fixtureRow,
        array $fixtureData,
        array $apiLineups,
        Carbon $kickoffUtc,
    ): void {
        $fixtureId   = (int) $fixtureRow->id;
        $confirmedAt = Carbon::now();

        // Pré-carregar o mapa [api_football_id → teams.id] para resolver
        // os team_ids internos sem N+1 queries dentro da transação.
        $teamIdMap = $this->buildTeamIdMap();

        // Construir as linhas para upsert em `fixture_lineups`.
        $lineupRows  = $this->buildLineupRows($apiLineups, $fixtureId, $teamIdMap, $confirmedAt);

        // Construir o payload completo do evento (fora da transação para não
        // bloquear o lock da transação enquanto formatamos dados).
        $eventPayload = $this->buildEventPayload($fixtureRow, $fixtureData, $apiLineups, $confirmedAt);

        // ── Transação atómica ────────────────────────────────────────────────
        DB::transaction(function () use ($fixtureId, $lineupRows, $confirmedAt): void {

            if (! empty($lineupRows)) {
                // Upsert das escalações — idempotente graças ao unique(fixture_id, team_id).
                DB::table('fixture_lineups')->upsert(
                    $lineupRows,
                    uniqueBy: ['fixture_id', 'team_id'],
                    update: [
                        'formation',
                        'starting_xi',
                        'substitutes',
                        'coach',
                        'is_confirmed',
                        'confirmed_at',
                        'updated_at',
                    ],
                );

                Log::info('[PollLineupJob] fixture_lineups guardados.', [
                    'fixture_id'  => $fixtureId,
                    'rows_upserted' => count($lineupRows),
                ]);
            }

            // Marcar o fixture como confirmado.
            $updated = DB::table('fixtures')
                ->where('id', $fixtureId)
                ->update([
                    'lineup_confirmed'    => true,
                    'lineup_confirmed_at' => $confirmedAt,
                    'updated_at'          => $confirmedAt,
                ]);

            Log::info('[PollLineupJob] fixtures.lineup_confirmed actualizado.', [
                'fixture_id' => $fixtureId,
                'rows_updated' => $updated,
                'confirmed_at' => $confirmedAt->toIso8601String(),
            ]);
        });

        // ── Disparar evento de broadcast (fora da transação) ─────────────────
        // O evento é disparado após a transação para garantir que os dados
        // estão comprometidos na BD antes de os clientes WebSocket receberem
        // a notificação e fazerem o seu pedido REST para buscar as escalações.
        LineupConfirmed::dispatch(
            fixtureId:    $eventPayload['fixture_id'],
            apiFixtureId: $eventPayload['api_fixture_id'],
            confirmedAt:  $eventPayload['confirmed_at'],
            homeTeam:     $eventPayload['home_team'],
            awayTeam:     $eventPayload['away_team'],
            lineups:      $eventPayload['lineups'],
        );

        Log::info('[PollLineupJob] Evento LineupConfirmed disparado com sucesso.', [
            'fixture_id'     => $fixtureId,
            'api_fixture_id' => $this->fixtureApiId,
            'channel'        => "fixtures.{$fixtureId}",
        ]);

        // ── Disparar notificação Push via FCM (RF02) ──────────────────────────
        // Executado após o WebSocket para manter a mesma ordem de prioridade:
        // WebSocket (tempo real no browser) → FCM (utilizadores com app fechada).
        // Os nomes das equipas são extraídos do payload do evento já construído.
        $homeTeamName = (string) ($eventPayload['home_team']['name'] ?? 'Equipa A');
        $awayTeamName = (string) ($eventPayload['away_team']['name'] ?? 'Equipa B');

        $this->fcmService->sendLineupConfirmedNotification(
            fixtureId: $fixtureId,
            homeTeam:  $homeTeamName,
            awayTeam:  $awayTeamName,
        );

        Log::info('[PollLineupJob] Push FCM solicitado ao FcmNotificationService.', [
            'fixture_id'  => $fixtureId,
            'home_team'   => $homeTeamName,
            'away_team'   => $awayTeamName,
        ]);
    }

    /**
     * Constrói as linhas para upsert na tabela `fixture_lineups`.
     *
     * Uma linha por equipa (máximo 2 por jogo). Se o team_id não for
     * resolvido (equipa não existe na BD), a linha é ignorada com log de aviso.
     *
     * @param  list<array<string, mixed>>  $apiLineups   Escalações da API.
     * @param  int                         $fixtureId    ID interno do jogo.
     * @param  array<int, int>             $teamIdMap    Mapa api_football_id → teams.id.
     * @param  Carbon                      $now          Timestamp de escrita.
     * @return list<array<string, mixed>>                Linhas para upsert.
     */
    private function buildLineupRows(
        array $apiLineups,
        int $fixtureId,
        array $teamIdMap,
        Carbon $now,
    ): array {
        $rows = [];

        foreach ($apiLineups as $lineupData) {
            /** @var array<string, mixed> $lineupData */

            /** @var array<string, mixed> $teamInfo */
            $teamInfo  = (array) ($lineupData['team'] ?? []);
            $teamApiId = isset($teamInfo['id']) ? (int) $teamInfo['id'] : null;

            if ($teamApiId === null) {
                Log::warning('[PollLineupJob] Escalação sem team.id — ignorada.', [
                    'fixture_id' => $fixtureId,
                ]);

                continue;
            }

            $teamId = $teamIdMap[$teamApiId] ?? null;

            if ($teamId === null) {
                Log::warning('[PollLineupJob] Equipa não encontrada na BD — escalação ignorada.', [
                    'fixture_id'  => $fixtureId,
                    'api_team_id' => $teamApiId,
                    'team_name'   => $teamInfo['name'] ?? 'desconhecido',
                ]);

                continue;
            }

            /** @var array<string, mixed> $coachInfo */
            $coachInfo = (array) ($lineupData['coach'] ?? []);

            // Normalizar startXI: extrair o objecto `player` de cada entrada.
            /** @var list<array<string, mixed>> $startXI */
            $startXI = array_map(
                static fn (mixed $entry): array => (array) ((array) $entry)['player'] ?? [],
                (array) ($lineupData['startXI'] ?? []),
            );

            // Normalizar substitutes: mesma estrutura que startXI.
            /** @var list<array<string, mixed>> $substitutes */
            $substitutes = array_map(
                static fn (mixed $entry): array => (array) ((array) $entry)['player'] ?? [],
                (array) ($lineupData['substitutes'] ?? []),
            );

            $rows[] = [
                'fixture_id'  => $fixtureId,
                'team_id'     => $teamId,
                'formation'   => isset($lineupData['formation']) ? (string) $lineupData['formation'] : null,
                'starting_xi' => json_encode($startXI, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'substitutes' => json_encode($substitutes, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'coach'       => ! empty($coachInfo)
                    ? json_encode($coachInfo, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
                    : null,
                'is_confirmed' => true,
                'confirmed_at' => $now,
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
        }

        return $rows;
    }

    /**
     * Constrói o payload estruturado para o evento `LineupConfirmed`.
     *
     * O payload inclui dados das equipas (para o frontend renderizar os headers
     * das colunas) e as escalações normalizadas (para o `LineupGrid.tsx`).
     *
     * @param  \stdClass                   $fixtureRow   Linha da tabela `fixtures`.
     * @param  array<string, mixed>        $fixtureData  Payload completo do jogo da API.
     * @param  list<array<string, mixed>>  $apiLineups   Escalações da API.
     * @param  Carbon                      $confirmedAt  Timestamp da confirmação.
     * @return array<string, mixed>
     */
    private function buildEventPayload(
        \stdClass $fixtureRow,
        array $fixtureData,
        array $apiLineups,
        Carbon $confirmedAt,
    ): array {
        // Resolver dados das equipas para o payload do evento.
        $homeTeamInfo = $this->resolveTeamInfo((int) $fixtureRow->home_team_id);
        $awayTeamInfo = $this->resolveTeamInfo((int) $fixtureRow->away_team_id);

        // Indexar as escalações por api_team_id para acesso O(1).
        $lineupsByApiTeamId = [];

        foreach ($apiLineups as $lineupData) {
            /** @var array<string, mixed> $lineupData */
            $teamApiId = (int) (((array) ($lineupData['team'] ?? []))['id'] ?? 0);

            if ($teamApiId > 0) {
                $lineupsByApiTeamId[$teamApiId] = $lineupData;
            }
        }

        return [
            'fixture_id'     => (int) $fixtureRow->id,
            'api_fixture_id' => $this->fixtureApiId,
            'confirmed_at'   => $confirmedAt->toIso8601String(),
            'home_team'      => $homeTeamInfo,
            'away_team'      => $awayTeamInfo,
            'lineups'        => $lineupsByApiTeamId,
        ];
    }

    /**
     * Resolve os dados públicos de uma equipa (nome, logo) pelo ID interno.
     *
     * Usado para enriquecer o payload do evento sem expor IDs sensíveis.
     *
     * @param  int  $teamId  ID interno da tabela `teams`.
     * @return array<string, mixed>
     */
    private function resolveTeamInfo(int $teamId): array
    {
        /** @var \stdClass|null $team */
        $team = DB::table('teams')
            ->where('id', $teamId)
            ->select(['id', 'api_football_id', 'name', 'logo_url'])
            ->first();

        if ($team === null) {
            return ['id' => $teamId, 'name' => 'Desconhecido', 'logo_url' => null];
        }

        return [
            'id'             => (int) $team->id,
            'api_football_id' => (int) $team->api_football_id,
            'name'           => (string) $team->name,
            'logo_url'       => $team->logo_url !== null ? (string) $team->logo_url : null,
        ];
    }

    /**
     * Carrega todos os teams da BD num mapa [api_football_id → id].
     *
     * Pré-carrega numa única query para evitar N+1 dentro do loop de
     * construção das linhas de escalação.
     *
     * @return array<int, int>  Mapa api_football_id → teams.id
     */
    private function buildTeamIdMap(): array
    {
        /** @var list<\stdClass> $teams */
        $teams = DB::table('teams')
            ->select(['id', 'api_football_id'])
            ->get()
            ->all();

        $map = [];

        foreach ($teams as $team) {
            $map[(int) $team->api_football_id] = (int) $team->id;
        }

        return $map;
    }
}
