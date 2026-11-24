<?php

namespace ChrisReedIO\APIAmigo\Tests\Fixtures\Integrations\Acme\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetWidget extends Request
{
    protected Method $method = Method::GET;

    public function __construct(protected int $widgetId) {}

    public function resolveEndpoint(): string
    {
        return "/widgets/{$this->widgetId}";
    }
}
