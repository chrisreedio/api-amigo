<?php

use ChrisReedIO\APIAmigo\Models\AmigoListener;
use ChrisReedIO\APIAmigo\Models\AmigoWebhook;
use ChrisReedIO\APIAmigo\Tests\Fixtures\Account;
use ChrisReedIO\APIAmigo\Tests\Fixtures\Webhooks\ProcessTestWebhook;
use Illuminate\Support\Carbon;

function makeAccount(string $name = 'Globex'): Account
{
    return Account::create(['name' => $name, 'email' => uniqid() . '@example.test', 'password' => 'secret']);
}

beforeEach(fn () => Carbon::setTestNow('2026-06-01 12:00:00'));

it('creates a single-use transient listener that expires in a day', function () {
    $account = makeAccount();

    $listener = $account->createTransientListener(ProcessTestWebhook::class);

    expect($listener->display_name)->toBe('ProcessTestWebhook for Account: Globex')
        ->and($listener->handler)->toBe(ProcessTestWebhook::class)
        ->and($listener->max_uses)->toBe(1)
        ->and($listener->expires_at)->toEqual(Carbon::parse('2026-06-02 12:00:00'))
        ->and($listener->listenable->is($account))->toBeTrue()
        ->and($account->listeners()->pluck('id')->all())->toBe([$listener->id]);
});

it('creates a transient listener with a custom expiry and use count', function () {
    $listener = makeAccount()->createTransientListener(ProcessTestWebhook::class, Carbon::parse('2026-06-10'), 5);

    expect($listener->max_uses)->toBe(5)
        ->and($listener->expires_at)->toEqual(Carbon::parse('2026-06-10'));
});

it('rejects a handler that is not a webhook job', function () {
    makeAccount()->createTransientListener(stdClass::class);
})->throws(InvalidArgumentException::class);

it('expires unused active listeners, optionally for one handler only', function () {
    $account = makeAccount();
    $unused = $account->createTransientListener(ProcessTestWebhook::class);
    $otherHandler = $account->listeners()->create(['handler' => 'App\\OtherJob', 'expires_at' => now()->addDay()]);
    $used = $account->createTransientListener(ProcessTestWebhook::class);
    $used->update(['uses' => 1]);
    $alreadyExpired = $account->createTransientListener(ProcessTestWebhook::class, now()->subHour());
    $otherAccount = makeAccount('Initech')->createTransientListener(ProcessTestWebhook::class);

    $account->pruneUnusedListeners(ProcessTestWebhook::class);

    expect($unused->fresh()->expires_at)->toEqual(now())
        ->and($otherHandler->fresh()->expires_at)->toEqual(now()->addDay())
        ->and($used->fresh()->expires_at)->toEqual(now()->addDay())
        ->and($alreadyExpired->fresh()->expires_at)->toEqual(now()->subHour())
        ->and($otherAccount->fresh()->expires_at)->toEqual(now()->addDay());

    $account->pruneUnusedListeners();

    expect($otherHandler->fresh()->expires_at)->toEqual(now());
});

it('replaces the unused listener for a handler when generating a new one', function () {
    $account = makeAccount();
    $old = $account->createTransientListener(ProcessTestWebhook::class);
    $used = $account->createTransientListener(ProcessTestWebhook::class);
    $used->update(['uses' => 1]);

    $new = $account->generateTransientListener(ProcessTestWebhook::class);

    expect($account->listeners()->pluck('id')->sort()->values()->all())->toBe([$used->id, $new->id])
        ->and(AmigoListener::find($old->id))->toBeNull();
});

it('scopes listeners by expiry and transience, also when combined', function () {
    $permanent = AmigoListener::create(['handler' => ProcessTestWebhook::class]);
    $limited = AmigoListener::create(['handler' => ProcessTestWebhook::class, 'max_uses' => 3]);
    $expired = AmigoListener::create(['handler' => ProcessTestWebhook::class, 'expires_at' => now()->subMinute()]);
    $owned = makeAccount()->createTransientListener(ProcessTestWebhook::class);

    $ids = fn ($query) => $query->orderBy('id')->pluck('id')->all();

    expect($ids(AmigoListener::expired()))->toBe([$expired->id])
        ->and($ids(AmigoListener::expired(false)))->toBe([$permanent->id, $limited->id, $owned->id])
        ->and($ids(AmigoListener::transient()))->toBe([$limited->id, $expired->id, $owned->id])
        ->and($ids(AmigoListener::transient(false)))->toBe([$permanent->id])
        // Each scope's OR conditions stay grouped, so combining them narrows the result.
        ->and($ids(AmigoListener::transient()->expired(false)))->toBe([$limited->id, $owned->id]);
});

it('links webhooks to the model they belong to', function () {
    $account = makeAccount();
    $webhook = AmigoWebhook::create([
        'listener_id' => AmigoListener::create(['handler' => ProcessTestWebhook::class])->id,
        'payload' => ['event' => 'created'],
        'webhookable_id' => $account->id,
        'webhookable_type' => $account->getMorphClass(),
    ]);

    expect($account->webhooks()->pluck('id')->all())->toBe([$webhook->id])
        ->and($webhook->webhookable->is($account))->toBeTrue();
});
