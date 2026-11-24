<?php

use ChrisReedIO\APIAmigo\APIAmigo;
use ChrisReedIO\APIAmigo\Enums\HTTPStatus;
use ChrisReedIO\APIAmigo\Models\AmigoConnector;
use ChrisReedIO\APIAmigo\Models\AmigoEndpoint;
use ChrisReedIO\APIAmigo\Models\AmigoRecording;
use ChrisReedIO\APIAmigo\Models\AmigoRequest;
use ChrisReedIO\APIAmigo\Models\AmigoResponse;
use ChrisReedIO\APIAmigo\Tests\Fixtures\Integrations\Acme\AcmeConnector;
use ChrisReedIO\APIAmigo\Tests\Fixtures\Integrations\Acme\Requests\CreateWidget;
use ChrisReedIO\APIAmigo\Tests\Fixtures\Integrations\Acme\Requests\GetStatus;
use ChrisReedIO\APIAmigo\Tests\Fixtures\Integrations\Acme\Requests\GetWidget;
use ChrisReedIO\APIAmigo\Tests\Fixtures\User;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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

it('tracks a call in 7 queries once its endpoint exists', function () {
    $connector = (new AcmeConnector)->withMockClient(new MockClient([
        MockResponse::make(['id' => 1], 200, ['X-RateLimit-Limit' => '100', 'X-RateLimit-Remaining' => '99']),
        MockResponse::make(['id' => 2], 200, ['X-RateLimit-Limit' => '100', 'X-RateLimit-Remaining' => '98']),
    ]));
    $connector->send(new GetWidget(1));

    DB::enableQueryLog();
    $connector->send(new GetWidget(2));

    // Endpoint lookup (3), request insert, active recordings, rate limit update, response insert.
    expect(DB::getQueryLog())->toHaveCount(7)
        ->and(AmigoResponse::count())->toBe(2);
});

it('only writes rate limits that changed', function () {
    sendWidgetRequests(MockResponse::make([], 200, ['X-RateLimit-Limit' => '100', 'X-RateLimit-Remaining' => '99']));
    $connector = AmigoConnector::sole();
    $connector->forceFill(['updated_at' => Carbon::parse('2026-01-01')])->saveQuietly();

    sendWidgetRequests(
        MockResponse::make([], 200, ['X-RateLimit-Limit' => '100', 'X-RateLimit-Remaining' => '99']),
        MockResponse::make([], 200),
    );

    // Neither an unchanged value nor a response without the headers touches the connector.
    expect($connector->fresh())
        ->rate_limit->toBe(100)
        ->rate_limit_remaining->toBe(99)
        ->updated_at->toEqual(Carbon::parse('2026-01-01'));

    sendWidgetRequests(MockResponse::make([], 200, ['X-RateLimit-Limit' => 'unlimited', 'X-RateLimit-Remaining' => '42']));

    expect($connector->fresh())
        ->rate_limit->toBeNull()
        ->rate_limit_remaining->toBe(42)
        ->and(AmigoResponse::count())->toBe(4);
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

it('captures the body when any of the request\'s recordings asks for it', function (bool $anyCaptures) {
    $this->actingAs(User::create(['name' => 'Owner', 'email' => 'owner@example.com', 'password' => 'secret']));
    $recordings = [
        AmigoRecording::create(['started_at' => now(), 'global' => true]),
        AmigoRecording::create(['started_at' => now(), 'global' => true, 'capture_body' => $anyCaptures]),
    ];

    sendWidgetRequests(MockResponse::make(['ok' => true], 200));

    $request = AmigoRequest::sole();
    expect($request->recordings->modelKeys())->toEqualCanonicalizing(array_map(fn ($recording) => $recording->id, $recordings))
        ->and($request->response->getRawOriginal('body') !== null)->toBe($anyCaptures);
})->with(['one recording captures' => true, 'none capture' => false]);

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

it('captures Guzzle response bodies for recordings that ask for it', function () {
    $this->actingAs(User::create(['name' => 'Owner', 'email' => 'owner@example.com', 'password' => 'secret']));
    $recording = AmigoRecording::create(['started_at' => now(), 'global' => true, 'capture_body' => true]);

    $client = APIAmigo::hookGuzzle(new Client([
        'base_uri' => 'https://legacy.example.test',
        'handler' => HandlerStack::create(new MockHandler([new Response(200, [], '{"ok":true}')])),
    ]));
    $client->get('/status');

    $request = AmigoRequest::sole();
    expect($request->recordings->modelKeys())->toBe([$recording->id])
        ->and($request->response->body)->toBe(['ok' => true]);
});

it('tracks a Saloon SoloRequest under its integration\'s Solo Requests connector', function () {
    (new GetStatus)->withMockClient(new MockClient([MockResponse::make(['up' => true])]))->send();

    $endpoint = AmigoEndpoint::sole();
    expect($endpoint->connector->getRawOriginal('name'))->toBe('Solo Requests')
        ->and($endpoint->connector->base_url)->toBe('status.acme.test')
        // Requests live in Acme\Requests, so the integration is the namespace above.
        ->and($endpoint->connector->integration->name)->toBe('Acme')
        ->and(AmigoResponse::sole()->endpoint_id)->toBe($endpoint->id);
});

it('captures the request payload with the response and redacts secret headers', function () {
    config()->set('api-amigo.responses.capture_body', true);
    config()->set('api-amigo.requests.redact_headers', ['authorization', 'X-Signature']);

    (new AcmeConnector)
        ->withMockClient(new MockClient([MockResponse::make(['id' => 7], 201)]))
        ->send(new CreateWidget(['name' => 'Sprocket']));

    $request = AmigoRequest::sole();
    expect($request->body)->toBe('{"name":"Sprocket"}')
        // Matching is case-insensitive in both directions; other headers are kept as sent.
        ->and($request->headers['Authorization'])->toBe('[redacted]')
        ->and($request->headers['X-Signature'])->toBe('[redacted]')
        ->and($request->headers['Accept'])->toBe('application/json')
        ->and(json_decode($request->response->getRawOriginal('body')))->toBe('{"id":7}');
});

it('captures the request payload only for failed responses by default', function () {
    (new AcmeConnector)
        ->withMockClient(new MockClient([MockResponse::make(['ok' => true], 200)]))
        ->send(new CreateWidget(['name' => 'Ok']));
    (new AcmeConnector)
        ->withMockClient(new MockClient([MockResponse::make(['error' => 'invalid'], 422)]))
        ->send(new CreateWidget(['name' => 'Bad']));

    [$succeeded, $failed] = AmigoRequest::orderBy('id')->get();
    expect($succeeded->headers)->toBeNull()
        ->and($succeeded->body)->toBeNull()
        ->and($failed->body)->toBe('{"name":"Bad"}')
        ->and($failed->headers['Authorization'])->toBe('[redacted]');
});

it('captures Guzzle request payloads, but not bodies too large to buffer', function () {
    config()->set('api-amigo.responses.capture_body', true);
    $history = [];
    $stack = HandlerStack::create(new MockHandler([new Response(200), new Response(200)]));
    $stack->push(Middleware::history($history));
    $client = APIAmigo::hookGuzzle(new Client(['base_uri' => 'https://legacy.example.test', 'handler' => $stack]));

    $client->post('/upload', ['body' => 'small=1', 'headers' => ['Authorization' => 'Bearer secret']]);
    $large = str_repeat('x', 1024 * 1024 + 1);
    $client->post('/upload', ['body' => $large]);

    [$small, $big] = AmigoRequest::orderBy('id')->get();
    expect($small->body)->toBe('small=1')
        ->and($small->headers['Authorization'])->toBe('[redacted]')
        ->and($big->body)->toBeNull()
        ->and($big->headers)->not->toBeNull()
        // Capturing must leave the bodies intact for the request that's actually sent.
        ->and((string) $history[0]['request']->getBody())->toBe('small=1')
        ->and(strlen((string) $history[1]['request']->getBody()))->toBe(strlen($large));
});

it('redacts the default headers when the app config predates redact_headers', function () {
    config()->set('api-amigo.responses.capture_body', true);
    // A config file published before 4.2 replaces the whole requests array, so the key is missing.
    config()->set('api-amigo.requests', ['header_key' => 'X-Amigo-Request-Id']);

    (new AcmeConnector)
        ->withMockClient(new MockClient([MockResponse::make(['id' => 7], 201)]))
        ->send(new CreateWidget(['name' => 'Sprocket']));

    expect(AmigoRequest::sole()->headers)
        ->Authorization->toBe('[redacted]')
        ->{'X-Signature'}->toBe('abc123');
});

it('stores every header as sent when redaction is turned off', function () {
    config()->set('api-amigo.responses.capture_body', true);
    config()->set('api-amigo.requests.redact_headers', []);

    (new AcmeConnector)
        ->withMockClient(new MockClient([MockResponse::make(['id' => 7], 201)]))
        ->send(new CreateWidget(['name' => 'Sprocket']));

    expect(AmigoRequest::sole()->headers['Authorization'])->toBe('Bearer secret');
});
