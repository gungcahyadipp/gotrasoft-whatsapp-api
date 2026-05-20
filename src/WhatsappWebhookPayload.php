<?php

namespace Gotrasoft\WhatsappApi;

/**
 * Immutable value object representing an incoming WhatsApp webhook payload.
 *
 * Properties:
 *   $event     – One of: qr, authenticated, ready, auth_failure, message,
 *                disconnected, reconnecting, reconnect_failed
 *   $sessionId – The session that triggered this event
 *   $data      – Event-specific payload (message body, QR string, error, etc.)
 *   $timestamp – ISO-8601 timestamp from the WhatsApp API server
 *   $raw       – The original unmodified array
 */
class WhatsappWebhookPayload
{
    public readonly string $event;
    public readonly string $sessionId;
    public readonly array $data;
    public readonly ?string $timestamp;
    public readonly array $raw;

    public function __construct(array $raw)
    {
        $this->raw = $raw;
        $this->event = $raw['event'] ?? 'unknown';
        $this->sessionId = $raw['sessionId'] ?? '';
        $this->data = $raw['data'] ?? [];
        $this->timestamp = $raw['timestamp'] ?? null;
    }

    // ─── Event type checks ──────────────────────────────────

    public function isMessage(): bool
    {
        return $this->event === 'message';
    }

    public function isQr(): bool
    {
        return $this->event === 'qr';
    }

    public function isAuthenticated(): bool
    {
        return $this->event === 'authenticated';
    }

    public function isReady(): bool
    {
        return $this->event === 'ready';
    }

    public function isAuthFailure(): bool
    {
        return $this->event === 'auth_failure';
    }

    public function isDisconnected(): bool
    {
        return $this->event === 'disconnected';
    }

    public function isReconnecting(): bool
    {
        return $this->event === 'reconnecting';
    }

    public function isReconnectFailed(): bool
    {
        return $this->event === 'reconnect_failed';
    }

    // ─── Message-specific accessors ─────────────────────────

    /** Sender ID (e.g. "628xxx@c.us" or "xxx@g.us"). */
    public function from(): ?string
    {
        return $this->data['from'] ?? null;
    }

    /** Recipient ID. */
    public function to(): ?string
    {
        return $this->data['to'] ?? null;
    }

    /** Message text body. */
    public function body(): ?string
    {
        return $this->data['body'] ?? null;
    }

    /** Clean phone number without @c.us suffix. */
    public function phoneNumber(): ?string
    {
        $from = $this->from();

        return $from ? str_replace('@c.us', '', $from) : null;
    }

    /** Whether the incoming message is from a group chat. */
    public function isGroup(): bool
    {
        return (bool) ($this->data['isGroup'] ?? false);
    }

    /** Whether the incoming message contains media. */
    public function hasMedia(): bool
    {
        return (bool) ($this->data['hasMedia'] ?? false);
    }

    /** Message type (chat, image, video, document, etc.). */
    public function messageType(): ?string
    {
        return $this->data['type'] ?? null;
    }

    /** Media metadata (only present when hasMedia = true). */
    public function mediaInfo(): ?array
    {
        return $this->data['media'] ?? null;
    }

    /** QR code raw string (only on 'qr' event). */
    public function qrString(): ?string
    {
        return $this->data['qr'] ?? null;
    }

    /** Disconnection reason (only on 'disconnected' event). */
    public function disconnectReason(): ?string
    {
        return $this->data['reason'] ?? null;
    }

    /** Auth failure error message (only on 'auth_failure' event). */
    public function authError(): ?string
    {
        return $this->data['error'] ?? null;
    }

    /** Reconnect attempt number (only on 'reconnecting' event). */
    public function reconnectAttempt(): ?int
    {
        return isset($this->data['attempt']) ? (int) $this->data['attempt'] : null;
    }

    /** Convert to array. */
    public function toArray(): array
    {
        return $this->raw;
    }
}
