<?php

namespace ChrisReedIO\APIAmigo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * ChrisReedIO\APIAmigo\Models\AmigoEndpointAggregate
 *
 * @property int $id
 * @property int $integration_id
 * @property int $endpoint_id
 * @property string $window_start
 * @property int $interval
 * @property int $total_requests
 * @property int $successful_requests
 * @property int $failed_requests
 * @property int $max_requests_per_minute
 * @property int $min_duration
 * @property int $max_duration
 * @property int $p50_duration
 * @property int $p75_duration
 * @property int $p95_duration
 * @property int $p99_duration
 * @property array $duration_histogram
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read AmigoEndpoint $endpoint
 * @property-read AmigoIntegration $integration
 */
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
