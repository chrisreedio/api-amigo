<?php

namespace ChrisReedIO\APIAmigo;

use ChrisReedIO\APIAmigo\Jobs\ProcessWebhookJob;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Illuminate\Support\Facades\File;

class APIAmigoPlugin implements Plugin
{
    public function getId(): string
    {
        return 'api-amigo';
    }

    public function register(Panel $panel): void
    {
        $panel->discoverClusters(in: __DIR__ . '/Clusters', for: 'ChrisReedIO\APIAmigo\Clusters');

        // Discover all classes in the Webhooks directory in the app directory
        // Custom logic to look for classes that extend the ProcessWebhookJob class
        $webhookHandlers = collect(File::allFiles(app_path('Webhooks')));
        $webhookHandlers->each(function ($file) {
            $class = 'App\\Webhooks\\' . str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());
            // dump('Checking webhook handler: ' . $class . ' - File: ' . $file->getRelativePathname());
            if (is_subclass_of($class, ProcessWebhookJob::class)) {
                // dump('Registering webhook handler: ' . $class . ' - File: ' . $file->getRelativePathname() . ' - Subclass of: ' . ProcessWebhookJob::class);
                APIAmigo::registerWebhookHandler($class);
            }
        });

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
