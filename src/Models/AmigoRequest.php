<?php

namespace ChrisReedIO\APIAmigo\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;
use Saloon\Http\PendingRequest;
use Stringable;

use function config;
use function in_array;

/**
 * AmigoRequest
 *
 * Represents a request to an API
 *
 * @property int $id
 * @property int $endpoint_id
 * @property string $unique_id
 * @property string $path
 * @property int $user_id
 * @property ?array $headers
 * @property ?string $body
 * @property AmigoEndpoint $endpoint
 * @property ?AmigoResponse $response
 * @property AmigoRecording[] $recordings
 */
class AmigoRequest extends AmigoModel
{
    protected $fillable = [
        'user_id',
        'unique_id',
        'endpoint_id',
        'path',
        'headers',
        'body',
    ];

    protected $casts = [
        'headers' => 'array',
    ];

    /**
     * Larger PSR request bodies, such as file uploads, aren't read into memory to be captured.
     */
    private const int MAX_CAPTURED_STREAM_SIZE = 1024 * 1024;

    /**
     * Redacted when the app's config has no requests.redact_headers, such as a config file
     * published before that option existed. Laravel merges package config one level deep only.
     */
    public const array DEFAULT_REDACTED_HEADERS = [
        'authorization',
        'proxy-authorization',
        'cookie',
        'x-api-key',
        'api-key',
        'x-auth-token',
    ];

    /** @return BelongsTo<Model, $this> */
    public function user(): BelongsTo
    {
        // return $this->belongsTo(User::class, 'user_id');
        return $this->belongsTo(config('api-amigo.models.user'), 'user_id');
    }

    /** @return BelongsTo<AmigoEndpoint, $this> */
    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(AmigoEndpoint::class);
    }

    /** @return HasOne<AmigoResponse, $this> */
    public function response(): HasOne
    {
        return $this->hasOne(AmigoResponse::class, 'request_id');
    }

    /** @return BelongsToMany<AmigoRecording, $this> */
    public function recordings(): BelongsToMany
    {
        return $this->belongsToMany(
            AmigoRecording::class,
            'amigo_recording_amigo_request',
            'request_id',
            'recording_id'
        );
    }

    public static function generateId(): string
    {
        return Str::ulid()->toBase58();
    }

    /**
     * Records the request before it's sent, and stores what the response side needs in the
     * request's config so it doesn't have to look any of it up again.
     */
    public static function track(PendingRequest $pendingRequest): self
    {
        $requestId = self::generateId();
        $config = $pendingRequest->config();
        $config->add('amigo.request_id', $requestId);
        $config->add('amigo.request_time', microtime(true));

        $endpoint = AmigoEndpoint::track($pendingRequest);

        $uri = $pendingRequest->getUri();
        $path = $uri->getPath();
        if ($uri->getQuery() !== '') {
            $path .= '?' . $uri->getQuery();
        }

        $recordings = AmigoRecording::activeFor($endpoint->connector_id);
        $capturePayload = self::capturesPayload($recordings);

        $request = $endpoint->requests()->create([
            'unique_id' => $requestId,
            'user_id' => auth()->user()?->getAuthIdentifier(),
            'path' => $path,
            ...($capturePayload ? self::saloonPayload($pendingRequest) : []),
        ]);
        $request->attachRecordings($recordings);

        $config->add('amigo.request_key', $request->id);
        $config->add('amigo.endpoint_id', $endpoint->id);
        $config->add('amigo.connector_id', $endpoint->connector_id);
        $config->add('amigo.capture_payload', $capturePayload);

        return $request;
    }

    /**
     * Whether to capture the request and response payloads before the response is known:
     * when configured to always, or when one of the request's recordings asks for it.
     *
     * @param  Collection<int, AmigoRecording>  $recordings
     */
    public static function capturesPayload(Collection $recordings): bool
    {
        return config('api-amigo.responses.capture_body') || $recordings->contains('capture_body', true);
    }

    /**
     * Whether to capture the payloads once the response has failed, when they weren't captured up front.
     */
    public static function capturesPayloadOnError(bool $failed): bool
    {
        return $failed && config('api-amigo.responses.capture_body_on_error');
    }

    /**
     * @param  Collection<int, AmigoRecording>  $recordings
     */
    public function attachRecordings(Collection $recordings): void
    {
        if ($recordings->isNotEmpty()) {
            $this->recordings()->attach($recordings->modelKeys());
        }
    }

    /**
     * Stores payloads captured after the request was recorded, such as when its response failed.
     *
     * @param  array{headers: array<string, mixed>, body: ?string}  $payload
     */
    public static function recordPayload(int $requestKey, array $payload): void
    {
        static::query()->whereKey($requestKey)->update([
            'headers' => json_encode($payload['headers']),
            'body' => $payload['body'],
        ]);
    }

    /**
     * Streamed and multipart Saloon bodies aren't captured, since reading them could consume or load the upload.
     *
     * @return array{headers: array<string, mixed>, body: ?string}
     */
    public static function saloonPayload(PendingRequest $pendingRequest): array
    {
        $body = $pendingRequest->body();

        return self::payload($pendingRequest->headers()->all(), $body instanceof Stringable ? (string) $body : null);
    }

    /**
     * Unseekable and large PSR bodies aren't captured; captured ones are rewound for sending.
     *
     * @return array{headers: array<string, mixed>, body: ?string}
     */
    public static function psrPayload(RequestInterface $request): array
    {
        $stream = $request->getBody();
        $body = null;

        if ($stream->isSeekable() && $stream->getSize() !== null && $stream->getSize() <= self::MAX_CAPTURED_STREAM_SIZE) {
            $body = (string) $stream;
            $stream->rewind();
        }

        return self::payload($request->getHeaders(), $body);
    }

    /**
     * @param  array<string, mixed>  $headers
     * @return array{headers: array<string, mixed>, body: ?string}
     */
    private static function payload(array $headers, ?string $body): array
    {
        $redacted = array_map('strtolower', config('api-amigo.requests.redact_headers') ?? self::DEFAULT_REDACTED_HEADERS);

        foreach ($headers as $name => $value) {
            if (in_array(strtolower($name), $redacted, true)) {
                $headers[$name] = '[redacted]';
            }
        }

        return ['headers' => $headers, 'body' => $body === '' ? null : $body];
    }
}
