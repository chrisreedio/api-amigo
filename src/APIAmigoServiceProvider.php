<?php

namespace ChrisReedIO\APIAmigo;

use ChrisReedIO\APIAmigo\Commands\APIAmigoCommand;
use ChrisReedIO\APIAmigo\Commands\ResponsesAggregationCommand;
use ChrisReedIO\APIAmigo\Commands\ResponsesDailyAggregationCommand;
use ChrisReedIO\APIAmigo\Controllers\WebhookController;
use ChrisReedIO\APIAmigo\Middleware\Saloon\TrackSaloonRequest;
use ChrisReedIO\APIAmigo\Middleware\Saloon\TrackSaloonResponse;
use ChrisReedIO\APIAmigo\Testing\TestsAPIAmigo;
use Filament\Support\Assets\Asset;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Fluent;
use Livewire\Features\SupportTesting\Testable;
use Saloon\Enums\PipeOrder;
use Saloon\Exceptions\DuplicatePipeNameException;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

use function number_format;

class APIAmigoServiceProvider extends PackageServiceProvider
{
    public static string $name = 'api-amigo';

    public static string $viewNamespace = 'api-amigo';

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name(static::$name)
            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('chrisreedio/api-amigo');
            });

        $configFileName = $package->shortName();

        if (file_exists($package->basePath("/../config/{$configFileName}.php"))) {
            $package->hasConfigFile();
        }

        if (file_exists($package->basePath('/../database/migrations'))) {
            $package->hasMigrations($this->getMigrations());
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void
    {
        Route::macro('webhooks', function () {
            $prefix = config('api-amigo.webhooks.prefix', 'webhooks');
            Route::post("/$prefix/{listener:unique_id}", WebhookController::class)
                ->withoutMiddleware(['csrf', 'auth'])
                ->name('webhooks.handler');
        });

        Grammar::macro('typeTdigest', function (Fluent $column) {
            return 'tdigest';
        });

        TextColumn::macro('floatDuration', function () {
            return $this
                ->sortable()
                ->badge()
                ->color(function ($state) {
                    if ($state >= config('api-amigo.thresholds.duration.error')) {
                        return Color::Red;
                    } elseif ($state >= config('api-amigo.thresholds.duration.warning')) {
                        return Color::Yellow;
                    } else {
                        return Color::Green;
                    }
                })
                ->formatStateUsing(function ($state) {
                    return number_format($state * 1000) . 'ms';
                });
        });
    }

    public function packageBooted(): void
    {
        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Icon Registration
        FilamentIcon::register($this->getIcons());

        // Handle Stubs
        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/api-amigo/{$file->getFilename()}"),
                ], 'api-amigo-stubs');
            }
        }

        // Testing
        Testable::mixin(new TestsAPIAmigo);

        // Begin Tracking work
        try {
            // If the package is enabled, we'll hook up the middleware to track requests and log responses
            if (config('api-amigo.enabled')) {
                \Saloon\Config::globalMiddleware()
                    ->onRequest(new TrackSaloonRequest, 'amigo-track-request', PipeOrder::LAST)
                    // ->onResponse(new TrackSaloonResponse(), 'amigo-log-response', PipeOrder::FIRST);
                    // Moving this to 'Last' so that we can pick up that the response is cached
                    ->onResponse(new TrackSaloonResponse, 'amigo-log-response', PipeOrder::LAST);
            }

        } catch (DuplicatePipeNameException $e) {
            // TODO: Log that we failed to hook up the response logger
        }
    }

    protected function getAssetPackageName(): ?string
    {
        return 'chrisreedio/api-amigo';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            // AlpineComponent::make('api-amigo', __DIR__ . '/../resources/dist/components/api-amigo.js'),
            // Css::make('api-amigo-styles', __DIR__ . '/../resources/dist/api-amigo.css'),
            // Js::make('api-amigo-scripts', __DIR__ . '/../resources/dist/api-amigo.js'),
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            APIAmigoCommand::class,
            ResponsesAggregationCommand::class,
            ResponsesDailyAggregationCommand::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getRoutes(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'create_amigo_integrations_table',
            'create_amigo_connectors_table',
            'create_amigo_endpoints_table',
            'create_amigo_endpoint_aggregates_table',
            'create_amigo_requests_table',
            'create_amigo_responses_table',
            'create_amigo_listeners_table',
            'create_amigo_webhooks_table',
            'create_amigo_recordings_table',
            'create_amigo_recording_amigo_request_table',
            'alter_amigo_endpoint_aggregates_restructure_table',
            'alter_amigo_listeners_add_transient_columns',
            'alter_amigo_responses_add_cached_flag',
            'alter_amigo_webhooks_add_webhookable',
            'alter_amigo_listeners_add_basic_auth_columns',
        ];
    }
}
