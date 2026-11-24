<?php

namespace ChrisReedIO\APIAmigo\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use ChrisReedIO\APIAmigo\APIAmigoServiceProvider;
use ChrisReedIO\APIAmigo\Tests\Fixtures\AdminPanelProvider;
use ChrisReedIO\APIAmigo\Tests\Fixtures\User;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\SanctumServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use ReflectionMethod;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;

/**
 * Runs against PostgreSQL, which the package's migrations and aggregation queries require.
 * The schema is built once per run from the package's migration list, and each test runs in a
 * transaction that is rolled back afterwards.
 */
class TestCase extends Orchestra
{
    private static bool $migrated = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (! self::$migrated) {
            $this->migrate();
            self::$migrated = true;
        }

        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();

        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [
            ActionsServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            FilamentServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            LivewireServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            SanctumServiceProvider::class,
            APIAmigoServiceProvider::class,
            AdminPanelProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'pgsql');
        $app['config']->set('database.connections.pgsql', [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'api_amigo_test'),
            'username' => env('DB_USERNAME', 'amigo'),
            'password' => env('DB_PASSWORD', 'amigo'),
            'charset' => 'utf8',
            'prefix' => '',
            'search_path' => 'public',
        ]);

        // Read at boot, so it has to be set here rather than in a test.
        $app['config']->set('api-amigo.enabled', true);
        $app['config']->set('api-amigo.models.user', User::class);
        $app['config']->set('auth.providers.users.model', User::class);

        // The resources use Font Awesome icons, which the package doesn't install.
        $app['config']->set('blade-icons.fallback', 'heroicon-o-question-mark-circle');
    }

    protected function defineRoutes($router)
    {
        // Apps register this in routes/web.php, whose group provides route model binding.
        Route::middleware('web')->group(fn () => Route::webhooks());
    }

    private function migrate(): void
    {
        DB::statement('DROP SCHEMA public CASCADE');
        DB::statement('CREATE SCHEMA public');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        (include __DIR__ . '/../vendor/laravel/sanctum/database/migrations/2019_12_14_000001_create_personal_access_tokens_table.php')->up();

        // The same list, in the same order, that the package publishes to apps.
        $migrations = (new ReflectionMethod(APIAmigoServiceProvider::class, 'getMigrations'))
            ->invoke($this->app->getProvider(APIAmigoServiceProvider::class));

        foreach ($migrations as $migration) {
            (include __DIR__ . "/../database/migrations/{$migration}.php.stub")->up();
        }
    }
}
