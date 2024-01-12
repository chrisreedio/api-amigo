<?php

namespace ChrisReedIO\APIAmigo\Models;

use ChrisReedIO\APIAmigo\Enums\Method;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionProperty;
use Saloon\Http\PendingRequest;

use Saloon\Http\Request;
use function class_basename;
use function collect;
use function dd;
use function get_class;

class AmigoEndpoint extends AmigoModel
{
    protected $fillable = [
        'connector_id',
        'method',
        'name',
        'class',
        'path',
    ];

    protected $casts = [
        'method' => Method::class,
    ];

    public function getNameAttribute(): string
    {
        if (empty($this->attributes['name'])) {
            return class_basename($this->class);
        }
        $snake = Str::snake($this->attributes['name']);
        return Str::title(str_replace('_', ' ', $snake));
    }

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
        $request = $pendingRequest->getRequest();

        $endpointName = class_basename($request);
        $endpointClass = get_class($request);

        // $urlParts = parse_url($pendingRequest->getUrl());
        // dd($urlParts);

        return $connector->endpoints()->firstOrCreate([
            'method' => $request->getMethod(),
            'name' => $endpointName,
            'class' => $endpointClass,
            // 'path' => $urlParts['path'],
            'path' => self::calculateGenericPath($request),
        ]);
    }

    private static function calculateGenericPath(Request $request): string
    {
        $params = self::getConstructorParameters($request);
        $fullPath = $request->resolveEndpoint();
        $genericPath = $fullPath;
        // Look for any of these params in the fullPath and replace them with their names
        $params->each(function ($value, $key) use (&$genericPath) {
            $genericPath = str_replace($value, "{{$key}}", $genericPath);
        });
        return $genericPath;
    }

    /**
     * Gets the non-null constructor parameters for the given request.
     *
     * @param Request $request
     * @return Collection
     */
    private static function getConstructorParameters(Request $request): Collection
    {
        $reflection = new ReflectionClass($request);

        // Get all properties
        $properties = collect($reflection->getProperties())
            ->mapWithKeys(function ($property) use ($request) {
                $property->setAccessible(true);
                return [$property->getName() => $property->getValue($request)];
            });

        // Get constructor parameters
        $constructorParameters = collect($reflection->getConstructor()->getParameters())
            ->map->getName();

        // Filter properties based on constructor parameters and non-null values
        return $properties
            ->filter(function ($value, $key) use ($constructorParameters) {
                return $constructorParameters->contains($key) && !is_null($value);
            });
    }
}
