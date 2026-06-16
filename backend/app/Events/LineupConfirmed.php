<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento de broadcast — Escalação oficial confirmada (RF02).
 *
 * Implementa `ShouldBroadcastNow` para garantir que o payload é emitido
 * **de forma síncrona** (sem passar pela fila de broadcasting) no momento
 * exacto em que `PollLineupJob` detecta as escalações. Isto minimiza a
 * latência entre a confirmação da API e a actualização do `LineupGrid.tsx`
 * no browser do utilizador.
 *
 * Canal público: `fixtures.{fixture_id}`
 *   - Prefixo `fixtures` agrupa todos os canais de jogos.
 *   - O `fixture_id` é o ID interno da tabela `fixtures` (PK), não o
 *     `api_football_id`. O frontend resolve este ID através do endpoint REST
 *     antes de subscrever o canal.
 *
 * O frontend (Laravel Echo + pusher-js) escuta o evento assim:
 * ```typescript
 * Echo.channel(`fixtures.${fixtureId}`)
 *     .listen('LineupConfirmed', (payload: LineupConfirmedPayload) => {
 *         queryClient.invalidateQueries(['lineup', fixtureId]);
 *     });
 * ```
 *
 * Payload estrutura:
 *   fixture_id          int     — ID interno da tabela fixtures
 *   api_fixture_id      int     — ID usado na API-Football (para referência)
 *   confirmed_at        string  — ISO 8601 UTC
 *   home_team           object  — { id, name, logo_url }
 *   away_team           object  — { id, name, logo_url }
 *   lineups             object  — { home: LineupData, away: LineupData }
 *
 * @see CONTEXT.md §10.1 (Camada 3), §11 (Pastas), §12 (RF02)
 * @see PollLineupJob — único produtor deste evento
 */
final class LineupConfirmed implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    // ─────────────────────────────────────────────────────────────────────────
    // Constructor
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @param  int                    $fixtureId      ID interno da tabela `fixtures` (PK).
     * @param  int                    $apiFixtureId   ID da API-Football (para referência no frontend).
     * @param  string                 $confirmedAt    Timestamp ISO 8601 UTC da confirmação.
     * @param  array<string, mixed>   $homeTeam       Dados da equipa da casa { id, name, logo_url }.
     * @param  array<string, mixed>   $awayTeam       Dados da equipa visitante { id, name, logo_url }.
     * @param  array<string, mixed>   $lineups        Escalações { home: LineupData, away: LineupData }.
     */
    public function __construct(
        public readonly int   $fixtureId,
        public readonly int   $apiFixtureId,
        public readonly string $confirmedAt,
        public readonly array  $homeTeam,
        public readonly array  $awayTeam,
        public readonly array  $lineups,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Broadcasting
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Canal público de broadcasting: `fixtures.{fixture_id}`.
     *
     * Canal público (sem autenticação) porque as escalações são dados
     * públicos que qualquer visitante da app pode consumir. Não existe
     * informação sensível no payload.
     *
     * @return Channel
     */
    public function broadcastOn(): Channel
    {
        return new Channel("fixtures.{$this->fixtureId}");
    }

    /**
     * Nome do evento tal como o frontend o vai escutar.
     *
     * Laravel usa por defeito o FQN da classe; ao sobrescrever este método
     * garantimos um nome de evento estável e independente de refactoring.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'LineupConfirmed';
    }

    /**
     * Payload completo enviado ao frontend.
     *
     * A estrutura é explícita para facilitar a tipagem TypeScript no
     * `LineupGrid.tsx` e evitar que propriedades privadas do modelo sejam
     * expostas acidentalmente.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'fixture_id'     => $this->fixtureId,
            'api_fixture_id' => $this->apiFixtureId,
            'confirmed_at'   => $this->confirmedAt,
            'home_team'      => $this->homeTeam,
            'away_team'      => $this->awayTeam,
            'lineups'        => $this->lineups,
        ];
    }
}
