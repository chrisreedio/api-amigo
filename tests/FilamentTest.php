<?php

use ChrisReedIO\APIAmigo\APIAmigo;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoConnectorResource;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoConnectorResource\Pages\ViewAmigoConnector;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoConnectorResource\RelationManagers\AmigoEndpointsRelationManager;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoConnectorResource\RelationManagers\AmigoRequestsRelationManager as ConnectorRequestsRelationManager;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\Pages\ViewAmigoEndpoint;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\RelationManagers\AmigoRequestsRelationManager as EndpointRequestsRelationManager;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoIntegrationResource;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoListenerResource;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoListenerResource\Pages\ListAmigoListeners;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoListenerResource\Pages\ViewAmigoListener;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoListenerResource\RelationManagers\AmigoWebhooksRelationManager;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoListenerResource\RelationManagers\TokensRelationManager;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoRecordingResource;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoRecordingResource\Pages\ViewAmigoRecording;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoRecordingResource\RelationManagers\AmigoRequestsRelationManager as RecordingRequestsRelationManager;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoResponseResource;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoResponseResource\Pages\ListAmigoResponses;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoWebhookResource;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoWebhookResource\Pages\ListAmigoWebhooks;
use ChrisReedIO\APIAmigo\Models\AmigoConnector;
use ChrisReedIO\APIAmigo\Models\AmigoEndpoint;
use ChrisReedIO\APIAmigo\Models\AmigoEndpointAggregate;
use ChrisReedIO\APIAmigo\Models\AmigoIntegration;
use ChrisReedIO\APIAmigo\Models\AmigoListener;
use ChrisReedIO\APIAmigo\Models\AmigoRecording;
use ChrisReedIO\APIAmigo\Models\AmigoResponse;
use ChrisReedIO\APIAmigo\Models\AmigoWebhook;
use ChrisReedIO\APIAmigo\Tests\Fixtures\User;
use ChrisReedIO\APIAmigo\Tests\Fixtures\Webhooks\ProcessTestWebhook;
use Filament\Facades\Filament;
use Livewire\Livewire;

/**
 * One integration with two endpoints, a response of each status class, a listener with a
 * successful and a failed webhook, and a recording of the requests.
 *
 * @return array<string, mixed>
 */
function seedPanelData(): array
{
    $integration = AmigoIntegration::create(['name' => 'Acme']);
    $connector = AmigoConnector::create(['integration_id' => $integration->id, 'name' => 'AcmeConnector', 'base_url' => 'api.acme.test']);
    $widgets = AmigoEndpoint::create(['connector_id' => $connector->id, 'method' => 'GET', 'name' => 'GetWidget', 'path' => '/widgets/{id}']);
    $orders = AmigoEndpoint::create(['connector_id' => $connector->id, 'method' => 'POST', 'name' => 'CreateOrder', 'path' => '/orders']);

    $responses = collect([[$widgets, 200], [$widgets, 404], [$orders, 201], [$orders, 500]])
        ->map(function (array $pair, int $index) {
            [$endpoint, $status] = $pair;
            $request = $endpoint->requests()->create(['unique_id' => "req-{$index}", 'path' => $endpoint->path]);

            return $endpoint->responses()->create([
                'request_id' => $request->id,
                'request_unique_id' => $request->unique_id,
                'status_code' => $status,
                'headers' => ['content-type' => ['application/json']],
                'body' => '{"ok":true}',
                'duration' => 0.05,
                'cached' => false,
            ]);
        });

    AmigoEndpointAggregate::create([
        'integration_id' => $integration->id,
        'endpoint_id' => $widgets->id,
        'window_start' => now()->subDay()->startOfHour(),
        'interval' => 3600,
        'total_requests' => 2,
        'successful_requests' => 1,
        'failed_requests' => 1,
        'avg_duration' => 0.05,
    ]);

    $listener = AmigoListener::create(['integration_id' => $integration->id, 'display_name' => 'Orders', 'handler' => ProcessTestWebhook::class]);
    $listener->createToken('ci');
    $webhooks = collect([null, ['code' => 500, 'message' => 'boom']])->map(fn (?array $error) => $listener->webhooks()->create([
        'sender' => '10.0.0.1',
        'headers' => ['content-type' => ['application/json']],
        'payload' => ['event' => 'created'],
        'processed_at' => now(),
        'error' => $error,
    ]));

    $recording = AmigoRecording::create(['name' => 'Debugging', 'started_at' => now(), 'global' => true]);
    $recording->requests()->attach($responses->pluck('request_id'));

    return compact('integration', 'connector', 'widgets', 'orders', 'responses', 'listener', 'webhooks', 'recording');
}

beforeEach(function () {
    $this->actingAs(User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'secret']));
    Filament::setCurrentPanel('admin');
});

it('renders every resource page', function () {
    $data = seedPanelData();

    $urls = [
        AmigoIntegrationResource::getUrl('index'),
        AmigoIntegrationResource::getUrl('view', ['record' => $data['integration']]),
        AmigoConnectorResource::getUrl('index'),
        AmigoConnectorResource::getUrl('view', ['record' => $data['connector']]),
        AmigoEndpointResource::getUrl('index'),
        AmigoEndpointResource::getUrl('view', ['record' => $data['widgets']]),
        AmigoEndpointResource::getUrl('stats', ['record' => $data['widgets']]),
        AmigoResponseResource::getUrl('index'),
        AmigoResponseResource::getUrl('view', ['record' => $data['responses'][1]]),
        AmigoListenerResource::getUrl('index'),
        AmigoListenerResource::getUrl('view', ['record' => $data['listener']]),
        AmigoWebhookResource::getUrl('index'),
        AmigoWebhookResource::getUrl('view', ['record' => $data['webhooks'][1]]),
        AmigoRecordingResource::getUrl('index'),
        AmigoRecordingResource::getUrl('view', ['record' => $data['recording']]),
    ];

    foreach ($urls as $url) {
        $this->get($url)->assertOk();
    }
});

it('renders every relation manager with its records', function (string $relationManager, string $owner, string $pageClass, int $count) {
    $data = seedPanelData();

    Livewire::test($relationManager, ['ownerRecord' => $data[$owner], 'pageClass' => $pageClass])
        ->assertOk()
        ->assertCountTableRecords($count);
})->with([
    'connector endpoints' => [AmigoEndpointsRelationManager::class, 'connector', ViewAmigoConnector::class, 2],
    'connector requests' => [ConnectorRequestsRelationManager::class, 'connector', ViewAmigoConnector::class, 4],
    'endpoint requests' => [EndpointRequestsRelationManager::class, 'widgets', ViewAmigoEndpoint::class, 2],
    'listener webhooks' => [AmigoWebhooksRelationManager::class, 'listener', ViewAmigoListener::class, 2],
    'listener tokens' => [TokensRelationManager::class, 'listener', ViewAmigoListener::class, 1],
    'recording requests' => [RecordingRequestsRelationManager::class, 'recording', ViewAmigoRecording::class, 4],
]);

it('creates a listener from the form', function () {
    APIAmigo::registerWebhookHandler(ProcessTestWebhook::class);

    Livewire::test(ListAmigoListeners::class)
        ->callAction('create', data: [
            'display_name' => 'Payments',
            'handler' => ProcessTestWebhook::class,
            'basic_auth_username' => 'amigo',
            'basic_auth_password' => 'secret',
        ])
        ->assertHasNoActionErrors();

    expect(AmigoListener::sole())
        ->display_name->toBe('Payments')
        ->handler->toBe(ProcessTestWebhook::class)
        ->basic_auth_password->toBe('secret');
});

it('searches responses by endpoint name, path, and integration', function () {
    $data = seedPanelData();
    $widgetResponses = $data['responses']->where('endpoint_id', $data['widgets']->id);

    Livewire::test(ListAmigoResponses::class)
        ->searchTable('GetWidget')
        ->assertCanSeeTableRecords($widgetResponses)
        ->assertCountTableRecords(2)
        ->searchTable('/orders')
        ->assertCountTableRecords(2)
        ->searchTable('acme')
        ->assertCountTableRecords(4)
        ->searchTable('nothing matches')
        ->assertCountTableRecords(0);
});

it('filters responses by success', function () {
    $data = seedPanelData();
    [$ok, $notFound, $created, $serverError] = $data['responses'];

    Livewire::test(ListAmigoResponses::class)
        ->filterTable('success', true)
        ->assertCanSeeTableRecords([$ok, $created])
        ->assertCanNotSeeTableRecords([$notFound, $serverError])
        ->filterTable('success', false)
        ->assertCanSeeTableRecords([$notFound, $serverError])
        ->assertCanNotSeeTableRecords([$ok, $created]);
});

it('lists failed webhooks by default', function () {
    [$succeeded, $failed] = seedPanelData()['webhooks'];

    Livewire::test(ListAmigoWebhooks::class)
        ->assertCanSeeTableRecords([$failed])
        ->assertCanNotSeeTableRecords([$succeeded]);
});

it('reprocesses a webhook with its handler', function () {
    $failed = seedPanelData()['webhooks'][1];

    Livewire::test(ListAmigoWebhooks::class)
        ->callTableAction('reprocess', $failed);

    expect($failed->fresh()->error)->toBeNull();
});

it('paginates with exact totals while the tables are small', function () {
    seedPanelData();

    expect(AmigoResponse::estimatedCount())->toBe(4)
        ->and(AmigoWebhook::estimatedCount())->toBe(2);

    Livewire::test(ListAmigoResponses::class)->assertCountTableRecords(4);
});
