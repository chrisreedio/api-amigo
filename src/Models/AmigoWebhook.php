<?php

namespace ChrisReedIO\APIAmigo\Models;

use ChrisReedIO\APIAmigo\Models\AmigoModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AmigoWebhook extends AmigoModel
{
    protected $fillable = [
        'unique_id',
        'url',
        'headers',
        'payload',
        'status',
        'status_message',
        'processed_at',
        'error',
    ];

    protected static function booted(): void
    {
        static::creating(function (AmigoWebhook $webhook) {
            $webhook->unique_id = Str::ulid()->toBase58();
        });
    }

    public function listener(): BelongsTo
    {
        return $this->belongsTo(AmigoListener::class, 'listener_id');
    }

}
