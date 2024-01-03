<?php

namespace ChrisReedIO\APIAmigo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Saloon\Http\Connectors\NullConnector;
use Saloon\Http\PendingRequest;

use Saloon\Http\SoloRequest;
use function class_basename;
use function get_class;
use function get_parent_class;

// use Illuminate\Database\Eloquent\SoftDeletes;

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

    public function integration(): BelongsTo
    {
        return $this->belongsTo(AmigoIntegration::class);
    }

    public function endpoints(): HasMany
    {
        return $this->hasMany(AmigoEndpoint::class, 'connector_id');
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
        $connectorNamespace = (new \ReflectionClass($connectorClassName))->getNamespaceName();
        $namespaceSegments = explode('\\', $connectorNamespace);
        // dd($namespaceSegments);
        // $integrationName = end($namespaceSegments);
        // Guess the integration name from the namespace
        // If the last part is "Requests" go up 1 level

        $integrationName = end($namespaceSegments);
        if ($integrationName === 'Requests') {
            $integrationName = $namespaceSegments[count($namespaceSegments) - 2];
        }

        $integration = AmigoIntegration::firstOrCreate([
            'name' => $integrationName,
        ]);

        return self::firstOrCreate([
            'name' => class_basename($saloonConnector),
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
