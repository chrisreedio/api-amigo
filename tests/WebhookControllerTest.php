<?php

use ChrisReedIO\APIAmigo\APIAmigo;
use ChrisReedIO\APIAmigo\Models\AmigoIntegration;
use ChrisReedIO\APIAmigo\Models\AmigoListener;
use ChrisReedIO\APIAmigo\Models\AmigoWebhook;
use ChrisReedIO\APIAmigo\Tests\Fixtures\Webhooks\ProcessTestWebhook;

function makeListener(array $attributes = []): AmigoListener
{
    return AmigoListener::create(['display_name' => 'Test', 'handler' => ProcessTestWebhook::class, ...$attributes]);
}

function postWebhook(AmigoListener $listener, array $payload = ['event' => 'created'], array $headers = [])
{
    return test()->postJson(route('webhooks.handler', $listener), $payload, $headers);
}

function signature(array $payload, string $secret): string
{
    return hash_hmac('sha256', json_encode($payload), $secret);
}

it('stores and processes a webhook', function () {
    $listener = makeListener();

    $response = postWebhook($listener, ['event' => 'created', 'id' => 7]);

    $webhook = AmigoWebhook::sole();
    $response->assertOk()->assertExactJson(['webhook_unique_id' => $webhook->unique_id]);
    expect($webhook->listener_id)->toBe($listener->id)
        ->and($webhook->payload)->toBe(['event' => 'created', 'id' => 7])
        ->and($webhook->processed_at)->not->toBeNull()
        ->and($webhook->error)->toBeNull()
        ->and($listener->fresh()->uses)->toBe(1);
});

it('records the error when the handler throws', function () {
    $listener = makeListener();

    postWebhook($listener, ['fail' => true])->assertOk();

    $webhook = AmigoWebhook::sole();
    expect($webhook->processed_at)->not->toBeNull()
        ->and($webhook->error['message'])->toBe('Handler failed')
        ->and($listener->fresh()->uses)->toBe(0);
});

it('fails the webhook when the listener has no handler', function () {
    postWebhook(makeListener(['handler' => null]))->assertStatus(500);

    expect(AmigoWebhook::sole()->error)->toBe(['code' => 500, 'message' => 'No handler configured']);
});

it('checks basic auth credentials', function () {
    $listener = makeListener(['basic_auth_username' => 'amigo', 'basic_auth_password' => 'secret']);

    postWebhook($listener)->assertUnauthorized();
    postWebhook($listener, headers: ['Authorization' => 'Basic ' . base64_encode('amigo:wrong')])->assertUnauthorized();
    postWebhook($listener, headers: ['Authorization' => 'Basic ' . base64_encode('amigo:secret')])->assertOk();

    expect(AmigoWebhook::count())->toBe(1);
});

it('checks the signature with the listener secret, falling back to the integration secret', function (array $listenerAttributes) {
    $payload = ['event' => 'created'];
    $listener = makeListener($listenerAttributes);

    postWebhook($listener, $payload)->assertUnauthorized();
    postWebhook($listener, $payload, ['X-Signature' => signature($payload, 'wrong')])->assertUnauthorized();
    postWebhook($listener, $payload, ['X-Signature' => signature($payload, 'shh')])->assertOk();

    expect(AmigoWebhook::count())->toBe(1);
})->with([
    'listener secret' => fn () => ['webhook_secret' => 'shh'],
    'integration secret' => fn () => ['integration_id' => AmigoIntegration::create(['name' => 'Acme', 'webhook_secret' => 'shh'])->id],
]);

it('checks bearer tokens when the listener has any', function () {
    $listener = makeListener();
    $token = $listener->createToken('ci')->plainTextToken;
    $plainToken = explode('|', $token, 2)[1];

    postWebhook($listener)->assertUnauthorized();
    postWebhook($listener, headers: ['Authorization' => 'Bearer not-a-token'])->assertUnauthorized();
    postWebhook($listener, headers: ['Authorization' => "Bearer {$plainToken}"])->assertOk();

    expect(AmigoWebhook::count())->toBe(1)
        ->and($listener->tokens()->sole()->last_used_at)->not->toBeNull();
});

it('rejects expired bearer tokens', function () {
    $listener = makeListener();
    $plainToken = explode('|', $listener->createToken('ci', expiresAt: now()->subMinute())->plainTextToken, 2)[1];

    postWebhook($listener, headers: ['Authorization' => "Bearer {$plainToken}"])->assertUnauthorized();

    expect(AmigoWebhook::count())->toBe(0);
});

it('rejects listeners that are used up or expired', function (array $attributes) {
    postWebhook(makeListener($attributes))->assertStatus(410);

    expect(AmigoWebhook::count())->toBe(0);
})->with([
    'max uses reached' => [['uses' => 3, 'max_uses' => 3]],
    'expired' => fn () => ['expires_at' => now()->subMinute()],
]);

it('accepts listeners under their limits', function () {
    postWebhook(makeListener(['uses' => 2, 'max_uses' => 3, 'expires_at' => now()->addMinute()]))->assertOk();
});

it('fails the webhook when the handler reports failure without an error', function () {
    $listener = makeListener();

    postWebhook($listener, ['reject' => true])->assertOk();

    expect(AmigoWebhook::sole()->error)->toBe(['code' => 500, 'message' => 'An unknown error occurred.'])
        ->and($listener->fresh()->uses)->toBe(0);
});

it('registers only webhook job classes as handlers', function () {
    APIAmigo::registerWebhookHandler(ProcessTestWebhook::class);

    expect(APIAmigo::getWebhookHandlers())->toContain(ProcessTestWebhook::class)
        ->and(fn () => APIAmigo::registerWebhookHandler('App\\Missing'))->toThrow(InvalidArgumentException::class, 'does not exist')
        ->and(fn () => APIAmigo::registerWebhookHandler(stdClass::class))->toThrow(InvalidArgumentException::class, 'must extend');
});
