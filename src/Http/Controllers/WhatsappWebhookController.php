<?php

namespace Gotrasoft\WhatsappApi\Http\Controllers;

use Gotrasoft\WhatsappApi\WhatsappService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    /**
     * Handle incoming WhatsApp webhook events.
     */
    public function __invoke(Request $request, WhatsappService $whatsapp): JsonResponse
    {
        $raw = $request->all();

        // Validate minimum required fields
        if (empty($raw['event'])) {
            Log::warning('[WhatsappWebhook] Webhook diterima tanpa field "event". Payload diabaikan.', [
                'payload' => $raw,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payload tidak valid: field "event" wajib ada.',
            ], 422);
        }

        if (empty($raw['sessionId'])) {
            Log::warning('[WhatsappWebhook] Webhook diterima tanpa field "sessionId".', [
                'event' => $raw['event'],
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payload tidak valid: field "sessionId" wajib ada.',
            ], 422);
        }

        try {
            $payload = $whatsapp->handleWebhook($raw);

            return response()->json([
                'success' => true,
                'message' => "Webhook event '{$payload->event}' untuk session '{$payload->sessionId}' berhasil diproses.",
                'event' => $payload->event,
                'sessionId' => $payload->sessionId,
            ]);
        } catch (\Throwable $e) {
            Log::error('[WhatsappWebhook] Gagal memproses webhook.', [
                'event' => $raw['event'] ?? 'unknown',
                'sessionId' => $raw['sessionId'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses webhook: ' . $e->getMessage(),
            ], 500);
        }
    }
}
