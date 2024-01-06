<?php

namespace ChrisReedIO\APIAmigo\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Saloon\Http\PendingRequest;

class AmigoRequest extends AmigoModel
{
    protected $fillable = [
        'unique_id',
        'endpoint_id',
    ];

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(AmigoEndpoint::class);
    }

    public function response(): HasOne
    {
        return $this->hasOne(AmigoResponse::class, 'response_id');
    }

    public static function track(PendingRequest $pendingRequest): self
    {
        $requestId = Str::ulid()->toBase58();
        $pendingRequest->config()->add('amigo.request_id', $requestId);
        $pendingRequest->config()->add('amigo.request_time', microtime(true));
        // dump('Injected Amigo tracking data into request config');

        // Find or create the endpoint
        $endpoint = AmigoEndpoint::track($pendingRequest);

        return $endpoint->requests()->create([
            'unique_id' => $requestId,
        ]);
    }
}
