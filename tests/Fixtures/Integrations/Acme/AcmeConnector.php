<?php

namespace ChrisReedIO\APIAmigo\Tests\Fixtures\Integrations\Acme;

use Saloon\Http\Connector;

class AcmeConnector extends Connector
{
    public function resolveBaseUrl(): string
    {
        return 'https://api.acme.test';
    }
}
