# Gotrasoft WhatsApp API

Laravel package untuk integrasi dengan WhatsApp Unofficial REST API. Mendukung Laravel 10, 11, 12, dan 13.

## Fitur

- Session management (start, QR, status, logout, auto-reconnect)
- Kirim pesan teks, media, dan lokasi
- Broadcast ke banyak penerima
- Manajemen webhook (set/get per-session)
- Inbound webhook handling dengan event listeners
- Cache vacuum & storage stats
- Health check & system endpoints
- Retry otomatis dengan exponential backoff
- Facade & dependency injection support

## Instalasi

```bash
composer require gotrasoft/whatsapp-api
```

Package ini menggunakan Laravel auto-discovery, jadi service provider dan facade akan terdaftar otomatis.

### Publish Config

```bash
php artisan vendor:publish --tag=whatsapp-config
```

## Konfigurasi

Tambahkan ke file `.env`:

```env
WHATSAPP_API_URL=https://wa.example.com
WHATSAPP_API_KEY=your-api-key-here
WHATSAPP_TIMEOUT=30
WHATSAPP_RETRY=3
WHATSAPP_RETRY_MS=500
WHATSAPP_MEDIA_TIMEOUT=120
```

## Penggunaan

### Menggunakan Facade

```php
use Gotrasoft\WhatsappApi\Facades\Whatsapp;

// Start session
Whatsapp::startSession('my-session');

// Get QR code
$qr = Whatsapp::getQrCode('my-session', asImage: true);

// Check session status
$status = Whatsapp::getSessionStatus('my-session');

// Send text message
Whatsapp::sendMessage('my-session', '628123456789', 'Hello World!');

// Send media
Whatsapp::sendMedia('my-session', '628123456789', 'https://example.com/image.jpg', 'Caption');

// Send location
Whatsapp::sendLocation('my-session', '628123456789', -8.65, 115.22, 'Bali, Indonesia');

// Broadcast
Whatsapp::broadcast('my-session', ['628111', '628222', '628333'], 'Broadcast message');

// Safe send (validates number first)
Whatsapp::sendMessageSafe('my-session', '628123456789', 'Hello!');

// Check if session is ready
if (Whatsapp::isSessionReady('my-session')) {
    // session is connected
}

// Logout
Whatsapp::logout('my-session');
```

### Menggunakan Dependency Injection

```php
use Gotrasoft\WhatsappApi\WhatsappService;

class NotificationController extends Controller
{
    public function send(WhatsappService $whatsapp)
    {
        $whatsapp->sendMessage('my-session', '628123456789', 'Hello!');
    }
}
```

### Webhook Handling

Package ini secara otomatis mendaftarkan route webhook di `POST /whatsapp/webhook`. Kamu bisa mengkonfigurasi path dan middleware di `config/whatsapp.php`.

#### Custom Webhook Handler

Jika ingin menangani webhook secara custom, disable route bawaan di config:

```php
// config/whatsapp.php
'webhook' => [
    'enabled' => false,
],
```

Lalu buat controller sendiri:

```php
use Gotrasoft\WhatsappApi\WhatsappService;
use Illuminate\Http\Request;

class MyWebhookController extends Controller
{
    public function handle(Request $request, WhatsappService $whatsapp)
    {
        $payload = $whatsapp->handleWebhook($request->all());

        if ($payload->isMessage()) {
            $from = $payload->phoneNumber();
            $body = $payload->body();
            // Process message...
        }

        return response()->json(['received' => true]);
    }
}
```

#### Event Listeners

```php
use Gotrasoft\WhatsappApi\WhatsappService;
use Gotrasoft\WhatsappApi\WhatsappWebhookPayload;

$whatsapp = app(WhatsappService::class);

// Listen for incoming messages
$whatsapp->onMessage(function (WhatsappWebhookPayload $payload) {
    Log::info("Message from {$payload->phoneNumber()}: {$payload->body()}");
});

// Listen for session ready
$whatsapp->onReady(function (WhatsappWebhookPayload $payload) {
    Log::info("Session {$payload->sessionId} is ready!");
});

// Listen for disconnection
$whatsapp->onDisconnected(function (WhatsappWebhookPayload $payload) {
    Log::warning("Session disconnected: {$payload->disconnectReason()}");
});

// Wildcard listener (catch all events)
$whatsapp->on('*', function (WhatsappWebhookPayload $payload) {
    Log::debug("Event: {$payload->event}");
});
```

### Session Management

```php
// List all sessions
$sessions = Whatsapp::listSessions();

// Get storage stats
$stats = Whatsapp::getStorageStats();

// Enable auto-reconnect
Whatsapp::setAutoReconnect('my-session', true);

// Set per-session webhook
Whatsapp::setWebhook('my-session', 'https://myapp.com/webhook');

// Vacuum cache
Whatsapp::vacuumCache('my-session', maxSizeMB: 10);
Whatsapp::vacuumAllCaches(maxSizeMB: 5);
```

### Contacts & Chats

```php
// Get all chats
$chats = Whatsapp::getChats('my-session');

// Get contact info
$contact = Whatsapp::getContact('my-session', '628123456789');

// Check if number is on WhatsApp
$check = Whatsapp::checkNumber('my-session', '628123456789');

// Download media from message
$media = Whatsapp::downloadMedia('my-session', 'message-id-here');
```

### System

```php
// Health check (no auth required)
$health = Whatsapp::healthCheck();

// Cron status
$cron = Whatsapp::getCronStatus();
```

## Webhook Events

Event yang dikirim oleh WhatsApp API:

| Event | Deskripsi |
|-------|-----------|
| `qr` | QR code generated |
| `authenticated` | Session authenticated |
| `ready` | Session ready to send/receive |
| `auth_failure` | Authentication failed |
| `message` | Incoming message received |
| `disconnected` | Session disconnected |
| `reconnecting` | Auto-reconnect in progress |
| `reconnect_failed` | All reconnect attempts failed |

## Error Handling

```php
use Gotrasoft\WhatsappApi\Exceptions\WhatsappApiException;

try {
    Whatsapp::sendMessage('my-session', '628123456789', 'Hello!');
} catch (WhatsappApiException $e) {
    $message = $e->getMessage();
    $code = $e->getCode();
    $context = $e->getContext(); // Raw API error payload
}
```

## Requirements

- PHP >= 8.1
- Laravel 10.x, 11.x, 12.x, atau 13.x

## License

MIT License. Lihat file [LICENSE](LICENSE) untuk detail.
