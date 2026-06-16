<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Aqui estão registados todos os canais de broadcasting usados pela app.
|
| Canais públicos (Channel) — dados de jogo não requerem autenticação,
| qualquer visitante pode subscrever.
|
| Canais privados (PrivateChannel) — seriam necessários para dados
| sensíveis ou personalizados, mas não são usados no MVP.
|
| @see CONTEXT.md §12 (RF02) — escalações em tempo real via Reverb
*/

/*
|--------------------------------------------------------------------------
| Canal: fixtures.{fixtureId}
|--------------------------------------------------------------------------
|
| Canal público para broadcasts de um jogo específico.
|
| Eventos emitidos neste canal:
|   - LineupConfirmed  → escalações oficiais disponíveis (RF02)
|   - LiveScoreUpdated → actualizações de marcador (RF03, fase futura)
|
| O frontend subscreve assim (Laravel Echo):
|   Echo.channel(`fixtures.${fixtureId}`)
|       .listen('LineupConfirmed', callback)
|
| Por ser um canal público, não é necessária autorização — a linha abaixo
| existe apenas para documentar o canal e poderá ser usado para autorização
| futura de canais privados (PrivateChannel).
|
*/
// Canal público — nenhuma autorização necessária.
// A definição abaixo serve como documentação e referência.
// Broadcast::channel('fixtures.{fixtureId}', fn (): bool => true);
