<?php

namespace ChrisReedIO\APIAmigo\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Saloon\Http\PendingRequest;
use function class_basename;

class AmigoEndpoint extends AmigoModel
{
    protected $fillable = [
        'connector_id',
        'name',
        'path',
    ];

    public function connector(): BelongsTo
    {
        return $this->belongsTo(AmigoConnector::class, 'connector_id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(AmigoRequest::class, 'endpoint_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(AmigoResponse::class, 'endpoint_id');
    }

    public static function track(PendingRequest $pendingRequest): self
    {
        $connector = AmigoConnector::track($pendingRequest);
        $endpointName = class_basename($pendingRequest->getRequest());
        $urlParts = parse_url($pendingRequest->getUrl());

        return $connector->endpoints()->firstOrCreate([
            'name' => $endpointName,
            'path' => $urlParts['path'],
        ]);
    }
}
