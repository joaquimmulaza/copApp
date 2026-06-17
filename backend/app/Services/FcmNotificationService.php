<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Exception\Messaging\InvalidMessage;
use Kreait\Firebase\Exception\FirebaseException;

/**
 * FcmNotificationService — Envio de Push Notifications via Firebase Cloud Messaging (RF02).
 *
 * Responsabilidades:
 *  1. Inicializar o cliente FCM a partir das credenciais configuradas em `.env`
 *     via `FIREBASE_CREDENTIALS_PATH` (nunca directamente em código).
 *  2. Enviar notificações em batch para todos os tokens activos na tabela
 *     `push_subscriptions`.
 *  3. Capturar excepções do Firebase e remover tokens expirados/revogados da BD
 *     para manter a lista limpa e evitar desperdício de quota.
 *
 * ── Autenticação ─────────────────────────────────────────────────────────────
 *   O SDK `kreait/firebase-php` suporta Service Account JSON. O caminho para
 *   o ficheiro é lido de `config('services.firebase.credentials_path')` que
 *   mapeia para `FIREBASE_CREDENTIALS_PATH` no `.env`.
 *
 * ── Batch sending ────────────────────────────────────────────────────────────
 *   FCM suporta até 500 tokens por batch. Este serviço usa `sendEachForMulticast()`
 *   que retorna um `MulticastSendReport` com o resultado individual de cada token,
 *   permitindo identificar e purgar tokens inválidos sem falhar toda a operação.
 *
 * ── Segurança de credenciais ─────────────────────────────────────────────────
 *   - O caminho das credenciais NUNCA é exposto nos logs.
 *   - Os tokens FCM são truncados nos logs (primeiros 20 caracteres + "…").
 *   - A chave privada do Service Account permanece exclusivamente no ficheiro JSON.
 *
 * @see CONTEXT.md §4 (FIREBASE_CREDENTIALS_PATH), §9.2 Migration 10, §12 RF02
 * @see PollLineupJob — invoca este serviço após confirmar uma escalação
 */
final class FcmNotificationService
{
    /**
     * Número máximo de tokens por batch FCM.
     * O limite oficial do FCM é 500; mantemos uma margem de segurança.
     */
    private const int BATCH_SIZE = 490;

    /**
     * Envia uma notificação push de "Escalação Confirmada" para todos os
     * subscritores activos que têm `notify_lineups = true`.
     *
     * Fluxo:
     *  1. Valida as credenciais Firebase.
     *  2. Inicializa o cliente FCM.
     *  3. Busca todos os tokens activos da BD.
     *  4. Constrói a mensagem FCM com título, corpo e payload de dados.
     *  5. Envia em batches de até BATCH_SIZE tokens.
     *  6. Processa o relatório: remove tokens expirados/revogados.
     *
     * @param  int     $fixtureId  ID interno do jogo na tabela `fixtures`.
     * @param  string  $homeTeam   Nome da equipa da casa (ex: "Brasil").
     * @param  string  $awayTeam   Nome da equipa visitante (ex: "Portugal").
     */
    public function sendLineupConfirmedNotification(
        int    $fixtureId,
        string $homeTeam,
        string $awayTeam,
    ): void {
        Log::info('[FcmNotificationService] A iniciar envio de notificação de escalação confirmada.', [
            'fixture_id' => $fixtureId,
            'home_team'  => $homeTeam,
            'away_team'  => $awayTeam,
        ]);

        // ── 1. Validar credenciais ──────────────────────────────────────────────
        /** @var string $credentialsPath */
        $credentialsPath = config('services.firebase.credentials_path', '');

        if (empty($credentialsPath)) {
            Log::error('[FcmNotificationService] FIREBASE_CREDENTIALS_PATH não está configurado. Push abortado.', [
                'fixture_id' => $fixtureId,
            ]);

            return;
        }

        if (! file_exists($credentialsPath)) {
            Log::error('[FcmNotificationService] Ficheiro de credenciais Firebase não encontrado. Push abortado.', [
                'fixture_id'     => $fixtureId,
                // Nunca logar o caminho completo em produção; usar apenas o basename.
                'credentials_file' => basename($credentialsPath),
            ]);

            return;
        }

        // ── 2. Inicializar cliente FCM ──────────────────────────────────────────
        try {
            $factory   = (new Factory())->withServiceAccount($credentialsPath);
            $messaging = $factory->createMessaging();
        } catch (FirebaseException $e) {
            Log::error('[FcmNotificationService] Falha ao inicializar o cliente Firebase.', [
                'fixture_id' => $fixtureId,
                'error'      => $e->getMessage(),
            ]);

            return;
        } catch (\Throwable $e) {
            Log::error('[FcmNotificationService] Erro inesperado ao inicializar Firebase.', [
                'fixture_id' => $fixtureId,
                'error'      => $e->getMessage(),
            ]);

            return;
        }

        // ── 3. Buscar tokens activos da BD ─────────────────────────────────────
        /** @var list<string> $tokens */
        $tokens = DB::table('push_subscriptions')
            ->where('notify_lineups', true)
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token')
            ->all();

        if (empty($tokens)) {
            Log::info('[FcmNotificationService] Nenhum token activo com notify_lineups=true. Push ignorado.', [
                'fixture_id' => $fixtureId,
            ]);

            return;
        }

        Log::info('[FcmNotificationService] Tokens activos encontrados para envio.', [
            'fixture_id'   => $fixtureId,
            'total_tokens' => count($tokens),
        ]);

        // ── 4. Construir a mensagem FCM ─────────────────────────────────────────
        $notification = Notification::create(
            title: 'Escalação Confirmada ⚽',
            body:  "Os titulares de {$homeTeam} vs {$awayTeam} já estão disponíveis!",
        );

        // Payload de dados (acessível no service worker mesmo com app fechada).
        $data = [
            'type'       => 'lineup_confirmed',
            'fixture_id' => (string) $fixtureId,
            'url'        => "/fixtures/{$fixtureId}",
            'home_team'  => $homeTeam,
            'away_team'  => $awayTeam,
        ];

        // ── 5. Enviar em batches ────────────────────────────────────────────────
        $batches      = array_chunk($tokens, self::BATCH_SIZE);
        $totalSent    = 0;
        $totalFailed  = 0;
        $tokensToDelete = [];

        foreach ($batches as $batchIndex => $batchTokens) {
            Log::info('[FcmNotificationService] A processar batch.', [
                'fixture_id'   => $fixtureId,
                'batch_index'  => $batchIndex + 1,
                'total_batches' => count($batches),
                'batch_size'   => count($batchTokens),
            ]);

            try {
                // Construir uma CloudMessage para multicast.
                $message = CloudMessage::new()
                    ->withNotification($notification)
                    ->withData($data);

                // sendEachForMulticast envia individualmente e retorna um relatório
                // com sucesso/falha por token — sem falhar toda a batch numa falha parcial.
                $report = $messaging->sendEachForMulticast($message, $batchTokens);

                // ── 6. Processar relatório do batch ──────────────────────────────
                $totalSent   += $report->successes()->count();
                $totalFailed += $report->failures()->count();

                // Identificar tokens inválidos para remoção posterior.
                foreach ($report->failures()->getItems() as $failure) {
                    $failedToken = $failure->target()->value();
                    $error       = $failure->error();

                    $isStale = $error instanceof NotFound
                        || $error instanceof InvalidMessage
                        || (
                            $error !== null
                            && str_contains((string) $error->getMessage(), 'UNREGISTERED')
                        );

                    if ($isStale) {
                        $tokensToDelete[] = $failedToken;

                        Log::info('[FcmNotificationService] Token inválido/expirado marcado para remoção.', [
                            'fixture_id'   => $fixtureId,
                            'token_prefix' => mb_substr($failedToken, 0, 20) . '…',
                            'error_class'  => $error !== null ? get_class($error) : 'unknown',
                        ]);
                    } else {
                        // Erros transientes (INTERNAL, QUOTA_EXCEEDED) — logar mas não remover.
                        Log::warning('[FcmNotificationService] Falha transiente no envio de token.', [
                            'fixture_id'   => $fixtureId,
                            'token_prefix' => mb_substr($failedToken, 0, 20) . '…',
                            'error'        => $error !== null ? $error->getMessage() : 'desconhecido',
                        ]);
                    }
                }

            } catch (FirebaseException $e) {
                Log::error('[FcmNotificationService] Erro Firebase no batch — batch ignorado.', [
                    'fixture_id'  => $fixtureId,
                    'batch_index' => $batchIndex + 1,
                    'error'       => $e->getMessage(),
                ]);

                $totalFailed += count($batchTokens);

            } catch (\Throwable $e) {
                Log::error('[FcmNotificationService] Erro inesperado no batch — batch ignorado.', [
                    'fixture_id'  => $fixtureId,
                    'batch_index' => $batchIndex + 1,
                    'error'       => $e->getMessage(),
                ]);

                $totalFailed += count($batchTokens);
            }
        }

        // ── 7. Remover tokens expirados/revogados da BD ──────────────────────
        if (! empty($tokensToDelete)) {
            $deleted = DB::table('push_subscriptions')
                ->whereIn('fcm_token', $tokensToDelete)
                ->delete();

            Log::info('[FcmNotificationService] Tokens expirados removidos da BD.', [
                'fixture_id'     => $fixtureId,
                'tokens_deleted' => $deleted,
            ]);
        }

        // ── 8. Log de sumário do ciclo de envio ──────────────────────────────
        Log::info('[FcmNotificationService] Ciclo de envio concluído.', [
            'fixture_id'       => $fixtureId,
            'total_tokens'     => count($tokens),
            'total_sent'       => $totalSent,
            'total_failed'     => $totalFailed,
            'tokens_purged'    => count($tokensToDelete),
            'batches_processed' => count($batches),
        ]);
    }
}
