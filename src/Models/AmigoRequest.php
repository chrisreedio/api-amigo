<?php

namespace ChrisReedIO\APIAmigo\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Saloon\Http\PendingRequest;

use function config;

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
 * @property AmigoResponse $response
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

    public function user(): BelongsTo
    {
        // return $this->belongsTo(User::class, 'user_id');
        return $this->belongsTo(config('api-amigo.models.user'), 'user_id');
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(AmigoEndpoint::class);
    }

    public function response(): HasOne
    {
        return $this->hasOne(AmigoResponse::class, 'request_id');
    }

    public function recordings(): BelongsToMany
    {
        return $this->belongsToMany(
            AmigoRecording::class,
            'amigo_recording_amigo_request',
            'request_id',
            'recording_id'
        );
    }

    public static function track(PendingRequest $pendingRequest): self
    {
        $requestId = Str::ulid()->toBase58();
        $pendingRequest->config()->add('amigo.request_id', $requestId);
        $pendingRequest->config()->add('amigo.request_time', microtime(true));
        // dump('Injected Amigo tracking data into request config');

        // Find or create the endpoint
        $endpoint = AmigoEndpoint::track($pendingRequest);

        $user = auth()->user();

        $request = $endpoint->requests()->create([
            'unique_id' => $requestId,
            'user_id' => $user?->id,
            'path' => $pendingRequest->getRequest()->resolveEndpoint(),
        ]);

        // Check for any active global recordings
        AmigoRecording::active()
            ->each(function (AmigoRecording $recording) use ($request, $user) {
                // If this isn't a global recording and the user is not the owner, skip it
                if (! $recording->global && (is_null($user) || $recording->user_id !== $user->id)) {
                    return;
                }

                // Now do a connector check to make sure it's not been filtered out
                if ($recording->connector_id !== null && $recording->connector_id !== $request->endpoint->connector_id) {
                    return;
                }

                // dd('Recording request', $recording, $request);
                $recording->requests()->attach($request);
            });

        return $request;
    }
}
