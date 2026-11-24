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
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoRequestResource;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoRequestResource\Pages\ListAmigoRequests;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoRequestResource\Pages\ViewAmigoRequest;
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
use ChrisReedIO\APIAmigo\Models\AmigoRequest;
use ChrisReedIO\APIAmigo\Models\AmigoResponse;
use ChrisReedIO\APIAmigo\Models\AmigoWebhook;
use ChrisReedIO\APIAmigo\Tests\Fixtures\User;
use ChrisReedIO\APIAmigo\Tests\Fixtures\Webhooks\ProcessTestWebhook;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
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
        AmigoRequestResource::getUrl('index'),
        AmigoRequestResource::getUrl('view', ['record' => $data['responses'][1]->request_id]),
        AmigoResponseResource::getUrl('index'),
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

    // A response's page sends you to its request's page, which shows both sides of the call.
    $this->get(AmigoResponseResource::getUrl('view', ['record' => $data['responses'][1]]))
        ->assertRedirect(AmigoRequestResource::getUrl('view', ['record' => $data['responses'][1]->request_id]));
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

/** Requests from seedPanelData plus one sent three days ago, with varied durations and one captured payload. */
function seedRequestList(): array
{
    $data = seedPanelData();
    $requests = AmigoRequest::orderBy('id')->get();
    [$ok, $notFound, $created, $serverError] = $requests;

    $notFound->response->update(['duration' => 0.08]);
    $serverError->response->update(['duration' => 0.25]);
    $created->update(['headers' => ['Accept' => 'application/json'], 'body' => '{"sku":"A-1"}']);

    $old = $data['widgets']->requests()->create(['unique_id' => 'req-old', 'path' => '/widgets/9']);
    $old->forceFill(['created_at' => now()->subDays(3)])->save();

    return compact('ok', 'notFound', 'created', 'serverError', 'old');
}

it('lists the last 24 hours of requests by default', function () {
    ['old' => $old] = $recent = seedRequestList();
    unset($recent['old']);

    Livewire::test(ListAmigoRequests::class)
        ->assertCanSeeTableRecords($recent)
        ->assertCanNotSeeTableRecords([$old])
        ->filterTable('sent', 'week')
        ->assertCanSeeTableRecords([$old])
        ->filterTable('sent', 'hour')
        ->assertCanNotSeeTableRecords([$old])
        ->filterTable('sent', null)
        ->assertCountTableRecords(5);
});

it('filters requests by their response', function () {
    ['ok' => $ok, 'notFound' => $notFound, 'created' => $created, 'serverError' => $serverError] = seedRequestList();

    Livewire::test(ListAmigoRequests::class)
        ->filterTable('status_code', [404, 500])
        ->assertCanSeeTableRecords([$notFound, $serverError])
        ->assertCanNotSeeTableRecords([$ok, $created])
        ->removeTableFilter('status_code')
        ->filterTable('success', true)
        ->assertCanSeeTableRecords([$ok, $created])
        ->assertCanNotSeeTableRecords([$notFound, $serverError])
        ->filterTable('success', false)
        ->assertCanSeeTableRecords([$notFound, $serverError])
        ->assertCanNotSeeTableRecords([$ok, $created]);
});

it('filters slow requests by the error threshold or a custom cutoff', function () {
    ['ok' => $ok, 'notFound' => $notFound, 'serverError' => $serverError] = seedRequestList();
    config()->set('api-amigo.thresholds.duration.error', 0.25);

    Livewire::test(ListAmigoRequests::class)
        // The threshold itself counts as slow.
        ->filterTable('slow')
        ->assertCanSeeTableRecords([$serverError])
        ->assertCanNotSeeTableRecords([$ok, $notFound])
        ->removeTableFilter('slow')
        // A custom cutoff is in milliseconds and exclusive: 0.05s calls aren't slower than 50ms.
        ->filterTable('duration', ['slower_than' => 50])
        ->assertCanSeeTableRecords([$notFound, $serverError])
        ->assertCanNotSeeTableRecords([$ok]);
});

it('filters requests by whether their payload was captured', function () {
    ['ok' => $ok, 'created' => $created] = seedRequestList();

    Livewire::test(ListAmigoRequests::class)
        ->filterTable('captured', true)
        ->assertCanSeeTableRecords([$created])
        ->assertCanNotSeeTableRecords([$ok])
        ->filterTable('captured', false)
        ->assertCanSeeTableRecords([$ok])
        ->assertCanNotSeeTableRecords([$created]);
});

it('shows a request and its response side by side', function () {
    ['created' => $created, 'ok' => $ok] = seedRequestList();

    $this->get(AmigoRequestResource::getUrl('view', ['record' => $created]))
        ->assertOk()
        // Bodies are syntax-highlighted token by token, so this checks the tokens in reading order.
        ->assertSeeInOrder(['/orders', '201 Created', 'Request', 'sku', 'A-1', 'Response', 'ok', 'true']);

    $this->get(AmigoRequestResource::getUrl('view', ['record' => $ok]))
        ->assertOk()
        ->assertSee('Not captured. Enable api-amigo.responses.capture_body');
});

it('downloads the response body from the request page', function () {
    ['created' => $created] = seedRequestList();

    Livewire::test(ViewAmigoRequest::class, ['record' => $created->getRouteKey()])
        ->callAction('download_response')
        ->assertFileDownloaded("amigo-response-{$created->response->id}.json", '{"ok":true}');
});

it('counts large filtered results with the planner estimate', function () {
    $endpoint = seedPanelData()['widgets'];
    DB::insert("insert into amigo_requests (endpoint_id, unique_id, path, created_at, updated_at)
        select ?, 'bulk-' || n, '/widgets/' || n, now(), now() from generate_series(1, 20000) n", [$endpoint->id]);
    DB::statement('analyze amigo_requests');

    $query = AmigoRequest::query()->where('created_at', '>=', now()->subDay());
    DB::enableQueryLog();
    $estimate = AmigoRequest::estimatedCountFor($query);

    // No COUNT(*) ran, and the estimate is close to the 20,004 matching rows.
    expect(collect(DB::getQueryLog())->pluck('query')->filter(fn ($sql) => str_contains($sql, 'count(')))->toBeEmpty()
        ->and($estimate)->toBeGreaterThan(18_000)->toBeLessThan(22_000)
        // Small results are still counted exactly.
        ->and(AmigoRequest::estimatedCountFor(AmigoRequest::query()->where('path', '/orders')))->toBe(2);
});
