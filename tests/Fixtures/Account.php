<?php

namespace ChrisReedIO\APIAmigo\Tests\Fixtures;

use ChrisReedIO\APIAmigo\Traits\HasTransientWebhooks;
use ChrisReedIO\APIAmigo\Traits\HasWebhooks;
use Illuminate\Database\Eloquent\Model;

/** An app model that owns webhooks and transient listeners. */
class Account extends Model
{
    use HasTransientWebhooks;
    use HasWebhooks;

    protected $table = 'users';

    protected $guarded = [];
}
