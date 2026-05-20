<?php

namespace Gotrasoft\WhatsappApi;

use Gotrasoft\WhatsappApi\Exceptions\WhatsappApiException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;

/**
 * ─────────────────────────────────────────────────────────────
 *  WhatsappService – Laravel HTTP client for WhatsApp REST API
 * ─────────────────────────────────────────────────────────────
 *
 * Covers every endpoint exposed by the WhatsApp Unofficial API:
 *   • Session lifecycle  (start, QR, status, logout, list, auto-reconnect)
 *   • Messaging          (text, media, location)
 *   • Contacts & Chats   (get chats, get contact, check number, download media)
 *   • Webhook management (get / set per-session webhook URL)
 *   • System             (health, storage stats, cache vacuum, cron status)
 */
class WhatsappService
{
    // ─── Configuration ──────────────────────────────────────────

    protected string $baseUrl;
    protected string $apiKey;
    protected int $timeout;
    protected int $retries;
    protected int $retryDelayMs;
    protected int $mediaTimeout;

    // ─── Constructor ────────────────────────────────────────────

    public function __construct()
    {
        $this->baseUrl = rtrim(config('whatsapp.url', ''), '/');
        $this->apiKey = config('whatsapp.key', '');
        $this->timeout = (int) config('whatsapp.timeout', 30);
        $this->retries = (int) config('whatsapp.retry', 3);
        $this->retryDelayMs = (int) config('whatsapp.retry_ms', 500);
        $this->mediaTimeout = (int) config('whatsapp.media_timeout', 120);

        if (empty($this->baseUrl) && empty($this->apiKey)) {
            Log::warning('[WhatsappService] Konfigurasi tidak lengkap: WHATSAPP_API_URL dan WHATSAPP_API_KEY belum diatur. Silakan tambahkan di file .env atau config/whatsapp.php.');
        } elseif (empty($this->baseUrl)) {
            Log::warning('[WhatsappService] Konfigurasi tidak lengkap: WHATSAPP_API_URL belum diatur. Silakan tambahkan di file .env.');
        } elseif (empty($this->apiKey)) {
            Log::warning('[WhatsappService] Konfigurasi tidak lengkap: WHATSAPP_API_KEY belum diatur. Silakan tambahkan di file .env.');
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  SESSION MANAGEMENT
    // ═══════════════════════════════════════════════════════════

    /**
     * Start a new WhatsApp session.
     *
     * @param  string      $sessionId   Unique session identifier
     * @param  string|null $webhookUrl  Optional per-session webhook URL
     * @return array       API response
     */
    public function startSession(string $sessionId, ?string $webhookUrl = null): array
    {
        return $this->post('/api/auth/start', array_filter([
            'sessionId' => $sessionId,
            'webhookUrl' => $webhookUrl,
        ]));
    }

    /**
     * Get QR code for a session.
     *
     * @param  string $sessionId
     * @param  bool   $asImage   Return base64 data URL if true
     * @return array
     */
    public function getQrCode(string $sessionId, bool $asImage = false): array
    {
        $query = $asImage ? ['format' => 'image'] : [];

        return $this->get("/api/auth/qr/{$sessionId}", $query);
    }

    /**
     * Get session status (in-memory + database fallback).
     */
    public function getSessionStatus(string $sessionId): array
    {
        return $this->get("/api/auth/status/{$sessionId}");
    }

    /**
     * Enable or disable auto-reconnect for a session.
     */
    public function setAutoReconnect(string $sessionId, bool $enabled): array
    {
        return $this->put("/api/auth/auto-reconnect/{$sessionId}", [
            'enabled' => $enabled,
        ]);
    }

    /**
     * Logout and destroy a session (deletes storage + DB record).
     */
    public function logout(string $sessionId): array
    {
        return $this->delete("/api/auth/logout/{$sessionId}");
    }

    /**
     * List all sessions (admin sees all, regular user sees own).
     */
    public function listSessions(): array
    {
        return $this->get('/api/auth/sessions');
    }

    /**
     * Get storage statistics (disk usage per session).
     */
    public function getStorageStats(): array
    {
        return $this->get('/api/auth/storage-stats');
    }

    // ═══════════════════════════════════════════════════════════
    //  WEBHOOK MANAGEMENT
    // ═══════════════════════════════════════════════════════════

    /**
     * Set a per-session webhook URL. Pass null to remove.
     */
    public function setWebhook(string $sessionId, ?string $webhookUrl): array
    {
        return $this->put("/api/auth/webhook/{$sessionId}", [
            'webhookUrl' => $webhookUrl,
        ]);
    }

    /**
     * Get the current webhook URL for a session.
     */
    public function getWebhook(string $sessionId): array
    {
        return $this->get("/api/auth/webhook/{$sessionId}");
    }

    // ═══════════════════════════════════════════════════════════
    //  CACHE MANAGEMENT
    // ═══════════════════════════════════════════════════════════

    /**
     * Vacuum cache for a specific session.
     *
     * @param  string $sessionId
     * @param  int    $maxSizeMB  Maximum cache size in MB before cleanup (default: 5)
     * @return array
     */
    public function vacuumCache(string $sessionId, int $maxSizeMB = 5): array
    {
        return $this->post("/api/auth/vacuum-cache/{$sessionId}", [], [
            'maxSizeMB' => $maxSizeMB,
        ]);
    }

    /**
     * Vacuum all session caches (admin only).
     *
     * @param  int  $maxSizeMB  Maximum cache size in MB before cleanup (default: 5)
     * @return array
     */
    public function vacuumAllCaches(int $maxSizeMB = 5): array
    {
        return $this->post('/api/auth/vacuum-all-caches', [], [
            'maxSizeMB' => $maxSizeMB,
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    //  MESSAGING
    // ═══════════════════════════════════════════════════════════

    /**
     * Send a text message.
     *
     * @param  string $sessionId
     * @param  string $to       Phone number (e.g. 628xxx) or group JID (xxx@g.us)
     * @param  string $message  Text body
     * @return array
     */
    public function sendMessage(string $sessionId, string $to, string $message): array
    {
        return $this->post('/api/message/send', [
            'sessionId' => $sessionId,
            'to' => $to,
            'message' => $message,
        ]);
    }

    /**
     * Send a media message (image, video, document, etc.).
     *
     * @param  string      $sessionId
     * @param  string      $to        Phone number or group JID
     * @param  string      $mediaUrl  Publicly accessible URL of the media file
     * @param  string|null $caption   Optional caption text
     * @return array
     */
    public function sendMedia(string $sessionId, string $to, string $mediaUrl, ?string $caption = null): array
    {
        return $this->post('/api/message/send-media', array_filter([
            'sessionId' => $sessionId,
            'to' => $to,
            'mediaUrl' => $mediaUrl,
            'caption' => $caption,
        ]), [], $this->mediaTimeout);
    }

    /**
     * Send a location message.
     *
     * @param  string      $sessionId
     * @param  string      $to
     * @param  float       $latitude
     * @param  float       $longitude
     * @param  string|null $description
     * @return array
     */
    public function sendLocation(
        string $sessionId,
        string $to,
        float $latitude,
        float $longitude,
        ?string $description = null,
    ): array {
        return $this->post('/api/message/send-location', array_filter([
            'sessionId' => $sessionId,
            'to' => $to,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'description' => $description,
        ]));
    }

    // ═══════════════════════════════════════════════════════════
    //  CHATS & CONTACTS
    // ═══════════════════════════════════════════════════════════

    /**
     * Get all chats for a session.
     */
    public function getChats(string $sessionId): array
    {
        return $this->get("/api/message/chats/{$sessionId}");
    }

    /**
     * Get contact info by phone number.
     *
     * @param  string $sessionId
     * @param  string $number   Phone number (with or without @c.us)
     * @return array
     */
    public function getContact(string $sessionId, string $number): array
    {
        return $this->get("/api/message/contact/{$sessionId}/{$number}");
    }

    /**
     * Check if a phone number is registered on WhatsApp.
     */
    public function checkNumber(string $sessionId, string $number): array
    {
        return $this->get("/api/message/check-number/{$sessionId}/{$number}");
    }

    /**
     * Download media from a message by its serialized ID.
     *
     * @return array  Contains mimetype, filename, filesize, and base64 data
     */
    public function downloadMedia(string $sessionId, string $messageId): array
    {
        return $this->get(
            "/api/message/media/{$sessionId}/{$messageId}",
            [],
            $this->mediaTimeout,
        );
    }

    // ═══════════════════════════════════════════════════════════
    //  SYSTEM / HEALTH
    // ═══════════════════════════════════════════════════════════

    /**
     * Health check (no auth required).
     */
    public function healthCheck(): array
    {
        return $this->request('GET', '/health', authenticated: false);
    }

    /**
     * Get cron jobs status.
     */
    public function getCronStatus(): array
    {
        return $this->request('GET', '/api/cron/status', authenticated: false);
    }

    /**
     * Get API documentation metadata.
     */
    public function getApiDocs(): array
    {
        return $this->request('GET', '/api/docs', authenticated: false);
    }

    // ═══════════════════════════════════════════════════════════
    //  CONVENIENCE / HELPERS (public)
    // ═══════════════════════════════════════════════════════════

    /**
     * Determine whether a session is currently "ready" (authenticated & connected).
     */
    public function isSessionReady(string $sessionId): bool
    {
        try {
            $status = $this->getSessionStatus($sessionId);

            return ($status['success'] ?? false)
                && ($status['status'] ?? '') === 'ready';
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Wait for a session QR code to become available (polling).
     *
     * @param  string $sessionId
     * @param  int    $maxWaitSeconds  Maximum seconds to wait (default: 30)
     * @param  int    $intervalMs      Polling interval in milliseconds (default: 2000)
     * @return array  QR response once available
     *
     * @throws WhatsappApiException if timeout
     */
    public function waitForQr(string $sessionId, int $maxWaitSeconds = 30, int $intervalMs = 2000): array
    {
        $deadline = microtime(true) + $maxWaitSeconds;

        while (microtime(true) < $deadline) {
            $response = $this->getQrCode($sessionId, asImage: true);

            if (!empty($response['qrImage']) || !empty($response['qr'])) {
                return $response;
            }

            // Already authenticated — no QR needed
            if (($response['status'] ?? '') === 'ready' || ($response['status'] ?? '') === 'authenticated') {
                return $response;
            }

            usleep($intervalMs * 1000);
        }

        throw new WhatsappApiException("QR code not available within {$maxWaitSeconds}s for session [{$sessionId}].");
    }

    /**
     * Send a text message with automatic number validation.
     * Returns false (with logging) instead of throwing if the number is unregistered.
     */
    public function sendMessageSafe(string $sessionId, string $to, string $message): array|false
    {
        // Skip validation for group JIDs
        if (!str_contains($to, '@g.us')) {
            try {
                $check = $this->checkNumber($sessionId, $to);

                if (!($check['isRegistered'] ?? false)) {
                    Log::info("[WhatsappService] Nomor {$to} tidak terdaftar di WhatsApp. Pesan tidak dikirim.", [
                        'sessionId' => $sessionId,
                        'to' => $to,
                    ]);

                    return false;
                }
            } catch (\Throwable $e) {
                Log::warning("[WhatsappService] Gagal memvalidasi nomor {$to}: {$e->getMessage()}. Pesan tetap akan dikirim.", [
                    'sessionId' => $sessionId,
                    'to' => $to,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->sendMessage($sessionId, $to, $message);
    }

    /**
     * Send a message to multiple recipients.
     *
     * @param  string   $sessionId
     * @param  string[] $recipients  Array of phone numbers or group JIDs
     * @param  string   $message
     * @param  int      $delayMs     Delay between sends to avoid rate-limit (default: 1000)
     * @return array    Results keyed by recipient
     */
    public function broadcast(string $sessionId, array $recipients, string $message, int $delayMs = 1000): array
    {
        $results = [];
        $total = count($recipients);
        $sent = 0;
        $failed = 0;

        Log::info("[WhatsappService] Memulai broadcast ke {$total} penerima.", [
            'sessionId' => $sessionId,
            'totalRecipients' => $total,
        ]);

        foreach ($recipients as $to) {
            try {
                $results[$to] = $this->sendMessage($sessionId, $to, $message);
                $results[$to]['_status'] = 'sent';
                $sent++;
            } catch (\Throwable $e) {
                $results[$to] = [
                    'success' => false,
                    '_status' => 'failed',
                    'error' => $e->getMessage(),
                ];
                $failed++;

                Log::warning("[WhatsappService] Broadcast gagal ke {$to}: {$e->getMessage()}", [
                    'sessionId' => $sessionId,
                    'to' => $to,
                ]);
            }

            if ($delayMs > 0 && $to !== end($recipients)) {
                usleep($delayMs * 1000);
            }
        }

        Log::info("[WhatsappService] Broadcast selesai. Terkirim: {$sent}/{$total}, Gagal: {$failed}/{$total}.", [
            'sessionId' => $sessionId,
            'sent' => $sent,
            'failed' => $failed,
            'total' => $total,
        ]);

        return $results;
    }

    // ═══════════════════════════════════════════════════════════
    //  INBOUND WEBHOOK HANDLING
    // ═══════════════════════════════════════════════════════════

    /** @var array<string, \Closure[]> */
    protected array $webhookListeners = [];

    /**
     * Parse an incoming webhook request into a typed DTO.
     */
    public function parseWebhook(array $raw): WhatsappWebhookPayload
    {
        return new WhatsappWebhookPayload($raw);
    }

    /**
     * Register a listener for a specific webhook event.
     *
     * @param  string   $event    Event name (message, ready, disconnected, etc.)
     * @param  \Closure $callback Receives WhatsappWebhookPayload
     * @return $this
     */
    public function on(string $event, \Closure $callback): static
    {
        $this->webhookListeners[$event][] = $callback;

        return $this;
    }

    /** Shorthand: register listener for incoming messages. */
    public function onMessage(\Closure $callback): static
    {
        return $this->on('message', $callback);
    }

    /** Shorthand: session is ready (authenticated + connected). */
    public function onReady(\Closure $callback): static
    {
        return $this->on('ready', $callback);
    }

    /** Shorthand: QR code generated. */
    public function onQr(\Closure $callback): static
    {
        return $this->on('qr', $callback);
    }

    /** Shorthand: session authenticated (before ready). */
    public function onAuthenticated(\Closure $callback): static
    {
        return $this->on('authenticated', $callback);
    }

    /** Shorthand: authentication failed. */
    public function onAuthFailure(\Closure $callback): static
    {
        return $this->on('auth_failure', $callback);
    }

    /** Shorthand: session disconnected. */
    public function onDisconnected(\Closure $callback): static
    {
        return $this->on('disconnected', $callback);
    }

    /** Shorthand: auto-reconnect in progress. */
    public function onReconnecting(\Closure $callback): static
    {
        return $this->on('reconnecting', $callback);
    }

    /** Shorthand: all reconnect attempts failed. */
    public function onReconnectFailed(\Closure $callback): static
    {
        return $this->on('reconnect_failed', $callback);
    }

    /**
     * Dispatch an incoming webhook to all registered listeners.
     */
    public function handleWebhook(array $raw): WhatsappWebhookPayload
    {
        $payload = $this->parseWebhook($raw);

        Log::info('[WhatsappService] Webhook received', [
            'event' => $payload->event,
            'sessionId' => $payload->sessionId,
        ]);

        // Dispatch to registered listeners
        $listeners = $this->webhookListeners[$payload->event] ?? [];

        foreach ($listeners as $listener) {
            try {
                $listener($payload);
            } catch (\Throwable $e) {
                Log::error('[WhatsappService] Webhook listener error', [
                    'event' => $payload->event,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        // Also dispatch wildcard '*' listeners (catch-all)
        $wildcardListeners = $this->webhookListeners['*'] ?? [];

        foreach ($wildcardListeners as $listener) {
            try {
                $listener($payload);
            } catch (\Throwable $e) {
                Log::error('[WhatsappService] Wildcard webhook listener error', [
                    'event' => $payload->event,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $payload;
    }

    /**
     * Check if an incoming message is from a group chat.
     */
    public function isGroupMessage(WhatsappWebhookPayload $payload): bool
    {
        return $payload->event === 'message'
            && ($payload->data['isGroup'] ?? false);
    }

    /**
     * Check if an incoming message contains media.
     */
    public function hasMedia(WhatsappWebhookPayload $payload): bool
    {
        return $payload->event === 'message'
            && ($payload->data['hasMedia'] ?? false);
    }

    /**
     * Extract the sender number from an incoming message payload.
     */
    public function getSender(WhatsappWebhookPayload $payload): ?string
    {
        return $payload->data['from'] ?? null;
    }

    /**
     * Extract clean phone number (without @c.us suffix) from an incoming message.
     */
    public function getSenderNumber(WhatsappWebhookPayload $payload): ?string
    {
        $from = $payload->data['from'] ?? null;

        if ($from === null) {
            return null;
        }

        return str_replace('@c.us', '', $from);
    }

    /**
     * Extract message body text from an incoming message payload.
     */
    public function getMessageBody(WhatsappWebhookPayload $payload): ?string
    {
        return $payload->data['body'] ?? null;
    }

    // ═══════════════════════════════════════════════════════════
    //  HTTP TRANSPORT (protected)
    // ═══════════════════════════════════════════════════════════

    protected function get(string $path, array $query = [], ?int $timeout = null): array
    {
        return $this->request('GET', $path, query: $query, timeout: $timeout);
    }

    protected function post(string $path, array $body = [], array $query = [], ?int $timeout = null): array
    {
        return $this->request('POST', $path, body: $body, query: $query, timeout: $timeout);
    }

    protected function put(string $path, array $body = []): array
    {
        return $this->request('PUT', $path, body: $body);
    }

    protected function delete(string $path): array
    {
        return $this->request('DELETE', $path);
    }

    /**
     * Core HTTP request with retry, logging, and structured error handling.
     */
    protected function request(
        string $method,
        string $path,
        array $body = [],
        array $query = [],
        ?int $timeout = null,
        bool $authenticated = true,
    ): array {
        $url = $this->baseUrl . $path;
        $timeout = $timeout ?? $this->timeout;

        $attempt = 0;
        $maxAttempts = $this->retries + 1;
        $lastError = null;

        while ($attempt < $maxAttempts) {
            $attempt++;

            try {
                $pending = Http::timeout($timeout)
                    ->acceptJson()
                    ->withHeaders($this->buildHeaders($authenticated));

                if (!empty($query)) {
                    $pending = $pending->withQueryParameters($query);
                }

                /** @var Response $response */
                $response = match (strtoupper($method)) {
                    'GET' => $pending->get($url),
                    'POST' => $pending->post($url, $body),
                    'PUT' => $pending->put($url, $body),
                    'DELETE' => $pending->delete($url),
                    'PATCH' => $pending->patch($url, $body),
                    default => throw new WhatsappApiException("Unsupported HTTP method: {$method}"),
                };

                // Non-retryable client errors (4xx) — break immediately
                if ($response->clientError()) {
                    $decoded = $response->json() ?? [];
                    $status = $response->status();
                    $apiMessage = $decoded['message'] ?? $decoded['error'] ?? null;

                    $errorMessage = match ($status) {
                        400 => "Bad Request: " . ($apiMessage ?? "Parameter tidak valid untuk [{$method} {$path}]"),
                        401 => "Unauthorized: API key tidak valid atau tidak diberikan untuk [{$method} {$path}]",
                        403 => "Forbidden: Akses ditolak untuk [{$method} {$path}]. Periksa permission API key Anda.",
                        404 => "Not Found: Endpoint atau resource tidak ditemukan [{$method} {$path}]",
                        409 => "Conflict: " . ($apiMessage ?? "Resource sudah ada atau konflik state [{$method} {$path}]"),
                        422 => "Unprocessable Entity: " . ($apiMessage ?? "Data tidak dapat diproses [{$method} {$path}]"),
                        429 => "Rate Limited: Terlalu banyak request ke [{$method} {$path}]. Coba lagi nanti.",
                        default => ($apiMessage ?? "Client error (HTTP {$status})") . " [{$method} {$path}]",
                    };

                    Log::warning("[WhatsappService] {$errorMessage}", [
                        'method' => $method,
                        'path' => $path,
                        'status' => $status,
                        'response' => $decoded,
                    ]);

                    throw new WhatsappApiException(
                        message: $errorMessage,
                        code: $status,
                        context: $decoded,
                        endpoint: $path,
                        httpMethod: $method,
                    );
                }

                // Server errors (5xx) — retryable
                if ($response->serverError()) {
                    $status = $response->status();
                    $decoded = $response->json() ?? [];
                    $serverMessage = $decoded['message'] ?? $decoded['error'] ?? "Server error (HTTP {$status})";

                    Log::warning("[WhatsappService] Server error pada [{$method} {$path}], attempt {$attempt}/{$maxAttempts}", [
                        'status' => $status,
                        'response' => $decoded,
                    ]);

                    throw new \RuntimeException("Server error (HTTP {$status}): {$serverMessage}");
                }

                // Success
                $result = $response->json() ?? [];

                Log::debug("[WhatsappService] Request berhasil [{$method} {$path}]", [
                    'status' => $response->status(),
                ]);

                return $result;
            } catch (WhatsappApiException $e) {
                // Client errors are not retryable
                throw $e;
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $lastError = $e;

                Log::error("[WhatsappService] Koneksi gagal ke [{$method} {$path}], attempt {$attempt}/{$maxAttempts}: Tidak dapat terhubung ke server WhatsApp API ({$this->baseUrl}). Pastikan server aktif dan URL benar.", [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < $maxAttempts) {
                    $delay = $this->retryDelayMs * pow(2, $attempt - 1);
                    usleep((int) ($delay * 1000));
                }
            } catch (\Illuminate\Http\Client\RequestException $e) {
                $lastError = $e;

                Log::warning("[WhatsappService] Request error pada [{$method} {$path}], attempt {$attempt}/{$maxAttempts}: {$e->getMessage()}", [
                    'attempt' => $attempt,
                    'method' => $method,
                    'path' => $path,
                ]);

                if ($attempt < $maxAttempts) {
                    $delay = $this->retryDelayMs * pow(2, $attempt - 1);
                    usleep((int) ($delay * 1000));
                }
            } catch (\Throwable $e) {
                $lastError = $e;

                Log::warning("[WhatsappService] Request gagal [{$method} {$path}], attempt {$attempt}/{$maxAttempts}: {$e->getMessage()}", [
                    'attempt' => $attempt,
                    'method' => $method,
                    'path' => $path,
                    'exception' => get_class($e),
                ]);

                if ($attempt < $maxAttempts) {
                    $delay = $this->retryDelayMs * pow(2, $attempt - 1);
                    usleep((int) ($delay * 1000));
                }
            }
        }

        $finalMessage = "Request ke [{$method} {$path}] gagal setelah {$maxAttempts} percobaan. ";

        if ($lastError instanceof \Illuminate\Http\Client\ConnectionException) {
            $finalMessage .= "Penyebab: Tidak dapat terhubung ke server ({$this->baseUrl}). Pastikan WHATSAPP_API_URL benar dan server aktif.";
        } else {
            $finalMessage .= "Penyebab: " . ($lastError?->getMessage() ?? 'Error tidak diketahui');
        }

        throw new WhatsappApiException(
            message: $finalMessage,
            code: 500,
            context: ['lastError' => $lastError?->getMessage()],
            endpoint: $path,
            httpMethod: $method,
        );
    }

    /**
     * Build HTTP headers.
     */
    protected function buildHeaders(bool $authenticated = true): array
    {
        $headers = [
            'Content-Type' => 'application/json',
        ];

        if ($authenticated) {
            $headers['x-api-key'] = $this->apiKey;
        }

        return $headers;
    }
}
