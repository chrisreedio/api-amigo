<?php

namespace ChrisReedIO\APIAmigo\Traits;

use ChrisReedIO\APIAmigo\Models\AmigoWebhook;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Trait HasWebhooks
 *
 * @mixin Model
 */
trait HasWebhooks
{
    /** @return MorphMany<AmigoWebhook, $this> */
    public function webhooks(): MorphMany
    {
        return $this->morphMany(AmigoWebhook::class, 'webhookable');
    }
}
