<?php

use ChrisReedIO\APIAmigo\APIAmigo;
use ChrisReedIO\APIAmigo\Enums\HTTPStatus;
use ChrisReedIO\APIAmigo\Models\AmigoConnector;
use ChrisReedIO\APIAmigo\Models\AmigoEndpoint;
use ChrisReedIO\APIAmigo\Models\AmigoRecording;
use ChrisReedIO\APIAmigo\Models\AmigoRequest;
use ChrisReedIO\APIAmigo\Models\AmigoResponse;
use ChrisReedIO\APIAmigo\Tests\Fixtures\Integrations\Acme\AcmeConnector;
use ChrisReedIO\APIAmigo\Tests\Fixtures\Integrations\Acme\Requests\GetWidget;
use ChrisReedIO\APIAmigo\Tests\Fixtures\User;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

function sendWidgetRequests(MockResponse ...$responses): void
{
    $connector = (new AcmeConnector)->withMockClient(new MockClient($responses));

    foreach (array_keys($responses) as $widgetId) {
        $connector->send(new GetWidget($widgetId + 41));
    }
}

it('tracks Saloon requests under one endpoint per request class', function () {
    sendWidgetRequests(
        MockResponse::make(['id' => 41], 200, ['X-RateLimit-Limit' => '100', 'X-RateLimit-Remaining' => '99']),
        MockResponse::make(['id' => 42], 200, ['X-RateLimit-Limit' => '100', 'X-RateLimit-Remaining' => '98']),
    );

    $endpoint = AmigoEndpoint::sole();
    expect($endpoint->path)->toBe('/widgets/{widgetId}')
        ->and($endpoint->name)->toBe('GetWidget')
        ->and($endpoint->connector->getRawOriginal('name'))->toBe('AcmeConnector')
        ->and($endpoint->connector->base_url)->toBe('api.acme.test')
        ->and($endpoint->connector->integration->name)->toBe('Acme')
        // The connector keeps the rate limit from the latest response.
        ->and($endpoint->connector->rate_limit)->toBe(100)
        ->and($endpoint->connector->rate_limit_remaining)->toBe(98);

    $requests = AmigoRequest::orderBy('id')->get();
    expect($requests->pluck('path')->all())->toBe(['/widgets/41', '/widgets/42']);

    foreach ($requests as $request) {
        expect($request->response)->not->toBeNull()
            ->and($request->response->request_unique_id)->toBe($request->unique_id)
            ->and($request->response->endpoint_id)->toBe($endpoint->id)
            ->and($request->response->status_code)->toBe(HTTPStatus::OK);
    }
});

it('only captures response bodies for errors by default', function () {
    sendWidgetRequests(
        MockResponse::make(['ok' => true], 200),
        MockResponse::make(['error' => 'boom'], 500),
    );

    [$success, $failure] = AmigoResponse::orderBy('id')->get();

    expect($success->getRawOriginal('body'))->toBeNull()
        ->and($failure->status_code)->toBe(HTTPStatus::INTERNAL_SERVER_ERROR)
        ->and($failure->getRawOriginal('body'))->toContain('boom');
});

it('captures every body when configured', function () {
    config()->set('api-amigo.responses.capture_body', true);

    sendWidgetRequests(MockResponse::make(['ok' => true], 200));

    expect(AmigoResponse::sole()->getRawOriginal('body'))->toContain('ok');
});

it('attaches requests to active recordings and captures bodies they ask for', function () {
    $owner = User::create(['name' => 'Owner', 'email' => 'owner@example.com', 'password' => 'secret']);
    $other = User::create(['name' => 'Other', 'email' => 'other@example.com', 'password' => 'secret']);
    // Recordings belong to whoever is signed in when they're created.
    $this->actingAs($owner);
    $recording = fn (array $attributes) => AmigoRecording::create(['started_at' => now(), ...$attributes]);

    $global = $recording(['global' => true, 'capture_body' => true]);
    $ownersOnly = $recording(['global' => false]);
    $otherConnector = $recording(['global' => true, 'connector_id' => AmigoConnector::create([
        'name' => 'OtherConnector',
        'base_url' => 'other.test',
    ])->id]);
    $ended = $recording(['global' => true, 'ended_at' => now()]);

    $this->actingAs($other);
    sendWidgetRequests(MockResponse::make(['ok' => true], 200));

    $request = AmigoRequest::sole();
    expect($request->recordings->modelKeys())->toBe([$global->id])
        ->and($request->user_id)->toBe($other->id)
        ->and($request->response->getRawOriginal('body'))->toContain('ok');

    expect($ownersOnly->requests()->count())->toBe(0)
        ->and($otherConnector->requests()->count())->toBe(0)
        ->and($ended->requests()->count())->toBe(0);
});

it('tracks Guzzle requests made through a hooked client', function () {
    $client = APIAmigo::hookGuzzle(new Client([
        'base_uri' => 'https://legacy.example.test',
        'handler' => HandlerStack::create(new MockHandler([
            new Response(200, [], '{"ok":true}'),
            new Response(503, [], '{"error":"down"}'),
        ])),
        'http_errors' => false,
    ]));

    $ok = $client->get('/status?verbose=1');
    $down = $client->get('/status');

    // Tracking must not consume the body the caller reads.
    expect((string) $ok->getBody())->toBe('{"ok":true}')
        ->and((string) $down->getBody())->toBe('{"error":"down"}');

    $endpoint = AmigoEndpoint::sole();
    expect($endpoint->path)->toBe('/status')
        ->and($endpoint->connector->getRawOriginal('name'))->toBe('legacy.example.test via Guzzle');

    $requests = AmigoRequest::with('response')->orderBy('id')->get();
    expect($requests->pluck('path')->all())->toBe(['/status?verbose=1', '/status'])
        ->and($requests->map(fn ($request) => $request->response->status_code->value)->all())->toBe([200, 503])
        ->and($requests[1]->response->body)->toBe(['error' => 'down']);
});
