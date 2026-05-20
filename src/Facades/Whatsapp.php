<?php

namespace Gotrasoft\WhatsappApi\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array startSession(string $sessionId, ?string $webhookUrl = null)
 * @method static array getQrCode(string $sessionId, bool $asImage = false)
 * @method static array getSessionStatus(string $sessionId)
 * @method static array setAutoReconnect(string $sessionId, bool $enabled)
 * @method static array logout(string $sessionId)
 * @method static array listSessions()
 * @method static array getStorageStats()
 * @method static array setWebhook(string $sessionId, ?string $webhookUrl)
 * @method static array getWebhook(string $sessionId)
 * @method static array vacuumCache(string $sessionId, int $maxSizeMB = 5)
 * @method static array vacuumAllCaches(int $maxSizeMB = 5)
 * @method static array sendMessage(string $sessionId, string $to, string $message)
 * @method static array sendMedia(string $sessionId, string $to, string $mediaUrl, ?string $caption = null)
 * @method static array sendLocation(string $sessionId, string $to, float $latitude, float $longitude, ?string $description = null)
 * @method static array getChats(string $sessionId)
 * @method static array getContact(string $sessionId, string $number)
 * @method static array checkNumber(string $sessionId, string $number)
 * @method static array downloadMedia(string $sessionId, string $messageId)
 * @method static array healthCheck()
 * @method static array getCronStatus()
 * @method static array getApiDocs()
 * @method static bool isSessionReady(string $sessionId)
 * @method static array waitForQr(string $sessionId, int $maxWaitSeconds = 30, int $intervalMs = 2000)
 * @method static array|false sendMessageSafe(string $sessionId, string $to, string $message)
 * @method static array broadcast(string $sessionId, array $recipients, string $message, int $delayMs = 1000)
 * @method static \Gotrasoft\WhatsappApi\WhatsappWebhookPayload parseWebhook(array $raw)
 * @method static \Gotrasoft\WhatsappApi\WhatsappWebhookPayload handleWebhook(array $raw)
 * @method static static on(string $event, \Closure $callback)
 * @method static static onMessage(\Closure $callback)
 * @method static static onReady(\Closure $callback)
 * @method static static onQr(\Closure $callback)
 * @method static static onAuthenticated(\Closure $callback)
 * @method static static onAuthFailure(\Closure $callback)
 * @method static static onDisconnected(\Closure $callback)
 * @method static static onReconnecting(\Closure $callback)
 * @method static static onReconnectFailed(\Closure $callback)
 * @method static bool isGroupMessage(\Gotrasoft\WhatsappApi\WhatsappWebhookPayload $payload)
 * @method static bool hasMedia(\Gotrasoft\WhatsappApi\WhatsappWebhookPayload $payload)
 * @method static ?string getSender(\Gotrasoft\WhatsappApi\WhatsappWebhookPayload $payload)
 * @method static ?string getSenderNumber(\Gotrasoft\WhatsappApi\WhatsappWebhookPayload $payload)
 * @method static ?string getMessageBody(\Gotrasoft\WhatsappApi\WhatsappWebhookPayload $payload)
 *
 * @see \Gotrasoft\WhatsappApi\WhatsappService
 */
class Whatsapp extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'whatsapp';
    }
}
