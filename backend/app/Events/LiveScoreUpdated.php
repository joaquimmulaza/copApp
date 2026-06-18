<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento de broadcast — Atualização do placar ao vivo (RF03).
 *
 * Implementa `ShouldBroadcastNow` para garantir que o payload é emitido
 * **de forma síncrona** (sem passar pela fila de broadcasting) no momento
 * exato em que o polling detecta alterações no placar. Isto minimiza a
 * latência entre a atualização na API e a exibição em tempo real no frontend.
 *
 * Canal público: `fixtures.{fixture_id}`
 *   - Prefixo `fixtures` agrupa todos os canais de jogos.
 *   - O `fixture_id` é o ID interno da tabela `fixtures` (PK). O frontend
 *     utiliza este ID para subscrever o canal correto.
 *
 * O frontend (Laravel Echo + pusher-js) escuta o evento assim:
 * ```typescript
 * Echo.channel(`fixtures.${fixtureId}`)
 *     .listen('LiveScoreUpdated', (payload: LiveScoreUpdatedPayload) => {
 *         // Lógica para atualizar placar na UI (ex: QueryClient.setQueryData)
 *     });
 * ```
 *
 * Payload estrutura:
 *   fixture_id          int     — ID interno da tabela fixtures
 *   status_short        string  — Status curto do jogo (ex: '1H', '2H', 'FT')
 *   elapsed_minutes     int|null— Minutos decorridos de jogo
 *   home_score          int|null— Golos atuais da equipa da casa
 *   away_score          int|null— Golos atuais da equipa visitante
 *
 * @see CONTEXT.md
 */
final class LiveScoreUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    // ─────────────────────────────────────────────────────────────────────────
    // Constructor
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @param  int       $fixtureId      ID interno da tabela `fixtures` (PK).
     * @param  string    $statusShort    Status curto do jogo (ex: '1H', '2H', 'FT').
     * @param  int|null  $elapsedMinutes Minutos decorridos.
     * @param  int|null  $homeScore      Golos da equipa da casa.
     * @param  int|null  $awayScore      Golos da equipa visitante.
     */
    public function __construct(
        public readonly int $fixtureId,
        public readonly string $statusShort,
        public readonly ?int $elapsedMinutes,
        public readonly ?int $homeScore,
        public readonly ?int $awayScore,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Broadcasting
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Canal público de broadcasting: `fixtures.{fixture_id}`.
     *
     * Canal público (sem autenticação) porque os placares são dados
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
        return 'LiveScoreUpdated';
    }

    /**
     * Payload completo enviado ao frontend.
     *
     * A estrutura é explícita para facilitar a tipagem TypeScript no
     * frontend e evitar que propriedades privadas do modelo sejam
     * expostas acidentalmente.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'fixture_id'      => $this->fixtureId,
            'status_short'    => $this->statusShort,
            'elapsed_minutes' => $this->elapsedMinutes,
            'home_score'      => $this->homeScore,
            'away_score'      => $this->awayScore,
        ];
    }
}
