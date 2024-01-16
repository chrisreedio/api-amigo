<?php

namespace ChrisReedIO\APIAmigo\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Saloon\Http\PendingRequest;

use function config;

class AmigoRequest extends AmigoModel
{
    protected $fillable = [
        'user_id',
        'unique_id',
        'endpoint_id',
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
        ]);

        // Check for any active global recordings
        AmigoRecording::active()
            ->each(function (AmigoRecording $recording) use ($request, $user) {
                // If this isn't a global recording and the user is not the owner, skip it
                if (! $recording->global && (is_null($user) || $recording->user_id !== $user->id)) {
                    return;
                }
                // dd('Recording request', $recording, $request);
                $recording->requests()->attach($request);
            });

        return $request;
    }
}
