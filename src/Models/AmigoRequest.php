<?php

namespace ChrisReedIO\APIAmigo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Saloon\Http\PendingRequest;

use function config;
use function is_null;

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

        $request = $endpoint->requests()->create([
            'unique_id' => $requestId,
            'user_id' => auth()->user()?->id,
            'path' => $path,
        ]);
        $request->setRelation('endpoint', $endpoint);

        $config->add('amigo.request_key', $request->id);
        $config->add('amigo.endpoint_id', $endpoint->id);
        $config->add('amigo.connector_id', $endpoint->connector_id);
        $config->add('amigo.capture_body', $request->attachRecordings());

        return $request;
    }

    /**
     * Attaches the request to the active recordings that include it.
     *
     * @return bool Whether any of those recordings captures response bodies.
     */
    public function attachRecordings(): bool
    {
        $user = auth()->user();

        $recordings = AmigoRecording::active()->get()->filter(function (AmigoRecording $recording) use ($user) {
            // Non-global recordings only include their owner's requests.
            if (! $recording->global && (is_null($user) || $recording->user_id !== $user->id)) {
                return false;
            }

            return $recording->connector_id === null || $recording->connector_id === $this->endpoint->connector_id;
        });

        if ($recordings->isNotEmpty()) {
            $this->recordings()->attach($recordings->modelKeys());
        }

        return $recordings->contains('capture_body', true);
    }
}
