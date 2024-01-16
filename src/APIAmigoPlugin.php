<?php

namespace ChrisReedIO\APIAmigo;

use Filament\Contracts\Plugin;
use Filament\Panel;

class APIAmigoPlugin implements Plugin
{
    public function getId(): string
    {
        return 'api-amigo';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            Resources\AmigoIntegrationResource::class,
            Resources\AmigoConnectorResource::class,
            Resources\AmigoRecordingResource::class,
            Resources\AmigoEndpointResource::class,
            Resources\AmigoResponseResource::class,
            Resources\AmigoListenerResource::class,
            Resources\AmigoWebhookResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
