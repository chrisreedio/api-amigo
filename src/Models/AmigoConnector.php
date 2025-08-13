<?php

namespace ChrisReedIO\APIAmigo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;
use ReflectionClass;
use Saloon\Http\Connectors\NullConnector;
use Saloon\Http\PendingRequest;
use Saloon\Http\SoloRequest;

use function class_basename;
use function get_class;
use function get_parent_class;

/**
 * AmigoConnector
 *
 * Represents a connector to an API
 *
 * @property int $id
 * @property int $integration_id
 * @property string $name
 * @property string $display_name
 * @property string $base_url
 * @property int $rate_limit
 * @property int $rate_limit_remaining
 * @property int $total_requests
 * @property int $total_errors
 * @property AmigoIntegration $integration
 * @property AmigoEndpoint[] $endpoints
 * @property AmigoRequest[] $requests
 * @property AmigoEndpointAggregate[] $aggregates
 */
class AmigoConnector extends Model
{
    // use SoftDeletes;

    protected $fillable = [
        'integration_id',
        'name',
        'display_name',
        'base_url',
        'rate_limit',
        'rate_limit_remaining',
        // 'total_requests',
        // 'total_errors',
    ];

    public function getNameAttribute(): string
    {
        $snake = Str::snake($this->attributes['name']);

        return Str::title(str_replace('_', ' ', $snake));
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(AmigoIntegration::class);
    }

    public function endpoints(): HasMany
    {
        return $this->hasMany(AmigoEndpoint::class, 'connector_id');
    }

    public function requests(): HasManyThrough
    {
        return $this->hasManyThrough(AmigoRequest::class, AmigoEndpoint::class, 'connector_id', 'endpoint_id');
    }

    public function aggregates(): HasManyThrough
    {
        return $this->hasManyThrough(AmigoEndpointAggregate::class, AmigoEndpoint::class, 'connector_id', 'endpoint_id');
    }

    public static function track(PendingRequest $pendingRequest): self
    {
        $saloonConnector = $pendingRequest->getConnector();
        $connectorClassName = get_class($saloonConnector);

        // If this a solo request, we need to calculate things a little differently
        if ($connectorClassName === NullConnector::class) {
            $parentClass = get_parent_class($pendingRequest->getRequest());

            if ($parentClass === SoloRequest::class) {
                $saloonConnector = $pendingRequest->getRequest();
                $connectorClassName = get_class($saloonConnector);
            }
        }

        // Calculate the integration name from the connector namespace
        $connectorNamespace = (new ReflectionClass($connectorClassName))->getNamespaceName();
        $namespaceSegments = explode('\\', $connectorNamespace);
        // dd($namespaceSegments);
        // $integrationName = end($namespaceSegments);
        // Guess the integration name from the namespace
        // If the last part is "Requests" go up 1 level

        $integrationName = end($namespaceSegments);
        if ($integrationName === 'Requests') {
            $integrationName = $namespaceSegments[count($namespaceSegments) - 2];
            // Solo Request - No connector but still within the integration
            // $integrationName = 'Solo Requests';
        }

        $integration = AmigoIntegration::firstOrCreate([
            'name' => $integrationName,
        ]);

        return self::firstOrCreate([
            'name' => (isset($parentClass) && $parentClass === SoloRequest::class) ? 'Solo Requests' : class_basename($saloonConnector),
            'integration_id' => $integration->id,
        ], [
            'base_url' => parse_url($pendingRequest->getUrl())['host'],
        ]);
    }

    public function getRateUsageAttribute(): int
    {
        if ($this->rate_limit_remaining === null || $this->rate_limit === null) {
            return 0;
        }

        return $this->rate_limit - $this->rate_limit_remaining;
    }

    public function getRateUsagePercentageAttribute(): float
    {
        if ($this->rate_usage === null || $this->rate_limit === null) {
            return 0;
        }

        if ($this->rate_usage === 0) {
            return 100;
        }

        if ($this->rate_limit === 0) {
            return 0;
        }

        return round(($this->rate_usage / $this->rate_limit) * 100, 2);
    }
}
