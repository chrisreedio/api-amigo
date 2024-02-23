<?php

namespace ChrisReedIO\APIAmigo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AmigoEndpointAggregate extends Model
{
    protected $fillable = [
        'integration_id',
        'endpoint_id',
        'window_start',
        'duration',
        'total_requests',
        'successful_requests',
        'failed_requests',
        'average_duration',
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(AmigoIntegration::class, 'integration_id');
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(AmigoEndpoint::class, 'endpoint_id');
    }
}
