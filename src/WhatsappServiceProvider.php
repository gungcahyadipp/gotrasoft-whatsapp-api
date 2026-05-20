<?php

namespace Gotrasoft\WhatsappApi;

use Illuminate\Support\ServiceProvider;

class WhatsappServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/whatsapp.php',
            'whatsapp'
        );

        $this->app->singleton('whatsapp', function ($app) {
            return new WhatsappService();
        });

        $this->app->alias('whatsapp', WhatsappService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish config
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/whatsapp.php' => config_path('whatsapp.php'),
            ], 'whatsapp-config');
        }

        // Register webhook route
        $this->registerWebhookRoute();
    }

    /**
     * Register the webhook route if enabled in config.
     */
    protected function registerWebhookRoute(): void
    {
        if (!config('whatsapp.webhook.enabled', true)) {
            return;
        }

        $this->app['router']->group([
            'middleware' => config('whatsapp.webhook.middleware', ['api']),
        ], function ($router) {
            $router->post(
                config('whatsapp.webhook.path', '/whatsapp/webhook'),
                Http\Controllers\WhatsappWebhookController::class
            );
        });
    }
}
