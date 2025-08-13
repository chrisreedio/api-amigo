<?php

namespace ChrisReedIO\APIAmigo\Models;

use const JSON_PRETTY_PRINT;

use ChrisReedIO\APIAmigo\Enums\HTTPStatus;
use Exception;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Saloon\Http\Response;

use function class_basename;
use function config;
use function gettype;

/**
 * AmigoResponse
 *
 * Represents a response from an API request
 *
 * @property int $id
 * @property int $endpoint_id
 * @property int $request_id
 * @property string $request_unique_id
 * @property array $headers
 * @property array $body
 * @property HTTPStatus $status_code
 * @property string $status_message
 * @property float $duration
 * @property bool $cached
 */
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
        'cached',
    ];

    protected $casts = [
        'headers' => 'array',
        'status_code' => HTTPStatus::class,
        'body' => 'array',
    ];

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(AmigoEndpoint::class);
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(AmigoRequest::class, 'request_id');
    }

    public static function track(Response $saloonResponse): ?self
    {
        // dd('hi', $saloonResponse);
        // $responseIdKey = config('api-amigo.responses.headers.keys.request_id');
        $rateLimitKey = config('api-amigo.responses.headers.keys.rate.limit');
        $rateLimitRemainingKey = config('api-amigo.responses.headers.keys.rate.remaining');
        // Here we need to log the response
        // Important things to track are the request URL, the response status code, and any quota headers
        $pendingRequest = $saloonResponse->getPendingRequest();
        $connector = $pendingRequest->getConnector();
        // dump('Integration Connector: ' . class_basename($connector));

        $amigoRequestId = $pendingRequest->config()->get('amigo.request_id');
        $requestTime = $pendingRequest->config()->get('amigo.request_time');
        $responseTime = microtime(true);
        $responseTimeDelta = $responseTime - $requestTime;
        // dump('Response Time: ' . round($responseTimeDelta, 3) . ' seconds');

        $rateLimit = $saloonResponse->headers()->get($rateLimitKey);
        $rateLimitRemaining = $saloonResponse->headers()->get($rateLimitRemainingKey);

        // dump("Rate Limit Total: $rateLimit - Remaining: $rateLimitRemaining");

        // dump('Original Request Config:', $pendingRequest->config()->all());

        // $responseRequestId = $saloonResponse->headers()->get($responseIdKey);

        // dump('Amigo Request ID: ' . $amigoRequestId);
        // dump('Response Request ID: ' . $responseRequestId);

        // dump('Full Response Headers:');
        // dump($saloonResponse->headers()->all());

        try {
            $endpoint = AmigoEndpoint::track($saloonResponse->getPendingRequest());

            // Update latest rate limit information
            $endpoint->connector->rate_limit = $rateLimit;
            $endpoint->connector->rate_limit_remaining = $rateLimitRemaining;
            $endpoint->connector->save();

            // Find the request that this response belongs to (if any)
            $request = AmigoRequest::where('unique_id', $amigoRequestId)->first();

            // Should we log the response body?
            // Do we have an active recording that wants to capture the body?
            $recordBody = $request?->recordings()->active()->where('capture_body', true)->count() > 0;
            // Capture all bodies based on config
            if (! $recordBody) {
                $recordBody = config('api-amigo.responses.capture_body');
            }
            // Capture Error bodies if enabled
            if (! $recordBody) {
                $recordBody = config('api-amigo.responses.capture_body_on_error') && $saloonResponse->failed();
            }

            // dd("'" . $saloonResponse->body() . "'");
            // dd('Cached Response? ', $saloonResponse->isCached());
            return $endpoint->responses()->create([
                'request_id' => $request?->id,
                'status_code' => $saloonResponse->status(),
                // 'status_message' => $response->status(),
                'headers' => $saloonResponse->headers()->all(),
                'body' => $recordBody ? trim($saloonResponse->body()) : null,
                'request_unique_id' => $amigoRequestId,
                // 'rate_limit' => $rateLimit,
                // 'rate_limit_remaining' => $rateLimitRemaining,
                'duration' => $responseTimeDelta,
                'cached' => $saloonResponse->isCached(),
            ]);
        } catch (Exception $e) {
            Log::error('API Amigo failed to track response: ' . $e->getMessage(), [
                'exception' => $e,
                'response' => $saloonResponse,
            ]);

            return null;
            // dump($e->getMessage());
            // dd($e->getTraceAsString());
        }
    }

    public function getBodyAttribute()
    {
        return json_decode(json_decode($this->attributes['body'], true), true);
    }

    public function getEncodedBodyAttribute(): string
    {
        // $json = $this->body;
        // dump(gettype($this->getOriginal('body')));
        // dd(gettype($json));
        return json_encode($this->body, JSON_PRETTY_PRINT);
        // return json_decode($this->attributes['body'], true, JSON_PRETTY_PRINT);
        // return $this->body;
    }

    public function getBodySizeAttribute(): float
    {
        return (float) strlen($this->attributes['body']);
    }
}
