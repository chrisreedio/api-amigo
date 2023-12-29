<?php

namespace ChrisReedIO\APIAmigo\Models;

class AmigoEndpoint extends AmigoModel
{
    protected $fillable = [
        'integration_id',
        'name',
        'path',
    ];
}
