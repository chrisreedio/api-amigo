<?php

namespace ChrisReedIO\APIAmigo\Models;

use const JSON_PRETTY_PRINT;

use ChrisReedIO\APIAmigo\Enums\HTTPStatus;
use Exception;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Saloon\Http\Response;

use function config;
use function gettype;

/**
 * AmigoResponse
 *
 * Represents a response from an API request
 *
 * @property int $id
 * @property int $endpoint_id
 * @property ?int $request_id
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

    /** @return BelongsTo<AmigoEndpoint, $this> */
    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(AmigoEndpoint::class);
    }

    /** @return BelongsTo<AmigoRequest, $this> */
    public function request(): BelongsTo
    {
        return $this->belongsTo(AmigoRequest::class, 'request_id');
    }

    /**
     * Records the response, using what AmigoRequest::track stored in the request's config.
     */
    public static function track(Response $saloonResponse): ?self
    {
        $config = $saloonResponse->getPendingRequest()->config();

        try {
            AmigoConnector::recordRateLimit(
                $config->get('amigo.connector_id'),
                $saloonResponse->headers()->get(config('api-amigo.responses.headers.keys.rate.limit')),
                $saloonResponse->headers()->get(config('api-amigo.responses.headers.keys.rate.remaining')),
            );

            $captured = $config->get('amigo.capture_payload');
            $recordBody = $captured || AmigoRequest::capturesPayloadOnError($saloonResponse->failed());

            // Keep the request's payload alongside a response body captured because it failed.
            if ($recordBody && ! $captured) {
                AmigoRequest::recordPayload($config->get('amigo.request_key'), AmigoRequest::saloonPayload($saloonResponse->getPendingRequest()));
            }

            return self::create([
                'endpoint_id' => $config->get('amigo.endpoint_id'),
                'request_id' => $config->get('amigo.request_key'),
                'request_unique_id' => $config->get('amigo.request_id'),
                'status_code' => $saloonResponse->status(),
                'headers' => $saloonResponse->headers()->all(),
                'body' => $recordBody ? trim($saloonResponse->body()) : null,
                'duration' => microtime(true) - $config->get('amigo.request_time'),
                'cached' => $saloonResponse->isCached(),
            ]);
        } catch (Exception $e) {
            Log::error('API Amigo failed to track response: ' . $e->getMessage(), [
                'exception' => $e,
                'response' => $saloonResponse,
            ]);

            return null;
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
