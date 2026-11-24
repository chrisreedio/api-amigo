<?php

namespace ChrisReedIO\APIAmigo\Tests\Fixtures\Integrations\Acme\Requests;

use Saloon\Enums\Method;
use Saloon\Http\SoloRequest;

class GetStatus extends SoloRequest
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return 'https://status.acme.test/v1/status';
    }
}
