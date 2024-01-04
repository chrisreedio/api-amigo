<?php

namespace ChrisReedIO\APIAmigo\Models;

use ChrisReedIO\APIAmigo\Models\AmigoModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AmigoListener extends AmigoModel
{
    protected $fillable = [
        'integration_id',
        'display_name',
        'url_prefix',
        'unique_id',
        'event',
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

}
