<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WhatsApp API Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL of your WhatsApp API server.
    | Example: https://wa.example.com
    |
    */
    'url' => env('WHATSAPP_API_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    |
    | The x-api-key used for authenticating requests to the WhatsApp API.
    |
    */
    'key' => env('WHATSAPP_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Default timeout in seconds for API requests.
    |
    */
    'timeout' => (int) env('WHATSAPP_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Number of retries on transient failures and base delay between retries.
    |
    */
    'retry' => (int) env('WHATSAPP_RETRY', 3),
    'retry_ms' => (int) env('WHATSAPP_RETRY_MS', 500),

    /*
    |--------------------------------------------------------------------------
    | Media Timeout
    |--------------------------------------------------------------------------
    |
    | Timeout in seconds for media-heavy requests (upload/download).
    |
    */
    'media_timeout' => (int) env('WHATSAPP_MEDIA_TIMEOUT', 120),

    /*
    |--------------------------------------------------------------------------
    | Webhook Route
    |--------------------------------------------------------------------------
    |
    | Configure the webhook route that receives events from the WhatsApp API.
    | Set 'enabled' to false if you want to define your own route.
    |
    */
    'webhook' => [
        'enabled' => true,
        'path' => '/whatsapp/webhook',
        'middleware' => ['api'],
    ],

];
