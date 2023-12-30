<?php

namespace ChrisReedIO\APIAmigo\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Saloon\Http\Response;
use function class_basename;
use function config;
use function dump;

class AmigoResponse extends AmigoModel
{
    protected $fillable = [
        // 'integration_id',
        'request_id',
        'endpoint_id',
        'request_unique_id',
        'headers',
        'body',
        'status_code',
        'status_message',
        'duration',
    ];

    protected $casts = [
        'headers' => 'array',
        // 'body' => 'array',
    ];

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(AmigoEndpoint::class);
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(AmigoRequest::class, 'request_id');
    }

    public static function track(Response $saloonResponse): self
    {
        // $responseIdKey = config('api-amigo.responses.headers.keys.request_id');
        $rateLimitKey = config('api-amigo.responses.headers.keys.rate.limit');
        $rateLimitRemainingKey = config('api-amigo.responses.headers.keys.rate.remaining');
        // Here we need to log the response
        // Important things to track are the request URL, the response status code, and any quota headers
        $pendingRequest = $saloonResponse->getPendingRequest();
        $connector = $pendingRequest->getConnector();
        dump('Integration Connector: ' . class_basename($connector));

        $amigoRequestId = $pendingRequest->config()->get('amigo.request_id');
        $requestTime = $pendingRequest->config()->get('amigo.request_time');
        $responseTime = microtime(true);
        $responseTimeDelta = $responseTime - $requestTime;
        dump('Response Time: ' . round($responseTimeDelta, 3) . ' seconds');

        $rateLimit = $saloonResponse->headers()->get($rateLimitKey);
        $rateLimitRemaining = $saloonResponse->headers()->get($rateLimitRemainingKey);

        dump("Rate Limit Total: $rateLimit - Remaining: $rateLimitRemaining");

        dump('Original Request Config:', $pendingRequest->config()->all());

        // $responseRequestId = $saloonResponse->headers()->get($responseIdKey);

        dump('Amigo Request ID: ' . $amigoRequestId);
        // dump('Response Request ID: ' . $responseRequestId);

        dump('Full Response Headers:');
        dump($saloonResponse->headers()->all());

        try {
        $endpoint = AmigoEndpoint::track($saloonResponse->getPendingRequest());

        // Update latest rate limit information
        $endpoint->connector->rate_limit = $rateLimit;
        $endpoint->connector->rate_limit_remaining = $rateLimitRemaining;
        $endpoint->connector->save();

        return $endpoint->responses()->create([
            'request_id' => AmigoRequest::whereUniqueId($amigoRequestId)->first()?->id,
            'status_code' => $saloonResponse->status(),
            // 'status_message' => $response->status(),
            'headers' => $saloonResponse->headers()->all(),
            // TODO: Allow body tracking to be configurable
            // 'body' => $saloonResponse->body(),
            'request_unique_id' => $amigoRequestId,
            // 'rate_limit' => $rateLimit,
            // 'rate_limit_remaining' => $rateLimitRemaining,
            'duration' => $responseTimeDelta,
        ]);
        } catch (\Exception $e) {
            dump($e->getMessage());
            dd($e->getTraceAsString());
        }
    }
}
