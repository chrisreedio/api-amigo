<?php

namespace ChrisReedIO\APIAmigo\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \ChrisReedIO\APIAmigo\APIAmigo
 */
class APIAmigo extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \ChrisReedIO\APIAmigo\APIAmigo::class;
    }
}
