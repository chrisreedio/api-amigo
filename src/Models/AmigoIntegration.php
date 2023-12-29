<?php

namespace ChrisReedIO\APIAmigo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmigoIntegration extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'total_requests',
        'total_errors',
    ];
}
