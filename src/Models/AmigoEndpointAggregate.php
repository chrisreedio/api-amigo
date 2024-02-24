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
        'interval',
        'total_requests',
        'successful_requests',
        'failed_requests',
        'max_requests_per_minute',
        'min_duration',
        'max_duration',
        'p50_duration',
        'p75_duration',
        'p95_duration',
        'p99_duration',
        'duration_histogram',
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
