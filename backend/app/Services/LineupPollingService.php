<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\PollLineupJob;
use App\Services\ApiFootballQuotaServiceInterface;
use App\Services\ApiFootballService;
use App\Services\FcmNotificationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Camada 3 — Orquestrador de Polling de Escalações (RF02).
 * 
 * Serviço responsável por descobrir quais jogos estão prestes a começar
 * e iniciar o ciclo de polling adaptativo (PollLineupJob) para cada um deles.
 */
final readonly class LineupPollingService
{
    /**
     * Janela de tempo antes do jogo para começar o polling (em minutos).
     */
    private const int POLLING_WINDOW_MINUTES = 70;

    /**
     * Tempo de expiração do lock (em segundos) no Redis.
     * Mantém o lock vivo por 2 horas (7200s), garantindo que
     * não relançamos o mesmo job repetidamente para o mesmo fixture.
     */
    private const int LOCK_TTL_SECONDS = 7200;

    public function __construct(
        private ApiFootballQuotaServiceInterface $quotaService,
        private ApiFootballService $apiService,
        private FcmNotificationService $fcmService,
    ) {}

    /**
     * Procura jogos a iniciar nos próximos 70 minutos e dispara o PollLineupJob
     * de forma segura (com locks) para evitar duplicados.
     */
    public function dispatchUpcomingPolls(): void
    {
        $now = Carbon::now();
        
        // Limite superior: 70 minutos no futuro.
        $windowEnd = $now->copy()->addMinutes(self::POLLING_WINDOW_MINUTES);
        
        // Limite inferior: 3 horas no passado (segurança para jogos muito atrasados que ainda estão 'NS').
        $windowStart = $now->copy()->subHours(3);

        $fixtures = DB::table('fixtures')
            ->where('kickoff_utc', '>=', $windowStart)
            ->where('kickoff_utc', '<=', $windowEnd)
            ->where('status_short', 'NS')
            ->where('lineup_confirmed', false)
            ->select(['id', 'api_football_id', 'kickoff_utc'])
            ->get();

        if ($fixtures->isEmpty()) {
            return;
        }

        foreach ($fixtures as $fixture) {
            $apiFixtureId = (int) $fixture->api_football_id;
            
            // Chave de lock única por fixture para evitar race conditions
            // e garantir que o polling job só é lançado uma vez por jogo.
            $lockKey = "lineup_polling_dispatched_{$apiFixtureId}";

            // Cache::add devolve false se a chave já existir.
            // O lock durará o suficiente para o jogo começar e terminar.
            $lockAcquired = Cache::add($lockKey, true, self::LOCK_TTL_SECONDS);

            if ($lockAcquired) {
                Log::info('[LineupPollingService] Jogo na janela de polling. Disparando PollLineupJob.', [
                    'fixture_id'      => $fixture->id,
                    'api_football_id' => $apiFixtureId,
                    'kickoff_utc'     => $fixture->kickoff_utc,
                ]);

                try {
                    PollLineupJob::dispatch(
                        $apiFixtureId,
                        $this->quotaService,
                        $this->apiService,
                        $this->fcmService
                    )->onQueue('default');
                } catch (Throwable $exception) {
                    Log::error('[LineupPollingService] Erro ao disparar PollLineupJob.', [
                        'fixture_id'      => $fixture->id,
                        'api_football_id' => $apiFixtureId,
                        'error'           => $exception->getMessage(),
                        'trace'           => $exception->getTraceAsString(),
                    ]);
                    
                    // Em caso de falha no dispatch, removemos o lock para que
                    // a próxima iteração do cron (1 minuto depois) tente novamente.
                    Cache::forget($lockKey);
                }
            }
        }
    }
}
