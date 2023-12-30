<?php

namespace ChrisReedIO\APIAmigo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
