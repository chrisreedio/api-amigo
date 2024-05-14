<?php

namespace ChrisReedIO\APIAmigo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class AmigoIntegration
 *
 * @package ChrisReedIO\APIAmigo\Models
 * @property int $id
 * @property string $name
 * @property string $display_name
 * @property string $color
 * @property string $webhook_secret
 * @property int $average_duration
 *
 */
class AmigoIntegration extends Model
{
    // use SoftDeletes;

    protected $fillable = [
        'name',
        'display_name',
        'color',
        'webhook_secret',
        'average_duration',
        // 'base_url',
        // 'total_requests',
        // 'total_errors',
    ];

    public function connectors(): HasMany
    {
        return $this->hasMany(AmigoConnector::class, 'integration_id');
    }
}
