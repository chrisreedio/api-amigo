<?php

use ChrisReedIO\APIAmigo\Models\AmigoConnector;
use ChrisReedIO\APIAmigo\Models\AmigoEndpoint;
use ChrisReedIO\APIAmigo\Models\AmigoEndpointAggregate;
use ChrisReedIO\APIAmigo\Models\AmigoIntegration;
use ChrisReedIO\APIAmigo\Models\AmigoResponse;
use Illuminate\Support\Carbon;

function makeEndpoint(): AmigoEndpoint
{
    $integration = AmigoIntegration::create(['name' => 'Acme']);
    $connector = AmigoConnector::create(['integration_id' => $integration->id, 'name' => 'AcmeConnector', 'base_url' => 'api.acme.test']);

    return AmigoEndpoint::create(['connector_id' => $connector->id, 'method' => 'GET', 'name' => 'GetWidget', 'path' => '/widgets/{id}']);
}

function makeResponse(AmigoEndpoint $endpoint, string $createdAt, int $status = 200, float $duration = 0.1): AmigoResponse
{
    $response = $endpoint->responses()->make([
        'request_unique_id' => uniqid('req-', true),
        'status_code' => $status,
        'duration' => $duration,
    ]);
    $response->created_at = Carbon::parse($createdAt);
    $response->save();

    return $response;
}

it('aggregates a day of responses into hourly windows', function () {
    $endpoint = makeEndpoint();

    // 09:00 window: four responses, one failed, two within the same minute.
    makeResponse($endpoint, '2026-03-10 09:05:10', duration: 0.1);
    makeResponse($endpoint, '2026-03-10 09:05:40', duration: 0.2);
    makeResponse($endpoint, '2026-03-10 09:30:00', duration: 0.3);
    makeResponse($endpoint, '2026-03-10 09:45:00', status: 500, duration: 0.4);
    // 14:00 window: one response.
    makeResponse($endpoint, '2026-03-10 14:20:00', duration: 0.5);
    // The next day is outside the requested date.
    makeResponse($endpoint, '2026-03-11 09:05:00');

    $this->artisan('responses:aggregate', ['startDate' => '2026-03-10'])->assertSuccessful();

    $aggregates = AmigoEndpointAggregate::orderBy('window_start')->get();
    expect($aggregates)->toHaveCount(2);

    [$morning, $afternoon] = $aggregates;
    expect($morning->window_start)->toEqual(Carbon::parse('2026-03-10 09:00:00'))
        ->and($morning->endpoint_id)->toBe($endpoint->id)
        ->and($morning->integration_id)->toBe($endpoint->connector->integration_id)
        ->and($morning->interval)->toBe(3600)
        ->and($morning->total_requests)->toBe(4)
        ->and($morning->successful_requests)->toBe(3)
        ->and($morning->failed_requests)->toBe(1)
        ->and($morning->max_requests_per_minute)->toBe(2)
        ->and($morning->min_duration)->toEqualWithDelta(0.1, 0.0001)
        ->and($morning->max_duration)->toEqualWithDelta(0.4, 0.0001)
        ->and($morning->avg_duration)->toEqualWithDelta(0.25, 0.0001)
        // Interpolated median of 0.1, 0.2, 0.3, 0.4.
        ->and($morning->p50_duration)->toEqualWithDelta(0.25, 0.0001);

    expect($afternoon->window_start)->toEqual(Carbon::parse('2026-03-10 14:00:00'))
        ->and($afternoon->total_requests)->toBe(1);
});

it('does not duplicate aggregates when run again', function () {
    makeResponse(makeEndpoint(), '2026-03-10 09:05:00');

    $this->artisan('responses:aggregate', ['startDate' => '2026-03-10'])->assertSuccessful();
    $this->artisan('responses:aggregate', ['startDate' => '2026-03-10'])->assertSuccessful();

    expect(AmigoEndpointAggregate::count())->toBe(1);
});

it('prunes responses older than the configured lifetime', function () {
    Carbon::setTestNow('2026-06-01 12:00:00');
    config()->set('api-amigo.aggregation.prune_lifetime_days', 30);
    $endpoint = makeEndpoint();

    $old = makeResponse($endpoint, '2026-05-02 11:59:00');
    $recent = makeResponse($endpoint, '2026-05-02 12:01:00');

    $this->artisan('responses:prune', ['--force' => true])->assertSuccessful();

    expect(AmigoResponse::pluck('id')->all())->toBe([$recent->id])
        ->and(AmigoResponse::find($old->id))->toBeNull();
});

it('aggregates every day in a date range', function () {
    Carbon::setTestNow('2026-06-01 12:00:00');
    $endpoint = makeEndpoint();
    makeResponse($endpoint, '2026-03-09 23:30:00');
    makeResponse($endpoint, '2026-03-10 09:05:00');
    makeResponse($endpoint, '2026-03-11 18:15:00');
    makeResponse($endpoint, '2026-03-12 00:30:00');

    $this->artisan('responses:aggregate', ['startDate' => '2026-03-10', 'endDate' => '2026-03-11'])->assertSuccessful();

    expect(AmigoEndpointAggregate::orderBy('window_start')->pluck('window_start')->map(fn ($start) => (string) $start)->all())
        ->toBe(['2026-03-10 09:00:00', '2026-03-11 18:00:00']);
});

it('produces the same aggregates in bulk mode', function () {
    Carbon::setTestNow('2026-06-01 12:00:00');
    $endpoint = makeEndpoint();
    makeResponse($endpoint, '2026-03-10 09:05:00', duration: 0.1);
    makeResponse($endpoint, '2026-03-10 09:06:00', status: 502, duration: 0.3);
    makeResponse($endpoint, '2026-03-11 18:15:00');

    $this->artisan('responses:aggregate', ['startDate' => '2026-03-10', 'endDate' => '2026-03-11', '--bulk' => true])->assertSuccessful();

    $aggregates = AmigoEndpointAggregate::orderBy('window_start')->get();
    expect($aggregates)->toHaveCount(2)
        ->and((string) $aggregates[0]->window_start)->toBe('2026-03-10 09:00:00')
        ->and($aggregates[0]->total_requests)->toBe(2)
        ->and($aggregates[0]->failed_requests)->toBe(1)
        ->and($aggregates[0]->max_requests_per_minute)->toBe(1)
        ->and($aggregates[0]->p50_duration)->toEqualWithDelta(0.2, 0.0001)
        ->and((string) $aggregates[1]->window_start)->toBe('2026-03-11 18:00:00');
});

it('aggregates from the earliest response through yesterday with --all', function () {
    Carbon::setTestNow('2026-03-12 08:00:00');
    $endpoint = makeEndpoint();
    makeResponse($endpoint, '2026-03-09 10:00:00');
    makeResponse($endpoint, '2026-03-11 22:00:00');
    // Today is left for the next run.
    makeResponse($endpoint, '2026-03-12 07:00:00');

    $this->artisan('responses:aggregate', ['--all' => true])->assertSuccessful();

    expect(AmigoEndpointAggregate::orderBy('window_start')->pluck('window_start')->map(fn ($start) => (string) $start)->all())
        ->toBe(['2026-03-09 10:00:00', '2026-03-11 22:00:00']);
});

it('reports when --all finds no responses', function () {
    $this->artisan('responses:aggregate', ['--all' => true])
        ->expectsOutputToContain('No responses found to aggregate.')
        ->assertSuccessful();

    expect(AmigoEndpointAggregate::count())->toBe(0);
});

it('aggregates yesterday with the daily command', function () {
    Carbon::setTestNow('2026-03-11 00:30:00');
    $endpoint = makeEndpoint();
    makeResponse($endpoint, '2026-03-09 23:59:00');
    makeResponse($endpoint, '2026-03-10 00:10:00');
    makeResponse($endpoint, '2026-03-10 23:10:00');
    makeResponse($endpoint, '2026-03-11 00:10:00');

    $this->artisan('responses:aggregate-daily')->assertSuccessful();

    expect(AmigoEndpointAggregate::orderBy('window_start')->pluck('window_start')->map(fn ($start) => (string) $start)->all())
        ->toBe(['2026-03-10 00:00:00', '2026-03-10 23:00:00']);
});

it('asks before pruning without --force', function (bool $confirmed) {
    Carbon::setTestNow('2026-06-01 12:00:00');
    config()->set('api-amigo.aggregation.prune_lifetime_days', 30);
    $endpoint = makeEndpoint();
    makeResponse($endpoint, '2026-04-01 12:00:00');
    makeResponse($endpoint, '2026-04-02 12:00:00');
    makeResponse($endpoint, '2026-05-20 12:00:00');

    $this->artisan('responses:prune')
        ->expectsOutputToContain('This will delete 2 responses older than 2026-05-02 12:00:00 (30 days ago).')
        ->expectsConfirmation('Are you sure you want to continue?', $confirmed ? 'yes' : 'no')
        ->expectsOutputToContain($confirmed ? 'Successfully deleted 2 old responses.' : 'Operation cancelled.')
        ->assertSuccessful();

    expect(AmigoResponse::count())->toBe($confirmed ? 1 : 3);
})->with(['confirmed' => true, 'declined' => false]);

it('counts a response on the hour in one window only', function () {
    $endpoint = makeEndpoint();
    makeResponse($endpoint, '2026-03-10 09:59:59');
    makeResponse($endpoint, '2026-03-10 10:00:00');

    $this->artisan('responses:aggregate', ['startDate' => '2026-03-10'])->assertSuccessful();

    expect(AmigoEndpointAggregate::orderBy('window_start')->get()->map(fn ($aggregate) => [(string) $aggregate->window_start, $aggregate->total_requests, $aggregate->max_requests_per_minute])->all())
        ->toBe([['2026-03-10 09:00:00', 1, 1], ['2026-03-10 10:00:00', 1, 1]]);
});
