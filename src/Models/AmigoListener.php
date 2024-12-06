<?php

namespace ChrisReedIO\APIAmigo\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

/**
 * Class AmigoListener
 *
 * @property int $id
 * @property int $integration_id
 * @property string $display_name
 * @property string $unique_id
 * @property string $handler
 * @property string $color
 * @property string $webhook_secret
 * @property string $url
 * @property int $uses
 * @property int $max_uses
 * @property ?Carbon $expires_at
 * @property AmigoIntegration $integration
 * @property AmigoWebhook[] $webhooks
 * @property MorphTo $listenable
 * @property ?string $listenable_type
 * @property ?int $listenable_id
 */
class AmigoListener extends AmigoModel
{
    use HasApiTokens;

    protected $fillable = [
        'integration_id',
        'display_name',
        'unique_id',
        'handler',
        'color',
        'webhook_secret',
        'uses',
        'max_uses',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
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

    public function listenable(): MorphTo
    {
        return $this->morphTo();
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

    public function scopeExpired(Builder $query, bool $value = true): Builder
    {
        return $query->when($value, function (Builder $query) {
            $query->where('expires_at', '<=', Carbon::now());
        }, function (Builder $query) {
            $query->whereNull('expires_at')
                ->orWhere('expires_at', '>', Carbon::now());
        });
    }

    public function scopeTransient(Builder $query, bool $value = true): Builder
    {
        return $query
            ->when($value, function (Builder $query) {
                $query->whereNotNull('listenable_id')
                    ->orWhereNotNull('expires_at')
                    ->orWhereNotNull('max_uses');
            }, function (Builder $query) {
                $query->whereNull('listenable_id')
                    ->whereNull('expires_at')
                    ->whereNull('max_uses');
            });
    }

    // public function validatePayload(string $payload, string $userSignature): bool
    // {
    //     $secret = $this->webhook_secret;
    //     if ($secret === null) {
    //         $secret = $this->integration->webhook_secret;
    //     }
    //     $generatedSignature = hash_hmac('sha256', $payload, $secret);
    //
    //     dump("User Signature: '$userSignature'");
    //     dump("Generated Signature: '$generatedSignature'");
    //
    //     return $userSignature == $generatedSignature;
    //
    //     // return $userSignature == hash_hmac('sha256', $payload, $secret);
    // }
}
