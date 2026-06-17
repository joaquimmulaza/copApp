<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * PushSubscriptionController — Registo e renovação de tokens FCM (RF02).
 *
 * Responsabilidade única: receber um token FCM do frontend e manter a tabela
 * `push_subscriptions` actualizada via upsert. O token é a chave de unicidade;
 * cada novo registo renova o `last_active_at` para o instante actual.
 *
 * ── Endpoint ─────────────────────────────────────────────────────────────────
 *   POST /api/push-subscriptions
 *   Body: { fcm_token: string, device_type?: "web" | "ios" | "android" }
 *
 * ── Segurança ────────────────────────────────────────────────────────────────
 *   - Rota pública (sem autenticação Sanctum): qualquer utilizador anónimo
 *     pode registar o seu device. Isto é intencional — o FCM token não é
 *     um segredo, e a notificação é de conteúdo público (escalação confirmada).
 *   - A validação do tipo de dispositivo é feita via enum para evitar valores
 *     arbitrários na BD.
 *   - O token é truncado implicitamente pelo tamanho da coluna VARCHAR(512).
 *
 * @see CONTEXT.md §9.2 Migration 10, §12 RF02
 * @see FcmNotificationService — consumidor desta tabela para envio de push
 */
final class PushSubscriptionController
{
    /**
     * Regista ou renova um token FCM na tabela `push_subscriptions`.
     *
     * Usa `DB::table()->upsert()` directamente (sem Eloquent) para garantir
     * atomicidade numa única query SQL, mesmo que dois requests com o mesmo
     * token cheguem em simultâneo (race condition via UNIQUE INDEX).
     *
     * Códigos de resposta:
     *   201 Created  — Token registado pela primeira vez.
     *   200 OK       — Token já existente; `last_active_at` actualizado.
     *   422 Unprocessable Entity — Validação falhou.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // ── Validação ─────────────────────────────────────────────────────────
        $validator = Validator::make($request->all(), [
            'fcm_token'   => ['required', 'string', 'min:10', 'max:512'],
            'device_type' => ['nullable', 'string', 'in:web,ios,android'],
        ]);

        if ($validator->fails()) {
            Log::warning('[PushSubscriptionController] Validação falhou ao registar token FCM.', [
                'errors' => $validator->errors()->toArray(),
                'ip'     => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Dados inválidos.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        /** @var array{fcm_token: string, device_type: string|null} $validated */
        $validated = $validator->validated();

        $fcmToken   = $validated['fcm_token'];
        $deviceType = $validated['device_type'] ?? null;
        $now        = now();

        // ── Upsert atómico ────────────────────────────────────────────────────
        // A coluna `fcm_token` tem um UNIQUE INDEX (Migration 10).
        // Em conflito, actualiza apenas `last_active_at` e `device_type` para
        // reflectir a sessão mais recente do utilizador.
        $affected = DB::table('push_subscriptions')->upsert(
            values: [
                [
                    'fcm_token'    => $fcmToken,
                    'device_type'  => $deviceType,
                    'last_active_at' => $now,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ],
            ],
            uniqueBy: ['fcm_token'],
            update:   ['device_type', 'last_active_at', 'updated_at'],
        );

        // `upsert()` retorna o número de linhas afectadas:
        //   - 1 → INSERT (token novo)
        //   - 2 → UPDATE (token existente, PostgreSQL conta INSERT + UPDATE = 2 em ON CONFLICT DO UPDATE)
        //   - 0 → nenhuma alteração (raro se o valor não mudou)
        $isNew = $affected === 1;

        Log::info('[PushSubscriptionController] Token FCM registado com sucesso.', [
            'device_type' => $deviceType,
            'action'      => $isNew ? 'inserted' : 'updated',
            'ip'          => $request->ip(),
            // Nunca registar o token completo nos logs por precaução.
            'token_prefix' => mb_substr($fcmToken, 0, 20) . '…',
        ]);

        return response()->json(
            data:   ['message' => $isNew ? 'Subscrição registada.' : 'Subscrição renovada.'],
            status: $isNew ? 201 : 200,
        );
    }
}
