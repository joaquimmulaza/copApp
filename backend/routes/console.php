<?php

declare(strict_types=1);

use App\Jobs\SyncFixturesJob;
use App\Jobs\SyncInjuriesJob;
use App\Jobs\SyncPlayersJob;
use App\Jobs\SyncStandingsJob;
use App\Jobs\SyncTeamsJob;
use App\Jobs\SyncTopStatsJob;
use Illuminate\Support\Facades\Schedule;

// =============================================================================
// CopApp — Task Scheduler  (routes/console.php)
//
// Todas as entradas seguem as frequências documentadas em CONTEXT.md §10.1.
// Cada job corre na fila Redis padrão ("default"), em modo de servidor único
// (->onOneServer()) para evitar execuções paralelas em ambientes multi-nó.
// O encadeamento ->withoutOverlapping() garante que uma execução em curso
// não é atropelada por uma nova invocação caso a API-Football responda com
// lentidão.
//
// Timezone: UTC — alinhado com os horários do spec e com os timestamps
// armazenados na coluna kickoff_utc da tabela fixtures.
// =============================================================================

// ─────────────────────────────────────────────────────────────────────────────
// CAMADA 1 — Dados estáticos (Layer 1 Static Data)
//
// Corre uma vez por dia no período de baixo tráfego (03:00–05:00 UTC).
// A sequência de horários respeita as dependências de FK:
//   Teams (03:00) → Players (03:30) → Fixtures (04:00) → TopStats (04:30)
// ─────────────────────────────────────────────────────────────────────────────

// Seleções participantes — endpoint /teams
// Custo: 1 request/dia.
Schedule::job(SyncTeamsJob::class, queue: 'default')
    ->dailyAt('03:00')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('sync-teams')
    ->description('Sincroniza as seleções do Mundial 2026 a partir da API-Football.');

// Jogadores — endpoint /players (paginado; auto-despacha páginas seguintes)
// A página 1 é disparada pelo scheduler via closure; as páginas 2…N são
// auto-despachadas pelo próprio SyncPlayersJob após cada resposta bem-sucedida.
// Custo: 2–3 requests/dia (dependente do número de páginas da API).
Schedule::call(static function (): void {
    SyncPlayersJob::dispatch(1);
})
    ->dailyAt('03:30')
    ->timezone('UTC')
    ->name('sync-players')
    ->withoutOverlapping()
    ->onOneServer()
    ->description('Sincroniza os jogadores do Mundial 2026 (página 1; restantes auto-despachadas).');



// Fixtures / Jogos — endpoint /fixtures
// Custo: 1 request/dia.
Schedule::job(SyncFixturesJob::class, queue: 'default')
    ->dailyAt('04:00')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('sync-fixtures')
    ->description('Sincroniza os 104 jogos do Mundial 2026 a partir da API-Football.');

// Estatísticas de topo — endpoints /players/topscorers + /players/topassists
// Custo: 2 requests/dia.
Schedule::job(SyncTopStatsJob::class, queue: 'default')
    ->dailyAt('04:30')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('sync-top-stats')
    ->description('Sincroniza artilheiros e assistentes de topo do Mundial 2026.');

// ─────────────────────────────────────────────────────────────────────────────
// CAMADA 2 — Dados semi-dinâmicos (Layer 2 Semi-Dynamic Data)
//
// Corre múltiplas vezes por dia para capturar actualizações sem polling contínuo.
// ─────────────────────────────────────────────────────────────────────────────

// Lesões e indisponibilidades — endpoint /injuries
// Três vezes por dia: manhã, meio-dia e final de tarde.
// Custo: 3 requests/dia.
Schedule::job(SyncInjuriesJob::class, queue: 'default')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('sync-injuries-07h')
    ->description('Sincroniza lesões — janela matinal (07:00 UTC).')
    ->dailyAt('07:00');

Schedule::job(SyncInjuriesJob::class, queue: 'default')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('sync-injuries-13h')
    ->description('Sincroniza lesões — janela de meio-dia (13:00 UTC).')
    ->dailyAt('13:00');

Schedule::job(SyncInjuriesJob::class, queue: 'default')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('sync-injuries-18h')
    ->description('Sincroniza lesões — janela de final de tarde (18:00 UTC).')
    ->dailyAt('18:00');

// Classificações — endpoint /standings
// Duas vezes por dia: logo após o fecho das jornadas.
// Custo: 2 requests/dia.
Schedule::job(SyncStandingsJob::class, queue: 'default')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('sync-standings-22h')
    ->description('Sincroniza classificações — fecho de jornada noturno (22:00 UTC).')
    ->dailyAt('22:00');

Schedule::job(SyncStandingsJob::class, queue: 'default')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('sync-standings-01h')
    ->description('Sincroniza classificações — fecho de jornada madrugada (01:00 UTC).')
    ->dailyAt('01:00');

// ─────────────────────────────────────────────────────────────────────────────
// CAMADA 3 — Dados em tempo real (Layer 3 Live Data)
//
// Corre a cada minuto para orquestrar o início do polling de escalações.
// ─────────────────────────────────────────────────────────────────────────────

Schedule::call(static function (\App\Services\LineupPollingService $service): void {
    $service->dispatchUpcomingPolls();
})
    ->everyMinute()
    ->timezone('UTC')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('orchestrate-lineup-polling')
    ->description('Descobre jogos a iniciar nos próximos 70 minutos e dispara o ciclo de polling de escalações.');

