<?php

namespace ChrisReedIO\APIAmigo\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Class AmigoListener
 * @package ChrisReedIO\APIAmigo\Models
 *
 * @property int $id
 * @property int $integration_id
 * @property string $display_name
 * @property string $unique_id
 * @property string $handler
 * @property string $color
 * @property string $webhook_secret
 * @property string $url
 * @property AmigoIntegration $integration
 * @property AmigoWebhook[] $webhooks
 */
class AmigoListener extends AmigoModel
{
    protected $fillable = [
        'integration_id',
        'display_name',
        'unique_id',
        'handler',
        'color',
        'webhook_secret',
    ];

    protected static function booted(): void
    {
        static::creating(function (AmigoListener $listener) {
            $listener->unique_id = Str::ulid()->toBase58();
        });
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(AmigoIntegration::class, 'integration_id');
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(AmigoWebhook::class, 'listener_id');
    }

    public function getUrlAttribute(): string
    {
        return implode(
            '/',
            array_filter([
                config('app.url'),
                'api',
                'webhooks',
                $this->unique_id,
            ])
        );
    }
}
