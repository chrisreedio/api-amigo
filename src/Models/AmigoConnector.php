<?php

namespace ChrisReedIO\APIAmigo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Saloon\Http\PendingRequest;
use function class_basename;
use function dump;

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
        $connectorNamespace = (new \ReflectionClass(get_class($saloonConnector)))->getNamespaceName();
        $namespaceSegments = explode('\\', $connectorNamespace);

        $integration = AmigoIntegration::firstOrCreate([
            'name' => end($namespaceSegments),
        ]);

        return self::firstOrCreate([
            'name' => class_basename($saloonConnector),
            'integration_id' => $integration->id,
        ], [
            'base_url' => parse_url($pendingRequest->getUrl())['host'],
        ]);
    }
}
