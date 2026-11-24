<?php

use ChrisReedIO\APIAmigo\Models\AmigoConnector;
use ChrisReedIO\APIAmigo\Models\AmigoIntegration;
use ChrisReedIO\APIAmigo\Models\AmigoRecording;
use ChrisReedIO\APIAmigo\Tests\Fixtures\User;
use Illuminate\Support\Carbon;

it('reports how much of the rate limit a connector has used', function (?int $limit, ?int $remaining, int $usage, float $percentage) {
    $connector = new AmigoConnector(['rate_limit' => $limit, 'rate_limit_remaining' => $remaining]);

    expect($connector->rate_usage)->toBe($usage)
        ->and($connector->rate_usage_percentage)->toEqual($percentage);
})->with([
    'unknown limit' => [null, null, 0, 0],
    'nothing used' => [100, 100, 0, 0],
    'partly used' => [200, 150, 50, 25],
    'exhausted' => [100, 0, 100, 100],
    'zero limit' => [0, 0, 0, 0],
]);

it('starts and stops a recording once each', function () {
    Carbon::setTestNow('2026-06-01 12:00:00');
    $this->actingAs(User::create(['name' => 'Owner', 'email' => 'owner@example.com', 'password' => 'secret']));
    $connector = AmigoConnector::create(['integration_id' => AmigoIntegration::create(['name' => 'Acme'])->id, 'name' => 'AcmeConnector', 'base_url' => 'api.acme.test']);
    $recording = AmigoRecording::create(['connector_id' => $connector->id]);

    expect($recording->stop())->toBeFalse()
        ->and(AmigoRecording::active()->exists())->toBeFalse()
        ->and($recording->start())->toBeTrue()
        ->and($recording->start())->toBeFalse()
        ->and($recording->fresh()->started_at)->toEqual(now())
        ->and(AmigoRecording::active()->pluck('id')->all())->toBe([$recording->id]);

    Carbon::setTestNow('2026-06-01 13:00:00');

    expect($recording->stop())->toBeTrue()
        ->and($recording->stop())->toBeFalse()
        ->and($recording->fresh()->ended_at)->toEqual(now())
        ->and(AmigoRecording::active()->exists())->toBeFalse();
});
