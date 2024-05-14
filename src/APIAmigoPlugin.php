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
        $panel->discoverClusters(in: __DIR__.'/Clusters', for: 'ChrisReedIO\APIAmigo\Clusters');
        // )
        // $panel->resources([
        //     // Clusters\APIAmigo::class,
        //     Clusters\APIManagement\Resources\AmigoIntegrationResource::class,
        //     Clusters\APIManagement\Resources\AmigoConnectorResource::class,
        //     Clusters\APIManagement\Resources\AmigoRecordingResource::class,
        //     Clusters\APIManagement\Resources\AmigoEndpointResource::class,
        //     Clusters\APIManagement\Resources\AmigoResponseResource::class,
        //     Clusters\APIManagement\Resources\AmigoListenerResource::class,
        //     Clusters\APIManagement\Resources\AmigoWebhookResource::class,
        // ]);
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
