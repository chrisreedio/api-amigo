<?php

namespace ChrisReedIO\APIAmigo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Saloon\Http\PendingRequest;

// use Illuminate\Database\Eloquent\SoftDeletes;

class AmigoIntegration extends Model
{
    // use SoftDeletes;

    protected $fillable = [
        'name',
        'display_name',
        // 'base_url',
        // 'total_requests',
        // 'total_errors',
    ];

    public function connectors(): HasMany
    {
        return $this->hasMany(AmigoConnector::class);
    }
}
