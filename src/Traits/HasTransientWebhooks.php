<?php

namespace ChrisReedIO\APIAmigo\Traits;

use ChrisReedIO\APIAmigo\Models\AmigoListener;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/** @mixin Model */
trait HasTransientWebhooks
{
    public function listeners(): MorphMany
    {
        return $this->morphMany(AmigoListener::class, 'listenable');
    }
}
